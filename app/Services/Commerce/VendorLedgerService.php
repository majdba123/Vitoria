<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use App\Models\VendorSettlement;
use Illuminate\Support\Facades\DB;

/**
 * The vendor's auditable financial history (spec §20).
 *
 * `vendors.paid_amount` is a single mutable scalar with no record of *when*
 * or *why* it changed, and the commission it is compared against was always
 * recomputed live from the category's *current* commission rate — so a rate
 * change retroactively rewrote every past vendor's historical commission.
 * Every entry here instead captures amounts at the moment the real business
 * event happened, and is never updated or deleted; a correction is a new
 * `adjustment` entry.
 */
class VendorLedgerService
{
    /**
     * Record a completed order's gross sale and the platform commission on
     * it. Called once, from the single place an order reaches `completed`
     * (OrderStatusService::transition), which is itself a terminal status —
     * so this cannot fire twice for the same order. The existence check
     * below is a second, defensive guard, not the only one.
     */
    public function recordSale(Order $order): void
    {
        if (VendorLedgerEntry::query()->where('order_id', $order->id)->where('type', VendorLedgerEntry::TYPE_SALE)->exists()) {
            return;
        }

        $order->loadMissing('items.product.category');

        $commissionTotal = 0.0;

        foreach ($order->items as $item) {
            /** @var OrderItem $item */
            $rate = (float) ($item->product?->category?->commission ?? 0);
            $commissionTotal += ((float) $item->line_total * $rate) / 100;
        }

        $commissionTotal = round($commissionTotal, 2);
        $orderNumber = $order->order_number;

        DB::transaction(function () use ($commissionTotal, $order, $orderNumber): void {
            VendorLedgerEntry::create([
                'vendor_id' => $order->vendor_id,
                'order_id' => $order->id,
                'type' => VendorLedgerEntry::TYPE_SALE,
                'direction' => VendorLedgerEntry::DIRECTION_CREDIT,
                'amount' => $order->subtotal_amount,
                'description' => __('vendor_ledger.entry.sale', ['order' => $orderNumber]),
            ]);

            if ($commissionTotal > 0) {
                VendorLedgerEntry::create([
                    'vendor_id' => $order->vendor_id,
                    'order_id' => $order->id,
                    'type' => VendorLedgerEntry::TYPE_COMMISSION,
                    'direction' => VendorLedgerEntry::DIRECTION_DEBIT,
                    'amount' => $commissionTotal,
                    'description' => __('vendor_ledger.entry.commission', ['order' => $orderNumber]),
                ]);
            }
        });
    }

    /**
     * Record a completed refund against the vendor that owned the order.
     * Called once, from RefundService::complete() — which is itself
     * exactly-once per refund (decision D1's pattern applied to money).
     */
    public function recordRefund(Refund $refund): void
    {
        $order = $refund->order()->firstOrFail();

        VendorLedgerEntry::create([
            'vendor_id' => $order->vendor_id,
            'order_id' => $order->id,
            'type' => VendorLedgerEntry::TYPE_REFUND,
            'direction' => VendorLedgerEntry::DIRECTION_DEBIT,
            'amount' => $refund->amount,
            'description' => __('vendor_ledger.entry.refund', ['order' => $order->order_number]),
        ]);
    }

    /**
     * A manual correction — never an edit to a past entry.
     */
    public function recordAdjustment(Vendor $vendor, User $actor, float $amount, string $direction, string $description): VendorLedgerEntry
    {
        return VendorLedgerEntry::create([
            'vendor_id' => $vendor->id,
            'order_id' => null,
            'type' => VendorLedgerEntry::TYPE_ADJUSTMENT,
            'direction' => $direction,
            'amount' => round($amount, 2),
            'description' => $description,
            'created_by_user_id' => $actor->id,
        ]);
    }

    /**
     * Record a payout to the vendor, capped at what is actually outstanding
     * so a settlement can never push the balance negative.
     *
     * @throws CartException
     */
    public function recordSettlement(Vendor $vendor, User $actor, float $amount, string $method, ?string $reference = null, ?string $notes = null): VendorSettlement
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new CartException(__('vendor_ledger.settlement_amount_invalid'));
        }

        if (! in_array($method, VendorSettlement::METHODS, true)) {
            throw new CartException(__('vendor_ledger.settlement_method_invalid'));
        }

        $outstanding = $this->summary($vendor)['outstanding'];

        if ($amount > $outstanding) {
            throw new CartException(__('vendor_ledger.settlement_exceeds_outstanding'));
        }

        return DB::transaction(function () use ($actor, $amount, $method, $notes, $reference, $vendor) {
            $entry = VendorLedgerEntry::create([
                'vendor_id' => $vendor->id,
                'order_id' => null,
                'type' => VendorLedgerEntry::TYPE_SETTLEMENT,
                'direction' => VendorLedgerEntry::DIRECTION_DEBIT,
                'amount' => $amount,
                'description' => __('vendor_ledger.entry.settlement'),
                'created_by_user_id' => $actor->id,
            ]);

            return VendorSettlement::create([
                'vendor_id' => $vendor->id,
                'ledger_entry_id' => $entry->id,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'notes' => $notes,
                'settled_by_user_id' => $actor->id,
                'settled_at' => now(),
            ]);
        });
    }

    /**
     * The six figures the vendor finance screen shows (spec §20).
     *
     * @return array{gross_sales: float, commission: float, refunds: float, net_earnings: float, settled: float, outstanding: float}
     */
    public function summary(Vendor $vendor): array
    {
        $totals = VendorLedgerEntry::query()
            ->where('vendor_id', $vendor->id)
            ->selectRaw('type, direction, COALESCE(SUM(amount), 0) as total')
            ->groupBy('type', 'direction')
            ->get()
            ->reduce(function (array $carry, $row) {
                $carry[$row->type][$row->direction] = (float) $row->total;

                return $carry;
            }, []);

        $amount = fn (string $type, string $direction) => (float) ($totals[$type][$direction] ?? 0.0);

        $grossSales = $amount(VendorLedgerEntry::TYPE_SALE, VendorLedgerEntry::DIRECTION_CREDIT);
        $commission = $amount(VendorLedgerEntry::TYPE_COMMISSION, VendorLedgerEntry::DIRECTION_DEBIT);
        $refunds = $amount(VendorLedgerEntry::TYPE_REFUND, VendorLedgerEntry::DIRECTION_DEBIT);
        $adjustmentsNet = $amount(VendorLedgerEntry::TYPE_ADJUSTMENT, VendorLedgerEntry::DIRECTION_CREDIT)
            - $amount(VendorLedgerEntry::TYPE_ADJUSTMENT, VendorLedgerEntry::DIRECTION_DEBIT);
        $settled = $amount(VendorLedgerEntry::TYPE_SETTLEMENT, VendorLedgerEntry::DIRECTION_DEBIT);

        $netEarnings = round($grossSales - $commission - $refunds + $adjustmentsNet, 2);
        $outstanding = round(max($netEarnings - $settled, 0), 2);

        return [
            'gross_sales' => round($grossSales, 2),
            'commission' => round($commission, 2),
            'refunds' => round($refunds, 2),
            'net_earnings' => $netEarnings,
            'settled' => round($settled, 2),
            'outstanding' => $outstanding,
        ];
    }
}
