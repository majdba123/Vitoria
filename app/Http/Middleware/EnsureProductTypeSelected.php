<?php

namespace App\Http\Middleware;

use App\Services\SelectedProductTypeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProductTypeSelected
{
    public function __construct(protected SelectedProductTypeService $selectedProductTypeService) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $this->selectedProductTypeService->requiresSelection($user)) {
            return $next($request);
        }

        if ($this->selectedProductTypeService->resolve($request)) {
            return $next($request);
        }

        if ($request->routeIs('product-type.*') || $request->is('api/*')) {
            return $next($request);
        }

        return redirect()->route('product-type.select');
    }
}
