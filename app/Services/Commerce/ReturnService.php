<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Return requests and their review lifecycle (spec §12).
 *
 * Mirrors the exactly-once discipline OrderCancellationService established for
 * order cancellation (decision D1): stock is returned to `products.quantity`
 * at most once per return, claimed by a conditional UPDATE on
 * `order_returns.stock_restored_at` rather than a status check, so a repeated
 * or concurrent call cannot double-restore.
 */
class ReturnService
{
    /**
     * Which target statuses each actor type may move a return into. Legality
     * of the *current* status is separately enforced by
     * OrderReturn::canTransitionTo(), so a customer can never jump a return
     * straight to `completed` even though `cancelled` is in their list.
     *
     * @var array<string, list<string>>
     */
    private const ACTOR_PERMISSIONS = [
        'customer' => [OrderReturn::STATUS_CANCELLED],
        'vendor' => [
            OrderReturn::STATUS_UNDER_REVIEW,
            OrderReturn::STATUS_APPROVED,
            OrderReturn::STATUS_REJECTED,
            OrderReturn::STATUS_RECEIVED,
            OrderReturn::STATUS_COMPLETED,
            OrderReturn::STATUS_CANCELLED,
        ],
        'admin' => [
            OrderReturn::STATUS_UNDER_REVIEW,
            OrderReturn::STATUS_APPROVED,
            OrderReturn::STATUS_REJECTED,
            OrderReturn::STATUS_RECEIVED,
            OrderReturn::STATUS_COMPLETED,
            OrderReturn::STATUS_CANCELLED,
        ],
    ];

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Request a return for part or all of a delivered order.
     */
    public function request(Order $order, User $user, array $items, string $reason, ?string $notes = null): OrderReturn
    {
        try {
            return $this->attemptRequest($order, $user, $items, $reason, $notes);
        } catch (CartException $exception) {
            Log::warning('Return request rejected.', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'reason' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  list<array{order_item_id: int, quantity: int}>  $items
     *
     * @throws CartException on any customer-actionable rejection
     */
    private function attemptRequest(Order $order, User $user, array $items, string $reason, ?string $notes = null): OrderReturn
    {
        if ($order->user_id !== $user->id) {
            // Defence in depth; the controller authorizes via policy first.
            throw new CartException(__('returns.order_invalid'));
        }

        if (! in_array($reason, OrderReturn::REASONS, true)) {
            throw new CartException(__('returns.reason_invalid'));
        }

        if (! $order->isReturnable()) {
            throw new CartException(__('returns.order_not_returnable', [
                'status' => __("orders.status.{$order->status}"),
            ]));
        }

        if ($items === []) {
            throw new CartException(__('returns.items_required'));
        }

        // Merge repeated lines for the same order item rather than reject —
        // the customer may have added it twice by mistake in the request.
        $requestedByItem = collect($items)->reduce(
            function (array $carry, array $line) {
                $itemId = (int) $line['order_item_id'];
                $carry[$itemId] = ($carry[$itemId] ?? 0) + (int) $line['quantity'];

                return $carry;
            },
            []
        );

        $orderItems = OrderItem::query()
            ->whereIn('id', array_keys($requestedByItem))
            ->where('order_id', $order->id)
            ->get()
            ->keyBy('id');

        $lines = [];
        $refundableAmount = 0.0;

        foreach ($requestedByItem as $orderItemId => $quantity) {
            if ($quantity < 1) {
                throw new CartException(__('returns.quantity_invalid'));
            }

            /** @var OrderItem|null $orderItem */
            $orderItem = $orderItems->get($orderItemId);

            if (! $orderItem) {
                throw new CartException(__('returns.item_invalid'));
            }

            $remaining = (int) $orderItem->quantity - $this->alreadyReturnedQuantity($orderItem->id);

            if ($quantity > $remaining) {
                throw new CartException(__('returns.quantity_exceeds', [
                    'product' => $orderItem->product_name,
                ]));
            }

            $lineTotal = round((float) $orderItem->unit_price * $quantity, 2);
            $refundableAmount = round($refundableAmount + $lineTotal, 2);

            $lines[] = [
                'order_item_id' => $orderItem->id,
                'product_id' => $orderItem->product_id,
                'quantity' => $quantity,
                'unit_price' => $orderItem->unit_price,
                'line_total' => $lineTotal,
            ];
        }

        $return = DB::transaction(function () use ($lines, $notes, $order, $reason, $refundableAmount, $user) {
            $orderReturn = OrderReturn::create([
                'return_number' => $this->generateReturnNumber(),
                'order_id' => $order->id,
                'user_id' => $user->id,
                'vendor_id' => $order->vendor_id,
                'status' => OrderReturn::STATUS_REQUESTED,
                'reason' => $reason,
                'notes' => $notes,
                'refundable_amount' => $refundableAmount,
                'requested_at' => now(),
            ]);

            foreach ($lines as $line) {
                ReturnItem::create(array_merge(['order_return_id' => $orderReturn->id], $line));
            }

            return $orderReturn;
        });

        $this->notificationService->notifyReturnRequested($return->fresh(['order', 'vendor']));

        return $return;
    }

    /**
     * Move a return to a new status.
     */
    public function transition(
        OrderReturn $return,
        string $to,
        User $actor,
        string $actorType,
        ?string $notes = null,
    ): OrderReturn {
        try {
            return $this->attemptTransition($return, $to, $actor, $actorType, $notes);
        } catch (CartException $exception) {
            Log::warning('Return status transition rejected.', [
                'return_id' => $return->id,
                'to' => $to,
                'actor_id' => $actor->id,
                'actor_type' => $actorType,
                'reason' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @throws CartException when the actor may not make this change, or the
     *                       state machine forbids it
     */
    private function attemptTransition(
        OrderReturn $return,
        string $to,
        User $actor,
        string $actorType,
        ?string $notes = null,
    ): OrderReturn {
        $allowedForActor = self::ACTOR_PERMISSIONS[$actorType] ?? [];

        if (! in_array($to, $allowedForActor, true)) {
            throw new CartException(__('returns.transition_not_permitted'));
        }

        if (! $return->canTransitionTo($to)) {
            throw new CartException(__('returns.transition_invalid', [
                'from' => __("returns.status.{$return->status}"),
                'to' => __("returns.status.{$to}"),
            ]));
        }

        $from = $return->status;
        $isReview = in_array($to, [OrderReturn::STATUS_APPROVED, OrderReturn::STATUS_REJECTED], true);

        DB::transaction(function () use ($actor, $from, $isReview, $notes, $return, $to): void {
            $applied = OrderReturn::query()
                ->whereKey($return->id)
                ->where('status', $from)
                ->update(array_merge(
                    ['status' => $to, 'updated_at' => now()],
                    $isReview ? [
                        'reviewed_by_user_id' => $actor->id,
                        'reviewed_at' => now(),
                        'review_notes' => $notes,
                    ] : [],
                ));

            if ($applied === 0) {
                throw new CartException(__('returns.transition_conflict'));
            }
        });

        $return->refresh();

        if ($to === OrderReturn::STATUS_RECEIVED) {
            $this->restoreStockOnce($return);
        }

        $this->notificationService->notifyReturnStatusUpdated($return->fresh(['order', 'user', 'vendor']), $to);

        return $return;
    }

    /**
     * Return this return's quantities to the product rows, at most once ever.
     * Safe to call repeatedly: the claim below succeeds for exactly one
     * caller, and every later call short-circuits (mirrors
     * OrderCancellationService::restoreStockOnce, decision D1).
     */
    public function restoreStockOnce(OrderReturn $return): bool
    {
        $claimed = OrderReturn::query()
            ->whereKey($return->id)
            ->whereNull('stock_restored_at')
            ->update(['stock_restored_at' => now()]);

        if ($claimed === 0) {
            return false;
        }

        DB::transaction(function () use ($return): void {
            $return->loadMissing('items');

            foreach ($return->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                Product::query()
                    ->whereKey($item->product_id)
                    ->update([
                        'quantity' => DB::raw("quantity + {$item->quantity}"),
                        'updated_at' => now(),
                    ]);
            }
        });

        try {
            Cache::tags(['products'])->flush();
        } catch (\Exception) {
            // `file` cache driver does not support tags; see CheckoutService.
        }

        return true;
    }

    /**
     * Quantity of this order item already claimed by a return that is not
     * rejected or cancelled — i.e. quantity that is spoken for, whether the
     * return is still pending review or has already gone through.
     */
    private function alreadyReturnedQuantity(int $orderItemId): int
    {
        return (int) ReturnItem::query()
            ->where('order_item_id', $orderItemId)
            ->whereHas('orderReturn', function ($query) {
                $query->whereNotIn('status', [
                    OrderReturn::STATUS_REJECTED,
                    OrderReturn::STATUS_CANCELLED,
                ]);
            })
            ->sum('quantity');
    }

    private function generateReturnNumber(): string
    {
        do {
            $number = 'RET-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (OrderReturn::query()->where('return_number', $number)->exists());

        return $number;
    }
}
