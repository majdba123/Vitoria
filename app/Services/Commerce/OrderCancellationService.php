<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cancellation with exactly-once stock restoration (spec §10, audit R1).
 *
 * The previous implementation checked `status !== cancelled` and then
 * incremented every product. Two concurrent requests both passed that check
 * before either wrote, and SQLite provided no row lock to serialize them, so
 * stock could be restored twice. Restoration is now claimed by a conditional
 * UPDATE on `stock_restored_at`, which exactly one caller can ever win.
 */
class OrderCancellationService
{
    public function __construct(
        private readonly OrderStatusService $orderStatusService,
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * @throws CartException
     */
    public function cancel(
        Order $order,
        User $actor,
        string $actorType,
        string $reason,
        ?string $notes = null,
    ): Order {
        if (! in_array($reason, Order::CANCEL_REASONS, true)) {
            throw new CartException(__('orders.cancel_reason_invalid'));
        }

        if ($order->status === Order::STATUS_CANCELLED) {
            throw new CartException(__('orders.already_cancelled'));
        }

        if (! $order->isCancellable()) {
            throw new CartException(__('orders.not_cancellable', [
                'status' => __("orders.status.{$order->status}"),
            ]));
        }

        // Moves the status (conditionally, with its own conflict guard) and
        // writes the history row.
        $order = $this->orderStatusService->transition(
            $order,
            Order::STATUS_CANCELLED,
            $actor,
            $actorType,
            $reason,
            $notes,
        );

        $order->update([
            'cancelled_at' => now(),
            'cancelled_by_user_id' => $actor->id,
            'cancellation_reason' => $reason,
            'cancellation_notes' => $notes,
        ]);

        $this->restoreStockOnce($order);

        $order->loadMissing('payment');

        if ($order->payment) {
            // A settled payment is untouched here — undoing money already
            // collected is a refund, not a cancellation (spec §11).
            $this->paymentService->cancelIfUnsettled($order->payment);
        }

        return $order->refresh();
    }

    /**
     * Return this order's quantities to the product rows, at most once ever.
     *
     * Safe to call repeatedly: the claim below succeeds for exactly one caller,
     * and every later call short-circuits.
     */
    public function restoreStockOnce(Order $order): bool
    {
        $claimed = Order::query()
            ->whereKey($order->id)
            ->whereNull('stock_restored_at')
            ->update(['stock_restored_at' => now()]);

        if ($claimed === 0) {
            return false;
        }

        DB::transaction(function () use ($order): void {
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    // Product was deleted after the order was placed; there is
                    // no row left to restore stock to.
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
}
