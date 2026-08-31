import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { Pagination } from '@/Components/shared/Pagination';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Button } from '@/Components/ui/button';
import { useAdminList } from '@/hooks/use-admin-list';
import { useI18n } from '@/hooks/use-i18n';

const TYPE_TONES = { 0: 'brand', 1: 'warning', 2: 'brand', 3: 'warning', 4: 'success' };

export default function UsersIndex() {
    const { admin, common } = useI18n();
    const filterType = new URLSearchParams(window.location.search).get('type') || '';
    const isEmployeeView = filterType === '4';
    const [page, setPage] = useState(1);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const { status, rows, meta, errorMessage, reload } = useAdminList('/api/admin/users', { page, type: filterType || undefined });

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios.delete(`/api/admin/users/${deleteTarget.id}`, { silent: true }).then(() => {
            setIsDeleting(false);
            setDeleteTarget(null);
            reload();
        }).catch(() => setIsDeleting(false));
    };

    const createHref = isEmployeeView ? route('admin.users.create', { type: 4 }) : route('admin.users.create');
    const addLabel = isEmployeeView ? admin.add_employee : admin.add_user;

    const columns = [
        {
            key: 'user',
            label: admin.name_label,
            render: (row) => (
                <div className="flex items-center gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-bold text-accent-foreground">
                        {(row.name || '?').charAt(0).toUpperCase()}
                    </span>
                    <div className="min-w-0">
                        <p className="truncate font-semibold text-foreground">{row.name}</p>
                        <p className="truncate text-xs text-muted-foreground">{row.email || '-'}</p>
                    </div>
                </div>
            ),
        },
        { key: 'phone', label: admin.th_phone, render: (row) => <span className="font-mono text-sm">{row.phone_number || '-'}</span> },
        { key: 'national_id', label: admin.th_national_id, render: (row) => <span className="font-mono text-xs text-muted-foreground">{row.national_id || '-'}</span> },
        { key: 'type', label: admin.type_label, render: (row) => <StatusBadge tone={TYPE_TONES[row.type] ?? 'brand'}>{admin.user_type_labels?.[row.type] ?? admin.user_type_labels?.[0]}</StatusBadge> },
        {
            key: 'actions',
            label: admin.actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex items-center gap-1.5">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={route('admin.users.show', row.id)}>{admin.view}</Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('admin.users.edit', row.id)}>{common.edit}</Link>
                    </Button>
                    <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>
                        {common.delete}
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={isEmployeeView ? admin.employees : admin.users}>
            <PageHeader
                title={isEmployeeView ? admin.manage_employees_title : admin.manage_users_title}
                copy={isEmployeeView ? admin.manage_employees_copy : admin.manage_users_copy}
                actions={
                    <Button asChild size="sm">
                        <Link href={createHref}>
                            <Plus className="size-4" />
                            {addLabel}
                        </Link>
                    </Button>
                }
            />

            <div>
                <DataTable
                    columns={columns}
                    rows={rows}
                    status={status}
                    errorMessage={errorMessage}
                    onRetry={reload}
                    emptyTitle={isEmployeeView ? admin.no_employees_yet : admin.no_users_yet}
                    emptyHint={isEmployeeView ? admin.create_employee_hint : admin.create_user_hint}
                />
                {status === 'ready' && rows.length > 0 && (
                    <div className="rounded-b-lg border border-t-0 border-border">
                        <Pagination meta={meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                    </div>
                )}
            </div>

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={admin.delete_user_title}
                description={admin.delete_user_warning}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
