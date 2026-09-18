<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Vendor;
use App\Services\Commerce\VendorLedgerService;
use Illuminate\Http\JsonResponse;

class FinancialSummaryController extends Controller
{
    public function __construct(
        private readonly VendorLedgerService $vendorLedgerService,
    ) {}

    /**
     * Platform-wide financial totals for the Admin Financials page (spec
     * §20 extended to every vendor): total commission across all vendors,
     * plus the other ledger aggregates and shipping fees, all computed with
     * database aggregation over the same immutable vendor ledger the
     * per-vendor screens read — never recomputed from today's category
     * commission rates.
     */
    public function index(): JsonResponse
    {
        $ledger = $this->vendorLedgerService->adminSummary();

        // Shipping fees are never recorded on the vendor ledger (§20 only
        // ledgers the vendor's own sale/commission/refund/settlement lines),
        // so this reads the one other reliable, already-existing source:
        // the order's own `shipping_total`, scoped to completed orders to
        // match the ledger's own completed-orders-only basis.
        $shippingFeesTotal = (float) Order::query()
            ->where('status', Order::STATUS_COMPLETED)
            ->sum('shipping_total');

        $topVendorIds = collect($ledger['top_vendors_by_commission'])->pluck('vendor_id');
        $vendorNames = Vendor::query()->whereIn('id', $topVendorIds)->pluck('store_name', 'id');

        $topVendorsByCommission = collect($ledger['top_vendors_by_commission'])
            ->map(fn (array $row) => [
                'vendor_id' => $row['vendor_id'],
                'store_name' => $vendorNames[$row['vendor_id']] ?? null,
                'commission' => $row['commission'],
            ])
            ->values();

        return response()->json([
            'message' => __('api.admin_financial_summary_retrieved'),
            'data' => [
                'gross_sales_total' => $ledger['gross_sales'],
                'commission_total' => $ledger['commission'],
                'refunds_total' => $ledger['refunds'],
                'net_earnings_total' => $ledger['net_earnings'],
                'settled_total' => $ledger['settled'],
                'outstanding_total' => $ledger['outstanding'],
                'shipping_fees_total' => round($shippingFeesTotal, 2),
                'vendor_count' => $ledger['vendor_count'],
                'top_vendors_by_commission' => $topVendorsByCommission,
            ],
        ]);
    }
}
