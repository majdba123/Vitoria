<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Vendor and category overview statistics for the admin dashboard.
     */
    public function overview(): JsonResponse
    {
        $totalVendors = Vendor::query()->count();

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
            ->withCount('vendors')
            ->orderByDesc('vendors_count')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'type_label' => Category::typeLabels()[$category->type] ?? $category->type,
                'vendors_count' => (int) $category->vendors_count,
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

        return response()->json([
            'message' => 'Dashboard overview statistics retrieved successfully.',
            'data' => [
                'total_vendors' => $totalVendors,
                'vendors_by_type' => $vendorsByType,
                'categories_by_type' => $categoriesByType,
                'most_selected_categories' => $categoryRows->take(5)->values(),
                'vendors_per_category' => $categoryRows,
                'recent_vendor_registrations' => $recentVendors,
            ],
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
