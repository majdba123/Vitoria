<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\Commerce\CartException;
use App\Services\Commerce\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShippingService $shippingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Shipment::query()
            ->with(['order:id,order_number,vendor_id', 'order.vendor:id,store_name', 'method', 'zone'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $shipments = $query->paginate(12);

        return response()->json([
            'message' => 'Shipments retrieved successfully.',
            'data' => $shipments->items(),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'per_page' => $shipments->perPage(),
                'total' => $shipments->total(),
            ],
        ]);
    }

    public function show(int $shipmentId): JsonResponse
    {
        $shipment = Shipment::query()->with(['order:id,order_number,vendor_id', 'method', 'zone', 'events'])->findOrFail($shipmentId);

        return response()->json(['message' => 'Shipment retrieved successfully.', 'data' => $shipment]);
    }

    public function markFailed(Request $request, int $shipmentId): JsonResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $shipment = Shipment::query()->findOrFail($shipmentId);

        try {
            $shipment = $this->shippingService->markFailed($shipment, $validated['reason'], $request->user(), 'admin');
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('shipping.transition_success'),
            'data' => ['id' => $shipment->id, 'status' => $shipment->status],
        ]);
    }

    public function markReturned(Request $request, int $shipmentId): JsonResponse
    {
        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:500']]);
        $shipment = Shipment::query()->findOrFail($shipmentId);

        try {
            $shipment = $this->shippingService->markReturned($shipment, $request->user(), 'admin', $validated['notes'] ?? null);
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('shipping.transition_success'),
            'data' => ['id' => $shipment->id, 'status' => $shipment->status],
        ]);
    }
}
