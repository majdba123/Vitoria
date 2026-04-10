<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVendor
{
    /**
     * Handle an incoming request.
     *
     * Ensures the user is a vendor AND their vendor profile is active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->type !== User::TYPE_VENDOR) {
            $message = __('Unauthorized. Vendor access required.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return response()->view('errors.403-vendor', ['message' => $message], 403);
        }

        // Check vendor profile exists and is active
        $vendor = $user->vendor;

        if (! $vendor || ! $vendor->is_active) {
            $message = __('Your vendor account is inactive. Please contact support.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return response()->view('errors.403-vendor', ['message' => $message], 403);
        }

        return $next($request);
    }
}
