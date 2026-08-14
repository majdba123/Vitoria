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

        // Search/AI crawlers never carry the preference cookie, so without this
        // check every indexable product/category URL 302s to the onboarding
        // selector and only that page ever gets indexed. Let bots through to
        // the real (unfiltered) content instead — same page a human sees once
        // they've picked a type, not a different one, so this isn't cloaking.
        if ($this->isCrawler($request)) {
            return $next($request);
        }

        return redirect()->route('product-type.select');
    }

    protected function isCrawler(Request $request): bool
    {
        $userAgent = (string) $request->userAgent();

        if ($userAgent === '') {
            return false;
        }

        return (bool) preg_match(
            '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegrambot|linkedinbot|embedly|quora link preview|pinterest|outbrain|vkshare|w3c_validator|GPTBot|ChatGPT-User|ClaudeBot|Claude-Web|PerplexityBot|Bytespider|Applebot/i',
            $userAgent
        );
    }
}
