<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin configuration of shipping zones, methods and rates (spec §14).
 *
 * Zones and methods are seeded by the shipping migration; this only exposes
 * what an admin actually needs to turn the mechanism on — editing a rate.
 * Creating new zones/governorate mappings is not exposed yet: the single
 * catch-all zone covers every order today, and no business requirement for
 * per-governorate rates has been supplied (decision D12).
 */
class ShippingConfigController extends Controller
{
    public function zones(): JsonResponse
    {
        $zones = ShippingZone::query()
            ->with(['governorates', 'rates.method'])
            ->orderByDesc('is_default')
            ->get();

        return response()->json(['message' => __('api.shipping_zones_retrieved'), 'data' => $zones]);
    }

    public function methods(): JsonResponse
    {
        $methods = ShippingMethod::query()->orderBy('sort_order')->get();

        return response()->json(['message' => __('api.shipping_methods_retrieved'), 'data' => $methods]);
    }

    public function updateRate(Request $request, int $rateId): JsonResponse
    {
        $validated = $request->validate([
            'rate' => ['required', 'numeric', 'min:0'],
            'free_over_subtotal' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rate = ShippingRate::query()->findOrFail($rateId);
        $rate->update([
            'rate' => round((float) $validated['rate'], 2),
            'free_over_subtotal' => isset($validated['free_over_subtotal']) ? round((float) $validated['free_over_subtotal'], 2) : null,
            'is_active' => $validated['is_active'] ?? $rate->is_active,
        ]);

        return response()->json(['message' => __('api.shipping_rate_updated'), 'data' => $rate->refresh()]);
    }
}
