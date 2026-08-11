<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

/**
 * The single place an order's status may change (spec §8, §9).
 *
 * Controllers call `transition()`. They never write `status` directly, so an
 * invalid or client-supplied status cannot reach the database, and every
 * change leaves an audit row.
 */
class OrderStatusService
{
    /**
     * Which statuses each actor type may move an order into.
     *
     * Customers may only cancel. Vendors drive fulfilment. Admins may do
     * anything the state machine itself allows.
     *
     * @var array<string, list<string>>
     */
    private const ACTOR_PERMISSIONS = [
        'customer' => [Order::STATUS_CANCELLED],
        'vendor' => [
            Order::STATUS_CONFIRMED,
            Order::STATUS_PREPARING,
            Order::STATUS_SHIPPED,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ],
        'employee' => [
            Order::STATUS_CONFIRMED,
            Order::STATUS_PREPARING,
            Order::STATUS_SHIPPED,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_COMPLETED,
        ],
        'admin' => [
            Order::STATUS_CONFIRMED,
            Order::STATUS_PREPARING,
            Order::STATUS_SHIPPED,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ],
    ];

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly PaymentService $paymentService,
        private readonly ShippingService $shippingService,
        private readonly VendorLedgerService $vendorLedgerService,
    ) {}

    /**
     * Record the order's creation so the customer timeline starts at a real
     * event rather than an inferred one.
     */
    public function recordInitial(Order $order, User $actor): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'previous_status' => null,
            'new_status' => $order->status,
            'changed_by_user_id' => $actor->id,
            'actor_type' => 'customer',
            'reason' => 'order_placed',
        ]);
    }

    /**
     * Move an order to a new status.
     *
     * @throws CartException when the actor may not make this change, or the
     *                       state machine forbids it
     */
    public function transition(
        Order $order,
        string $to,
        User $actor,
        string $actorType,
        ?string $reason = null,
        ?string $notes = null,
    ): Order {
        $allowedForActor = self::ACTOR_PERMISSIONS[$actorType] ?? [];

        if (! in_array($to, $allowedForActor, true)) {
            throw new CartException(__('orders.transition_not_permitted'));
        }

        if (! $order->canTransitionTo($to)) {
            throw new CartException(__('orders.transition_invalid', [
                'from' => __("orders.status.{$order->status}"),
                'to' => __("orders.status.{$to}"),
            ]));
        }

        $from = $order->status;

        DB::transaction(function () use ($actor, $actorType, $from, $notes, $order, $reason, $to): void {
            // Conditional update: if another request already moved the order,
            // this affects 0 rows and we abort rather than overwrite.
            $applied = Order::query()
                ->whereKey($order->id)
                ->where('status', $from)
                ->update(['status' => $to, 'updated_at' => now()]);

            if ($applied === 0) {
                throw new CartException(__('orders.transition_conflict'));
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'previous_status' => $from,
                'new_status' => $to,
                'changed_by_user_id' => $actor->id,
                'actor_type' => $actorType,
                'reason' => $reason,
                'notes' => $notes,
            ]);
        });

        $order->refresh();

        // Keeps the shipment's delivery-tracking status in step with the
        // order's own fulfilment status (spec §14). Best-effort by design —
        // see ShippingService::syncFromOrderStatus.
        $this->shippingService->syncFromOrderStatus($order, $to, $actor, $actorType);

        if ($to === Order::STATUS_COMPLETED) {
            // COD settles when the courier hands over the goods and collects
            // cash — i.e. exactly when fulfilment reaches its terminal state.
            // This is the only place a payment is marked paid, so every path
            // that completes an order (vendor, employee, admin) settles it.
            $order->loadMissing('payment');

            if ($order->payment) {
                $this->paymentService->markPaid($order->payment);
            }

            // Likewise the only place a sale lands on the vendor ledger
            // (spec §20) — the commission rate is captured now, not
            // recomputed later if the category's rate changes.
            $this->vendorLedgerService->recordSale($order);
        }

        $this->notificationService->notifyOrderStatusUpdated($order, $to);

        return $order;
    }
}
