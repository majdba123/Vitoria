<?php

namespace App\Services\Syndicate;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Syndicate;
use App\Models\Vendor;
use App\Services\ApplicationCacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SyndicateDashboardService
{
    public function __construct(protected ApplicationCacheService $cacheService) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Syndicate $syndicate): array
    {
        return $this->cacheService->remember("syndicate_dashboard:{$syndicate->id}:{$syndicate->type}", 300, function () use ($syndicate): array {
            $type = $syndicate->type;
            $ordersByStatus = $this->ordersByStatus($type);
            $sales = $this->salesStats($type);

            return [
                'syndicate' => $this->syndicateSummary($syndicate),
                'total_categories' => $this->categoryQuery($type)->count(),
                'total_merchants' => $this->vendorQuery($type)->count(),
                'total_products' => $this->productQuery($type)->count(),
                'total_podcasts' => 0,
                'order_stats' => $ordersByStatus,
                'sales_stats' => $sales,
                'product_stats' => $this->productStats($type),
                'category_stats' => $this->categoryStats($type),
                'merchant_stats' => $this->merchantStats($type),
                'podcast_stats' => $this->podcastStats(),
                'recent_completed_orders' => $this->recentCompletedOrders($type),
                'recent_products' => $this->recentProducts($type),
                'recent_merchants' => $this->recentMerchants($type),
                'monthly_sales' => $this->monthlySales($type),
                'monthly_order_growth' => $this->monthlyOrders($type),
                'top_selling_categories' => $this->topSellingCategories($type),
                'top_selling_products' => $this->topSellingProducts($type),
                'top_merchants_by_sales' => $this->topMerchantsBySales($type),
            ];
        }, ['syndicates', 'dashboard']);
    }

    public function categories(Syndicate $syndicate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->categoryQuery($syndicate->type)
            ->withCount([
                'vendors',
                'subcategories as products_count' => fn ($query) => $query->join('products', 'products.subcategory_id', '=', 'subcategories.id'),
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function vendors(Syndicate $syndicate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->vendorQuery($syndicate->type)
            ->with(['user:id,name,email,phone_number', 'categories:id,name,type'])
            ->withCount('products')
            ->latest()
            ->paginate($perPage);
    }

    public function products(Syndicate $syndicate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productQuery($syndicate->type)
            ->with(['vendor:id,store_name,business_type', 'subcategory.category:id,name,type'])
            ->latest()
            ->paginate($perPage);
    }

    public function orders(Syndicate $syndicate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderQuery($syndicate->type)
            ->with([
                'user:id,name,email',
                'vendor:id,store_name',
                'items:id,order_id,product_id,product_name,quantity,line_total,unit_price',
                'items.product:id,subcategory_id,vendor_id',
                'items.product.subcategory:id,name,category_id',
                'items.product.subcategory.category:id,name,type,commission',
            ])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function podcasts(Syndicate $syndicate): array
    {
        return [
            'syndicate' => $this->syndicateSummary($syndicate),
            'data' => [],
            'meta' => ['total' => 0],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reports(Syndicate $syndicate): array
    {
        return [
            'syndicate' => $this->syndicateSummary($syndicate),
            'sales' => $this->salesStats($syndicate->type),
            'orders' => $this->ordersByStatus($syndicate->type),
            'products' => $this->productStats($syndicate->type),
            'categories' => $this->categoryStats($syndicate->type),
            'merchants' => $this->merchantStats($syndicate->type),
            'podcasts' => $this->podcastStats(),
        ];
    }

    public function categoryQuery(string $type): Builder
    {
        return Category::query()->where('type', $type);
    }

    public function vendorQuery(string $type): Builder
    {
        return Vendor::query()
            ->where(function (Builder $query) use ($type) {
                $query->where('business_type', $type)
                    ->orWhere('business_type', Vendor::BUSINESS_TYPE_BOTH)
                    ->orWhereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->where('categories.type', $type));
            });
    }

    public function productQuery(string $type): Builder
    {
        return Product::query()
            ->whereHas('subcategory.category', fn (Builder $query) => $query->where('type', $type));
    }

    public function orderQuery(string $type): Builder
    {
        return Order::query()
            ->whereHas('items.product.subcategory.category', fn (Builder $query) => $query->where('type', $type));
    }

    /**
     * @return array<string, mixed>
     */
    protected function syndicateSummary(Syndicate $syndicate): array
    {
        return [
            'id' => $syndicate->id,
            'name' => $syndicate->name,
            'type' => $syndicate->type,
            'type_label' => Category::typeLabels()[$syndicate->type] ?? $syndicate->type,
            'status' => $syndicate->status,
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function ordersByStatus(string $type): array
    {
        $counts = $this->orderQuery($type)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total_orders' => (int) $this->orderQuery($type)->count(),
            'pending_orders' => (int) ($counts[Order::STATUS_PENDING] ?? 0),
            'processing_orders' => (int) ($counts[Order::STATUS_CONFIRMED] ?? 0),
            'completed_orders' => (int) ($counts[Order::STATUS_COMPLETED] ?? 0),
            'cancelled_orders' => (int) ($counts[Order::STATUS_CANCELLED] ?? 0),
            'refunded_orders' => 0,
            'failed_orders' => 0,
            'orders_today' => (int) $this->orderQuery($type)->whereDate('created_at', today())->count(),
            'orders_this_month' => (int) $this->orderQuery($type)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    protected function salesStats(string $type): array
    {
        $totalSales = $this->lineTotalQuery($type)->sum('order_items.line_total');
        $completedSales = $this->lineTotalQuery($type)->where('orders.status', Order::STATUS_COMPLETED)->sum('order_items.line_total');
        $pendingSales = $this->lineTotalQuery($type)->where('orders.status', Order::STATUS_PENDING)->sum('order_items.line_total');
        $cancelledSales = $this->lineTotalQuery($type)->where('orders.status', Order::STATUS_CANCELLED)->sum('order_items.line_total');
        $completedOrders = max(1, $this->orderQuery($type)->where('status', Order::STATUS_COMPLETED)->count());

        return [
            'total_sales' => round((float) $totalSales, 2),
            'completed_sales' => round((float) $completedSales, 2),
            'pending_sales' => round((float) $pendingSales, 2),
            'cancelled_sales' => round((float) $cancelledSales, 2),
            'refunded_sales' => 0,
            'sales_today' => round((float) $this->lineTotalQuery($type)->whereDate('orders.created_at', today())->sum('order_items.line_total'), 2),
            'sales_this_week' => round((float) $this->lineTotalQuery($type)->where('orders.created_at', '>=', now()->startOfWeek())->sum('order_items.line_total'), 2),
            'sales_this_month' => round((float) $this->lineTotalQuery($type)->where('orders.created_at', '>=', now()->startOfMonth())->sum('order_items.line_total'), 2),
            'sales_this_year' => round((float) $this->lineTotalQuery($type)->where('orders.created_at', '>=', now()->startOfYear())->sum('order_items.line_total'), 2),
            'average_order_value' => round(((float) $completedSales) / $completedOrders, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function productStats(string $type): array
    {
        return [
            'total_products' => (int) $this->productQuery($type)->count(),
            'active_products' => (int) $this->productQuery($type)->where('is_active', true)->count(),
            'inactive_products' => (int) $this->productQuery($type)->where('is_active', false)->count(),
            'products_with_images' => (int) $this->productQuery($type)->whereNotNull('image')->count(),
            'products_without_images' => (int) $this->productQuery($type)->whereNull('image')->count(),
            'products_by_category' => $this->productsByCategory($type),
            'products_by_merchant' => $this->productsByMerchant($type),
            'top_selling_products' => $this->topSellingProducts($type),
            'recently_added_products' => $this->recentProducts($type),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function categoryStats(string $type): array
    {
        return [
            'total_categories' => (int) $this->categoryQuery($type)->count(),
            'categories_with_products' => (int) $this->categoryQuery($type)->whereHas('subcategories.products')->count(),
            'categories_without_products' => (int) $this->categoryQuery($type)->whereDoesntHave('subcategories.products')->count(),
            'categories_with_merchants' => (int) $this->categoryQuery($type)->whereHas('vendors')->count(),
            'categories_without_merchants' => (int) $this->categoryQuery($type)->whereDoesntHave('vendors')->count(),
            'top_by_products' => $this->topCategoriesByProducts($type),
            'top_by_orders' => $this->topSellingCategories($type),
            'top_by_sales' => $this->topSellingCategories($type),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function merchantStats(string $type): array
    {
        return [
            'total_merchants' => (int) $this->vendorQuery($type)->count(),
            'total' => (int) $this->vendorQuery($type)->count(),
            'active_merchants' => (int) $this->vendorQuery($type)->where('is_active', true)->count(),
            'inactive_merchants' => (int) $this->vendorQuery($type)->where('is_active', false)->count(),
            'merchants_with_products' => (int) $this->vendorQuery($type)->whereHas('products')->count(),
            'merchants_without_products' => (int) $this->vendorQuery($type)->whereDoesntHave('products')->count(),
            'merchants_by_category' => $this->merchantsByCategory($type),
            'merchants_by_order_count' => $this->merchantsByOrderCount($type),
            'merchants_by_sales' => $this->topMerchantsBySales($type),
            'recent_merchant_registrations' => $this->recentMerchants($type),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function podcastStats(): array
    {
        return [
            'total' => 0,
            'total_podcasts' => 0,
            'active' => 0,
            'active_podcasts' => 0,
            'inactive' => 0,
            'inactive_podcasts' => 0,
        ];
    }

    public function lineTotalQuery(string $type): \Illuminate\Database\Query\Builder
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('subcategories', 'subcategories.id', '=', 'products.subcategory_id')
            ->join('categories', 'categories.id', '=', 'subcategories.category_id')
            ->where('categories.type', $type);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function recentCompletedOrders(string $type): Collection
    {
        return $this->orderQuery($type)
            ->where('status', Order::STATUS_COMPLETED)
            ->with(['user:id,name', 'vendor:id,store_name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => $order->user?->name,
                'merchant' => $order->vendor?->store_name,
                'total_amount' => (float) $order->total_amount,
                'status' => $order->status,
                'payment_status' => $order->payment_way,
                'created_at' => $order->created_at,
            ]);
    }

    protected function recentProducts(string $type): Collection
    {
        return $this->productQuery($type)
            ->with(['vendor:id,store_name', 'subcategory.category:id,name,type'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'merchant' => $product->vendor?->store_name,
                'category' => $product->subcategory?->category?->name,
                'is_active' => $product->is_active,
                'created_at' => $product->created_at,
            ]);
    }

    protected function recentMerchants(string $type): Collection
    {
        return $this->vendorQuery($type)
            ->with('categories:id,name,type')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Vendor $vendor) => [
                'id' => $vendor->id,
                'store_name' => $vendor->store_name,
                'business_type' => $vendor->business_type,
                'categories' => $vendor->categories->pluck('name')->values(),
                'is_active' => $vendor->is_active,
                'created_at' => $vendor->created_at,
            ]);
    }

    protected function monthlySales(string $type): Collection
    {
        return $this->monthAggregateQuery($this->lineTotalQuery($type), 'orders.created_at')
            ->selectRaw('sum(order_items.line_total) as total')
            ->get()
            ->map(fn ($row) => ['month' => $row->month, 'total' => round((float) $row->total, 2)]);
    }

    protected function monthlyOrders(string $type): Collection
    {
        return $this->monthAggregateQuery(
            DB::table('orders')
                ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->join('subcategories', 'subcategories.id', '=', 'products.subcategory_id')
                ->join('categories', 'categories.id', '=', 'subcategories.category_id')
                ->where('categories.type', $type),
            'orders.created_at',
        )
            ->selectRaw('count(distinct orders.id) as total')
            ->get()
            ->map(fn ($row) => ['month' => $row->month, 'total' => (int) $row->total]);
    }

    protected function productsByCategory(string $type): Collection
    {
        return DB::table('categories')
            ->leftJoin('subcategories', 'subcategories.category_id', '=', 'categories.id')
            ->leftJoin('products', 'products.subcategory_id', '=', 'subcategories.id')
            ->where('categories.type', $type)
            ->selectRaw('categories.id, categories.name, categories.type, count(products.id) as products_count')
            ->groupBy('categories.id', 'categories.name', 'categories.type')
            ->orderByDesc('products_count')
            ->limit(10)
            ->get();
    }

    protected function productsByMerchant(string $type): Collection
    {
        return $this->productQuery($type)
            ->join('vendors', 'vendors.id', '=', 'products.vendor_id')
            ->selectRaw('vendors.id, vendors.store_name, count(products.id) as products_count')
            ->groupBy('vendors.id', 'vendors.store_name')
            ->orderByDesc('products_count')
            ->limit(10)
            ->get();
    }

    protected function topCategoriesByProducts(string $type): Collection
    {
        return $this->productsByCategory($type);
    }

    protected function merchantsByCategory(string $type): Collection
    {
        return DB::table('categories')
            ->leftJoin('category_vendor', 'category_vendor.category_id', '=', 'categories.id')
            ->where('categories.type', $type)
            ->selectRaw('categories.id, categories.name, categories.type, count(distinct category_vendor.vendor_id) as merchants_count')
            ->groupBy('categories.id', 'categories.name', 'categories.type')
            ->orderByDesc('merchants_count')
            ->limit(10)
            ->get();
    }

    protected function merchantsByOrderCount(string $type): Collection
    {
        return $this->lineTotalQuery($type)
            ->join('vendors', 'vendors.id', '=', 'products.vendor_id')
            ->selectRaw('vendors.id, vendors.store_name, count(distinct orders.id) as orders_count')
            ->groupBy('vendors.id', 'vendors.store_name')
            ->orderByDesc('orders_count')
            ->limit(10)
            ->get();
    }

    protected function monthAggregateQuery(\Illuminate\Database\Query\Builder $query, string $dateColumn): \Illuminate\Database\Query\Builder
    {
        $monthExpression = match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT({$dateColumn}, '%Y-%m')",
            'pgsql' => "TO_CHAR({$dateColumn}, 'YYYY-MM')",
            default => "strftime('%Y-%m', {$dateColumn})",
        };

        return $query
            ->selectRaw("{$monthExpression} as month")
            ->where($dateColumn, '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month');
    }

    protected function topSellingCategories(string $type): Collection
    {
        return $this->lineTotalQuery($type)
            ->selectRaw('categories.id, categories.name, count(distinct orders.id) as orders_count, sum(order_items.line_total) as sales_total')
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('sales_total')
            ->limit(5)
            ->get();
    }

    protected function topSellingProducts(string $type): Collection
    {
        return $this->lineTotalQuery($type)
            ->selectRaw('products.id, products.name, sum(order_items.quantity) as sold_quantity, sum(order_items.line_total) as sales_total')
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sales_total')
            ->limit(5)
            ->get();
    }

    protected function topMerchantsBySales(string $type): Collection
    {
        return $this->lineTotalQuery($type)
            ->join('vendors', 'vendors.id', '=', 'products.vendor_id')
            ->selectRaw('vendors.id, vendors.store_name, sum(order_items.line_total) as sales_total, count(distinct orders.id) as orders_count')
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->groupBy('vendors.id', 'vendors.store_name')
            ->orderByDesc('sales_total')
            ->limit(5)
            ->get();
    }
}
