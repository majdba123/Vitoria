<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorRequest;
use App\Http\Requests\Admin\UpdateVendorRequest;
use App\Http\Resources\Admin\VendorResource;
use App\Models\Vendor;
use App\Services\Admin\VendorService;
use Illuminate\Http\JsonResponse;

class VendorController extends Controller
{
    public function __construct(public VendorService $vendorService) {}

    /**
     * List all vendors.
     */
    public function index(): JsonResponse
    {
        $vendors = Vendor::query()
            ->with(['user', 'categories'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'message' => __('Vendors retrieved successfully.'),
            'data' => VendorResource::collection($vendors),
            'meta' => [
                'current_page' => $vendors->currentPage(),
                'last_page' => $vendors->lastPage(),
                'per_page' => $vendors->perPage(),
                'total' => $vendors->total(),
            ],
        ]);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        $vendor->load(['user', 'categories']);

        return response()->json([
            'message' => __('Vendor retrieved successfully.'),
            'data' => new VendorResource($vendor),
        ]);
    }

    /**
     * Create a new vendor (user account + vendor profile).
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        $vendor = $this->vendorService->create($request->validated());
        $vendor->load(['user', 'categories']);

        return response()->json([
            'message' => __('Vendor created successfully.'),
            'data' => new VendorResource($vendor),
        ], 201);
    }

    /**
     * Update an existing vendor.
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): JsonResponse
    {
        $vendor = $this->vendorService->update($vendor, $request->validated());

        return response()->json([
            'message' => __('Vendor updated successfully.'),
            'data' => new VendorResource($vendor),
        ]);
    }

    /**
     * Toggle vendor active/inactive status.
     */
    public function toggleActive(Vendor $vendor): JsonResponse
    {
        $vendor = $this->vendorService->toggleActive($vendor);

        return response()->json([
            'message' => $vendor->is_active
                ? __('Vendor activated successfully.')
                : __('Vendor deactivated successfully.'),
            'data' => new VendorResource($vendor),
        ]);
    }

    /**
     * Delete a vendor and its user account.
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        $this->vendorService->delete($vendor);

        return response()->json([
            'message' => __('Vendor deleted successfully.'),
        ]);
    }
}
