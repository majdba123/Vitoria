<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline audit coverage for privileged workspaces (alias `audit.mutations`,
 * applied to the admin, vendor, employee and syndicate API groups).
 *
 * After a write request (POST/PUT/PATCH/DELETE) succeeds (2xx), records an
 * `api.mutation` entry — route, target entity, and the *names* of the
 * submitted fields, never their values — unless the action already wrote a
 * domain-specific audit row during the request (those carry the meaningful
 * before/after values). Failed writes are not recorded as changes; refused
 * ones are already recorded as `authorization.denied`.
 *
 * Runs after the controller returns, i.e. after its transaction committed,
 * so an entry never describes a write that rolled back.
 */
class AuditPrivilegedMutations
{
    public function __construct(private AuditLogService $auditLog) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldRecord($request, $response)) {
            [$entityType, $entityId] = $this->resolveEntity($request);

            $this->auditLog->record($request->user(), 'api.mutation', $entityType, $entityId, null, [
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'fields' => collect(array_keys($request->all()))->map(fn ($key) => (string) $key)->sort()->values()->all(),
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        return ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            && $response->isSuccessful()
            && $request->user() !== null
            && ! $request->attributes->get(AuditLogService::REQUEST_RECORDED_FLAG, false);
    }

    /**
     * The first bound model on the route, otherwise the first numeric route
     * parameter named after the resource (e.g. `{refundId}` → Refund).
     *
     * @return array{0: string, 1: int|null}
     */
    private function resolveEntity(Request $request): array
    {
        $route = $request->route();
        $parameters = $route?->parameters() ?? [];

        foreach ($parameters as $value) {
            if ($value instanceof Model) {
                return [class_basename($value), (int) $value->getKey()];
            }
        }

        foreach ($parameters as $name => $value) {
            if (is_numeric($value) && str_ends_with((string) $name, 'Id')) {
                return [Str::studly(substr((string) $name, 0, -2)), (int) $value];
            }
        }

        $segments = explode('.', (string) $route?->getName());
        $resource = $segments[count($segments) - 2] ?? 'request';

        return [Str::studly(Str::singular($resource)), null];
    }
}
