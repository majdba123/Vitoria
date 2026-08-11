<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\Commerce\CartException;
use App\Services\Commerce\CartService;
use App\Services\Commerce\CheckoutService;
use App\Services\Commerce\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Checkout against the server cart (spec §7).
 *
 * The request body is three fields: which saved address, which payment method,
 * and an optional idempotency-friendly confirmation. Nothing about price,
 * quantity or discount is accepted from the client.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly CouponService $couponService,
    ) {}

    /**
     * Everything the checkout screen needs to render, priced server-side.
     */
    public function summary(Request $request): JsonResponse
    {
        $cart = $this->cartService->resolve($request);
        $notices = $this->cartService->reconcile($cart);

        $summary = $this->cartService->summarize($cart);
        $subtotal = (float) $summary['subtotal'];

        $coupon = $cart->coupon_code
            ? $this->couponService->resolveUsable($cart->coupon_code, $request->user(), $subtotal)
            : null;

        $discount = $coupon ? $this->couponService->discountFor($coupon, $subtotal) : 0.0;
        $shipping = 0.0;
        $tax = 0.0;

        return response()->json([
            'data' => [
                'cart' => $summary,
                'addresses' => $request->user()->addresses()->get(['id', 'label', 'recipient_name', 'phone', 'governorate', 'city', 'district', 'street', 'is_default']),
                'payment_methods' => $this->checkoutService->availablePaymentMethods(),
                'totals' => [
                    'subtotal' => $subtotal,
                    'discount_total' => $discount,
                    'shipping_total' => $shipping,
                    'tax_total' => $tax,
                    'grand_total' => round(max($subtotal - $discount + $shipping + $tax, 0), 2),
                    'currency' => config('vetora.currency', 'SYP'),
                ],
                'coupon' => $coupon ? ['code' => $coupon->code, 'discount' => $discount] : null,
                'notices' => array_values(array_filter([
                    $notices['removed'] ? __('cart.items_removed_notice') : null,
                    $notices['adjusted'] ? __('cart.items_adjusted_notice') : null,
                ])),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string', Rule::in($this->checkoutService->availablePaymentMethods())],
        ]);

        $user = $request->user();
        $address = UserAddress::query()->findOrFail($validated['address_id']);

        // Policy, not a where-clause — so the failure mode is an explicit 403
        // rather than a 404 that silently hides an ownership bug.
        $this->authorize('view', $address);

        $cart = $this->cartService->resolve($request);

        try {
            $orders = $this->checkoutService->place($cart, $user, $address, $validated['payment_method']);
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            Log::error('Checkout failed.', [
                'user_id' => $user->id,
                'exception' => $exception,
            ]);

            return response()->json(['message' => __('cart.checkout_failed')], 500);
        }

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
                    'vendor_id' => $order->vendor_id,
                    'items_count' => $order->items_count,
                    'grand_total' => $order->grand_total,
                    'currency' => $order->currency,
                    'status' => $order->status,
                ])->values(),
            ],
        ], 201);
    }
}
