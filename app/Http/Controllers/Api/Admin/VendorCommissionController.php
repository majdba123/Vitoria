<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateVendorCommissionPaidRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class VendorCommissionController extends Controller
{
    /**
     * Show commission and payout statistics for a vendor.
     */
    public function show(Vendor $vendor): JsonResponse
    {
        $statusCounts = [
            'pending' => $this->statusCount($vendor, [Order::STATUS_PENDING]),
            'completed' => $this->statusCount($vendor, [Order::STATUS_CONFIRMED, 'completed']),
            'cancelled' => $this->statusCount($vendor, [Order::STATUS_CANCELLED]),
        ];

        $completedOrders = Order::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', [Order::STATUS_CONFIRMED, 'completed'])
            ->with([
                'items:id,order_id,product_id,line_total',
                'items.product:id,subcategory_id',
                'items.product.subcategory:id,category_id',
                'items.product.subcategory.category:id,name,commission',
            ])
            ->get();

        $completedOrderTotal = (float) $completedOrders->sum(fn (Order $order) => (float) $order->total_amount);

        $categoryBreakdownMap = [];
        $commissionTotal = 0.0;
        $last7Days = $this->buildLastSevenDaysBuckets();

        foreach ($completedOrders as $order) {
            $dateKey = optional($order->created_at)->toDateString();
            if ($dateKey && array_key_exists($dateKey, $last7Days)) {
                $last7Days[$dateKey] += 1;
            }

            foreach ($order->items as $item) {
                /** @var OrderItem $item */
                $category = $item->product?->subcategory?->category;
                $categoryId = $category?->id ?? 0;
                $categoryName = $category?->name ?? 'Unknown';
                $commissionRate = (float) ($category?->commission ?? 0);
                $lineTotal = (float) $item->line_total;
                $commissionAmount = ($lineTotal * $commissionRate) / 100;

                $commissionTotal += $commissionAmount;

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

        $commissionTotal = round($commissionTotal, 2);
        $completedOrderTotal = round($completedOrderTotal, 2);
        $vendorNetTotal = round(max($completedOrderTotal - $commissionTotal, 0), 2);
        $paidAmount = round((float) ($vendor->paid_amount ?? 0), 2);
        $remainingAmount = round(max($commissionTotal - $paidAmount, 0), 2);

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
            'message' => 'Vendor commission statistics retrieved successfully.',
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
                    'completed_order_total' => $completedOrderTotal,
                    'commission_total' => $commissionTotal,
                    'vendor_net_total' => $vendorNetTotal,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                ],
                'category_breakdown' => $categoryBreakdown,
                'completed_orders_last_7_days' => collect($last7Days)->map(function (int $count, string $date) {
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
     */
    public function updatePaidAmount(UpdateVendorCommissionPaidRequest $request, Vendor $vendor): JsonResponse
    {
        $vendor->update([
            'paid_amount' => (float) $request->validated('paid_amount'),
        ]);

        return response()->json([
            'message' => 'Vendor paid amount updated successfully.',
            'data' => [
                'id' => $vendor->id,
                'paid_amount' => (float) $vendor->paid_amount,
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
