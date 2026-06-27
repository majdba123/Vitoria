<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
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
            ])
            ->findOrFail($orderId);

        return response()->json([
            'message' => 'Order retrieved successfully.',
            'data' => $order,
        ]);
    }

    /**
     * Mark an order as completed (admin only).
     */
    public function markCompleted(int $orderId): JsonResponse
    {
        $order = Order::query()->findOrFail($orderId);

        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json([
                'message' => 'Cancelled orders cannot be marked as completed.',
            ], 422);
        }

        if ($order->status === Order::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Order is already completed.',
            ]);
        }

        $order->update([
            'status' => Order::STATUS_COMPLETED,
        ]);

        try {
            Cache::tags(['products'])->flush();
        } catch (\Exception $e) {
            // Silently fail if cache driver doesn't support tags
        }

        $this->notificationService->notifyOrderStatusUpdated($order, Order::STATUS_COMPLETED);

        return response()->json([
            'message' => 'Order marked as completed successfully.',
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
            ],
        ]);
    }
}
