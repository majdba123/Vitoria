<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Syndicate;
use App\Models\Vendor;
use App\Services\ApplicationCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(protected ApplicationCacheService $cacheService) {}

    /**
     * Vendor and category overview statistics for the admin dashboard.
     */
    public function overview(): JsonResponse
    {
        $data = $this->cacheService->remember(ApplicationCacheService::DASHBOARD_ADMIN_STATS, 300, function (): array {
            $totalVendors = Vendor::query()->count();
            $totalProducts = Product::query()->count();
            $totalCategories = Category::query()->count();
            $totalSyndicates = Syndicate::query()->count();

            $vendorsByType = collect(Vendor::businessTypeLabels())
                ->map(fn (string $label, string $type) => [
                    'type' => $type,
                    'label' => $label,
                    'total' => Vendor::query()->where('business_type', $type)->count(),
                ])
                ->values();

            $categoriesByType = collect(Category::typeLabels())
                ->map(fn (string $label, string $type) => [
                    'type' => $type,
                    'label' => $label,
                    'total' => Category::query()->where('type', $type)->count(),
                ])
                ->values();

            $categoryRows = Category::query()
                ->withCount([
                    'vendors',
                    'products',
                ])
                ->orderByDesc('vendors_count')
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'type_label' => Category::typeLabels()[$category->type] ?? $category->type,
                    'vendors_count' => (int) $category->vendors_count,
                    'products_count' => (int) $category->products_count,
                ])
                ->values();

            $productsByCategoryType = collect(Category::typeLabels())
                ->map(fn (string $label, string $type) => [
                    'type' => $type,
                    'label' => $label,
                    'total' => Product::query()
                        ->whereHas('category', fn ($query) => $query->where('type', $type))
                        ->count(),
                ])
                ->values();

            $topVendorsByProductCount = Vendor::query()
                ->with('user:id,name')
                ->withCount('products')
                ->orderByDesc('products_count')
                ->limit(5)
                ->get()
                ->map(fn (Vendor $vendor) => [
                    'id' => $vendor->id,
                    'store_name' => $vendor->store_name,
                    'business_type' => $vendor->business_type,
                    'business_type_label' => Vendor::businessTypeLabels()[$vendor->business_type] ?? $vendor->business_type,
                    'products_count' => (int) $vendor->products_count,
                    'user' => $vendor->user ? ['name' => $vendor->user->name] : null,
                ])
                ->values();

            $recentProducts = Product::query()
                ->with(['category:id,name,type', 'vendor:id,store_name'])
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $product->image ? asset('storage/'.$product->image) : null,
                    'status' => $product->status,
                    'is_active' => $product->is_active,
                    'created_at' => $product->created_at,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'type' => $product->category->type,
                    ] : null,
                    'vendor' => $product->vendor ? [
                        'id' => $product->vendor->id,
                        'store_name' => $product->vendor->store_name,
                    ] : null,
                ])
                ->values();

            $monthExpression = match (DB::connection()->getDriverName()) {
                'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m')",
                'pgsql' => "TO_CHAR(created_at, 'YYYY-MM')",
                default => "strftime('%Y-%m', created_at)",
            };

            $monthlyProductGrowth = Product::query()
                ->selectRaw("{$monthExpression} as month, count(*) as total")
                ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn ($row) => [
                    'month' => $row->month,
                    'total' => (int) $row->total,
                ])
                ->values();

            $recentVendors = Vendor::query()
                ->with(['user:id,name,email,phone_number', 'categories:id,name,type'])
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Vendor $vendor) => [
                    'id' => $vendor->id,
                    'store_name' => $vendor->store_name,
                    'business_type' => $vendor->business_type,
                    'business_type_label' => Vendor::businessTypeLabels()[$vendor->business_type] ?? $vendor->business_type,
                    'status' => $vendor->status,
                    'is_active' => $vendor->is_active,
                    'registration_source' => $vendor->registration_source,
                    'created_at' => $vendor->created_at,
                    'user' => $vendor->user ? [
                        'name' => $vendor->user->name,
                        'email' => $vendor->user->email,
                        'phone_number' => $vendor->user->phone_number,
                    ] : null,
                    'categories' => $vendor->categories->map(fn (Category $category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'type' => $category->type,
                    ])->values(),
                ])
                ->values();

            return [
                'total_categories' => $totalCategories,
                'total_vendors' => $totalVendors,
                'total_products' => $totalProducts,
                'total_syndicates' => $totalSyndicates,
                'active_syndicates' => Syndicate::query()->where('status', Syndicate::STATUS_ACTIVE)->count(),
                'inactive_syndicates' => Syndicate::query()->where('status', Syndicate::STATUS_INACTIVE)->count(),
                'syndicates_by_type' => collect(Category::typeLabels())->map(fn (string $label, string $type) => [
                    'type' => $type,
                    'label' => $label,
                    'total' => Syndicate::query()->where('type', $type)->count(),
                ])->values(),
                'recent_syndicate_agents' => Syndicate::query()
                    ->with('user:id,name,email')
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn (Syndicate $syndicate) => [
                        'id' => $syndicate->id,
                        'name' => $syndicate->name,
                        'type' => $syndicate->type,
                        'type_label' => Category::typeLabels()[$syndicate->type] ?? $syndicate->type,
                        'status' => $syndicate->status,
                        'user' => $syndicate->user ? [
                            'name' => $syndicate->user->name,
                            'email' => $syndicate->user->email,
                        ] : null,
                        'created_at' => $syndicate->created_at,
                    ])
                    ->values(),
                'active_products' => Product::query()->where('is_active', true)->count(),
                'inactive_products' => Product::query()->where('is_active', false)->count(),
                'vendors_by_type' => $vendorsByType,
                'categories_by_type' => $categoriesByType,
                'most_selected_categories' => $categoryRows->take(5)->values(),
                'categories_with_no_products' => $categoryRows->where('products_count', 0)->values(),
                'categories_with_no_vendors' => $categoryRows->where('vendors_count', 0)->values(),
                'vendors_per_category' => $categoryRows,
                'products_by_category' => $categoryRows->map(fn (array $row) => [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'type_label' => $row['type_label'],
                    'products_count' => $row['products_count'],
                ])->values(),
                'products_by_category_type' => $productsByCategoryType,
                'top_vendors_by_product_count' => $topVendorsByProductCount,
                'recent_vendor_registrations' => $recentVendors,
                'recent_products' => $recentProducts,
                'monthly_product_growth' => $monthlyProductGrowth,
                'type_stats' => [
                    'products_in_agriculture' => $productsByCategoryType->firstWhere('type', Category::TYPE_AGRICULTURE)['total'] ?? 0,
                    'products_in_veterinary' => $productsByCategoryType->firstWhere('type', Category::TYPE_VETERINARY)['total'] ?? 0,
                    'vendors_in_agriculture' => $vendorsByType->firstWhere('type', Vendor::BUSINESS_TYPE_AGRICULTURE)['total'] ?? 0,
                    'vendors_in_veterinary' => $vendorsByType->firstWhere('type', Vendor::BUSINESS_TYPE_VETERINARY)['total'] ?? 0,
                    'vendors_in_both' => $vendorsByType->firstWhere('type', Vendor::BUSINESS_TYPE_BOTH)['total'] ?? 0,
                ],
            ];
        }, ['dashboard']);

        return response()->json([
            'message' => 'Dashboard overview statistics retrieved successfully.',
            'data' => $data,
        ]);
    }

    /**
     * Vendor statistics grouped by assigned category.
     */
    public function vendorCategoryStats(): JsonResponse
    {
        $categories = Category::query()
            ->withCount([
                'vendors as total_vendors',
                'vendors as active_vendors' => fn ($query) => $query->where('vendors.is_active', true),
                'vendors as pending_vendors' => fn ($query) => $query->where('vendors.status', Vendor::STATUS_PENDING),
                'vendors as inactive_vendors' => fn ($query) => $query
                    ->where('vendors.is_active', false)
                    ->where('vendors.status', '!=', Vendor::STATUS_PENDING),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'type_label' => Category::typeLabels()[$category->type] ?? $category->type,
                'total_vendors' => (int) $category->total_vendors,
                'active_vendors' => (int) $category->active_vendors,
                'pending_vendors' => (int) $category->pending_vendors,
                'inactive_vendors' => (int) $category->inactive_vendors,
            ])
            ->values();

        $uncategorizedTotal = Vendor::query()->doesntHave('categories')->count();
        $uncategorizedActive = Vendor::query()->doesntHave('categories')->where('is_active', true)->count();
        $uncategorizedPending = Vendor::query()->doesntHave('categories')->where('status', Vendor::STATUS_PENDING)->count();
        $uncategorizedInactive = Vendor::query()
            ->doesntHave('categories')
            ->where('is_active', false)
            ->where('status', '!=', Vendor::STATUS_PENDING)
            ->count();

        if ($uncategorizedTotal > 0) {
            $categories->push([
                'id' => null,
                'name' => 'Not assigned',
                'total_vendors' => $uncategorizedTotal,
                'active_vendors' => $uncategorizedActive,
                'pending_vendors' => $uncategorizedPending,
                'inactive_vendors' => $uncategorizedInactive,
            ]);
        }

        return response()->json([
            'message' => 'Vendor category statistics retrieved successfully.',
            'data' => $categories,
        ]);
    }
}
