<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only admin audit trail (spec §35): filterable list, detail, and an
 * authorized CSV export. There is deliberately no update or delete action —
 * audit entries are append-only (see AuditLog::booted()).
 */
class AuditLogController extends Controller
{
    /**
     * Upper bound on one export, so a single request can never stream an
     * unbounded table. Narrow the filters to export more specific ranges.
     */
    private const EXPORT_LIMIT = 10000;

    public function __construct(public AuditLogService $auditLog) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        $perPage = min(max((int) ($filters['per_page'] ?? 30), 10), 100);

        $logs = $this->filteredQuery($filters)->paginate($perPage);

        return response()->json([
            'message' => __('api.audit_log_retrieved'),
            'data' => $logs->getCollection()->map(fn (AuditLog $log) => $this->present($log))->values(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'facets' => $this->facets(),
            ],
        ]);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('actor:id,name,email,type');

        return response()->json([
            'data' => $this->present($auditLog, detailed: true),
        ]);
    }

    /**
     * Stream the filtered audit trail as CSV. The export itself is audited
     * (`audit_logs.exported`, with the filters used) before streaming starts.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        unset($filters['per_page'], $filters['page']);

        $this->auditLog->record($request->user(), 'audit_logs.exported', 'AuditLog', null, null, [
            'filters' => $filters,
            'limit' => self::EXPORT_LIMIT,
        ]);

        $query = $this->filteredQuery($filters);
        $filename = 'audit-trail-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['id', 'created_at', 'actor_id', 'actor_name', 'actor_type', 'action', 'entity_type', 'entity_id', 'old_values', 'new_values', 'ip_address', 'request_id']);

            $written = 0;

            // lazyByIdDesc() eager-loads the actor per chunk (cursor() would
            // not) and keeps the newest-first order of the list view.
            foreach ($query->lazyByIdDesc(500) as $log) {
                if ($written++ >= self::EXPORT_LIMIT) {
                    break;
                }

                $row = $this->present($log);
                fputcsv($out, array_map([$this, 'csvSafe'], [
                    $row['id'],
                    optional($log->created_at)->toIso8601String(),
                    $row['actor']['id'] ?? '',
                    $row['actor']['name'] ?? '',
                    $row['actor_type'] ?? '',
                    $row['action'],
                    $row['entity_type'],
                    $row['entity_id'] ?? '',
                    $row['old_values'] !== null ? json_encode($row['old_values'], JSON_UNESCAPED_UNICODE) : '',
                    $row['new_values'] !== null ? json_encode($row['new_values'], JSON_UNESCAPED_UNICODE) : '',
                    $row['ip_address'] ?? '',
                    $row['request_id'] ?? '',
                ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Distinct filter values for the list view. Scanning the whole
     * append-only table on every request would grow without bound, so the
     * lists are cached briefly; a new action type appears within minutes.
     *
     * @return array{actions: mixed, entity_types: mixed, actor_types: mixed}
     */
    private function facets(): array
    {
        return Cache::remember('audit_logs:facets', now()->addMinutes(10), fn (): array => [
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action')->all(),
            'entity_types' => AuditLog::query()->distinct()->orderBy('entity_type')->pluck('entity_type')->all(),
            'actor_types' => AuditLog::query()->whereNotNull('actor_type')->distinct()->orderBy('actor_type')->pluck('actor_type')->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'action' => ['nullable', 'string', 'max:60'],
            'entity_type' => ['nullable', 'string', 'max:60'],
            'entity_id' => ['nullable', 'integer', 'min:1'],
            'actor_user_id' => ['nullable', 'integer', 'min:1'],
            'actor_type' => ['nullable', 'string', 'max:20'],
            'request_id' => ['nullable', 'string', 'max:36'],
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AuditLog>
     */
    private function filteredQuery(array $filters): Builder
    {
        return AuditLog::query()
            ->with('actor:id,name,email')
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => str_ends_with($action, '.*')
                ? $query->where('action', 'like', substr($action, 0, -1).'%')
                : $query->where('action', $action))
            ->when($filters['entity_type'] ?? null, fn (Builder $query, string $type) => $query->where('entity_type', $type))
            ->when($filters['entity_id'] ?? null, fn (Builder $query, $id) => $query->where('entity_id', (int) $id))
            ->when($filters['actor_user_id'] ?? null, fn (Builder $query, $id) => $query->where('actor_user_id', (int) $id))
            ->when($filters['actor_type'] ?? null, fn (Builder $query, string $type) => $query->where('actor_type', $type))
            ->when($filters['request_id'] ?? null, fn (Builder $query, string $id) => $query->where('request_id', $id))
            ->when($filters['search'] ?? null, function (Builder $query, string $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn (Builder $inner) => $inner
                    ->where('action', 'like', $like)
                    ->orWhere('entity_type', 'like', $like)
                    ->orWhereHas('actor', fn (Builder $actor) => $actor->where('name', 'like', $like)->orWhere('email', 'like', $like)));
            })
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('id');
    }

    /**
     * Stored values are re-redacted on the way out, so rows written before
     * the current redaction rules never expose a secret either.
     *
     * @return array<string, mixed>
     */
    private function present(AuditLog $log, bool $detailed = false): array
    {
        $data = [
            'id' => $log->id,
            'actor' => $log->actor ? ['id' => $log->actor->id, 'name' => $log->actor->name] : null,
            'actor_user_id' => $log->actor_user_id,
            'actor_type' => $log->actor_type,
            'action' => $log->action,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'old_values' => is_array($log->old_values) ? $this->auditLog->redact($log->old_values) : null,
            'new_values' => is_array($log->new_values) ? $this->auditLog->redact($log->new_values) : null,
            'ip_address' => $log->ip_address,
            'request_id' => $log->request_id,
            'created_at' => $log->created_at,
        ];

        if ($detailed) {
            $data['user_agent'] = $log->user_agent;
            $data['actor'] = $log->actor ? ['id' => $log->actor->id, 'name' => $log->actor->name, 'email' => $log->actor->email] : null;
        }

        return $data;
    }

    /**
     * Neutralize spreadsheet formula injection in exported cells.
     */
    private function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
