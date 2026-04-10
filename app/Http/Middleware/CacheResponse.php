<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Add HTTP cache headers to public API responses.
     */
    public function handle(Request $request, Closure $next, int $seconds = 120): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $response->headers->set('Cache-Control', "public, max-age={$seconds}, s-maxage={$seconds}");
            $response->headers->set('Vary', 'Accept');
        }

        return $response;
    }
}
