<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\VendorLedgerEntry;
use App\Services\Commerce\VendorLedgerService;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

/**
 * One-time reconciliation for any COMPLETED order that predates the vendor
 * ledger (VendorLedgerService) — e.g. an order that completed before that
 * service existed, or before this specific deploy that made the commission
 * dashboards read from the ledger instead of recomputing live from each
 * category's *current* commission rate. Without this, such an order simply
 * has no ledger entry and is invisible to `VendorLedgerService::summary()`,
 * understating a vendor's gross sales/commission on the (now-authoritative)
 * commission dashboards.
 *
 * Safe to run repeatedly: `recordSale()` is itself idempotent (guarded by a
 * unique `idempotency_key` per order), so an order that already has an entry
 * is skipped, never duplicated.
 *
 * KNOWN ACCURACY LIMIT — read before running against production data:
 * neither `orders` nor `order_items` stores the commission rate that was in
 * effect when the order actually completed. `recordSale()` always computes
 * commission from each item's category's *current* rate, which is correct
 * for a live order (it fires the moment the order completes, so "current"
 * and "at completion time" are the same instant) but is only an
 * approximation for a historical order being backfilled long after the
 * fact: if the category's commission rate has changed since that order
 * completed, the backfilled commission entry will reflect today's rate, not
 * the rate the vendor actually agreed to at the time. This command cannot
 * reconstruct a rate that was never stored — it surfaces the computed
 * amount in --dry-run and requires confirmation before writing so an
 * operator can catch a rate-drift discrepancy before it becomes a
 * permanent ledger entry, but it cannot detect drift on its own.
 */
class BackfillVendorLedgerSales extends Command
{
    use ConfirmableTrait;

    /**
     * @var string
     */
    protected $signature = 'ledger:backfill-completed-orders
        {--dry-run : Report what would be recorded, with computed amounts, without writing anything}
        {--force : Skip the confirmation prompt (still required in addition to running without --dry-run)}';

    /**
     * @var string
     */
    protected $description = 'Record a ledger sale/commission entry for any completed order that does not have one yet.';

    public function handle(VendorLedgerService $vendorLedgerService): int
    {
        $completedOrderIds = Order::query()->where('status', Order::STATUS_COMPLETED)->pluck('id');

        $alreadyRecorded = VendorLedgerEntry::query()
            ->whereIn('order_id', $completedOrderIds)
            ->where('type', VendorLedgerEntry::TYPE_SALE)
            ->pluck('order_id');

        $missingOrderIds = $completedOrderIds->diff($alreadyRecorded);

        if ($missingOrderIds->isEmpty()) {
            $this->info('Every completed order already has a ledger sale entry — nothing to backfill.');

            return self::SUCCESS;
        }

        $missingOrders = Order::query()
            ->whereIn('id', $missingOrderIds)
            ->with('items.product.category')
            ->get();

        $preview = $missingOrders->map(function (Order $order) {
            $commission = $order->items->sum(function ($item) {
                $rate = (float) ($item->product?->category?->commission ?? 0);

                return ((float) $item->line_total * $rate) / 100;
            });

            return [
                $order->id,
                $order->order_number,
                number_format((float) $order->subtotal_amount, 2),
                number_format(round($commission, 2), 2),
                $order->updated_at?->toDateString() ?? '—',
            ];
        });

        $this->warn("Found {$missingOrderIds->count()} completed order(s) with no ledger entry.");
        $this->warn('Commission below is computed from each category\'s CURRENT rate — if a rate changed since '
            .'an order completed, the figure will not match what was actually owed at that time. No historical '
            .'rate is stored anywhere in this database, so this cannot be corrected automatically. Review before '
            .'proceeding.');
        $this->table(['order_id', 'order_number', 'subtotal', 'commission (current rate)', 'completed'], $preview->all());

        if ($this->option('dry-run')) {
            $this->info('Dry run only — nothing was written. Re-run without --dry-run to record these entries.');

            return self::SUCCESS;
        }

        if (! $this->confirmToProceed(
            'This will permanently write ledger sale/commission entries for the orders above, using CURRENT commission rates.'
        )) {
            return self::FAILURE;
        }

        $bar = $this->output->createProgressBar($missingOrderIds->count());
        foreach ($missingOrders->chunk(100) as $chunk) {
            $chunk->each(function (Order $order) use ($vendorLedgerService, $bar) {
                $vendorLedgerService->recordSale($order);
                $bar->advance();
            });
        }
        $bar->finish();
        $this->newLine();

        $this->info("Backfilled {$missingOrderIds->count()} order(s).");

        return self::SUCCESS;
    }
}
