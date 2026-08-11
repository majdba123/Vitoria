<?php

namespace App\Services\Commerce;

use App\Models\Invoice;
use App\Models\Order;

/**
 * Invoice creation (spec §19).
 *
 * One invoice per order, created inside the same checkout transaction as the
 * order — every figure it snapshots is already final and immutable at that
 * point (decision D8), so there is no later moment that would make a
 * "pending" invoice more accurate than one issued at placement.
 */
class InvoiceService
{
    public function createForOrder(Order $order): Invoice
    {
        return Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'order_id' => $order->id,
            'vendor_id' => $order->vendor_id,
            'user_id' => $order->user_id,
            'subtotal_amount' => $order->subtotal_amount,
            'discount_total' => $order->coupon_discount_amount,
            'shipping_total' => $order->shipping_total,
            'tax_total' => $order->tax_total,
            'grand_total' => $order->grand_total,
            'currency' => $order->currency,
            'payment_method' => $order->payment_way,
            'issued_at' => now(),
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Invoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }
}
