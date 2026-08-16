import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Plus, Store, Package, Users, ArrowRight } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Button } from '@/Components/ui/button';
import { StatCard } from '@/Components/admin/dashboard/StatCard';
import { InsightPanel, ViewAllLink } from '@/Components/admin/dashboard/InsightPanel';
import { ListRow, StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { MetricTileGrid } from '@/Components/admin/dashboard/MetricTileGrid';
import { GrowthChart } from '@/Components/admin/dashboard/GrowthChart';
import { CategoryCoverage } from '@/Components/admin/dashboard/CategoryCoverage';
import { useAdminDashboard } from '@/hooks/use-admin-dashboard';
import { useI18n } from '@/hooks/use-i18n';

function combinedStatus(...statuses) {
    if (statuses.includes('ready')) return 'ready';
    if (statuses.includes('loading')) return 'loading';
    return 'error';
}

function typeLabel(admin, type, fallback) {
    if (type === 'agriculture') return admin.type_agriculture ?? fallback ?? type;
    if (type === 'veterinary') return admin.type_veterinary ?? fallback ?? type;
    if (type === 'both') return admin.type_both ?? fallback ?? type;

    return fallback ?? type ?? '';
}

export default function Dashboard() {
    const { admin } = useI18n();
    const { overview, categoryStats, users, vendors, products } = useAdminDashboard();
    const [rangeMonths, setRangeMonths] = useState(12);

    const changeRange = (months) => {
        setRangeMonths(months);
        overview.refetch({ range_months: months });
    };

    const overviewData = overview.data?.data ?? null;
    const vendorsData = vendors.data;
    const productsData = products.data;

    const vendorsTotal = overviewData?.total_vendors ?? vendorsData?.meta?.total ?? 0;
    const activeVendors = overviewData?.active_vendors ?? (vendorsData?.data ?? []).filter((v) => v.is_active).length;
    const inactiveVendors = overviewData?.inactive_vendors ?? Math.max(0, vendorsTotal - activeVendors);
    const productsTotal = overviewData?.total_products ?? productsData?.meta?.total ?? 0;
    const recentProducts = overviewData?.recent_products ?? productsData?.data ?? [];
    const activeProducts = overviewData?.active_products ?? recentProducts.filter((p) => p.is_active).length;
    const usersTotal = users.data?.meta?.total ?? 0;
    const syndicatesTotal = overviewData?.total_syndicates ?? 0;

    const vendorsStatCardStatus = combinedStatus(overview.status, vendors.status);
    const productsStatCardStatus = combinedStatus(overview.status, products.status);

    const metricUrl = {
        vendor: (type) => route('admin.vendors.index', { business_type: type }),
        syndicate: (type) => route('admin.syndicates.index', { type }),
        product: (type) => route('admin.products.index', { category_type: type }),
        category: (type) => route('admin.categories.index', { type }),
    };

    const withLabels = (rows, admin) => (rows ?? []).map((r) => ({ ...r, label: typeLabel(admin, r.type, r.label) }));

    return (
        <AdminLayout title={admin.dashboard}>
            {/* Page header */}
            <section className="flex flex-col gap-4 rounded-lg border border-border/80 bg-card px-5 py-5 sm:px-6 sm:py-6 lg:flex-row lg:items-start lg:justify-between">
                <div className="min-w-0">
                    <p className="text-[11px] font-bold uppercase tracking-[0.24em] text-primary">{admin.badge}</p>
                    <h2 className="mt-2 text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{admin.dashboard_title}</h2>
                    <p className="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground">{admin.dashboard_copy}</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button asChild size="sm">
                        <Link href={route('admin.vendors.create')}>
                            <Plus className="size-4" />
                            {admin.add_vendor}
                        </Link>
                    </Button>
                    <Button asChild size="sm" variant="outline">
                        <Link href={route('admin.users.create')}>
                            <Plus className="size-4" />
                            {admin.add_user}
                        </Link>
                    </Button>
                </div>
            </section>

            {/* Platform snapshot */}
            <section className="space-y-4">
                <div>
                    <h3 className="text-sm font-bold text-foreground">{admin.stats_section_title}</h3>
                    <p className="mt-0.5 text-xs text-muted-foreground">{admin.stats_section_copy}</p>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 @4xl/main:grid-cols-4">
                    <StatCard label={admin.total_vendors} value={vendorsTotal} icon={Store} status={vendorsStatCardStatus} onRetry={vendors.refetch} />
                    <StatCard label={admin.total_products} value={productsTotal} icon={Package} status={productsStatCardStatus} onRetry={products.refetch} />
                    <StatCard label={admin.total_users} value={usersTotal} icon={Users} status={users.status} onRetry={users.refetch} />
                    <StatCard label={admin.total_syndicates} value={syndicatesTotal} icon={Users} status={overview.status} onRetry={overview.refetch} />
                </div>

                <div className="flex flex-wrap gap-y-3 border-t border-border/70 pt-4">
                    {[
                        { label: admin.active_vendors, value: activeVendors, tone: 'success' },
                        { label: admin.inactive_vendors, value: inactiveVendors, tone: 'danger' },
                        { label: admin.active_products, value: activeProducts, tone: 'success' },
                    ].map((item, index) => (
                        <div key={item.label} className={`flex flex-col gap-1 px-4 py-1 ${index === 0 ? 'ps-0' : 'border-s border-border/70'}`}>
                            <span className="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{item.label}</span>
                            <span
                                className={`text-base font-bold tabular-nums ${item.tone === 'success' ? 'text-[var(--color-success-strong)]' : item.tone === 'danger' ? 'text-[var(--color-danger-strong)]' : 'text-foreground'}`}
                            >
                                {overview.status === 'error' && overviewData === null ? '—' : item.value}
                            </span>
                        </div>
                    ))}
                </div>
            </section>

            {/* Business insights */}
            <section className="space-y-4">
                <div>
                    <h3 className="text-sm font-bold text-foreground">{admin.insights_section_title}</h3>
                    <p className="mt-0.5 text-xs text-muted-foreground">{admin.insights_section_copy}</p>
                </div>

                <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <InsightPanel
                        title={admin.vendors_by_type_title}
                        copy={admin.vendors_by_type_copy}
                        status={overview.status}
                        isEmpty={!(overviewData?.vendors_by_type ?? []).length}
                        emptyMessage={admin.no_metric_data}
                        onRetry={overview.refetch}
                    >
                        <MetricTileGrid rows={withLabels(overviewData?.vendors_by_type, admin)} hrefFor={metricUrl.vendor} />
                    </InsightPanel>

                    <InsightPanel
                        title={admin.categories_by_type_title}
                        copy={admin.categories_by_type_copy}
                        status={overview.status}
                        isEmpty={!(overviewData?.categories_by_type ?? []).length}
                        emptyMessage={admin.no_metric_data}
                        onRetry={overview.refetch}
                    >
                        <MetricTileGrid rows={withLabels(overviewData?.categories_by_type, admin)} hrefFor={metricUrl.category} columns={2} />
                    </InsightPanel>

                    <InsightPanel
                        title={admin.syndicates_by_type_title}
                        copy={admin.syndicates_by_type_copy}
                        status={overview.status}
                        isEmpty={!(overviewData?.syndicates_by_type ?? []).length}
                        emptyMessage={admin.no_metric_data}
                        onRetry={overview.refetch}
                    >
                        <MetricTileGrid rows={withLabels(overviewData?.syndicates_by_type, admin)} hrefFor={metricUrl.syndicate} columns={2} />
                    </InsightPanel>

                    <InsightPanel
                        title={admin.recent_syndicate_agents_title}
                        copy={admin.recent_syndicate_agents_copy}
                        status={overview.status}
                        isEmpty={!(overviewData?.recent_syndicate_agents ?? []).length}
                        emptyMessage={admin.no_syndicate_agents_yet}
                        onRetry={overview.refetch}
                    >
                        <div className="space-y-2">
                            {(overviewData?.recent_syndicate_agents ?? []).map((syndicate) => (
                                <ListRow
                                    key={syndicate.id}
                                    href={route('admin.syndicates.show', syndicate.id)}
                                    title={syndicate.name}
                                    subtitle={`${syndicate.user?.email ?? ''} · ${typeLabel(admin, syndicate.type, syndicate.type_label)}`}
                                    trailing={<StatusBadge tone={syndicate.status === 'active' ? 'success' : 'danger'}>{syndicate.status === 'active' ? admin.status_active : admin.status_inactive}</StatusBadge>}
                                />
                            ))}
                        </div>
                    </InsightPanel>

                    <InsightPanel
                        title={admin.products_by_type_title}
                        copy={admin.products_by_type_copy}
                        status={overview.status}
                        isEmpty={!(overviewData?.products_by_category_type ?? []).length}
                        emptyMessage={admin.no_metric_data}
                        onRetry={overview.refetch}
                    >
                        <MetricTileGrid rows={withLabels(overviewData?.products_by_category_type, admin)} hrefFor={metricUrl.product} columns={2} />
                    </InsightPanel>

                    <InsightPanel
                        title={admin.monthly_product_growth_title}
                        copy={admin.monthly_product_growth_copy}
                        status={overview.status}
                        isEmpty={!(overviewData?.monthly_product_growth ?? []).length}
                        emptyMessage={admin.no_growth_data}
                        onRetry={() => overview.refetch({ range_months: rangeMonths })}
                        rows={1}
                    >
                        <div className="mb-3 flex items-center justify-end gap-1.5" role="group" aria-label="Chart period">
                            {[3, 6, 12].map((months) => (
                                <button
                                    key={months}
                                    type="button"
                                    onClick={() => changeRange(months)}
                                    aria-pressed={rangeMonths === months}
                                    className={`rounded-md px-2.5 py-1 text-xs font-semibold transition-colors ${rangeMonths === months ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'}`}
                                >
                                    {months}mo
                                </button>
                            ))}
                        </div>
                        <GrowthChart rows={overviewData?.monthly_product_growth ?? []} totalLabel={admin.total_products} />
                    </InsightPanel>
                </div>

                <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <InsightPanel
                        title={admin.top_vendors_title}
                        copy={admin.top_vendors_copy}
                        status={overview.status}
                        isEmpty={!(overviewData?.top_vendors_by_product_count ?? []).length}
                        emptyMessage={admin.no_top_vendors_yet}
                        onRetry={overview.refetch}
                    >
                        <div className="space-y-2">
                            {(overviewData?.top_vendors_by_product_count ?? []).map((vendor) => (
                                <ListRow
                                    key={vendor.id}
                                    href={route('admin.vendors.show', vendor.id)}
                                    title={vendor.store_name}
                                    subtitle={typeLabel(admin, vendor.business_type, vendor.business_type_label)}
                                    trailing={
                                        <StatusBadge tone="brand">
                                            {(admin.products_count_label ?? ':count products').replace(':count', String(vendor.products_count ?? 0))}
                                        </StatusBadge>
                                    }
                                />
                            ))}
                        </div>
                    </InsightPanel>

                    <InsightPanel
                        title={admin.most_selected_categories_title}
                        copy={admin.most_selected_categories_copy}
                        status={overview.status}
                        isEmpty={!(overviewData?.most_selected_categories ?? []).length}
                        emptyMessage={admin.no_category_preferences_yet}
                        onRetry={overview.refetch}
                    >
                        <div className="space-y-2">
                            {(overviewData?.most_selected_categories ?? []).map((category) => (
                                <ListRow
                                    key={category.id}
                                    href={route('admin.vendors.index', { category_id: category.id })}
                                    title={category.name}
                                    subtitle={typeLabel(admin, category.type, category.type_label)}
                                    trailing={
                                        <StatusBadge tone="brand">
                                            {(admin.vendors_count_label ?? ':count vendors').replace(':count', String(category.vendors_count ?? 0))}
                                        </StatusBadge>
                                    }
                                />
                            ))}
                        </div>
                    </InsightPanel>

                    <InsightPanel
                        title={admin.categories_without_products_title}
                        copy={admin.categories_without_products_copy}
                        status={overview.status}
                        isEmpty={!(overviewData?.categories_with_no_products ?? []).length}
                        emptyMessage={admin.no_gap_data}
                        onRetry={overview.refetch}
                    >
                        <div className="space-y-2">
                            {(overviewData?.categories_with_no_products ?? []).slice(0, 8).map((category) => (
                                <ListRow
                                    key={category.id}
                                    href={route('admin.categories.show', category.id)}
                                    title={category.name}
                                    subtitle={typeLabel(admin, category.type, category.type_label)}
                                    trailing={<StatusBadge tone="danger">{category.products_count ?? 0}</StatusBadge>}
                                />
                            ))}
                        </div>
                    </InsightPanel>

                    <InsightPanel
                        title={admin.categories_without_vendors_title}
                        copy={admin.categories_without_vendors_copy}
                        status={overview.status}
                        isEmpty={!(overviewData?.categories_with_no_vendors ?? []).length}
                        emptyMessage={admin.no_gap_data}
                        onRetry={overview.refetch}
                    >
                        <div className="space-y-2">
                            {(overviewData?.categories_with_no_vendors ?? []).slice(0, 8).map((category) => (
                                <ListRow
                                    key={category.id}
                                    href={route('admin.categories.show', category.id)}
                                    title={category.name}
                                    subtitle={typeLabel(admin, category.type, category.type_label)}
                                    trailing={<StatusBadge tone="danger">{category.vendors_count ?? 0}</StatusBadge>}
                                />
                            ))}
                        </div>
                    </InsightPanel>
                </div>
            </section>

            {/* Coverage + recent products */}
            <section className="grid grid-cols-1 gap-4 xl:grid-cols-[1.3fr_1fr]">
                <InsightPanel
                    title={admin.vendors_by_category_title}
                    copy={admin.vendors_by_category_copy}
                    action={<ViewAllLink href={route('admin.vendors.index')}>{admin.view_vendors}</ViewAllLink>}
                    status={categoryStats.status}
                    isEmpty={!(categoryStats.data?.data ?? []).length}
                    emptyMessage={admin.no_vendor_categories_yet}
                    onRetry={categoryStats.refetch}
                >
                    <CategoryCoverage rows={categoryStats.data?.data ?? []} />
                </InsightPanel>

                <InsightPanel
                    title={admin.recent_products_title}
                    copy={admin.recent_products_copy}
                    action={<ViewAllLink href={route('admin.products.index')}>{admin.show_all}</ViewAllLink>}
                    status={combinedStatus(overview.status, products.status)}
                    isEmpty={!recentProducts.length}
                    emptyMessage={admin.no_products_yet}
                    onRetry={overview.status === 'error' ? overview.refetch : products.refetch}
                >
                    <div className="space-y-2">
                        {recentProducts.map((product) => (
                            <ListRow
                                key={product.id}
                                href={route('admin.products.show', product.id)}
                                title={product.name}
                                subtitle={product.category?.name || product.status || ''}
                                trailing={<StatusBadge tone={product.is_active ? 'success' : 'danger'}>{product.is_active ? admin.status_active : admin.status_inactive}</StatusBadge>}
                            />
                        ))}
                    </div>
                </InsightPanel>
            </section>

            {/* Recent activity */}
            <section className="space-y-4">
                <div>
                    <h3 className="text-sm font-bold text-foreground">{admin.recent_activity_title}</h3>
                    <p className="mt-0.5 text-xs text-muted-foreground">{admin.recent_activity_copy}</p>
                </div>

                <InsightPanel
                    title={admin.recent_vendors_title}
                    copy={admin.recent_vendors_copy}
                    status={overview.status}
                    isEmpty={!(overviewData?.recent_vendor_registrations ?? []).length}
                    emptyMessage={admin.no_vendors_yet}
                    onRetry={overview.refetch}
                >
                    <div className="space-y-2">
                        {(overviewData?.recent_vendor_registrations ?? []).map((vendor) => (
                            <ListRow
                                key={vendor.id}
                                href={route('admin.vendors.show', vendor.id)}
                                title={vendor.store_name}
                                subtitle={`${vendor.user?.name ?? ''} · ${typeLabel(admin, vendor.business_type, vendor.business_type_label)}`}
                                trailing={
                                    <StatusBadge tone={vendor.status === 'pending' ? 'warning' : vendor.is_active ? 'success' : 'danger'}>
                                        {vendor.status === 'pending' ? admin.status_pending : vendor.is_active ? admin.status_active : admin.status_inactive}
                                    </StatusBadge>
                                }
                            />
                        ))}
                    </div>
                </InsightPanel>
            </section>

            {/* Quick actions */}
            <section className="space-y-4">
                <div>
                    <h3 className="text-sm font-bold text-foreground">{admin.actions_section_title}</h3>
                    <p className="mt-0.5 text-xs text-muted-foreground">{admin.actions_section_copy}</p>
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    {[
                        { href: route('admin.vendors.index'), icon: Store, title: admin.manage_vendors_title, copy: admin.manage_vendors_copy },
                        { href: route('admin.users.index'), icon: Users, title: admin.manage_users_title, copy: admin.manage_users_copy },
                        { href: route('admin.products.index'), icon: Package, title: admin.manage_products_title, copy: admin.manage_products_copy },
                    ].map((action) => (
                        <Link
                            key={action.title}
                            href={action.href}
                            className="group flex items-center justify-between gap-4 rounded-lg border border-border/80 bg-card p-4 transition-colors hover:border-primary/50 hover:bg-accent/40"
                        >
                            <div className="flex items-center gap-4">
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-md bg-accent text-accent-foreground">
                                    <action.icon className="size-5" strokeWidth={1.5} />
                                </span>
                                <div className="min-w-0">
                                    <p className="text-sm font-bold text-foreground">{action.title}</p>
                                    <p className="mt-0.5 text-sm text-muted-foreground">{action.copy}</p>
                                </div>
                            </div>
                            <ArrowRight className="size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" />
                        </Link>
                    ))}
                </div>
            </section>
        </AdminLayout>
    );
}
