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
            // Public payloads are localized from the request header or locale
            // cookies. Shared proxies must keep those representations apart.
            $response->setVary(['Accept', 'Accept-Language', 'Cookie']);
        }

        return $response;
    }
}
