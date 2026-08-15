<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicVendorResource;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;

class VendorController extends Controller
{
    /**
     * Public vendor directory listing is disabled — storefront is marketplace-only.
     * Individual vendor storefronts (show) are public; browsing all vendors is not.
     */
    public function index(): never
    {
        abort(404, __('Not found.'));
    }

    /**
     * Public vendor storefront: name, logo, description, city — never the
     * vendor's contact details, documents, or payout figures.
     */
    public function show(Vendor $vendor): JsonResponse
    {
        if (! $vendor->is_active || $vendor->status !== Vendor::STATUS_ACTIVE) {
            abort(404, __('Not found.'));
        }

        $vendor->loadMissing('city');

        return response()->json([
            'data' => new PublicVendorResource($vendor),
        ]);
    }
}
