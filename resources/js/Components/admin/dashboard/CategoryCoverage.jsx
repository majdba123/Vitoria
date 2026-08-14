import { Link } from '@inertiajs/react';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { useI18n } from '@/hooks/use-i18n';

/**
 * Vendor coverage per category: a three-segment bar (active/pending/
 * inactive) plus counts, replacing the hand-built <div style="width:%">
 * bars in the original dashboard script.
 */
export function CategoryCoverage({ rows }) {
    const { admin } = useI18n();

    return (
        <div className="space-y-3">
            {rows.map((row) => {
                const total = Number(row.total_vendors || 0);
                const active = Number(row.active_vendors || 0);
                const pending = Number(row.pending_vendors || 0);
                const inactive = Number(row.inactive_vendors || 0);
                const activeWidth = total > 0 ? Math.round((active / total) * 100) : 0;
                const pendingWidth = total > 0 ? Math.round((pending / total) * 100) : 0;
                const inactiveWidth = Math.max(0, 100 - activeWidth - pendingWidth);
                const href = row.id ? route('admin.vendors.index', { category_id: row.id }) : route('admin.vendors.index');

                return (
                    <Link
                        key={row.id ?? 'unassigned'}
                        href={href}
                        className="block rounded-md border border-border/70 bg-card p-4 transition-colors hover:border-primary/50"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="min-w-0">
                                <p className="truncate text-sm font-semibold text-foreground">{row.name}</p>
                                <p className="mt-0.5 text-xs text-muted-foreground">
                                    {(admin.vendors_total_label ?? ':count vendors total').replace(':count', String(total))}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-1.5">
                                <StatusBadge tone="success">
                                    {active} {admin.status_active}
                                </StatusBadge>
                                <StatusBadge tone="warning">
                                    {pending} {admin.status_pending}
                                </StatusBadge>
                                <StatusBadge tone="danger">
                                    {inactive} {admin.status_inactive}
                                </StatusBadge>
                            </div>
                        </div>
                        <div className="mt-3 flex h-1.5 overflow-hidden rounded-full bg-muted">
                            <div style={{ width: `${activeWidth}%`, background: 'var(--color-success-500)' }} />
                            <div style={{ width: `${pendingWidth}%`, background: 'var(--color-warning-500)' }} />
                            <div style={{ width: `${inactiveWidth}%`, background: 'var(--color-danger-500)' }} />
                        </div>
                    </Link>
                );
            })}
        </div>
    );
}
