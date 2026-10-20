<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AdminRoutePermissions;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side back-office authorization (alias `admin.permission`).
 *
 * Runs after `admin` on every admin web and API route and resolves the
 * permission the matched route requires from AdminRoutePermissions. Deny by
 * default: an unmapped route is refused for everyone but a super
 * administrator. Every refusal is written to the audit trail as
 * `authorization.denied` so probing for hidden screens leaves evidence.
 *
 * Hiding a menu item in React is only a convenience — this middleware, the
 * policies, and the role services are what actually enforce access.
 */
class EnsureAdminPermission
{
    public function __construct(private AuditLogService $auditLog) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();
        $required = AdminRoutePermissions::for($routeName);

        if ($user !== null && $user->isAdmin() && $this->allows($user, $required)) {
            return $next($request);
        }

        $this->auditLog->record($user, 'authorization.denied', 'route', null, null, [
            'route' => $routeName,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'required_permission' => $required,
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => __('common.forbidden'),
                'status' => Response::HTTP_FORBIDDEN,
            ], Response::HTTP_FORBIDDEN);
        }

        return Inertia::render('Admin/Forbidden', [
            'requiredPermission' => $required,
        ])->toResponse($request)->setStatusCode(Response::HTTP_FORBIDDEN);
    }

    private function allows(User $user, ?string $required): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($required === null) {
            return false;
        }

        return AdminRoutePermissions::allows($user, $required);
    }
}
