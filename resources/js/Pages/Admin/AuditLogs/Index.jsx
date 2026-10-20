import { useEffect, useState } from 'react';
import { Download, Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { Pagination } from '@/Components/shared/Pagination';
import { StatusMessage } from '@/Components/admin/StatusMessage';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { useAdminList } from '@/hooks/use-admin-list';
import { useAdminPermissions } from '@/hooks/use-admin-permissions';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatDateTime } from '@/lib/date-time';

const EMPTY_FILTERS = { search: '', action: 'all', entity_type: 'all', actor_type: 'all', entity_id: '', actor_user_id: '', from: '', to: '' };

const ACTION_TONES = { denied: 'warning', failed: 'warning', deleted: 'warning', login_failed: 'warning' };

function actionTone(action) {
    const verb = action.split('.').pop();
    return ACTION_TONES[verb] ?? (action.startsWith('authorization.') ? 'warning' : 'brand');
}

/**
 * Read-only audit trail. Entries can be filtered, opened and — with the
 * separate `audit_logs.export` permission — exported; nothing here can edit
 * or remove an entry, and the server exposes no endpoint that could.
 */
export default function AuditLogsIndex() {
    const { admin } = useI18n();
    const locale = useLocale();
    const { can } = useAdminPermissions();
    const [page, setPage] = useState(1);
    const [filters, setFilters] = useState(EMPTY_FILTERS);
    const [debouncedSearch, setDebouncedSearch] = useState('');
    const [facets, setFacets] = useState({ actions: [], entity_types: [], actor_types: [] });
    const [detail, setDetail] = useState(null);
    const [isExporting, setIsExporting] = useState(false);
    const [exportMessage, setExportMessage] = useState(null);

    useEffect(() => {
        const timer = window.setTimeout(() => setDebouncedSearch(filters.search.trim()), 300);
        return () => window.clearTimeout(timer);
    }, [filters.search]);

    const query = {
        search: debouncedSearch || undefined,
        action: filters.action !== 'all' ? filters.action : undefined,
        entity_type: filters.entity_type !== 'all' ? filters.entity_type : undefined,
        actor_type: filters.actor_type !== 'all' ? filters.actor_type : undefined,
        entity_id: /^\d+$/.test(filters.entity_id) ? filters.entity_id : undefined,
        actor_user_id: /^\d+$/.test(filters.actor_user_id) ? filters.actor_user_id : undefined,
        from: filters.from || undefined,
        to: filters.to || undefined,
    };

    const { status, rows, meta, errorMessage, reload } = useAdminList('/api/admin/audit-logs', { ...query, page });

    useEffect(() => {
        if (meta?.facets) setFacets(meta.facets);
    }, [meta]);

    const setFilter = (key) => (value) => {
        setFilters((current) => ({ ...current, [key]: value }));
        setPage(1);
    };

    const openDetail = (row) => {
        setDetail({ status: 'loading', row });
        window.axios
            .get(`/api/admin/audit-logs/${row.id}`, { silent: true })
            .then((res) => setDetail({ status: 'ready', row: res.data?.data ?? row }))
            .catch(() => setDetail({ status: 'error', row }));
    };

    const exportCsv = async () => {
        setIsExporting(true);
        setExportMessage(null);
        try {
            const response = await window.axios.get('/api/admin/audit-logs/export', { params: query, responseType: 'blob', silent: true });
            const url = URL.createObjectURL(response.data);
            const link = document.createElement('a');
            link.href = url;
            link.download = `audit-trail-${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 60_000);
            setExportMessage({ tone: 'success', text: admin.audit_export_started });
        } catch {
            setExportMessage({ tone: 'error', text: admin.rbac_load_error });
        } finally {
            setIsExporting(false);
        }
    };

    const actorTypeLabel = (type) => (type ? admin.audit_actor_types?.[type] ?? type : admin.audit_system_actor);

    const columns = [
        { key: 'time', label: admin.audit_th_time, render: (row) => <span className="whitespace-nowrap text-sm">{formatDateTime(row.created_at, locale)}</span> },
        {
            key: 'actor',
            label: admin.audit_th_actor,
            truncate: true,
            render: (row) => (
                <div className="min-w-0">
                    <p className="truncate font-medium text-foreground">{row.actor?.name ?? (row.actor_user_id ? `#${row.actor_user_id}` : admin.audit_system_actor)}</p>
                    <p className="truncate text-xs text-muted-foreground">{actorTypeLabel(row.actor_type)}</p>
                </div>
            ),
        },
        { key: 'action', label: admin.audit_th_action, render: (row) => <StatusBadge tone={actionTone(row.action)}>{row.action}</StatusBadge> },
        {
            key: 'record',
            label: admin.audit_th_record,
            render: (row) => (
                <span className="font-mono text-xs">
                    {row.entity_type}
                    {row.entity_id ? ` #${row.entity_id}` : ''}
                </span>
            ),
        },
        { key: 'ip', label: admin.audit_th_ip, render: (row) => <span className="font-mono text-xs text-muted-foreground">{row.ip_address || '-'}</span> },
        {
            key: 'actions',
            label: admin.actions,
            align: 'end',
            render: (row) => (
                <Button variant="ghost" size="sm" onClick={() => openDetail(row)}>
                    {admin.audit_view_details}
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title={admin.audit_title}>
            <PageHeader
                title={admin.audit_title}
                copy={admin.audit_copy}
                actions={
                    can('audit_logs.export') && (
                        <Button size="sm" variant="outline" onClick={exportCsv} disabled={isExporting}>
                            {isExporting ? <Loader2 className="size-4 animate-spin" aria-hidden="true" /> : <Download className="size-4" aria-hidden="true" />}
                            {admin.audit_export}
                        </Button>
                    )
                }
            />

            {exportMessage && <StatusMessage tone={exportMessage.tone}>{exportMessage.text}</StatusMessage>}

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="sm:col-span-2">
                        <Label htmlFor="audit-search" className="mb-1.5">{admin.audit_filter_search}</Label>
                        <Input id="audit-search" type="search" value={filters.search} onChange={(e) => setFilter('search')(e.target.value)} />
                    </div>
                    <FacetSelect id="audit-action" label={admin.audit_filter_action} value={filters.action} onValueChange={setFilter('action')} allLabel={admin.audit_filter_all} options={facets.actions.map((value) => ({ value, label: value }))} />
                    <FacetSelect id="audit-entity" label={admin.audit_filter_entity} value={filters.entity_type} onValueChange={setFilter('entity_type')} allLabel={admin.audit_filter_all} options={facets.entity_types.map((value) => ({ value, label: value }))} />
                    <FacetSelect id="audit-actor-type" label={admin.audit_filter_actor_type} value={filters.actor_type} onValueChange={setFilter('actor_type')} allLabel={admin.audit_filter_all} options={facets.actor_types.map((value) => ({ value, label: actorTypeLabel(value) }))} />
                    <FilterInput id="audit-entity-id" label={admin.audit_filter_entity_id} inputMode="numeric" value={filters.entity_id} onChange={setFilter('entity_id')} />
                    <FilterInput id="audit-actor-id" label={admin.audit_filter_actor_id} inputMode="numeric" value={filters.actor_user_id} onChange={setFilter('actor_user_id')} />
                    <FilterInput id="audit-from" label={admin.audit_filter_from} type="date" value={filters.from} onChange={setFilter('from')} />
                    <FilterInput id="audit-to" label={admin.audit_filter_to} type="date" min={filters.from || undefined} value={filters.to} onChange={setFilter('to')} />
                    <div className="flex items-end sm:col-span-2 lg:col-span-4 lg:justify-end">
                        <Button type="button" variant="ghost" size="sm" onClick={() => { setFilters(EMPTY_FILTERS); setPage(1); }}>
                            {admin.audit_filter_reset}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <div>
                <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle={admin.audit_empty} emptyHint={admin.audit_empty_hint} />
                {status === 'ready' && rows.length > 0 && (
                    <div className="rounded-b-lg border border-t-0 border-border">
                        <Pagination meta={meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                    </div>
                )}
            </div>

            <AuditDetailDialog detail={detail} onClose={() => setDetail(null)} actorTypeLabel={actorTypeLabel} onRetry={() => detail && openDetail(detail.row)} />
        </AdminLayout>
    );
}

function AuditDetailDialog({ detail, onClose, actorTypeLabel, onRetry }) {
    const { admin, common } = useI18n();
    const locale = useLocale();
    const entry = detail?.row;
    const before = entry?.old_values ?? {};
    const after = entry?.new_values ?? {};
    const fields = [...new Set([...Object.keys(before), ...Object.keys(after)])];

    return (
        <Dialog open={!!detail} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{(admin.audit_detail_title ?? '').replace(':id', entry?.id ?? '')}</DialogTitle>
                    <DialogDescription>{admin.audit_detail_redacted_note}</DialogDescription>
                </DialogHeader>

                {detail?.status === 'loading' && <Skeleton className="h-40 w-full" />}
                {detail?.status === 'error' && (
                    <div className="flex flex-col items-start gap-3">
                        <StatusMessage tone="error">{admin.rbac_load_error}</StatusMessage>
                        <Button size="sm" variant="outline" onClick={onRetry}>{common.retry}</Button>
                    </div>
                )}

                {detail?.status === 'ready' && entry && (
                    <div className="space-y-5 text-sm">
                        <dl className="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                            <DetailItem label={admin.audit_th_time} value={formatDateTime(entry.created_at, locale)} />
                            <DetailItem label={admin.audit_th_action} value={entry.action} mono />
                            <DetailItem
                                label={admin.audit_th_actor}
                                value={entry.actor ? `${entry.actor.name}${entry.actor.email ? ` · ${entry.actor.email}` : ''} (#${entry.actor.id})` : entry.actor_user_id ? `#${entry.actor_user_id}` : admin.audit_system_actor}
                            />
                            <DetailItem label={admin.audit_filter_actor_type} value={actorTypeLabel(entry.actor_type)} />
                            <DetailItem label={admin.audit_th_record} value={`${entry.entity_type}${entry.entity_id ? ` #${entry.entity_id}` : ''}`} mono />
                            <DetailItem label={admin.audit_th_ip} value={entry.ip_address || '-'} mono />
                            <DetailItem label={admin.audit_detail_request_id} value={entry.request_id || '-'} mono />
                            <DetailItem label={admin.audit_detail_user_agent} value={entry.user_agent || '-'} />
                        </dl>

                        <section aria-labelledby="audit-changes-heading">
                            <h3 id="audit-changes-heading" className="mb-2 text-sm font-bold text-foreground">{admin.audit_detail_changes}</h3>
                            {fields.length === 0 ? (
                                <p className="text-muted-foreground">{admin.audit_detail_no_changes}</p>
                            ) : (
                                <div className="overflow-x-auto rounded-md border border-border">
                                    <table className="w-full text-start text-xs">
                                        <thead className="bg-muted/50">
                                            <tr>
                                                <th scope="col" className="px-3 py-2 text-start font-semibold">{admin.audit_detail_field}</th>
                                                <th scope="col" className="px-3 py-2 text-start font-semibold">{admin.audit_detail_before}</th>
                                                <th scope="col" className="px-3 py-2 text-start font-semibold">{admin.audit_detail_after}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {fields.map((field) => (
                                                <tr key={field} className="border-t border-border align-top">
                                                    <th scope="row" className="px-3 py-2 text-start font-mono font-medium">{field}</th>
                                                    <td className="px-3 py-2"><ValueCell value={before[field]} /></td>
                                                    <td className="px-3 py-2"><ValueCell value={after[field]} /></td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </section>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}

function DetailItem({ label, value, mono = false }) {
    return (
        <div className="min-w-0">
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className={`break-words text-foreground ${mono ? 'font-mono text-xs' : ''}`}>{value}</dd>
        </div>
    );
}

function ValueCell({ value }) {
    if (value === undefined) return <span className="text-muted-foreground">—</span>;
    const text = typeof value === 'object' ? JSON.stringify(value, null, 2) : String(value);
    return <pre className="whitespace-pre-wrap break-words font-mono">{text}</pre>;
}

function FilterInput({ id, label, onChange, ...props }) {
    return (
        <div>
            <Label htmlFor={id} className="mb-1.5">{label}</Label>
            <Input id={id} onChange={(e) => onChange(e.target.value)} {...props} />
        </div>
    );
}

function FacetSelect({ id, label, value, onValueChange, allLabel, options }) {
    return (
        <div>
            <Label id={`${id}-label`} className="mb-1.5">{label}</Label>
            <Select value={value} onValueChange={onValueChange}>
                <SelectTrigger id={id} className="w-full" aria-labelledby={`${id}-label`}>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{allLabel}</SelectItem>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
