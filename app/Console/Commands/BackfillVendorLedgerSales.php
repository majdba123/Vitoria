<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\VendorLedgerEntry;
use App\Services\Commerce\VendorLedgerService;
use Illuminate\Console\Command;

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
 */
class BackfillVendorLedgerSales extends Command
{
    /**
     * @var string
     */
    protected $signature = 'ledger:backfill-completed-orders {--dry-run : Report what would be recorded without writing anything}';

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

        $this->info("Found {$missingOrderIds->count()} completed order(s) with no ledger entry.");

        if ($this->option('dry-run')) {
            $this->table(['order_id'], $missingOrderIds->map(fn ($id) => [$id])->all());

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($missingOrderIds->count());
        foreach ($missingOrderIds->chunk(100) as $chunk) {
            Order::query()->whereIn('id', $chunk)->get()->each(function (Order $order) use ($vendorLedgerService, $bar) {
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
