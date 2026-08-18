import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Plus, Check } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DataTable } from '@/Components/admin/DataTable';
import { Pagination } from '@/Components/admin/Pagination';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
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
import { VendorMapPanel } from '@/Components/maps/VendorMapPanel';
import { ViewSwitch } from '@/Components/maps/ViewSwitch';
import { useAdminList } from '@/hooks/use-admin-list';
import { useI18n } from '@/hooks/use-i18n';

export default function VendorsIndex() {
    const { admin, common } = useI18n();
    const [view, setView] = useState('table');
    const [page, setPage] = useState(1);
    const [status, setStatusFilter] = useState('all');
    const [businessType, setBusinessType] = useState('all');
    const [categoryType, setCategoryType] = useState('all');
    const [categoryId, setCategoryId] = useState('all');
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [categories, setCategories] = useState([]);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [flash, setFlash] = useState(null);

    const { status: loadStatus, rows, meta, errorMessage, reload } = useAdminList('/api/admin/vendors', {
        page,
        status: status === 'all' ? undefined : status,
        business_type: businessType === 'all' ? undefined : businessType,
        category_type: categoryType === 'all' ? undefined : categoryType,
        category_id: categoryId === 'all' ? undefined : categoryId,
        name: name || undefined,
        email: email || undefined,
    });

    useEffect(() => {
        window.axios.get('/api/admin/categories', { silent: true }).then((res) => setCategories(res.data?.data ?? []));
    }, []);

    const showFlash = (message) => {
        setFlash(message);
        setTimeout(() => setFlash(null), 4000);
    };

    const toggleActive = (vendor) => {
        window.axios.patch(`/api/admin/vendors/${vendor.id}/toggle-active`, {}, { silent: true }).then((res) => {
            showFlash(res.data.message);
            reload();
        });
    };

    const approve = (vendor) => {
        window.axios.patch(`/api/admin/vendors/${vendor.id}/approve`, {}, { silent: true }).then((res) => {
            showFlash(res.data.message);
            reload();
        });
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios.delete(`/api/admin/vendors/${deleteTarget.id}`, { silent: true }).then(() => {
            setIsDeleting(false);
            setDeleteTarget(null);
            reload();
        }).catch(() => setIsDeleting(false));
    };

    const columns = [
        {
            key: 'store',
            label: admin.th_store,
            render: (row) => (
                <div className="flex items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-md bg-accent text-xs font-bold text-accent-foreground">
                        {(row.store_name || '?').charAt(0).toUpperCase()}
                    </span>
                    <div className="min-w-0">
                        <p className="truncate font-semibold text-foreground">{row.store_name}</p>
                        <p className="truncate text-xs text-muted-foreground">{row.user?.name ?? '—'}</p>
                    </div>
                </div>
            ),
        },
        { key: 'owner', label: admin.th_owner, render: (row) => row.user?.name ?? '—' },
        {
            key: 'type',
            label: admin.th_type,
            render: (row) => <StatusBadge tone="brand">{row.business_type_label ?? row.business_type ?? admin.both}</StatusBadge>,
        },
        {
            key: 'status',
            label: admin.th_status,
            render: (row) =>
                row.status === 'pending' ? (
                    <StatusBadge tone="warning">{common.pending ?? 'Pending'}</StatusBadge>
                ) : (
                    <button type="button" onClick={() => toggleActive(row)} title={admin.title_click_to_toggle}>
                        <StatusBadge tone={row.is_active ? 'success' : 'danger'}>{row.is_active ? common.active : common.inactive}</StatusBadge>
                    </button>
                ),
        },
        {
            key: 'actions',
            label: admin.th_actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex flex-wrap items-center justify-end gap-1.5">
                    {row.status === 'pending' && (
                        <Button size="sm" onClick={() => approve(row)} title={admin.title_approve_vendor}>
                            <Check className="size-3.5" />
                            {common.approve ?? 'Approve'}
                        </Button>
                    )}
                    <Button asChild variant="ghost" size="sm">
                        <Link href={route('admin.vendors.show', row.id)}>{common.view_details ?? 'View'}</Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('admin.vendors.edit', row.id)}>{common.edit ?? 'Edit'}</Link>
                    </Button>
                    <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>
                        {common.delete ?? 'Delete'}
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={admin.manage_vendors_title}>
            <PageHeader
                title={admin.manage_vendors_title}
                copy={admin.manage_vendor_accounts_copy}
                actions={
                    <>
                        <ViewSwitch view={view} onChange={setView} />
                        <Button asChild size="sm">
                            <Link href={route('admin.vendors.create')}>
                                <Plus className="size-4" />
                                {admin.add_vendor}
                            </Link>
                        </Button>
                    </>
                }
            />

            {flash && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{flash}</p>}

            {view === 'map' && <VendorMapPanel endpoint="/api/admin/vendors/map" />}

            {view === 'table' && (
            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-4 sm:grid-cols-3 lg:grid-cols-6">
                    <FilterSelect label={admin.status_label} value={status} onValueChange={(v) => { setStatusFilter(v); setPage(1); }} allLabel={admin.all_statuses} options={[{ value: 'pending', label: common.pending }, { value: 'active', label: common.active }, { value: 'inactive', label: common.inactive }]} />
                    <FilterSelect label={admin.business_type_label} value={businessType} onValueChange={(v) => { setBusinessType(v); setPage(1); }} allLabel={admin.all_business_types} options={[{ value: 'agriculture', label: admin.type_agriculture }, { value: 'veterinary', label: admin.type_veterinary }, { value: 'both', label: admin.both }]} />
                    <FilterSelect label={admin.category_type_label} value={categoryType} onValueChange={(v) => { setCategoryType(v); setPage(1); }} allLabel={admin.all_category_types} options={[{ value: 'agriculture', label: admin.type_agriculture }, { value: 'veterinary', label: admin.type_veterinary }]} />
                    <FilterSelect label={admin.category_label} value={categoryId} onValueChange={(v) => { setCategoryId(v); setPage(1); }} allLabel={admin.all_categories} options={categories.map((c) => ({ value: String(c.id), label: c.name }))} />
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">{admin.name_label}</label>
                        <Input placeholder={admin.store_or_owner_name} value={name} onChange={(e) => { setName(e.target.value); setPage(1); }} />
                    </div>
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">{admin.email_label}</label>
                        <Input type="search" placeholder="owner@example.com" value={email} onChange={(e) => { setEmail(e.target.value); setPage(1); }} />
                    </div>
                </CardContent>
            </Card>
            )}

            {view === 'table' && (
            <div>
                <DataTable
                    columns={columns}
                    rows={rows}
                    status={loadStatus}
                    errorMessage={errorMessage}
                    onRetry={reload}
                    emptyTitle={admin.no_vendors_yet}
                    emptyHint={admin.get_started_create_vendor}
                />
                {loadStatus === 'ready' && rows.length > 0 && (
                    <div className="rounded-b-lg border border-t-0 border-border">
                        <Pagination meta={meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                    </div>
                )}
            </div>
            )}

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={admin.delete_vendor_title}
                description={admin.delete_vendor_warning}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}

function FilterSelect({ label, value, onValueChange, allLabel, options }) {
    return (
        <div>
            <label className="mb-1.5 block text-sm font-medium">{label}</label>
            <Select value={value} onValueChange={onValueChange}>
                <SelectTrigger className="w-full">
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
