<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Vendor;
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

            return redirect()->route('login')->with('error', $message);
        }

        // Check vendor profile exists and is active. managedVendor() covers
        // both the owner and an active staff member (spec §22).
        $vendor = $user->managedVendor();

        if (! $vendor || ! $vendor->is_active) {
            $message = $vendor?->status === Vendor::STATUS_PENDING
                ? __('Your vendor account is pending admin approval.')
                : __('Your vendor account is inactive. Please contact support.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}
