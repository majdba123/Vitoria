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
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency, formatDate } from '@/lib/date-time';

const TYPE_TONES = { 0: 'brand', 1: 'warning', 2: 'brand', 3: 'warning', 4: 'success' };

export default function UsersIndex() {
    const { admin, common } = useI18n();
    const locale = useLocale();
    const filterType = new URLSearchParams(window.location.search).get('type') || '';
    const isEmployeeView = filterType === '4';
    const isCustomerView = filterType === '0';
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

    const createHref = isEmployeeView
        ? route('admin.users.create', { type: 4 })
        : isCustomerView
          ? route('admin.users.create', { type: 0 })
          : route('admin.users.create');
    const addLabel = isEmployeeView ? admin.add_employee : isCustomerView ? admin.add_customer : admin.add_user;

    const nameColumn = {
        key: 'user',
        label: admin.name_label,
        width: isCustomerView ? '18%' : undefined,
        truncate: true,
        render: (row) => (
            <div className="flex items-center gap-3">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-bold text-accent-foreground">
                    {(row.name || '?').charAt(0).toUpperCase()}
                </span>
                <div className="min-w-0">
                    <p className="truncate font-semibold text-foreground">{row.name}</p>
                    {!isCustomerView && <p className="truncate text-xs text-muted-foreground">{row.email || '-'}</p>}
                </div>
            </div>
        ),
    };

    const columns = isCustomerView
        ? [
              nameColumn,
              {
                  key: 'contact',
                  label: admin.th_contact,
                  truncate: true,
                  render: (row) => (
                      <div className="min-w-0">
                          <p className="truncate text-sm text-foreground">{row.email || '-'}</p>
                          <p className="truncate font-mono text-xs text-muted-foreground">{row.phone_number || '-'}</p>
                      </div>
                  ),
              },
              { key: 'city', label: admin.th_city, truncate: true, render: (row) => row.city?.name || '-' },
              { key: 'registered', label: admin.th_registered, render: (row) => formatDate(row.created_at, locale) },
              {
                  key: 'account_status',
                  label: admin.th_account_status,
                  align: 'center',
                  render: (row) => (
                      <StatusBadge tone={row.email_verified_at ? 'success' : 'warning'}>{row.email_verified_at ? admin.status_verified : admin.status_unverified}</StatusBadge>
                  ),
              },
              { key: 'orders_count', label: admin.th_orders_count, align: 'end', render: (row) => row.orders_count ?? 0 },
              { key: 'total_purchases', label: admin.th_total_purchases, align: 'end', render: (row) => formatCurrency(row.total_purchases ?? 0, locale) },
              { key: 'last_order', label: admin.th_last_order, render: (row) => (row.last_order_at ? formatDate(row.last_order_at, locale) : '-') },
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
          ]
        : [
              nameColumn,
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
        <AdminLayout title={isEmployeeView ? admin.employees : isCustomerView ? admin.customers : admin.users}>
            <PageHeader
                title={isEmployeeView ? admin.manage_employees_title : isCustomerView ? admin.manage_customers_title : admin.manage_users_title}
                copy={isEmployeeView ? admin.manage_employees_copy : isCustomerView ? admin.manage_customers_copy : admin.manage_users_copy}
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
                    emptyTitle={isEmployeeView ? admin.no_employees_yet : isCustomerView ? admin.no_customers_yet : admin.no_users_yet}
                    emptyHint={isEmployeeView ? admin.create_employee_hint : isCustomerView ? admin.create_customer_hint : admin.create_user_hint}
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
