<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Refunds (spec §13).
 *
 * The invariant that matters is cumulative: completed refunds against a
 * payment must never exceed what was actually paid. `complete()` enforces
 * that with a conditional UPDATE against `payments.refunded_amount`
 * (`WHERE amount - refunded_amount >= :requested`) rather than trusting the
 * amount validated at `initiate()` time, so a second refund racing the first
 * cannot overdraw the payment (decision D1's pattern, applied to money).
 *
 * `refunds.order_return_id` is unique, so at most one refund can ever exist
 * per return — a database guarantee, not just an application check.
 */
class RefundService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Initiate a refund against a return that has been received back.
     *
     * @throws CartException
     */
    public function initiate(OrderReturn $return, User $actor, ?float $amount = null, ?string $notes = null): Refund
    {
        if (! in_array($return->status, [OrderReturn::STATUS_RECEIVED, OrderReturn::STATUS_COMPLETED], true)) {
            throw new CartException(__('refunds.return_not_ready'));
        }

        if (Refund::query()->where('order_return_id', $return->id)->exists()) {
            throw new CartException(__('refunds.duplicate'));
        }

        $order = $return->order()->firstOrFail();
        $order->loadMissing('payment');
        $payment = $order->payment;

        if (! $payment) {
            throw new CartException(__('refunds.no_payment'));
        }

        $amount = round($amount ?? (float) $return->refundable_amount, 2);

        if ($amount <= 0) {
            throw new CartException(__('refunds.amount_invalid'));
        }

        if ($amount > $payment->refundableAmount()) {
            throw new CartException(__('refunds.amount_exceeds'));
        }

        try {
            $refund = Refund::create([
                'refund_number' => $this->generateRefundNumber(),
                'order_id' => $order->id,
                'order_return_id' => $return->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'currency' => $order->currency,
                'status' => Refund::STATUS_PENDING,
                'reason' => $return->reason,
                'notes' => $notes,
                'initiated_by_user_id' => $actor->id,
            ]);
        } catch (QueryException) {
            // The unique index on order_return_id caught a concurrent
            // initiate() call the existence check above missed.
            throw new CartException(__('refunds.duplicate'));
        }

        $this->notificationService->notifyRefundStatusUpdated($refund->fresh(['order']), Refund::STATUS_PENDING);

        return $refund;
    }

    /**
     * Admin-initiated refund with no return behind it — a duplicate COD
     * collection or a goodwill adjustment (spec §13).
     *
     * @throws CartException
     */
    public function initiateAdHoc(Order $order, User $actor, float $amount, string $reason, ?string $notes = null): Refund
    {
        if (! in_array($reason, Refund::ADHOC_REASONS, true)) {
            throw new CartException(__('refunds.reason_invalid'));
        }

        $order->loadMissing('payment');
        $payment = $order->payment;

        if (! $payment) {
            throw new CartException(__('refunds.no_payment'));
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new CartException(__('refunds.amount_invalid'));
        }

        if ($amount > $payment->refundableAmount()) {
            throw new CartException(__('refunds.amount_exceeds'));
        }

        $refund = Refund::create([
            'refund_number' => $this->generateRefundNumber(),
            'order_id' => $order->id,
            'order_return_id' => null,
            'payment_id' => $payment->id,
            'amount' => $amount,
            'currency' => $order->currency,
            'status' => Refund::STATUS_PENDING,
            'reason' => $reason,
            'notes' => $notes,
            'initiated_by_user_id' => $actor->id,
        ]);

        $this->notificationService->notifyRefundStatusUpdated($refund->fresh(['order']), Refund::STATUS_PENDING);

        return $refund;
    }

    /**
     * Settle a refund: claim it, atomically draw it against the payment
     * without overdrawing, and finalize both. Safe to call twice — the second
     * call finds nothing left to claim and rejects rather than double-paying.
     *
     * @throws CartException
     */
    public function complete(Refund $refund, ?string $providerReference = null): Refund
    {
        DB::transaction(function () use ($providerReference, $refund): void {
            $claimed = Refund::query()
                ->whereKey($refund->id)
                ->whereIn('status', [Refund::STATUS_PENDING, Refund::STATUS_PROCESSING])
                ->update(['status' => Refund::STATUS_PROCESSING, 'updated_at' => now()]);

            if ($claimed === 0) {
                throw new CartException(__('refunds.already_finalized'));
            }

            $amount = (float) $refund->amount;

            // The cap check is interpolated rather than bound: Laravel binds
            // PHP floats as PDO::PARAM_STR, and SQLite orders TEXT above every
            // NUMERIC value regardless of content, so a bound float can never
            // satisfy a numeric >= comparison against a computed column here —
            // the check would silently fail closed on every request. $amount
            // is a trusted, server-computed decimal (never user input), so
            // formatting it into the raw expression is safe — the same
            // reasoning CheckoutService and OrderCancellationService already
            // apply to the analogous integer case (decision D1).
            $amountSql = number_format($amount, 2, '.', '');

            $paymentUpdated = Payment::query()
                ->whereKey($refund->payment_id)
                ->whereRaw("(amount - refunded_amount) >= {$amountSql}")
                ->increment('refunded_amount', $amount, ['updated_at' => now()]);

            if ($paymentUpdated === 0) {
                throw new CartException(__('refunds.amount_exceeds'));
            }

            /** @var Payment $payment */
            $payment = Payment::query()->whereKey($refund->payment_id)->firstOrFail();
            $isFullyRefunded = round((float) $payment->refunded_amount, 2) >= round((float) $payment->amount, 2);

            $payment->update([
                'status' => $isFullyRefunded ? Payment::STATUS_REFUNDED : Payment::STATUS_PARTIALLY_REFUNDED,
            ]);

            $refund->update([
                'status' => Refund::STATUS_COMPLETED,
                'completed_at' => now(),
                'provider_reference' => $providerReference,
            ]);

            if ($refund->orderReturn && $refund->orderReturn->status === OrderReturn::STATUS_RECEIVED) {
                // Closes the loop the return started: money moved, so the
                // return itself is done. Uses the same conditional-update
                // transition path as any other actor-driven change.
                OrderReturn::query()
                    ->whereKey($refund->order_return_id)
                    ->where('status', OrderReturn::STATUS_RECEIVED)
                    ->update(['status' => OrderReturn::STATUS_COMPLETED, 'updated_at' => now()]);
            }
        });

        $refund->refresh();

        $this->notificationService->notifyRefundStatusUpdated($refund->fresh(['order']), Refund::STATUS_COMPLETED);

        return $refund;
    }

    /**
     * Cancel a refund that has not been finalized.
     *
     * @throws CartException
     */
    public function cancel(Refund $refund): Refund
    {
        $claimed = Refund::query()
            ->whereKey($refund->id)
            ->whereIn('status', [Refund::STATUS_PENDING, Refund::STATUS_FAILED])
            ->update(['status' => Refund::STATUS_CANCELLED, 'updated_at' => now()]);

        if ($claimed === 0) {
            throw new CartException(__('refunds.already_finalized'));
        }

        return $refund->refresh();
    }

    private function generateRefundNumber(): string
    {
        do {
            $number = 'RFD-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Refund::query()->where('refund_number', $number)->exists());

        return $number;
    }
}
