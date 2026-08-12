<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregation queries for the admin reports (spec §36).
 *
 * Deliberately built on the existing Order/OrderItem/VendorLedgerEntry data
 * rather than a new reporting schema — there is no requirement for
 * scheduled/exported report snapshots, so these are computed on demand.
 */
class ReportService
{
    /**
     * Revenue and order counts over a date range, grouped by day or month.
     *
     * @return array<string, mixed>
     */
    public function sales(Carbon $from, Carbon $to, string $groupBy = 'day'): array
    {
        $dateExpression = $this->dateGroupExpression($groupBy);

        $rows = Order::query()
            ->selectRaw("{$dateExpression} as period, count(*) as orders_count, sum(grand_total) as revenue")
            ->whereBetween('created_at', [$from, $to])
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->period,
                'orders_count' => (int) $row->orders_count,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values();

        $statusBreakdown = Order::query()
            ->selectRaw('status, count(*) as total')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => ['status' => $row->status, 'total' => (int) $row->total])
            ->values();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'group_by' => $groupBy,
            'total_orders' => (int) $rows->sum('orders_count'),
            'total_revenue' => round((float) $rows->sum('revenue'), 2),
            'series' => $rows,
            'status_breakdown' => $statusBreakdown,
        ];
    }

    /**
     * Per-vendor order/revenue/ledger totals over a date range.
     *
     * @return array<string, mixed>
     */
    public function vendorPerformance(Carbon $from, Carbon $to): array
    {
        $orderTotals = Order::query()
            ->selectRaw('vendor_id, count(*) as orders_count, sum(grand_total) as revenue')
            ->whereBetween('created_at', [$from, $to])
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        $ledgerTotals = VendorLedgerEntry::query()
            ->selectRaw("vendor_id, sum(case when direction = 'credit' then amount else -amount end) as net_ledger")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('vendor_id')
            ->get()
            ->keyBy('vendor_id');

        $vendors = Vendor::query()
            ->whereIn('id', $orderTotals->keys()->merge($ledgerTotals->keys())->unique())
            ->get(['id', 'store_name', 'business_type'])
            ->map(function (Vendor $vendor) use ($orderTotals, $ledgerTotals) {
                $orderRow = $orderTotals->get($vendor->id);
                $ledgerRow = $ledgerTotals->get($vendor->id);

                return [
                    'vendor_id' => $vendor->id,
                    'store_name' => $vendor->store_name,
                    'business_type' => $vendor->business_type,
                    'orders_count' => (int) ($orderRow->orders_count ?? 0),
                    'revenue' => round((float) ($orderRow->revenue ?? 0), 2),
                    'net_ledger_amount' => round((float) ($ledgerRow->net_ledger ?? 0), 2),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'vendors' => $vendors,
        ];
    }

    /**
     * Best-selling products by quantity and revenue over a date range.
     *
     * @return array<string, mixed>
     */
    public function productPerformance(Carbon $from, Carbon $to, int $limit = 20): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.status', '!=', Order::STATUS_CANCELLED)
            ->selectRaw('order_items.product_id, order_items.product_name, sum(order_items.quantity) as quantity_sold, sum(order_items.line_total) as revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'products' => $rows,
        ];
    }

    private function dateGroupExpression(string $groupBy): string
    {
        $driver = DB::connection()->getDriverName();

        if ($groupBy === 'month') {
            return match ($driver) {
                'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m')",
                'pgsql' => "TO_CHAR(created_at, 'YYYY-MM')",
                default => "strftime('%Y-%m', created_at)",
            };
        }

        return match ($driver) {
            'mysql', 'mariadb' => 'DATE(created_at)',
            'pgsql' => 'created_at::date',
            default => "strftime('%Y-%m-%d', created_at)",
        };
    }
}
