<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePortRequest;
use App\Http\Requests\Admin\UpdatePortRequest;
use App\Models\Port;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ports (المنافذ). `index` is mounted for admins, employees and syndicates
 * (to fill the shipment form's select); writes are admin-only routes.
 */
class PortController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $includeInactive = $request->user()?->isAdmin() && $request->boolean('all');

        $ports = Port::query()
            ->when(! $includeInactive, fn ($query) => $query->active())
            ->withCount('importShipments')
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => __('import_shipments.ports_retrieved'),
            'data' => $ports,
        ]);
    }

    public function store(StorePortRequest $request): JsonResponse
    {
        $port = DB::transaction(fn () => Port::query()->create($request->validated()));

        return response()->json([
            'message' => __('import_shipments.port_created'),
            'data' => $port->loadCount('importShipments'),
        ], 201);
    }

    public function update(UpdatePortRequest $request, Port $port): JsonResponse
    {
        DB::transaction(fn () => $port->update($request->validated()));

        return response()->json([
            'message' => __('import_shipments.port_updated'),
            'data' => $port->fresh()->loadCount('importShipments'),
        ]);
    }

    public function destroy(Port $port): JsonResponse
    {
        if ($port->importShipments()->exists()) {
            return response()->json([
                'message' => __('import_shipments.port_in_use'),
            ], 422);
        }

        DB::transaction(fn () => $port->delete());

        return response()->json([
            'message' => __('import_shipments.port_deleted'),
        ]);
    }
}
