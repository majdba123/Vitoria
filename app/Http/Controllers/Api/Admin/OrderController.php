<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Commerce\CartException;
use App\Services\Commerce\OrderStatusService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected OrderStatusService $orderStatusService,
    ) {}

    /**
     * List orders with admin filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->with([
                'user:id,name,email',
                'vendor:id,store_name',
                'items:id,order_id,product_id,product_name,quantity,line_total',
                'items.product:id,category_id',
                'items.product.category:id,name',
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->input('vendor_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');
            $query->whereHas('items.product', function ($builder) use ($request) {
                $builder->where('category_id', (int) $request->input('category_id'));
            });
        }

        if ($request->filled('product_id')) {
            $query->whereHas('items', function ($builder) use ($request) {
                $builder->where('product_id', (int) $request->input('product_id'));
            });
        }

        if ($request->filled('product')) {
            $term = trim((string) $request->input('product'));
            $query->whereHas('items', function ($builder) use ($term) {
                $builder->where('product_name', 'like', "%{$term}%");
            });
        }

        $orders = $query->paginate(12);

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

    /**
     * Show a single order for admin.
     */
    public function show(int $orderId): JsonResponse
    {
        $order = Order::query()
            ->with([
                'user:id,name,email',
                'vendor:id,store_name',
                'items:id,order_id,product_id,product_name,original_unit_price,has_discount,applied_discount_percentage,unit_price,quantity,line_total,discount_amount',
                'items.product:id,category_id',
                'items.product.category:id,name',
                'payment',
                'returns',
            ])
            ->findOrFail($orderId);

        return response()->json([
            'message' => 'Order retrieved successfully.',
            'data' => $order,
        ]);
    }

    /**
     * Mark an order as completed (admin only).
     *
     * Routed through OrderStatusService rather than writing `status` directly:
     * that is what enforces the state machine (an order must actually reach
     * `out_for_delivery` first), writes the audit history row, and — as of
     * Phase C — settles the order's COD payment (spec §8, §11).
     */
    public function markCompleted(Request $request, int $orderId): JsonResponse
    {
        $order = Order::query()->findOrFail($orderId);

        if ($order->status === Order::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Order is already completed.',
            ]);
        }

        try {
            $order = $this->orderStatusService->transition(
                $order,
                Order::STATUS_COMPLETED,
                $request->user(),
                'admin',
            );
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Order marked as completed successfully.',
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
            ],
        ]);
    }
}
