<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\Commerce\CartException;
use App\Services\Commerce\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor shipment management (spec §14). Happy-path status is driven by
 * order fulfilment (ShippingService::syncFromOrderStatus); this controller
 * covers the delivery-specific actions that are not order actions —
 * recording a tracking number and reporting a failed or returned delivery.
 */
class ShipmentController extends Controller
{
    public function __construct(
        private readonly ShippingService $shippingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()?->managedVendor();
        if (! $vendor) {
            abort(403, __('api.vendor_profile_not_found'));
        }

        $query = Shipment::query()
            ->with(['order:id,order_number,vendor_id', 'method', 'zone'])
            ->whereHas('order', fn ($builder) => $builder->where('vendor_id', $vendor->id))
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $shipments = $query->paginate(12);

        return response()->json([
            'message' => __('api.shipments_retrieved'),
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
        $this->authorize('view', $shipment);

        return response()->json(['message' => __('api.shipment_retrieved'), 'data' => $shipment]);
    }

    public function updateTracking(Request $request, int $shipmentId): JsonResponse
    {
        $validated = $request->validate([
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'carrier_name' => ['nullable', 'string', 'max:100'],
        ]);

        $shipment = Shipment::query()->findOrFail($shipmentId);
        $this->authorize('manage', $shipment);

        $shipment = $this->shippingService->setTracking(
            $shipment,
            $validated['tracking_number'] ?? null,
            $validated['carrier_name'] ?? null,
        );

        return response()->json([
            'message' => __('api.tracking_updated'),
            'data' => ['id' => $shipment->id, 'tracking_number' => $shipment->tracking_number, 'carrier_name' => $shipment->carrier_name],
        ]);
    }

    public function markFailed(Request $request, int $shipmentId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $shipment = Shipment::query()->findOrFail($shipmentId);
        $this->authorize('manage', $shipment);

        try {
            $shipment = $this->shippingService->markFailed($shipment, $validated['reason'], $request->user(), 'vendor');
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
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $shipment = Shipment::query()->findOrFail($shipmentId);
        $this->authorize('manage', $shipment);

        try {
            $shipment = $this->shippingService->markReturned($shipment, $request->user(), 'vendor', $validated['notes'] ?? null);
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('shipping.transition_success'),
            'data' => ['id' => $shipment->id, 'status' => $shipment->status],
        ]);
    }
}
