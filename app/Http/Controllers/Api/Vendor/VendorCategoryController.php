<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorCategoryController extends Controller
{
    /**
     * Return categories the authenticated vendor is allowed to sell in.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->managedVendor();

        if (! $vendor) {
            return response()->json([
                'message' => __('Vendor profile not found.'),
                'data' => [],
            ], 404);
        }

        $categories = Category::query()
            ->compatibleWithVendor($vendor)
            ->with('subcategories')
            ->when($request->filled('type'), fn ($query) => $query->where('type', (string) $request->string('type')))
            ->get();

        return response()->json([
            'message' => __('Allowed categories retrieved successfully.'),
            'data' => $categories,
        ]);
    }
}
