<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserTimezone
{
    /**
     * Resolve the viewer's timezone for display only.
     *
     * Storage stays in `app.timezone` (UTC): overriding it per request made
     * Eloquent write `created_at`/`updated_at` in each user's local time.
     * Formatting code reads `app.display_timezone` instead.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = $request->user()?->timezone
            ?? ($request->hasSession() ? $request->session()->get('timezone') : null)
            ?? $request->cookie('sz_timezone');

        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.display_timezone' => $timezone]);
            $request->attributes->set('timezone', $timezone);
        }

        return $next($request);
    }
}
