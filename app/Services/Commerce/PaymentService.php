<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

/**
 * Payment lifecycle (spec §11).
 *
 * The provider abstraction is deliberately thin. COD is the only method the
 * platform configures, so this class records what happened rather than talking
 * to anything. A real gateway would slot in as another `provider` value with
 * its own authorize/capture calls; nothing here pretends that already exists
 * (decision D9).
 *
 * No card number, CVV, or token is accepted, stored, or logged anywhere.
 */
class PaymentService
{
    /**
     * Record the payment intent for a newly created order.
     *
     * COD starts `pending`: the money is collected by the courier, so nothing
     * is settled at checkout time.
     */
    public function createForOrder(Order $order, User $user, string $method): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'provider' => $this->providerFor($method),
            'method' => $method,
            'status' => Payment::STATUS_PENDING,
            'amount' => $order->grand_total,
            'currency' => $order->currency,
        ]);
    }

    /**
     * Settle a payment. For COD this is driven by the order reaching its
     * terminal success state — the courier has handed over the goods and taken
     * the cash.
     */
    public function markPaid(Payment $payment, ?string $providerReference = null): Payment
    {
        if ($payment->status === Payment::STATUS_PAID) {
            return $payment;
        }

        // Conditional update so two concurrent settlements cannot both apply.
        Payment::query()
            ->whereKey($payment->id)
            ->where('status', Payment::STATUS_PENDING)
            ->update([
                'status' => Payment::STATUS_PAID,
                'paid_at' => now(),
                'provider_reference' => $providerReference,
                'updated_at' => now(),
            ]);

        return $payment->refresh();
    }

    public function markFailed(Payment $payment, string $reason): Payment
    {
        Payment::query()
            ->whereKey($payment->id)
            ->where('status', Payment::STATUS_PENDING)
            ->update([
                'status' => Payment::STATUS_FAILED,
                'failed_at' => now(),
                'failure_reason' => $reason,
                'updated_at' => now(),
            ]);

        return $payment->refresh();
    }

    /**
     * A cancelled order's uncollected payment is cancelled with it. A payment
     * already settled is left alone — that is a refund, not a cancellation.
     */
    public function cancelIfUnsettled(Payment $payment): Payment
    {
        Payment::query()
            ->whereKey($payment->id)
            ->where('status', Payment::STATUS_PENDING)
            ->update([
                'status' => Payment::STATUS_CANCELLED,
                'updated_at' => now(),
            ]);

        return $payment->refresh();
    }

    /**
     * Methods the platform actually accepts. Kept in one place so the checkout
     * summary, the checkout validator and this service cannot disagree.
     *
     * @return list<string>
     */
    public function availableMethods(): array
    {
        return ['cash'];
    }

    private function providerFor(string $method): string
    {
        return match ($method) {
            'cash' => Payment::PROVIDER_COD,
            default => Payment::PROVIDER_COD,
        };
    }
}
