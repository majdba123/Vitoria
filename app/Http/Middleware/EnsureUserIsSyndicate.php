<?php

namespace App\Http\Middleware;

use App\Models\Syndicate;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSyndicate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->type !== User::TYPE_SYNDICATE) {
            $message = __('Unauthorized. Syndicate access required.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return response()->view('errors.403-vendor', ['message' => $message], 403);
        }

        $syndicate = $user->syndicate;

        if (! $syndicate || $syndicate->status !== Syndicate::STATUS_ACTIVE) {
            $message = __('Your syndicate account is inactive. Please contact support.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return response()->view('errors.403-vendor', ['message' => $message], 403);
        }

        return $next($request);
    }
}
