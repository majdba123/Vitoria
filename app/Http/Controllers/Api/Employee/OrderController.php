<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only order visibility for employees whose assigned role grants
 * `orders.view` (stakeholder review #24 — employee capability now depends on
 * their assigned role, not blanket employee type). An employee without that
 * permission gets a 403, same as a vendor-staff member lacking a permission.
 */
class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeOrdersView($request);

        $query = Order::query()
            ->with([
                'user:id,name',
                'vendor:id,store_name',
                'items:id,order_id,product_id,product_name,quantity,line_total',
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $orders = $query->paginate(15);

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, int $orderId): JsonResponse
    {
        $this->authorizeOrdersView($request);

        $order = Order::query()
            ->with([
                'user:id,name',
                'vendor:id,store_name',
                'items:id,order_id,product_id,product_name,original_unit_price,unit_price,quantity,line_total',
            ])
            ->findOrFail($orderId);

        return response()->json([
            'message' => 'Order retrieved successfully.',
            'data' => $order,
        ]);
    }

    private function authorizeOrdersView(Request $request): void
    {
        $user = $request->user();

        if (! $user || ! $user->hasEmployeePermission('orders.view')) {
            abort(403, __('You are not allowed to view orders.'));
        }
    }
}
