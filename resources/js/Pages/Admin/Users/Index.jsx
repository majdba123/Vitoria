import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DataTable } from '@/Components/admin/DataTable';
import { Pagination } from '@/Components/admin/Pagination';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Button } from '@/Components/ui/button';
import { useAdminList } from '@/hooks/use-admin-list';

const TYPE_LABELS = { 0: 'User', 1: 'Admin', 2: 'Vendor', 3: 'Syndicate', 4: 'Employee' };
const TYPE_TONES = { 0: 'brand', 1: 'warning', 2: 'brand', 3: 'warning', 4: 'success' };

export default function UsersIndex() {
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
    const addLabel = isEmployeeView ? 'Add Employee' : 'Add User';

    const columns = [
        {
            key: 'user',
            label: 'User',
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
        { key: 'phone', label: 'Phone', render: (row) => <span className="font-mono text-sm">{row.phone_number || '-'}</span> },
        { key: 'national_id', label: 'National ID', render: (row) => <span className="font-mono text-xs text-muted-foreground">{row.national_id || '-'}</span> },
        { key: 'type', label: 'Type', render: (row) => <StatusBadge tone={TYPE_TONES[row.type] ?? 'brand'}>{TYPE_LABELS[row.type] ?? 'User'}</StatusBadge> },
        {
            key: 'actions',
            label: 'Actions',
            align: 'end',
            render: (row) => (
                <div className="inline-flex items-center gap-1.5">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={route('admin.users.show', row.id)}>View</Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('admin.users.edit', row.id)}>Edit</Link>
                    </Button>
                    <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>
                        Delete
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={isEmployeeView ? 'Employees' : 'Users'}>
            <PageHeader
                title={isEmployeeView ? 'Manage employee accounts' : 'Manage all user accounts'}
                copy={isEmployeeView ? 'Create, review, and update employee access.' : 'Manage all user accounts.'}
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
                    emptyTitle={isEmployeeView ? 'No employees yet' : 'No users yet'}
                    emptyHint={isEmployeeView ? 'Create an employee account to get started.' : 'Get started by creating a new user.'}
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
                title="Delete user"
                description="This will permanently delete this user account and all their tokens."
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
