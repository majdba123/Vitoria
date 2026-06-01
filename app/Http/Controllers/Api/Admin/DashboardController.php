<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
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
