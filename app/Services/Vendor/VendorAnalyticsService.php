<?php

namespace App\Services\Vendor;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use App\Models\VendorSettlement;
use App\Services\Commerce\VendorLedgerService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VendorAnalyticsService
{
    public function __construct(private readonly VendorLedgerService $vendorLedgerService) {}

    /** @param array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null} $period */
    public function overview(Vendor $vendor, array $period, ?string $domain = null): array
    {
        $products = $this->productQuery($vendor, $domain);
        $periodOrders = $this->period($this->orderQuery($vendor, $domain), $period, 'orders.created_at');
        $completed = $this->lines($vendor, $period, $domain)->where('orders.status', Order::STATUS_COMPLETED)
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) units, COALESCE(SUM(order_items.line_total), 0) sales, COUNT(DISTINCT orders.id) orders')->first();
        $completedOrders = (int) ($completed?->orders ?? 0);
        $grossSales = round((float) ($completed?->sales ?? 0), 2);
        $refunds = $domain ? $this->domainRefunds($vendor, $period, $domain) : $this->periodRefunds($vendor, $period);

        return [
            'scope' => ['domain' => $domain, 'period' => $this->presentPeriod($period), 'period_basis' => 'order_created_at', 'all_time_finance_separate' => true],
            'vendor' => $this->identity($vendor, $domain),
            'kpis' => [
                'total_products' => (clone $products)->count(),
                'active_products' => (clone $products)->where('is_active', true)->count(),
                'pending_products' => (clone $products)->where('status', Product::STATUS_PENDING)->count(),
                'total_orders' => (clone $periodOrders)->count(),
                'completed_orders' => $completedOrders,
                'cancelled_orders' => (clone $periodOrders)->where('status', Order::STATUS_CANCELLED)->count(),
                'units_sold' => (int) ($completed?->units ?? 0),
                'gross_sales' => $grossSales,
                'refunds' => $refunds['amount'],
                'average_completed_order_value' => $completedOrders ? round($grossSales / $completedOrders, 2) : 0.0,
                'last_sale_at' => $this->lines($vendor, ['key' => 'all', 'from' => null, 'to' => null], $domain)->where('orders.status', Order::STATUS_COMPLETED)->max('orders.created_at'),
            ],
            'trend' => $this->trend($vendor, $period, $domain),
            'top_products' => $this->topProducts($vendor, $period, $domain),
            'category_performance' => $this->categoryPerformance($vendor, $period, $domain),
            'recent_orders' => $this->orders($vendor, $period, [], 5, $domain)->items(),
            'finance' => $domain ? $this->domainFinance($vendor, $period, $domain) : ['all_time' => $this->vendorLedgerService->summary($vendor), 'period_refunds' => $refunds['amount']],
        ];
    }

    /** @param array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null} $period */
    public function products(Vendor $vendor, array $period, array $filters, int $perPage, ?string $domain = null): LengthAwarePaginator
    {
        $metrics = $this->lines($vendor, $period, $domain)->where('orders.status', Order::STATUS_COMPLETED)
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) units_sold, SUM(order_items.line_total) sales, COUNT(DISTINCT orders.id) order_count, MAX(orders.created_at) last_sold_at')->groupBy('order_items.product_id');
        $returns = DB::table('return_items')->join('order_returns', 'order_returns.id', '=', 'return_items.order_return_id')
            ->where('order_returns.vendor_id', $vendor->id)->selectRaw('return_items.product_id, COUNT(DISTINCT order_returns.id) returns_count')->groupBy('return_items.product_id');
        $query = $this->productQuery($vendor, $domain)
            ->with(['category:id,name,type', 'subcategory:id,name_ar,name_en', 'photos:id,product_id,path,image_type,is_primary,sort_order'])
            ->withAvg('reviews', 'rating')->withCount('favouritedBy')
            ->leftJoinSub($metrics, 'sales_metrics', 'sales_metrics.product_id', '=', 'products.id')
            ->leftJoinSub($returns, 'return_metrics', 'return_metrics.product_id', '=', 'products.id')
            ->select('products.*')->selectRaw('COALESCE(sales_metrics.units_sold, 0) units_sold, COALESCE(sales_metrics.sales, 0) completed_sales_amount, COALESCE(sales_metrics.order_count, 0) completed_order_count, sales_metrics.last_sold_at, COALESCE(return_metrics.returns_count, 0) returns_count')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $nested) => $nested->where('products.name_ar', 'like', "%{$search}%")->orWhere('products.name_en', 'like', "%{$search}%")))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $id) => $query->where('products.category_id', $id))
            ->when($filters['product_status'] ?? null, fn (Builder $query, string $status) => $query->where('products.status', $status))
            ->when(array_key_exists('active', $filters), fn (Builder $query) => $query->where('products.is_active', (bool) $filters['active']));
        $sorts = ['name' => 'products.name', 'price' => 'products.price', 'units_sold' => 'units_sold', 'sales' => 'completed_sales_amount', 'last_sold_at' => 'last_sold_at', 'created_at' => 'products.created_at'];
        $query->orderBy($sorts[$filters['sort'] ?? 'created_at'] ?? 'products.created_at', ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc');

        return $query->paginate($perPage)->through(fn (Product $product) => $this->presentProduct($product));
    }

    /** @param array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null} $period */
    public function orders(Vendor $vendor, array $period, array $filters, int $perPage, ?string $domain = null): LengthAwarePaginator
    {
        $query = $this->period($this->orderQuery($vendor, $domain), $period, 'orders.created_at')
            ->with(['user:id,name,email,phone_number', 'payment', 'cancelledBy:id,name', 'statusHistories.changedBy:id,name'])
            ->with(['items' => fn ($query) => $query->when($domain, fn ($items) => $items->whereHas('product.category', fn ($category) => $category->where('type', $domain)))])->withCount('items')
            ->when($filters['order_status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $nested) => $nested->where('order_number', 'like', "%{$search}%")->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"))));
        $sorts = ['order_number' => 'order_number', 'total' => 'grand_total', 'status' => 'status', 'created_at' => 'created_at'];
        $query->orderBy($sorts[$filters['sort'] ?? 'created_at'] ?? 'created_at', ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc');

        return $query->paginate($perPage)->through(fn (Order $order) => $this->presentOrder($order, $domain));
    }

    /** @param array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null} $period */
    public function returns(Vendor $vendor, array $period, int $perPage, ?string $domain = null): LengthAwarePaginator
    {
        $query = OrderReturn::query()->where('vendor_id', $vendor->id)
            ->when($domain, fn (Builder $builder) => $builder->whereHas('items.product.category', fn (Builder $category) => $category->where('type', $domain)))
            ->with(['order:id,order_number', 'refund:id,order_return_id,amount,status,completed_at', 'items' => fn ($items) => $items->when($domain, fn ($scoped) => $scoped->whereHas('product.category', fn ($category) => $category->where('type', $domain)))->with('product:id,name_ar,name_en')]);
        $this->period($query, $period, 'order_returns.created_at');

        return $query->latest()->paginate($perPage)->through(function (OrderReturn $return) use ($domain): array {
            $scopedAmount = round((float) $return->items->sum('line_total'), 2);
            $complete = $domain === null || abs($scopedAmount - (float) $return->refundable_amount) < 0.01;

            return [
                'id' => $return->id, 'return_number' => $return->return_number, 'order' => $return->order?->only(['id', 'order_number']),
                'items' => $return->items->map(fn ($item) => ['product' => $item->product?->only(['id', 'name']), 'quantity' => $item->quantity, 'line_total' => (float) $item->line_total])->values(),
                'reason' => $return->reason, 'status' => $return->status, 'scoped_return_amount' => $scopedAmount,
                'refund_amount' => $complete && $return->refund?->status === Refund::STATUS_COMPLETED ? (float) $return->refund->amount : null,
                'financial_attribution_complete' => $complete, 'created_at' => $return->created_at, 'completed_at' => $return->refund?->completed_at,
            ];
        });
    }

    public function activity(Vendor $vendor, int $perPage): LengthAwarePaginator
    {
        $productIds = $vendor->products()->pluck('id');
        $ledgerIds = VendorLedgerEntry::query()->where('vendor_id', $vendor->id)->pluck('id');
        $settlementIds = VendorSettlement::query()->where('vendor_id', $vendor->id)->pluck('id');

        return AuditLog::query()->with('actor:id,name')->where(function (Builder $query) use ($ledgerIds, $productIds, $settlementIds, $vendor): void {
            $query->where(fn (Builder $nested) => $nested->where('entity_type', 'Vendor')->where('entity_id', $vendor->id))
                ->orWhere(fn (Builder $nested) => $nested->where('entity_type', 'Product')->whereIn('entity_id', $productIds))
                ->orWhere(fn (Builder $nested) => $nested->where('entity_type', 'VendorLedgerEntry')->whereIn('entity_id', $ledgerIds))
                ->orWhere(fn (Builder $nested) => $nested->where('entity_type', 'VendorSettlement')->whereIn('entity_id', $settlementIds));
        })->latest()->paginate($perPage);
    }

    public function productQuery(Vendor $vendor, ?string $domain = null): Builder
    {
        return Product::query()->where('vendor_id', $vendor->id)
            ->when($domain, fn (Builder $query) => $query->whereHas('category', fn (Builder $category) => $category->where('type', $domain)));
    }

    public function orderQuery(Vendor $vendor, ?string $domain = null): Builder
    {
        return Order::query()->where('vendor_id', $vendor->id)
            ->when($domain, fn (Builder $query) => $query->whereHas('items.product.category', fn (Builder $category) => $category->where('type', $domain)));
    }

    /** @param array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null} $period */
    private function lines(Vendor $vendor, array $period, ?string $domain): QueryBuilder
    {
        $query = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.vendor_id', $vendor->id)->whereColumn('products.vendor_id', 'orders.vendor_id')
            ->when($domain, fn (QueryBuilder $builder) => $builder->where('categories.type', $domain));

        return $this->period($query, $period, 'orders.created_at');
    }

    /** @param Builder|QueryBuilder $query @param array{key: string, from: CarbonImmutable|null, to: CarbonImmutable|null} $period @return Builder|QueryBuilder */
    private function period($query, array $period, string $column)
    {
        if ($period['from']) {
            $query->where($column, '>=', $period['from']);
        }
        if ($period['to']) {
            $query->where($column, '<=', $period['to']);
        }

        return $query;
    }

    private function identity(Vendor $vendor, ?string $domain): array
    {
        $vendor->loadMissing(['user:id,name,email,phone_number', 'city:id,name', 'categories:id,name,type']);

        return [
            'id' => $vendor->id, 'store_name' => $vendor->store_name, 'logo_url' => $vendor->logo ? asset('storage/'.$vendor->logo) : null,
            'owner' => $vendor->user?->only(['id', 'name', 'email', 'phone_number']), 'business_type' => $vendor->business_type,
            'city' => $vendor->city?->only(['id', 'name']), 'registration_source' => $vendor->registration_source, 'status' => $vendor->status,
            'is_active' => (bool) $vendor->is_active, 'joined_at' => $vendor->created_at,
            'categories' => $vendor->categories->when($domain, fn (Collection $categories) => $categories->where('type', $domain))->values()->map->only(['id', 'name', 'type']),
        ];
    }

    private function presentProduct(Product $product): array
    {
        $photo = $product->photos->sortBy(fn ($item) => $item->image_type === 'primary' ? 0 : ($item->is_primary ? 1 : 2))->first();

        return [
            'id' => $product->id, 'name' => $product->name, 'image_url' => $photo ? asset('storage/'.$photo->path) : null,
            'category' => $product->category?->only(['id', 'name', 'type']), 'subcategory' => $product->subcategory?->only(['id', 'name_ar', 'name_en']),
            'status' => $product->status, 'is_active' => (bool) $product->is_active, 'price' => (float) $product->price,
            'quantity' => $product->quantity, 'minimum_order_quantity' => $product->minimum_order_quantity, 'units_sold' => (int) $product->units_sold,
            'completed_sales_amount' => (float) $product->completed_sales_amount, 'order_count' => (int) $product->completed_order_count,
            'returns_count' => (int) $product->returns_count, 'last_sold_at' => $product->last_sold_at,
            'average_rating' => $product->reviews_avg_rating ? round((float) $product->reviews_avg_rating, 1) : null, 'favourite_count' => (int) $product->favourited_by_count,
        ];
    }

    /**
     * Full order detail for the vendor-360 orders list. Admin and syndicate
     * viewers both see the complete order - what was sold, to whom, the
     * delivery address, payment, coupon, and full status history - since
     * this is an operational drill-down, not a public-facing summary. Only
     * the domain-gated financial totals stay scoped, to preserve the
     * cross-syndicate revenue-leak protection already in place above.
     */
    private function presentOrder(Order $order, ?string $domain): array
    {
        $scopedSubtotal = round((float) $order->items->sum('line_total'), 2);
        $scopedDiscount = round((float) $order->items->sum('discount_amount'), 2);
        $pure = $domain === null || $order->items->count() === (int) $order->items_count;
        $completedAt = $order->statusHistories->where('new_status', Order::STATUS_COMPLETED)->sortByDesc('created_at')->first()?->created_at;

        return [
            'id' => $order->id, 'order_number' => $order->order_number,
            'customer' => $order->user?->only(['id', 'name', 'email', 'phone_number']),
            'shipping_address' => $order->shippingAddress(),
            'item_count' => $order->items->sum('quantity'),
            'products' => $order->items->map(fn ($item) => [
                'id' => $item->product_id, 'name' => $item->product_name, 'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price, 'original_unit_price' => (float) $item->original_unit_price,
                'has_discount' => (bool) $item->has_discount, 'discount_amount' => (float) $item->discount_amount,
                'line_total' => (float) $item->line_total,
            ])->values(),
            'subtotal' => $pure ? (float) $order->subtotal_amount : $scopedSubtotal, 'discount' => $pure ? (float) $order->coupon_discount_amount : $scopedDiscount,
            'shipping' => $pure ? (float) $order->shipping_total : null, 'tax' => $pure ? (float) $order->tax_total : null,
            'grand_total' => $pure ? (float) $order->grand_total : null, 'scoped_sales' => $scopedSubtotal, 'financial_attribution_complete' => $pure,
            'coupon' => $order->coupon_code ? [
                'code' => $order->coupon_code, 'type' => $order->coupon_type, 'value' => (float) $order->coupon_value,
            ] : null,
            'payment' => $order->payment ? [
                'provider' => $order->payment->provider, 'method' => $order->payment->method, 'status' => $order->payment->status,
                'amount' => (float) $order->payment->amount, 'refunded_amount' => (float) $order->payment->refunded_amount,
                'provider_reference' => $order->payment->provider_reference, 'paid_at' => $order->payment->paid_at,
                'failed_at' => $order->payment->failed_at, 'failure_reason' => $order->payment->failure_reason,
            ] : null,
            'status' => $order->status,
            'status_history' => $order->statusHistories->map(fn ($history) => [
                'from' => $history->previous_status, 'to' => $history->new_status,
                'changed_by' => $history->changedBy?->name, 'reason' => $history->reason, 'notes' => $history->notes,
                'created_at' => $history->created_at,
            ])->values(),
            'cancellation' => $order->status === Order::STATUS_CANCELLED ? [
                'reason' => $order->cancellation_reason, 'notes' => $order->cancellation_notes,
                'cancelled_by' => $order->cancelledBy?->name, 'cancelled_at' => $order->cancelled_at,
            ] : null,
            'created_at' => $order->created_at, 'completed_at' => $completedAt,
        ];
    }

    private function trend(Vendor $vendor, array $period, ?string $domain): Collection
    {
        return $this->lines($vendor, $period, $domain)->where('orders.status', Order::STATUS_COMPLETED)
            ->selectRaw('DATE(orders.created_at) date, SUM(order_items.line_total) sales, COUNT(DISTINCT orders.id) orders')
            ->groupByRaw('DATE(orders.created_at)')->orderBy('date')->get()
            ->map(fn ($row) => ['date' => $row->date, 'sales' => round((float) $row->sales, 2), 'orders' => (int) $row->orders]);
    }

    private function topProducts(Vendor $vendor, array $period, ?string $domain): Collection
    {
        return $this->lines($vendor, $period, $domain)->where('orders.status', Order::STATUS_COMPLETED)
            ->selectRaw('products.id, products.name_ar, products.name_en, SUM(order_items.quantity) units_sold, SUM(order_items.line_total) sales')
            ->groupBy('products.id', 'products.name_ar', 'products.name_en')->orderByDesc('sales')->limit(8)->get()
            ->map(fn ($row) => ['id' => $row->id, 'name' => app()->getLocale() === 'ar' ? ($row->name_ar ?: $row->name_en) : ($row->name_en ?: $row->name_ar), 'units_sold' => (int) $row->units_sold, 'sales' => round((float) $row->sales, 2)]);
    }

    private function categoryPerformance(Vendor $vendor, array $period, ?string $domain): Collection
    {
        return $this->lines($vendor, $period, $domain)->where('orders.status', Order::STATUS_COMPLETED)
            ->selectRaw('categories.id, categories.name, categories.type, SUM(order_items.quantity) units_sold, SUM(order_items.line_total) sales')
            ->groupBy('categories.id', 'categories.name', 'categories.type')->orderByDesc('sales')->get()
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name, 'type' => $row->type, 'units_sold' => (int) $row->units_sold, 'sales' => round((float) $row->sales, 2)]);
    }

    private function periodRefunds(Vendor $vendor, array $period): array
    {
        $query = VendorLedgerEntry::query()->where('vendor_id', $vendor->id)->where('type', VendorLedgerEntry::TYPE_REFUND)
            ->whereHas('order', fn (Builder $order) => $this->period($order, $period, 'orders.created_at'));

        return ['amount' => round((float) $query->sum('amount'), 2), 'complete' => true];
    }

    private function domainRefunds(Vendor $vendor, array $period, string $domain): array
    {
        if ($this->mixedDomainOrders($vendor, $period, $domain) > 0) {
            return ['amount' => null, 'complete' => false];
        }
        $query = VendorLedgerEntry::query()->where('vendor_id', $vendor->id)->where('type', VendorLedgerEntry::TYPE_REFUND)
            ->whereHas('order', fn (Builder $order) => $this->period($order, $period, 'orders.created_at'))
            ->whereHas('order.items.product.category', fn (Builder $category) => $category->where('type', $domain));

        return ['amount' => round((float) $query->sum('amount'), 2), 'complete' => true];
    }

    private function domainFinance(Vendor $vendor, array $period, string $domain): array
    {
        $mixed = $this->mixedDomainOrders($vendor, $period, $domain);
        if ($mixed) {
            return ['net_earnings' => null, 'commission' => null, 'refunds' => null, 'attribution_complete' => false, 'mixed_domain_orders' => $mixed];
        }
        $orderIds = $this->period($this->orderQuery($vendor, $domain)->where('status', Order::STATUS_COMPLETED), $period, 'orders.created_at')->pluck('id');
        $totals = VendorLedgerEntry::query()->where('vendor_id', $vendor->id)->whereIn('order_id', $orderIds)->selectRaw('type, SUM(amount) total')->groupBy('type')->pluck('total', 'type');
        $gross = (float) ($totals[VendorLedgerEntry::TYPE_SALE] ?? 0);
        $commission = (float) ($totals[VendorLedgerEntry::TYPE_COMMISSION] ?? 0);
        $refunds = (float) ($totals[VendorLedgerEntry::TYPE_REFUND] ?? 0);

        return ['net_earnings' => round($gross - $commission - $refunds, 2), 'commission' => round($commission, 2), 'refunds' => round($refunds, 2), 'attribution_complete' => true, 'mixed_domain_orders' => 0];
    }

    private function mixedDomainOrders(Vendor $vendor, array $period, string $domain): int
    {
        return $this->period($this->orderQuery($vendor, $domain)->where('status', Order::STATUS_COMPLETED), $period, 'orders.created_at')
            ->whereHas('items.product.category', fn (Builder $category) => $category->where('type', '!=', $domain))->count();
    }

    private function presentPeriod(array $period): array
    {
        return ['key' => $period['key'], 'from' => $period['from']?->toDateString(), 'to' => $period['to']?->toDateString()];
    }
}
