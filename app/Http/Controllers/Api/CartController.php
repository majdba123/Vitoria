<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Commerce\CartException;
use App\Services\Commerce\CartService;
use App\Services\Commerce\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Server-authoritative cart endpoints (spec §5).
 *
 * Requests carry intent only. No endpoint here accepts a price, a discount or
 * a subtotal, and none is trusted to report availability — every response is
 * recomputed by CartService from the database.
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CouponService $couponService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->resolve($request, createIfMissing: false);

        if (! $cart) {
            $response = [
                'data' => [
                    'id' => null,
                    'items' => [],
                    'items_count' => 0,
                    'subtotal' => 0,
                    'currency' => config('vetora.currency', 'SYP'),
                    'coupon' => null,
                    'discount' => 0,
                    'total' => 0,
                ],
            ];

            if ($debug = $this->debugCartSnapshot($request, null)) {
                $response['_debug'] = $debug;
            }

            return response()->json($response);
        }

        $notices = $this->cartService->reconcile($cart);

        return $this->cartResponse($request, $cart, notices: $notices);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_LINE_QUANTITY],
        ]);

        $cart = $this->cartService->resolve($request);

        try {
            $cart = $this->cartService->add($cart, (int) $validated['product_id'], (int) $validated['quantity']);
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return $this->cartResponse($request, $cart, __('cart.updated'));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:0', 'max:'.CartService::MAX_LINE_QUANTITY],
        ]);

        $cart = $this->cartService->resolve($request);

        try {
            $cart = $this->cartService->updateQuantity($cart, (int) $validated['product_id'], (int) $validated['quantity']);
        } catch (CartException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return $this->cartResponse($request, $cart, __('cart.updated'));
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $cart = $this->cartService->resolve($request);
        $cart = $this->cartService->remove($cart, $productId);

        return $this->cartResponse($request, $cart, __('cart.updated'));
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->resolve($request);
        $cart = $this->cartService->clear($cart);

        return $this->cartResponse($request, $cart, __('cart.cleared'));
    }

    /**
     * Attach a coupon code to the cart. The code is only stored here; its
     * value is recomputed on every read and re-validated again at checkout,
     * so a coupon that expires between now and checkout cannot be used.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_code' => ['required', 'string', 'max:60'],
        ]);

        $cart = $this->cartService->resolve($request);
        $summary = $this->cartService->summarize($cart);

        $coupon = $this->couponService->resolveUsable(
            $validated['coupon_code'],
            $request->user(),
            (float) $summary['subtotal'],
        );

        if (! $coupon) {
            return response()->json(['message' => __('cart.coupon_invalid')], 422);
        }

        $cart->forceFill(['coupon_code' => $coupon->code])->save();

        return $this->cartResponse($request, $cart->fresh(), __('cart.coupon_applied'));
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->cartService->resolve($request);
        $cart->forceFill(['coupon_code' => null])->save();

        return $this->cartResponse($request, $cart->fresh(), __('cart.coupon_removed'));
    }

    /**
     * One response shape for every cart mutation, so the frontend can replace
     * its state wholesale instead of reconciling deltas.
     *
     * @param  array{removed: list<string>, adjusted: list<array{name: string, quantity: int}>}|null  $notices
     */
    private function cartResponse(Request $request, $cart, ?string $message = null, ?array $notices = null): JsonResponse
    {
        $summary = $this->cartService->summarize($cart);
        $subtotal = (float) $summary['subtotal'];

        $coupon = $cart->coupon_code
            ? $this->couponService->resolveUsable($cart->coupon_code, $request->user(), $subtotal)
            : null;

        // A code that is no longer usable (expired, subtotal fell below the
        // minimum) is silently dropped rather than blocking the cart.
        if ($cart->coupon_code && ! $coupon) {
            $cart->forceFill(['coupon_code' => null])->save();
        }

        $discount = $coupon ? $this->couponService->discountFor($coupon, $subtotal) : 0.0;

        $payload = array_merge($summary, [
            'coupon' => $coupon ? [
                'code' => $coupon->code,
                'type' => $coupon->discount_type,
                'value' => $coupon->discount_value,
            ] : null,
            'discount' => $discount,
            'total' => round(max($subtotal - $discount, 0), 2),
        ]);

        $response = ['data' => $payload];

        if ($message) {
            $response['message'] = $message;
        }

        if ($notices && ($notices['removed'] || $notices['adjusted'])) {
            $response['notices'] = array_values(array_filter([
                $notices['removed'] ? __('cart.items_removed_notice') : null,
                $notices['adjusted'] ? __('cart.items_adjusted_notice') : null,
            ]));
        }

        if ($debug = $this->debugCartSnapshot($request, $cart)) {
            $response['_debug'] = $debug;
        }

        return response()->json($response);
    }

    /**
     * TEMPORARY diagnostic for the "cart empties between requests" investigation.
     * Only activates for callers sending the debug header, so normal traffic is
     * unaffected. Remove once the root cause is confirmed.
     */
    private function debugCartSnapshot(Request $request, ?\App\Models\Cart $cart): ?array
    {
        if ($request->header('X-Debug-Cart') !== 'vetora-cart-debug-2026') {
            return null;
        }

        $pdoConnectionId = null;
        try {
            $pdoConnectionId = DB::selectOne('SELECT CONNECTION_ID() AS id')->id ?? null;
        } catch (\Throwable $e) {
            $pdoConnectionId = 'error: '.$e->getMessage();
        }

        return [
            'connection_name' => DB::connection()->getName(),
            'database_name' => DB::connection()->getDatabaseName(),
            'pdo_connection_id' => $pdoConnectionId,
            'transaction_level' => DB::transactionLevel(),
            'carts_table_total_rows' => DB::table('carts')->count(),
            'cart_items_table_total_rows' => DB::table('cart_items')->count(),
            'resolved_cart_id' => $cart?->id,
            'resolved_cart_row_exists_raw' => $cart ? DB::table('carts')->where('id', $cart->id)->exists() : null,
            'auth_user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'session_driver' => config('session.driver'),
            'guest_token_in_session' => $request->session()->get(CartService::GUEST_TOKEN_KEY),
            'server_time' => now()->toDateTimeString(),
            'app_env' => app()->environment(),
        ];
    }
}
