<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);
        $request->setRequestLocale($locale);
        $request->headers->set('Accept-Language', $locale);

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $candidates = [
            $request->user()?->locale,
            $request->session()->get('locale'),
            $request->cookie('locale'),
            $request->cookie('sz_locale'),
            $request->getPreferredLanguage(['ar', 'en']),
            config('app.locale'),
        ];

        foreach ($candidates as $candidate) {
            $locale = $this->normalizeLocale($candidate);
            if ($locale !== null) {
                return $locale;
            }
        }

        return 'en';
    }

    protected function normalizeLocale(mixed $locale): ?string
    {
        if (! is_string($locale) || $locale === '') {
            return null;
        }

        $locale = Str::lower(Str::substr($locale, 0, 2));

        return in_array($locale, ['ar', 'en'], true) ? $locale : null;
    }
}
