<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Commerce\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public shipping quotes for the checkout screen (spec §14).
 */
class ShippingController extends Controller
{
    public function __construct(
        private readonly ShippingService $shippingService,
    ) {}

    public function methods(Request $request): JsonResponse
    {
        $governorate = $request->query('governorate') ? (string) $request->query('governorate') : null;
        $subtotal = (float) $request->query('subtotal', 0);

        $quotes = $this->shippingService->availableMethods($governorate, $subtotal);

        return response()->json([
            'message' => __('api.shipping_methods_retrieved'),
            'data' => collect($quotes)->map(fn (array $quote) => [
                'code' => $quote['method']->code,
                'name' => __("shipping.method.{$quote['method']->code}"),
                'amount' => $quote['amount'],
            ])->values(),
        ]);
    }
}
