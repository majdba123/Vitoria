<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    /**
     * Paginated authenticated user order history.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $status = strtolower((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));
        $allowedStatuses = [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED];

        $query = Order::query()
            ->with([
                'items:id,order_id,product_id,product_name,original_unit_price,has_discount,applied_discount_percentage,unit_price,quantity,line_total,discount_amount',
            ])
            ->where('user_id', $userId);

        if ($status !== '' && in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemsQuery) use ($search) {
                        $itemsQuery->where('product_name', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()->paginate(6);

        $data = $orders->getCollection()->map(function (Order $order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_way' => $order->payment_way,
                'items_count' => $order->items_count,
                'subtotal_amount' => $order->subtotal_amount,
                'coupon_discount_amount' => $order->coupon_discount_amount,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at,
                'coupon' => $order->coupon_code ? [
                    'code' => $order->coupon_code,
                    'type' => $order->coupon_type,
                    'value' => $order->coupon_value,
                    'discount_amount' => $order->coupon_discount_amount,
                ] : null,
                'items' => $order->items->map(function (OrderItem $item) {
                    return [
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
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'message' => 'Orders retrieved successfully.',
            'data' => $data,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Show a single user order with items details.
     */
    public function show(Request $request, int $orderId): JsonResponse
    {
        $order = Order::query()
            ->with([
                'items:id,order_id,product_id,product_name,original_unit_price,has_discount,applied_discount_percentage,unit_price,quantity,line_total,discount_amount',
                'items.product:id,subcategory_id',
                'items.product.subcategory:id,name,category_id',
                'items.product.subcategory.category:id,name',
            ])
            ->where('user_id', $request->user()->id)
            ->findOrFail($orderId);

        return response()->json([
            'message' => 'Order retrieved successfully.',
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_way' => $order->payment_way,
                'items_count' => $order->items_count,
                'subtotal_amount' => $order->subtotal_amount,
                'coupon_discount_amount' => $order->coupon_discount_amount,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at,
                'coupon' => $order->coupon_code ? [
                    'code' => $order->coupon_code,
                    'type' => $order->coupon_type,
                    'value' => $order->coupon_value,
                    'discount_amount' => $order->coupon_discount_amount,
                ] : null,
                'items' => $order->items->map(function (OrderItem $item) {
                    return [
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
                        'subcategory_name' => $item->product?->subcategory?->name,
                        'category_name' => $item->product?->subcategory?->category?->name,
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Create checkout orders split by vendor.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $paymentWay = 'cash';
        $couponCode = strtoupper(trim((string) $request->validated('coupon_code', '')));
        $coupon = $couponCode !== '' ? $this->resolveCoupon($couponCode) : null;

        if ($couponCode !== '' && ! $coupon) {
            return response()->json([
                'message' => 'Coupon is invalid or inactive.',
            ], 422);
        }

        $items = collect($request->validated('items'))
            ->groupBy('product_id')
            ->map(function ($group) {
                return [
                    'product_id' => (int) $group->first()['product_id'],
                    'quantity' => (int) $group->sum('quantity'),
                ];
            })
            ->values();

        if ($items->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty.',
            ], 422);
        }

        $productIds = $items->pluck('product_id')->all();
        $products = Product::query()
            ->with('vendor:id,store_name,is_active')
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->where('status', Product::STATUS_APPROVED)
            ->get()
            ->keyBy('id');

        if (count($productIds) !== $products->count()) {
            return response()->json([
                'message' => 'Some products are unavailable.',
            ], 422);
        }

        foreach ($items as $item) {
            /** @var Product|null $product */
            $product = $products->get($item['product_id']);
            if (! $product || ! $product->vendor || ! $product->vendor->is_active) {
                return response()->json([
                    'message' => 'Some products are no longer available for purchase.',
                ], 422);
            }

            if ($product->quantity < $item['quantity']) {
                return response()->json([
                    'message' => "Insufficient stock for product: {$product->name}.",
                ], 422);
            }
        }

        $groupedByVendor = $items->groupBy(function ($item) use ($products) {
            return $products[$item['product_id']]->vendor_id;
        });

        $vendorSubtotals = $groupedByVendor->map(function (Collection $vendorItems) use ($products) {
            $subtotal = 0.0;
            foreach ($vendorItems as $item) {
                /** @var Product $product */
                $product = $products[$item['product_id']];
                $unitPrice = $product->getDiscountedPrice();
                $subtotal += ($unitPrice * $item['quantity']);
            }

            return round($subtotal, 2);
        });

        $globalSubtotal = (float) round((float) $vendorSubtotals->sum(), 2);
        $globalCouponDiscount = $coupon ? $this->calculateCouponDiscount($coupon, $globalSubtotal) : 0.0;
        $allocatedCouponDiscounts = $this->allocateCouponDiscountByVendor($vendorSubtotals, $globalCouponDiscount);

        try {
            $createdOrders = DB::transaction(function () use (
                $allocatedCouponDiscounts,
                $coupon,
                $groupedByVendor,
                $paymentWay,
                $productIds,
                &$products,
                $user,
                $vendorSubtotals
            ) {
                $orders = [];
                $products = Product::query()
                    ->with('vendor:id,store_name,is_active')
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($groupedByVendor as $vendorId => $vendorItems) {
                    $subtotalAmount = (float) ($vendorSubtotals->get((int) $vendorId, 0) ?: 0);
                    $couponDiscountAmount = (float) ($allocatedCouponDiscounts->get((int) $vendorId, 0) ?: 0);
                    $totalAmount = max(round($subtotalAmount - $couponDiscountAmount, 2), 0);

                    $order = Order::create([
                        'order_number' => $this->generateOrderNumber(),
                        'user_id' => $user->id,
                        'vendor_id' => (int) $vendorId,
                        'coupon_id' => $coupon?->id,
                        'coupon_code' => $coupon?->code,
                        'coupon_type' => $coupon?->discount_type,
                        'coupon_value' => $coupon?->discount_value,
                        'status' => Order::STATUS_PENDING,
                        'payment_way' => $paymentWay,
                        'items_count' => 0,
                        'subtotal_amount' => $subtotalAmount,
                        'coupon_discount_amount' => $couponDiscountAmount,
                        'total_amount' => $totalAmount,
                    ]);

                    $itemsCount = 0;

                    foreach ($vendorItems as $item) {
                        /** @var Product $product */
                        $product = $products[$item['product_id']];

                        if (! $product->is_active || $product->status !== Product::STATUS_APPROVED || ! $product->vendor?->is_active) {
                            throw new \RuntimeException('Some products are no longer available for purchase.');
                        }

                        if ($product->quantity < $item['quantity']) {
                            throw new \RuntimeException("Insufficient stock for product: {$product->name}.");
                        }

                        $originalUnitPrice = (float) $product->price;
                        $hasDiscount = $product->hasActiveDiscount();
                        $appliedDiscountPercentage = $hasDiscount ? (float) ($product->discount_percentage ?? 0) : null;
                        $unitPrice = $product->getDiscountedPrice();
                        $lineTotal = round($unitPrice * $item['quantity'], 2);
                        $discountAmount = round(($originalUnitPrice - $unitPrice) * $item['quantity'], 2);

                        $order->items()->create([
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'original_unit_price' => $originalUnitPrice,
                            'has_discount' => $hasDiscount,
                            'applied_discount_percentage' => $appliedDiscountPercentage,
                            'unit_price' => $unitPrice,
                            'quantity' => $item['quantity'],
                            'line_total' => $lineTotal,
                            'discount_amount' => max($discountAmount, 0),
                        ]);

                        $product->decrement('quantity', $item['quantity']);
                        $itemsCount += $item['quantity'];
                    }

                    $order->update([
                        'items_count' => $itemsCount,
                    ]);

                    $orders[] = $order;
                }

                if ($coupon) {
                    $coupon->increment('used_count');
                }

                return collect($orders);
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('Checkout transaction failed.', [
                'user_id' => $user->id,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Checkout failed. Please try again.',
            ], 500);
        }

        try {
            Cache::tags(['products'])->flush();
        } catch (\Exception $e) {
            // Silently fail if cache driver doesn't support tags
        }

        foreach ($createdOrders as $order) {
            $this->notificationService->notifyNewOrder($order);
        }

        $ordersData = $createdOrders->map(function (Order $order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'items_count' => $order->items_count,
                'payment_way' => $order->payment_way,
                'subtotal_amount' => $order->subtotal_amount,
                'coupon_discount_amount' => $order->coupon_discount_amount,
                'total_amount' => $order->total_amount,
                'status' => $order->status,
                'coupon' => $order->coupon_code ? [
                    'code' => $order->coupon_code,
                    'type' => $order->coupon_type,
                    'value' => $order->coupon_value,
                ] : null,
            ];
        })->values();

        $count = $createdOrders->count();
        $message = $count > 1
            ? "Checkout successful. {$count} orders placed."
            : 'Checkout successful. Your order has been placed.';

        return response()->json([
            'message' => $message,
            'data' => [
                'orders_count' => $createdOrders->count(),
                'orders' => $ordersData,
            ],
        ], 201);
    }

    /**
     * Cancel an authenticated user's order.
     */
    public function cancel(Request $request, int $orderId): JsonResponse
    {
        $order = Order::query()
            ->where('user_id', $request->user()->id)
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

        DB::transaction(function () use ($order): void {
            $order->update([
                'status' => Order::STATUS_CANCELLED,
            ]);

            $this->restoreOrderQuantities($order);
        });
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

    private function resolveCoupon(string $couponCode): ?Coupon
    {
        $coupon = Coupon::query()
            ->where('code', $couponCode)
            ->first();

        if (! $coupon) {
            return null;
        }

        if (! $coupon->is_active || $coupon->status !== Coupon::STATUS_ACTIVE) {
            return null;
        }

        $now = now();
        if ($coupon->starts_at && $coupon->starts_at->gt($now)) {
            return null;
        }

        if ($coupon->ends_at && $coupon->ends_at->lt($now)) {
            return null;
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return null;
        }

        return $coupon;
    }

    private function calculateCouponDiscount(Coupon $coupon, float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        if ($coupon->discount_type === 'fixed') {
            return round(min((float) $coupon->discount_value, $subtotal), 2);
        }

        return round(min($subtotal * ((float) $coupon->discount_value / 100), $subtotal), 2);
    }

    /**
     * @param  Collection<int, float>  $vendorSubtotals
     * @return Collection<int, float>
     */
    private function allocateCouponDiscountByVendor(Collection $vendorSubtotals, float $totalDiscount): Collection
    {
        $sum = (float) $vendorSubtotals->sum();
        $allocated = collect();

        if ($totalDiscount <= 0 || $sum <= 0) {
            return $vendorSubtotals->map(fn () => 0.0);
        }

        $remaining = round($totalDiscount, 2);
        $keys = $vendorSubtotals->keys()->values();
        $lastKey = $keys->last();

        foreach ($vendorSubtotals as $vendorId => $subtotal) {
            if ((int) $vendorId === (int) $lastKey) {
                $allocated->put((int) $vendorId, round(max($remaining, 0), 2));

                continue;
            }

            $share = round($totalDiscount * (((float) $subtotal) / $sum), 2);
            $share = min($share, (float) $subtotal);
            $allocated->put((int) $vendorId, $share);
            $remaining = round($remaining - $share, 2);
        }

        return $allocated;
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

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
