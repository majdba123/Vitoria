import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { StatusMessage } from '@/Components/admin/StatusMessage';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Button } from '@/Components/ui/button';
import { useAdminList } from '@/hooks/use-admin-list';
import { useAdminPermissions } from '@/hooks/use-admin-permissions';
import { useI18n } from '@/hooks/use-i18n';

export default function RolesIndex() {
    const { admin, common } = useI18n();
    const { can } = useAdminPermissions();
    const { status, rows, errorMessage, reload } = useAdminList('/api/admin/roles');
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [message, setMessage] = useState(null);

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios
            .delete(`/api/admin/roles/${deleteTarget.id}`, { silent: true })
            .then(() => {
                setMessage({ tone: 'success', text: admin.rbac_role_deleted });
                setDeleteTarget(null);
                reload();
            })
            .catch((error) => {
                setMessage({ tone: 'error', text: error.response?.data?.message || admin.rbac_load_error });
                setDeleteTarget(null);
            })
            .finally(() => setIsDeleting(false));
    };

    const columns = [
        {
            key: 'role',
            label: admin.rbac_th_role,
            truncate: true,
            render: (row) => (
                <div className="min-w-0">
                    <p className="truncate font-semibold text-foreground">{row.name}</p>
                    <p className="truncate font-mono text-xs text-muted-foreground">{row.key}</p>
                </div>
            ),
        },
        {
            key: 'type',
            label: admin.rbac_th_type,
            render: (row) =>
                row.is_super_admin ? (
                    <StatusBadge tone="warning">{admin.rbac_super_admin_badge}</StatusBadge>
                ) : (
                    <StatusBadge tone={row.is_system ? 'brand' : 'success'}>{row.is_system ? admin.rbac_system_role : admin.rbac_custom_role}</StatusBadge>
                ),
        },
        { key: 'permissions', label: admin.rbac_th_permissions, align: 'end', render: (row) => row.permissions_count },
        { key: 'users', label: admin.rbac_th_users, align: 'end', render: (row) => row.users_count },
        {
            key: 'actions',
            label: admin.actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex items-center gap-1.5">
                    {row.can_edit && can('roles.manage') && (
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('admin.roles.edit', row.id)}>{common.edit}</Link>
                        </Button>
                    )}
                    {!row.is_super_admin && can('roles.manage') && (
                        <Button asChild variant="ghost" size="sm">
                            <Link href={route('admin.roles.create', { clone: row.id })}>{admin.rbac_clone_role}</Link>
                        </Button>
                    )}
                    {row.can_delete && can('roles.manage') && (
                        <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>
                            {common.delete}
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={admin.rbac_roles}>
            <PageHeader
                title={admin.rbac_roles}
                copy={admin.rbac_roles_copy}
                actions={
                    can('roles.manage') && (
                        <Button asChild size="sm">
                            <Link href={route('admin.roles.create')}>
                                <Plus className="size-4" aria-hidden="true" />
                                {admin.rbac_new_role}
                            </Link>
                        </Button>
                    )
                }
            />

            {message && <StatusMessage tone={message.tone}>{message.text}</StatusMessage>}

            <DataTable
                columns={columns}
                rows={rows}
                status={status}
                errorMessage={errorMessage}
                onRetry={reload}
                emptyTitle={admin.rbac_no_roles}
                emptyHint={admin.rbac_no_roles_hint}
            />

            <DeleteConfirmDialog
                open={!!deleteTarget}
                onOpenChange={(open) => !open && setDeleteTarget(null)}
                title={admin.rbac_delete_role_title}
                description={(admin.rbac_delete_role_warning ?? '').replace(':name', deleteTarget?.name ?? '')}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
