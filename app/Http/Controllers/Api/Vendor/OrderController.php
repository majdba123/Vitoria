<?php

namespace App\Http\Controllers\Api\Vendor;

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
     * List vendor orders with filters scoped to vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()?->vendor;
        if (! $vendor) {
            abort(403, 'Vendor profile not found.');
        }

        $query = Order::query()
            ->with([
                'user:id,name,email',
                'vendor:id,store_name',
                'items:id,order_id,product_id,product_name,quantity,line_total',
                'items.product:id,category_id',
                'items.product.category:id,name',
            ])
            ->where('vendor_id', $vendor->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
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
     * Show single vendor order.
     */
    public function show(Request $request, int $orderId): JsonResponse
    {
        $vendor = $request->user()?->vendor;
        if (! $vendor) {
            abort(403, 'Vendor profile not found.');
        }

        $order = Order::query()
            ->with([
                'user:id,name,email',
                'vendor:id,store_name',
                'items:id,order_id,product_id,product_name,original_unit_price,has_discount,applied_discount_percentage,unit_price,quantity,line_total,discount_amount',
                'items.product:id,category_id',
                'items.product.category:id,name',
            ])
            ->where('vendor_id', $vendor->id)
            ->findOrFail($orderId);

        return response()->json([
            'message' => 'Order retrieved successfully.',
            'data' => $order,
        ]);
    }

    /**
     * Cancel a vendor order.
     */
    public function cancel(Request $request, int $orderId): JsonResponse
    {
        $vendor = $request->user()?->vendor;
        if (! $vendor) {
            abort(403, 'Vendor profile not found.');
        }

        $order = Order::query()
            ->where('vendor_id', $vendor->id)
            ->findOrFail($orderId);

        if ($order->status === Order::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Completed orders cannot be cancelled.',
            ], 422);
        }

        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json([
                'message' => 'Order is already cancelled.',
            ]);
        }

        $order->update([
            'status' => Order::STATUS_CANCELLED,
        ]);

        $this->restoreOrderQuantities($order);
        try {
            Cache::tags(['products'])->flush();
        } catch (\Exception $e) {
            // Silently fail if cache driver doesn't support tags
        }

        $this->notificationService->notifyOrderStatusUpdated($order, Order::STATUS_CANCELLED);

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
            ],
        ]);
    }

    private function restoreOrderQuantities(Order $order): void
    {
        $order->load('items');
        foreach ($order->items as $item) {
            \App\Models\Product::query()
                ->where('id', $item->product_id)
                ->increment('quantity', $item->quantity);
        }
    }
}
