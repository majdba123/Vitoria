<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorCategoryController extends Controller
{
    /**
     * Return categories the authenticated vendor is allowed to sell in.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = Vendor::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $vendor) {
            return response()->json([
                'message' => __('Vendor profile not found.'),
                'data' => [],
            ], 404);
        }

        $categories = $vendor->categories()
            ->with('subcategories')
            ->get();

        return response()->json([
            'message' => __('Allowed categories retrieved successfully.'),
            'data' => $categories,
        ]);
    }
}
