<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Services\Commerce\CartException;
use App\Services\Commerce\CartService;
use App\Services\Commerce\CheckoutService;
use App\Services\Commerce\CouponService;
use App\Services\Commerce\OrderCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
        protected CouponService $couponService,
        protected OrderCancellationService $cancellationService,
    ) {}

    /**
     * Paginated authenticated user order history.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $status = strtolower((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $query = Order::query()
            ->with([
                'items:id,order_id,product_id,product_name,original_unit_price,has_discount,applied_discount_percentage,unit_price,quantity,line_total,discount_amount',
                'vendor:id,store_name,logo',
            ])
            ->where('user_id', $userId);

        if ($status !== '' && array_key_exists($status, Order::TRANSITIONS)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($items) => $items->where('product_name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(6);

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'data' => $orders->getCollection()->map(fn (Order $order) => $this->presentOrder($order))->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Show a single order with items, delivery snapshot and status timeline.
     */
    public function show(Request $request, int $orderId): JsonResponse
    {
        $order = Order::query()
            ->with([
                'items:id,order_id,product_id,product_name,original_unit_price,has_discount,applied_discount_percentage,unit_price,quantity,line_total,discount_amount',
                'items.product:id,category_id',
                'items.product.category:id,name',
                'statusHistories',
                'payment',
                'returns',
                'shipment.method',
                'shipment.events',
                'vendor:id,store_name,logo',
            ])
            ->findOrFail($orderId);

        $this->authorize('view', $order);

        return response()->json([
            'message' => 'Order retrieved successfully.',
            'data' => array_merge($this->presentOrder($order), [
                'shipping_address' => $order->shippingAddress(),
                'payment' => $order->payment ? [
                    'status' => $order->payment->status,
                    'status_name' => __("payments.status.{$order->payment->status}"),
                    'method' => $order->payment->method,
                    'amount' => $order->payment->amount,
                    'refunded_amount' => $order->payment->refunded_amount,
                    'paid_at' => $order->payment->paid_at,
                ] : null,
                'shipment' => $order->shipment ? [
                    'status' => $order->shipment->status,
                    'status_name' => __("shipping.status.{$order->shipment->status}"),
                    'method_name' => $order->shipment->method ? __("shipping.method.{$order->shipment->method->code}") : null,
                    'tracking_number' => $order->shipment->tracking_number,
                    'carrier_name' => $order->shipment->carrier_name,
                    'shipped_at' => $order->shipment->shipped_at,
                    'delivered_at' => $order->shipment->delivered_at,
                    'events' => $order->shipment->events->map(fn (\App\Models\ShipmentEvent $event) => [
                        'previous_status' => $event->previous_status,
                        'new_status' => $event->new_status,
                        'status_name' => __("shipping.status.{$event->new_status}"),
                        'notes' => $event->notes,
                        'created_at' => $event->created_at,
                    ])->values(),
                ] : null,
                'returns' => $order->returns->map(fn (\App\Models\OrderReturn $return) => [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'status' => $return->status,
                    'status_name' => __("returns.status.{$return->status}"),
                    'requested_at' => $return->requested_at,
                ])->values(),
                'timeline' => $order->statusHistories->map(fn (OrderStatusHistory $entry) => [
                    'previous_status' => $entry->previous_status,
                    'new_status' => $entry->new_status,
                    'status_name' => __("orders.status.{$entry->new_status}"),
                    'actor_type' => $entry->actor_type,
                    'reason' => $entry->reason,
                    'reason_name' => $entry->reason && $entry->reason !== 'order_placed'
                        ? __("orders.cancel_reason.{$entry->reason}")
                        : null,
                    'notes' => $entry->notes,
                    'created_at' => $entry->created_at,
                ])->values(),
                'cancellation' => $order->cancelled_at ? [
                    'cancelled_at' => $order->cancelled_at,
                    'reason' => $order->cancellation_reason,
                    'reason_name' => __("orders.cancel_reason.{$order->cancellation_reason}"),
                    'notes' => $order->cancellation_notes,
                ] : null,
            ]),
        ]);
    }

    /**
     * Legacy checkout accepting a client-supplied `items[]` array.
     *
     * DEPRECATED in favour of POST /api/checkout, which uses the server cart
     * and requires a delivery address. Retained because shipped mobile clients
     * still call it. It writes the supplied items into the caller's server cart
     * and delegates to CheckoutService, so there remains exactly one code path
     * that decrements product quantity (decision D1).
     *
     * Orders created through this endpoint carry no address snapshot.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $cart = $this->cartService->resolve($request);

        try {
            $this->cartService->clear($cart);

            foreach ($request->validated('items') as $item) {
                $cart = $this->cartService->add($cart, (int) $item['product_id'], (int) $item['quantity']);
            }

            $couponCode = trim((string) $request->validated('coupon_code', ''));

            if ($couponCode !== '') {
                $summary = $this->cartService->summarize($cart);
                $coupon = $this->couponService->resolveUsable($couponCode, $user, (float) $summary['subtotal']);

                if (! $coupon) {
                    return response()->json(['message' => __('cart.coupon_invalid')], 422);
                }

                $cart->forceFill(['coupon_code' => $coupon->code])->save();
                $cart = $cart->fresh();
            }

            $orders = $this->checkoutService->place($cart, $user, null, 'cash');
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            Log::error('Checkout transaction failed.', [
                'user_id' => $user->id,
                'exception' => $exception,
            ]);

            return response()->json(['message' => __('cart.checkout_failed')], 500);
        }

        return $this->checkoutResponse($orders);
    }

    /**
     * Cancel an order.
     *
     * Stock restoration is exactly-once even if this is called twice
     * concurrently — see OrderCancellationService (audit R1).
     */
    public function cancel(Request $request, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'in:'.implode(',', Order::CANCEL_REASONS)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = Order::query()->findOrFail($orderId);
        $this->authorize('cancel', $order);

        $user = $request->user();
        $actorType = match (true) {
            $user->isAdmin() => 'admin',
            $user->isVendor() => 'vendor',
            default => 'customer',
        };

        try {
            $order = $this->cancellationService->cancel(
                $order,
                $user,
                $actorType,
                $validated['reason'] ?? 'customer_changed_mind',
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

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function checkoutResponse(Collection $orders): JsonResponse
    {
        $count = $orders->count();

        return response()->json([
            'message' => $count > 1
                ? __('orders.placed_success_multi', ['count' => $count])
                : __('orders.placed_success'),
            'data' => [
                'orders_count' => $count,
                'orders' => $orders->map(fn (Order $order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'items_count' => $order->items_count,
                    'payment_way' => $order->payment_way,
                    'subtotal_amount' => $order->subtotal_amount,
                    'coupon_discount_amount' => $order->coupon_discount_amount,
                    'shipping_total' => $order->shipping_total,
                    'tax_total' => $order->tax_total,
                    'grand_total' => $order->grand_total,
                    'total_amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'status' => $order->status,
                    'coupon' => $order->coupon_code ? [
                        'code' => $order->coupon_code,
                        'type' => $order->coupon_type,
                        'value' => $order->coupon_value,
                    ] : null,
                ])->values(),
            ],
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_name' => __("orders.status.{$order->status}"),
            'payment_way' => $order->payment_way,
            'items_count' => $order->items_count,
            'subtotal_amount' => $order->subtotal_amount,
            'coupon_discount_amount' => $order->coupon_discount_amount,
            'shipping_total' => $order->shipping_total,
            'tax_total' => $order->tax_total,
            'grand_total' => $order->grand_total,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'created_at' => $order->created_at,
            'vendor' => $order->relationLoaded('vendor') && $order->vendor ? [
                'id' => $order->vendor->id,
                'store_name' => $order->vendor->store_name,
                'logo_url' => $order->vendor->logo ? asset('storage/'.$order->vendor->logo) : null,
            ] : null,
            'coupon' => $order->coupon_code ? [
                'code' => $order->coupon_code,
                'type' => $order->coupon_type,
                'value' => $order->coupon_value,
                'discount_amount' => $order->coupon_discount_amount,
            ] : null,
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'original_unit_price' => $item->original_unit_price,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
                'has_discount' => $item->has_discount,
                'applied_discount_percentage' => $item->applied_discount_percentage,
                'discount_amount' => $item->discount_amount,
                'category_name' => $item->relationLoaded('product') ? $item->product?->category?->name : null,
            ])->values(),
        ];
    }
}
