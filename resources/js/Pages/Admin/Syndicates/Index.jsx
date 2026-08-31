import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Plus, Building2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { Pagination } from '@/Components/shared/Pagination';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Card, CardContent } from '@/Components/ui/card';
import { useAdminList } from '@/hooks/use-admin-list';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatDate, formatNumber } from '@/lib/date-time';

export default function SyndicatesIndex() {
    const { admin, common } = useI18n();
    const locale = useLocale();
    const [page, setPage] = useState(1);
    const [type, setType] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [flash, setFlash] = useState(null);

    const { status, rows, meta, errorMessage, reload } = useAdminList('/api/admin/syndicates', {
        page,
        per_page: 15,
        type: type === 'all' ? undefined : type,
        status: statusFilter === 'all' ? undefined : statusFilter,
        search: search || undefined,
    });

    const showFlash = (message) => {
        setFlash(message);
        setTimeout(() => setFlash(null), 4000);
    };

    const toggleActive = (row) => {
        window.axios.patch(`/api/admin/syndicates/${row.id}/toggle-active`, {}, { silent: true }).then(() => {
            showFlash(admin.js_syndicate_status_updated);
            reload();
        });
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios.delete(`/api/admin/syndicates/${deleteTarget.id}`, { silent: true }).then(() => {
            setIsDeleting(false);
            setDeleteTarget(null);
            reload();
        }).catch(() => setIsDeleting(false));
    };

    const typeLabel = (t) => (t === 'agriculture' ? admin.type_agriculture : t === 'veterinary' ? admin.type_veterinary : t);

    const columns = [
        {
            key: 'name',
            label: admin.name_label,
            render: (row) => (
                <div className="flex items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-accent text-sm font-bold text-accent-foreground">
                        {row.logo_url ? <img src={row.logo_url} alt="" className="size-full object-cover" /> : (row.name || '?').charAt(0)}
                    </span>
                    <div className="min-w-0">
                        <p className="truncate font-semibold text-foreground">{row.name}</p>
                        <p className="truncate text-xs text-muted-foreground">{row.phone || admin.no_phone}</p>
                    </div>
                </div>
            ),
        },
        { key: 'account', label: admin.th_account, render: (row) => <><p className="font-semibold">{row.email || row.user?.email || '—'}</p><p className="text-xs text-muted-foreground">#{row.user_id}</p></> },
        { key: 'type', label: admin.type_label, render: (row) => <StatusBadge tone="brand">{typeLabel(row.type)}</StatusBadge> },
        { key: 'status', label: admin.status_label, render: (row) => <StatusBadge tone={row.is_active ? 'success' : 'danger'}>{row.is_active ? common.active : common.inactive}</StatusBadge> },
        {
            key: 'data',
            label: admin.th_data,
            render: (row) => (
                <div className="grid min-w-40 grid-cols-2 gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
                    <span><bdi>{formatNumber(row.categories_count, locale)}</bdi> {admin.categories_count_suffix}</span>
                    <span><bdi>{formatNumber(row.vendors_count, locale)}</bdi> {admin.vendors_count_suffix}</span>
                    <span><bdi>{formatNumber(row.products_count, locale)}</bdi> {admin.products_count_suffix_short}</span>
                    <span><bdi>{formatNumber(row.orders_count, locale)}</bdi> {admin.orders_count_suffix}</span>
                </div>
            ),
        },
        { key: 'created_at', label: admin.th_created_at, render: (row) => formatDate(row.created_at, locale) || '—' },
        {
            key: 'actions',
            label: admin.th_actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex flex-wrap items-center justify-end gap-1.5">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={route('admin.syndicates.show', row.id)}>{admin.view}</Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('admin.syndicates.edit', row.id)}>{common.edit}</Link>
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => toggleActive(row)}>
                        {row.is_active ? admin.disable : admin.enable}
                    </Button>
                    <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>
                        {common.delete}
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={admin.syndicate_agents_heading}>
            <PageHeader
                title={admin.syndicate_agents_heading}
                copy={admin.syndicate_agents_copy}
                actions={
                    <Button asChild size="sm">
                        <Link href={route('admin.syndicates.create')}>
                            <Plus className="size-4" />
                            {admin.add_syndicate_agent}
                        </Link>
                    </Button>
                }
            />

            {flash && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{flash}</p>}

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-4 lg:grid-cols-4">
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">{admin.type_label}</label>
                        <Select value={type} onValueChange={(v) => { setType(v); setPage(1); }}>
                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{admin.all_types_plain}</SelectItem>
                                <SelectItem value="agriculture">{admin.type_agriculture}</SelectItem>
                                <SelectItem value="veterinary">{admin.type_veterinary}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">{admin.status_label}</label>
                        <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v); setPage(1); }}>
                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{admin.all_statuses}</SelectItem>
                                <SelectItem value="active">{common.active}</SelectItem>
                                <SelectItem value="inactive">{common.inactive}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="lg:col-span-2">
                        <label className="mb-1.5 block text-sm font-medium">{admin.search}</label>
                        <Input placeholder={admin.search_by_arabic_english_name} value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} />
                    </div>
                </CardContent>
            </Card>

            <div>
                <DataTable columns={columns} rows={rows} status={status} errorMessage={errorMessage} onRetry={reload} emptyTitle={admin.js_no_syndicates_match_filters} />
                {status === 'ready' && rows.length > 0 && (
                    <div className="rounded-b-lg border border-t-0 border-border">
                        <Pagination meta={meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                    </div>
                )}
            </div>

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={common.delete}
                description={admin.js_confirm_delete_syndicate}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
