<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorRequest;
use App\Http\Requests\Admin\UpdateVendorRequest;
use App\Http\Resources\Admin\VendorResource;
use App\Models\Vendor;
use App\Services\Admin\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorController extends Controller
{
    public function __construct(public VendorService $vendorService) {}

    /**
     * List all vendors.
     */
    public function index(Request $request): JsonResponse
    {
        $vendors = Vendor::query()
            ->with(['user', 'categories', 'city'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', (string) $request->string('status'));
            })
            ->when($request->filled('name'), function ($query) use ($request) {
                $name = (string) $request->string('name');
                $query->where(function ($builder) use ($name) {
                    $builder->where('store_name', 'like', "%{$name}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$name}%"));
                });
            })
            ->when($request->filled('email'), function ($query) use ($request) {
                $email = (string) $request->string('email');
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$email}%"));
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->where('categories.id', (int) $request->input('category_id')));
            })
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
        $vendor->load(['user', 'categories', 'city']);

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
     * Approve a pending vendor.
     */
    public function approve(Vendor $vendor): JsonResponse
    {
        $vendor = $this->vendorService->approve($vendor);

        return response()->json([
            'message' => __('Vendor approved successfully.'),
            'data' => new VendorResource($vendor),
        ]);
    }

    /**
     * Download a self-registered vendor's commercial register document.
     */
    public function downloadCommercialRegister(Vendor $vendor): StreamedResponse|JsonResponse
    {
        if (! $vendor->commercial_register_file || ! Storage::disk('local')->exists($vendor->commercial_register_file)) {
            return response()->json([
                'message' => __('Commercial registration document was not found.'),
            ], 404);
        }

        return Storage::disk('local')->download($vendor->commercial_register_file);
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
