<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsEmployee
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->type !== User::TYPE_EMPLOYEE) {
            $message = __('Unauthorized. Employee access required.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
