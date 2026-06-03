<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProductTypeSelected
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->type !== User::TYPE_USER) {
            return $next($request);
        }

        if ($user->preferred_product_type || $request->session()->has('preferred_product_type')) {
            return $next($request);
        }

        if ($request->routeIs('product-type.*') || $request->is('api/*')) {
            return $next($request);
        }

        return redirect()->route('product-type.select');
    }
}
