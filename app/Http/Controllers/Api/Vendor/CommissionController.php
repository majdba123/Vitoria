<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vendor;
use App\Services\Commerce\VendorLedgerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function __construct(
        private readonly VendorLedgerService $vendorLedgerService,
    ) {}

    /**
     * Show authenticated vendor commission statistics.
     */
    public function show(Request $request): JsonResponse
    {
        $vendor = $request->user()?->managedVendor();
        if (! $vendor instanceof Vendor) {
            abort(403, 'Vendor profile not found.');
        }

        // Every non-pending, non-cancelled status counts as "completed" here —
        // this bucket only feeds the status-breakdown percentage bars, not the
        // commission/financial figures below, so it must cover every status
        // Order::TRANSITIONS can produce or the three bars silently undercount
        // orders sitting in preparing/shipped/out_for_delivery.
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

        // The per-category breakdown and the "projected" total below stay a
        // live, informational preview (including CONFIRMED orders that have
        // not completed yet — hence "projected", not "completed") — but the
        // authoritative "what do we owe this vendor" figures come from the
        // ledger: an immutable snapshot taken once when each order actually
        // completes, not recomputed from the category's *current* commission
        // rate on every request (that recompute previously meant editing a
        // category's rate retroactively rewrote every past order's
        // commission). The two must never be labeled the same way — a figure
        // that includes in-progress orders is never called "completed".
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
            'message' => 'Commission statistics retrieved successfully.',
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
                // Metadata so any consumer of this response (frontend or
                // otherwise) can tell which top-level fields are the
                // immutable ledger record and which are a live, in-progress
                // preview — instead of inferring it from field names alone.
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
