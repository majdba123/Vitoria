<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks privileged, authenticated roles (Admin, Vendor, Syndicate,
 * Employee) from acting as a buyer - cart mutation, coupon, checkout summary
 * and checkout. Guests are untouched here: they build a session cart and are
 * only gated by `auth:sanctum` at authenticated-checkout, per the guest-cart
 * flow documented in routes/api.php.
 */
class EnsureCanPurchase
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canPurchase()) {
            return response()->json(['message' => __('purchase.customer_only')], 403);
        }

        return $next($request);
    }
}
