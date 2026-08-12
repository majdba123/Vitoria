<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Commerce\CartException;
use App\Services\Commerce\OrderCancellationService;
use App\Services\Commerce\OrderStatusService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected OrderStatusService $orderStatusService,
        protected OrderCancellationService $cancellationService,
    ) {}

    /**
     * List vendor orders with filters scoped to vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()?->managedVendor();
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
        $vendor = $request->user()?->managedVendor();
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
                'payment',
                'returns',
            ])
            ->where('vendor_id', $vendor->id)
            ->findOrFail($orderId);

        return response()->json([
            'message' => 'Order retrieved successfully.',
            'data' => $order,
        ]);
    }

    /**
     * Move a vendor order forward through fulfilment (spec §8).
     *
     * The vendor may only submit a status the state machine allows from the
     * order's current status, and only one their actor type permits. An
     * arbitrary status in the request body is rejected by OrderStatusService.
     */
    public function updateStatus(Request $request, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(Order::TRANSITIONS))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::query()->findOrFail($orderId);
        $this->authorize('updateStatus', $order);

        try {
            $order = $this->orderStatusService->transition(
                $order,
                $validated['status'],
                $request->user(),
                'vendor',
                notes: $validated['notes'] ?? null,
            );
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('orders.transition_success'),
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
                'status_name' => __("orders.status.{$order->status}"),
            ],
        ]);
    }

    /**
     * Cancel a vendor order.
     *
     * Delegates to OrderCancellationService so vendor cancellations use the
     * same exactly-once stock restoration as customer cancellations. The
     * previous implementation duplicated the restore loop and carried the same
     * double-restore race (audit R1).
     */
    public function cancel(Request $request, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', Rule::in(Order::CANCEL_REASONS)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::query()->findOrFail($orderId);
        $this->authorize('cancel', $order);

        try {
            $order = $this->cancellationService->cancel(
                $order,
                $request->user(),
                'vendor',
                $validated['reason'] ?? 'vendor_issue',
                $validated['notes'] ?? null,
            );
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('orders.cancelled_success'),
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
                'cancelled_at' => $order->cancelled_at,
            ],
        ]);
    }
}
