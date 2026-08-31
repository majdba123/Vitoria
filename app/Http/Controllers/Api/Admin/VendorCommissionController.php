<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVendorCommissionPaidRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vendor;
use App\Services\Commerce\CartException;
use App\Services\Commerce\VendorLedgerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class VendorCommissionController extends Controller
{
    public function __construct(
        private readonly VendorLedgerService $vendorLedgerService,
    ) {}

    /**
     * Show commission and payout statistics for a vendor.
     */
    public function show(Vendor $vendor): JsonResponse
    {
        // Every non-pending, non-cancelled status counts as "completed" here —
        // this bucket only feeds the status-breakdown percentage bars, not the
        // commission/financial figures below, so it must cover every status
        // Order::TRANSITIONS can produce or the three bars silently undercount
        // orders sitting in preparing/shipped/out_for_delivery (same fix as
        // Api\Vendor\CommissionController).
        $statusCounts = [
            'pending' => $this->statusCount($vendor, [Order::STATUS_PENDING]),
            'completed' => $this->statusCount($vendor, [
                Order::STATUS_CONFIRMED,
                Order::STATUS_PREPARING,
                Order::STATUS_SHIPPED,
                Order::STATUS_OUT_FOR_DELIVERY,
                Order::STATUS_COMPLETED,
            ]),
            'cancelled' => $this->statusCount($vendor, [Order::STATUS_CANCELLED]),
        ];

        $completedOrders = Order::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_COMPLETED])
            ->where('created_at', '>=', now()->subDays(365))
            ->with([
                'items:id,order_id,product_id,line_total',
                'items.product:id,category_id',
                'items.product.category:id,name,commission',
            ])
            ->get();

        $completedOrderTotal = (float) $completedOrders->sum(fn (Order $order) => (float) $order->total_amount);

        $categoryBreakdownMap = [];
        $last7Days = $this->buildLastSevenDaysBuckets();

        foreach ($completedOrders as $order) {
            $dateKey = optional($order->created_at)->toDateString();
            if ($dateKey && array_key_exists($dateKey, $last7Days)) {
                $last7Days[$dateKey] += 1;
            }

            foreach ($order->items as $item) {
                /** @var OrderItem $item */
                $category = $item->product?->category;
                $categoryId = $category?->id ?? 0;
                $categoryName = $category?->name ?? 'Unknown';
                $commissionRate = (float) ($category?->commission ?? 0);
                $lineTotal = (float) $item->line_total;
                $commissionAmount = ($lineTotal * $commissionRate) / 100;

                if (! isset($categoryBreakdownMap[$categoryId])) {
                    $categoryBreakdownMap[$categoryId] = [
                        'category_id' => $categoryId,
                        'category_name' => $categoryName,
                        'commission_rate' => round($commissionRate, 2),
                        'sales_total' => 0.0,
                        'commission_amount' => 0.0,
                    ];
                }

                $categoryBreakdownMap[$categoryId]['sales_total'] += $lineTotal;
                $categoryBreakdownMap[$categoryId]['commission_amount'] += $commissionAmount;
            }
        }

        // Same fix as Api\Vendor\CommissionController: the authoritative
        // owed-to-vendor figures come from the immutable ledger snapshot
        // (taken once at order completion), not recomputed live from the
        // category's current commission rate on every request. The
        // "projected" total below includes CONFIRMED orders that have not
        // completed yet, so it is never labeled or compared as "completed".
        // (This previously also exposed a `vendor_net_total` computed as
        // projected_total − ledger_commission — a mixed-basis figure with no
        // frontend consumer. Removed rather than fixed in place, since
        // mixing a live/projected number with a ledger-snapshotted one is
        // exactly the ambiguity being eliminated here.)
        $ledger = $this->vendorLedgerService->summary($vendor);
        $commissionTotal = $ledger['commission'];
        $paidAmount = $ledger['settled'];
        $remainingAmount = $ledger['outstanding'];
        $projectedOrderTotal = round($completedOrderTotal, 2);

        $categoryBreakdown = collect($categoryBreakdownMap)
            ->map(function (array $row) {
                $row['sales_total'] = round((float) $row['sales_total'], 2);
                $row['commission_amount'] = round((float) $row['commission_amount'], 2);

                return $row;
            })
            ->sortByDesc('commission_amount')
            ->values()
            ->all();

        return response()->json([
            'message' => __('api.vendor_commission_statistics_retrieved'),
            'data' => [
                'vendor' => [
                    'id' => $vendor->id,
                    'store_name' => $vendor->store_name,
                    'paid_amount' => $paidAmount,
                ],
                'orders' => [
                    'status_counts' => $statusCounts,
                    'total' => array_sum($statusCounts),
                ],
                'financials' => [
                    // Ledger-authoritative: COMPLETED orders only, snapshotted
                    // at completion time, never recomputed from today's rates.
                    'commission_total' => $commissionTotal,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    // Projected/live preview: CONFIRMED + COMPLETED orders
                    // from the last 365 days, recomputed on every request.
                    // Never compare this to the ledger figures above as if
                    // they measured the same thing.
                    'projected_order_total' => $projectedOrderTotal,
                ],
                'basis' => [
                    'ledger' => ['financials.commission_total', 'financials.paid_amount', 'financials.remaining_amount'],
                    'projected' => ['financials.projected_order_total', 'category_breakdown', 'recent_orders_last_7_days'],
                ],
                'category_breakdown' => $categoryBreakdown,
                'recent_orders_last_7_days' => collect($last7Days)->map(function (int $count, string $date) {
                    return [
                        'date' => $date,
                        'count' => $count,
                    ];
                })->values()->all(),
            ],
        ]);
    }

    /**
     * Update vendor paid amount used in commission settlement.
     *
     * This screen's UX is "type the new total amount paid to date" — but the
     * ledger (the single source of truth for payouts, see VendorLedgerService)
     * only understands incremental settlements, capped at what's outstanding.
     * Previously this endpoint wrote `vendors.paid_amount` directly: a bare,
     * uncapped, unaudited scalar overwrite completely disconnected from the
     * ledger, so the same vendor could be paid twice — once here, once via
     * the proper Admin\SettlementController — with nothing to notice or
     * prevent it. This now computes the *difference* against what the ledger
     * already has recorded as settled, and records only that difference as a
     * real, capped, audited settlement — so the two payout paths can never
     * double-pay the same money.
     */
    public function updatePaidAmount(UpdateVendorCommissionPaidRequest $request, Vendor $vendor): JsonResponse
    {
        $newTotal = round((float) $request->validated('paid_amount'), 2);
        $alreadySettled = $this->vendorLedgerService->summary($vendor)['settled'];
        $delta = round($newTotal - $alreadySettled, 2);

        if ($delta <= 0) {
            return response()->json([
                'message' => __('vendor_ledger.paid_amount_not_greater_than_settled', ['settled' => $alreadySettled]),
            ], 422);
        }

        try {
            $this->vendorLedgerService->recordSettlement(
                $vendor,
                $request->user(),
                $delta,
                'other',
                null,
                'Recorded from the vendor commission paid-amount screen.',
            );
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $summary = $this->vendorLedgerService->summary($vendor);

        return response()->json([
            'message' => __('api.vendor_paid_amount_updated'),
            'data' => [
                'id' => $vendor->id,
                'paid_amount' => $summary['settled'],
            ],
        ]);
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private function statusCount(Vendor $vendor, array $statuses): int
    {
        return Order::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', $statuses)
            ->count();
    }

    /**
     * @return array<string, int>
     */
    private function buildLastSevenDaysBuckets(): array
    {
        $buckets = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $buckets[$date] = 0;
        }

        return $buckets;
    }
}
