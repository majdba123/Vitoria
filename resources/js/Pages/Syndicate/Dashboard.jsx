import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import SyndicateLayout from '@/Layouts/SyndicateLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DataTable } from '@/Components/admin/DataTable';
import { Pagination } from '@/Components/admin/Pagination';
import { StatCard } from '@/Components/admin/dashboard/StatCard';
import { InsightPanel } from '@/Components/admin/dashboard/InsightPanel';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { VendorMapPanel } from '@/Components/maps/VendorMapPanel';
import { ViewSwitch } from '@/Components/maps/ViewSwitch';
import { Store, Package, ShoppingBag } from 'lucide-react';
import { useI18n } from '@/hooks/use-i18n';
import { GrowthChart } from '@/Components/admin/dashboard/GrowthChart';

const TABLE_SECTIONS = ['categories', 'vendors', 'products', 'orders'];
const TYPE_LABEL_KEY = { agriculture: 'type_agriculture', veterinary: 'type_veterinary' };
const STATUS_TONE = { pending: 'warning', confirmed: 'success', preparing: 'success', shipped: 'brand', out_for_delivery: 'brand', completed: 'brand', cancelled: 'danger' };

function money(v) {
    return `${Number(v || 0).toLocaleString()} SYP`;
}

function ReportCard({ title, rows }) {
    return (
        <Card className="border-border/80 shadow-none">
            <CardHeader className="border-b border-border/80">
                <CardTitle className="text-sm font-bold">{title}</CardTitle>
            </CardHeader>
            <CardContent className="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3">
                {rows.map(([label, value]) => (
                    <div key={label} className="rounded-md border border-border bg-muted/40 px-3 py-2.5">
                        <p className="text-[10px] font-bold uppercase tracking-[0.16em] text-muted-foreground">{label}</p>
                        <p className="mt-1 text-base font-bold tabular-nums text-foreground">{value}</p>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}

export default function SyndicateDashboard({ section = 'dashboard' }) {
    const { syndicate, common, admin } = useI18n();
    const isTableSection = TABLE_SECTIONS.includes(section);
    const isPodcasts = section === 'podcasts';
    const isReports = section === 'reports';
    const isOverview = !isTableSection && !isPodcasts && !isReports;

    const [overviewStatus, setOverviewStatus] = useState('loading');
    const [overview, setOverview] = useState({});

    const loadOverview = () => {
        setOverviewStatus('loading');
        window.axios.get('/api/syndicate/overview', { silent: true }).then((res) => {
            setOverview(res.data?.data ?? {});
            setOverviewStatus('ready');
        }).catch(() => setOverviewStatus('error'));
    };

    useEffect(loadOverview, []);

    const [page, setPage] = useState(1);
    const [vendorView, setVendorView] = useState('table');
    const isVendorSection = section === 'vendors';
    const showVendorMap = isVendorSection && vendorView === 'map';
    const { status: tableStatus, rows: tableRows, meta: tableMeta, errorMessage: tableError, reload: reloadTable } = useSyndicateTable(section, isTableSection && !showVendorMap, page);

    const [reportsStatus, setReportsStatus] = useState('loading');
    const [reports, setReports] = useState(null);

    useEffect(() => {
        if (!isReports) return;
        setReportsStatus('loading');
        window.axios.get('/api/syndicate/reports', { silent: true }).then((res) => {
            setReports(res.data?.data ?? {});
            setReportsStatus('ready');
        }).catch(() => setReportsStatus('error'));
    }, [isReports]);

    const syndicateInfo = overview.syndicate ?? {};
    const typeKey = TYPE_LABEL_KEY[syndicateInfo.type] ?? 'type_default';
    const orderStats = overview.order_stats ?? {};
    const merchantStats = overview.merchant_stats ?? {};
    const topRows = (overview.top_merchants_by_sales || overview.top_selling_categories || []).slice(0, 6);
    const monthlyOrderGrowth = overview.monthly_order_growth || [];

    return (
        <SyndicateLayout title={syndicate[section] ?? syndicate.dashboard}>
            <PageHeader
                title={overviewStatus === 'ready' ? (syndicateInfo.name || 'Vetora') : syndicate.loading_data}
                copy={syndicate.dashboard_copy}
                actions={
                    <>
                        <StatusBadge tone={syndicateInfo.status === 'inactive' ? 'danger' : 'success'}>{syndicateInfo.status === 'inactive' ? syndicate.inactive : syndicate.active}</StatusBadge>
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('syndicate.sales')}>{syndicate.sales_cta}</Link>
                        </Button>
                        <Button asChild size="sm">
                            <Link href={route('syndicate.reports')}>{syndicate.reports_cta}</Link>
                        </Button>
                    </>
                }
            />

            {/* Visually hidden — gives this section a real heading level so InsightPanel's
                and ReportCard's h3 titles below don't jump straight from the page's h1,
                which axe-core flags as an invalid heading order. */}
            <h2 className="sr-only">{syndicate.dashboard}</h2>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard label={syndicate.vendors} value={overview.total_merchants ?? 0} icon={Store} status={overviewStatus} onRetry={loadOverview} />
                <StatCard label={syndicate.products} value={overview.total_products ?? 0} icon={Package} status={overviewStatus} onRetry={loadOverview} />
                <StatCard label={syndicate.total_orders} value={orderStats.total_orders ?? 0} icon={ShoppingBag} status={overviewStatus} onRetry={loadOverview} />
            </div>

            {overviewStatus === 'ready' && (
                <div className="flex flex-wrap gap-x-6 gap-y-2 border-t border-border pt-4">
                    {[
                        { label: syndicate.categories, value: overview.total_categories || 0 },
                        { label: syndicate.active_merchants, value: merchantStats.active_merchants || 0, tone: 'success' },
                        { label: syndicate.pending_orders, value: orderStats.pending_orders || 0, tone: 'warning' },
                        { label: syndicate.completed_orders, value: orderStats.completed_orders || 0, tone: 'success' },
                    ].map((item) => (
                        <div key={item.label} className="flex items-center gap-2">
                            <span className="text-xs font-semibold text-muted-foreground">{item.label}</span>
                            <span className={`text-sm font-bold tabular-nums ${item.tone === 'success' ? 'text-[var(--color-success-strong)]' : item.tone === 'warning' ? 'text-[var(--color-warning-strong)]' : 'text-foreground'}`}>{item.value}</span>
                        </div>
                    ))}
                </div>
            )}

            {showVendorMap && (
                <>
                    <div className="flex justify-end">
                        <ViewSwitch view={vendorView} onChange={setVendorView} />
                    </div>
                    <VendorMapPanel endpoint="/api/syndicate/vendors/map" />
                </>
            )}

            {isTableSection && !showVendorMap && (
                <Card className="gap-0 border-border/80 py-0 shadow-none">
                    <CardHeader className="flex-row items-center justify-between border-b border-border/80 py-4">
                        <div>
                            <CardTitle className="text-base font-bold">{syndicate.records_title}</CardTitle>
                            <p className="mt-1 text-xs text-muted-foreground">{syndicate.records_subtitle}</p>
                        </div>
                        <div className="flex items-center gap-3">
                            {isVendorSection && <ViewSwitch view={vendorView} onChange={setVendorView} />}
                            {tableStatus === 'ready' && <StatusBadge tone="brand">{tableMeta.total}</StatusBadge>}
                        </div>
                    </CardHeader>
                    <CardContent className="p-4">
                        <SyndicateTable section={section} rows={tableRows} status={tableStatus} errorMessage={tableError} onRetry={reloadTable} i18n={syndicate} common={common} />
                        {tableStatus === 'ready' && tableRows.length > 0 && (
                            <div className="mt-4 rounded-md border border-border">
                                <Pagination meta={tableMeta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            {isPodcasts && (
                <Card className="border-border/80 shadow-none">
                    <CardContent className="py-14 text-center">
                        <p className="text-sm font-semibold text-foreground">{syndicate.podcasts_unavailable}</p>
                        <p className="mt-1 text-sm text-muted-foreground">{syndicate.podcasts_unavailable_copy}</p>
                    </CardContent>
                </Card>
            )}

            {isReports && (
                <div className="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    {reportsStatus === 'loading' && <p className="col-span-full py-8 text-center text-sm text-muted-foreground">{syndicate.loading_data}</p>}
                    {reportsStatus === 'error' && <p className="col-span-full py-8 text-center text-sm text-[var(--color-danger-strong)]">{syndicate.load_failed_section}</p>}
                    {reportsStatus === 'ready' && reports && (
                        <>
                            <ReportCard title={syndicate.reports_sales_title} rows={[
                                [syndicate.total_sales, money(reports.sales?.total_sales)],
                                [syndicate.completed_sales, money(reports.sales?.completed_sales)],
                                [syndicate.average_order_value, money(reports.sales?.average_order_value)],
                            ]} />
                            <ReportCard title={syndicate.reports_orders_title} rows={[
                                [syndicate.total_orders, reports.orders?.total_orders || 0],
                                [syndicate.pending_orders, reports.orders?.pending_orders || 0],
                                [syndicate.completed_orders, reports.orders?.completed_orders || 0],
                                [syndicate.cancelled_orders, reports.orders?.cancelled_orders || 0],
                            ]} />
                            <ReportCard title={syndicate.reports_products_title} rows={[
                                [syndicate.products, reports.products?.total_products || 0],
                                [syndicate.active_products, reports.products?.active_products || 0],
                                [syndicate.inactive_products, reports.products?.inactive_products || 0],
                            ]} />
                            <ReportCard title={syndicate.reports_categories_title} rows={[
                                [syndicate.categories, reports.categories?.total_categories || 0],
                                [admin.categories_without_products_title, reports.categories?.categories_without_products || 0],
                                [admin.categories_without_vendors_title, reports.categories?.categories_without_merchants || 0],
                            ]} />
                            <ReportCard title={syndicate.reports_merchants_title} rows={[
                                [syndicate.vendors, reports.merchants?.total_merchants || 0],
                                [syndicate.active_merchants, reports.merchants?.active_merchants || 0],
                                [syndicate.inactive_merchants, reports.merchants?.inactive_merchants || 0],
                            ]} />
                        </>
                    )}
                </div>
            )}

            {isOverview && (
                <div className="grid grid-cols-1 gap-5 xl:grid-cols-3">
                    <div className="xl:col-span-2">
                        <InsightPanel
                            title={syndicate.top_performance}
                            copy={syndicate.top_performance_copy}
                            status={overviewStatus}
                            isEmpty={topRows.length === 0}
                            emptyMessage={syndicate.noData}
                            onRetry={loadOverview}
                        >
                            <div className="space-y-2">
                                {topRows.map((row, index) => (
                                    <div key={index} className="flex items-center justify-between gap-3 rounded-md border border-border bg-muted/40 px-4 py-3">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold text-foreground">{row.store_name || row.name || '—'}</p>
                                            {row.orders_count != null && <p className="mt-0.5 text-xs text-muted-foreground">{row.orders_count} {syndicate.total_orders?.toLowerCase()}</p>}
                                        </div>
                                        <StatusBadge tone="brand">{row.sales_total != null ? money(row.sales_total) : (row.products_count || row.count || 0)}</StatusBadge>
                                    </div>
                                ))}
                            </div>
                        </InsightPanel>
                    </div>

                    <Card className="border-border/80 shadow-none">
                        <CardHeader className="border-b border-border/80">
                            <CardTitle className="text-base font-bold">{syndicate.quick_summary}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 p-5">
                            {[
                                [syndicate.categories, overview.total_categories || 0],
                                [syndicate.vendors, overview.total_merchants || 0],
                                [syndicate.products, overview.total_products || 0],
                            ].map(([label, value]) => (
                                <div key={label} className="flex items-center justify-between rounded-md border border-border bg-muted/40 px-4 py-3">
                                    <span className="text-sm font-semibold text-foreground">{label}</span>
                                    <StatusBadge tone="brand">{value}</StatusBadge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            )}

            {isOverview && (
                <InsightPanel
                    title={syndicate.monthly_order_growth_title}
                    copy={syndicate.monthly_order_growth_copy}
                    status={overviewStatus}
                    isEmpty={monthlyOrderGrowth.every((row) => Number(row.total || 0) === 0)}
                    emptyMessage={syndicate.noData}
                    onRetry={loadOverview}
                >
                    <GrowthChart rows={monthlyOrderGrowth} totalLabel={syndicate.total_orders} />
                </InsightPanel>
            )}
        </SyndicateLayout>
    );
}

function useSyndicateTable(section, enabled, page) {
    const [status, setStatus] = useState('loading');
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState({});
    const [errorMessage, setErrorMessage] = useState(null);

    const load = () => {
        if (!enabled) return;
        setStatus('loading');
        window.axios.get(`/api/syndicate/${section}`, { params: { page, per_page: 15 }, silent: true }).then((res) => {
            setRows(res.data?.data ?? []);
            setMeta(res.data?.meta ?? {});
            setStatus('ready');
        }).catch((error) => {
            setErrorMessage(error.response?.data?.message ?? null);
            setStatus('error');
        });
    };

    useEffect(load, [section, enabled, page]);

    return { status, rows, meta, errorMessage, reload: load };
}

function SyndicateTable({ section, rows, status, errorMessage, onRetry, i18n, common }) {
    const columnsBySection = {
        categories: [
            { key: 'name', label: i18n.th_category, render: (r) => <span className="font-semibold text-foreground">{r.name}</span> },
            { key: 'type', label: i18n.th_type, render: (r) => typeLabel(r.type, i18n) },
            { key: 'vendors_count', label: i18n.th_vendors, align: 'end', render: (r) => Number(r.vendors_count || 0) },
            { key: 'products_count', label: i18n.th_products, align: 'end', render: (r) => Number(r.products_count || 0) },
        ],
        vendors: [
            { key: 'store_name', label: i18n.th_store, render: (r) => <span className="font-semibold text-foreground">{r.store_name}</span> },
            { key: 'business_type', label: i18n.th_type, render: (r) => typeLabel(r.business_type, i18n) },
            { key: 'city', label: i18n.th_city ?? 'City', render: (r) => r.city?.name || '—' },
            { key: 'products_count', label: i18n.th_products, align: 'end', render: (r) => Number(r.products_count || 0) },
            { key: 'completed_orders_count', label: i18n.completed_orders, align: 'end', render: (r) => Number(r.completed_orders_count || 0) },
            { key: 'domain_sales', label: i18n.completed_sales, align: 'end', render: (r) => money(r.domain_sales) },
            { key: 'last_activity_at', label: i18n.last_activity ?? 'Last activity', render: (r) => r.last_activity_at ? new Date(r.last_activity_at).toLocaleDateString() : '—' },
            { key: 'status', label: i18n.th_status, render: (r) => <StatusBadge tone={r.is_active ? 'success' : 'danger'}>{r.is_active ? common.active : common.inactive}</StatusBadge> },
        ],
        products: [
            { key: 'name', label: i18n.th_products, render: (r) => <span className="font-semibold text-foreground">{r.name}</span> },
            { key: 'store', label: i18n.th_store, render: (r) => r.vendor?.store_name || '—' },
            { key: 'category', label: i18n.th_category, render: (r) => r.category?.name || '—' },
            { key: 'status', label: i18n.th_status, render: (r) => <StatusBadge tone={r.is_active ? 'success' : 'danger'}>{r.is_active ? common.active : common.inactive}</StatusBadge> },
        ],
        orders: [
            { key: 'order', label: i18n.th_order, render: (r) => <span className="font-semibold text-foreground">{r.order_number || `#${r.id}`}</span> },
            { key: 'customer', label: i18n.th_customer, render: (r) => r.user?.name || '—' },
            { key: 'store', label: i18n.th_store, render: (r) => r.vendor?.store_name || '—' },
            { key: 'total', label: i18n.th_total, align: 'end', render: (r) => money(r.total_amount) },
            { key: 'status', label: i18n.th_status, render: (r) => <StatusBadge tone={STATUS_TONE[r.status] ?? 'warning'}>{r.status}</StatusBadge> },
        ],
    };

    return (
        <DataTable
            columns={columnsBySection[section] ?? []}
            rows={rows}
            status={status}
            errorMessage={errorMessage}
            onRetry={onRetry}
            rowHref={section === 'vendors' ? (row) => route('syndicate.vendors.show', row.id) : undefined}
            emptyTitle={common.no_data ?? 'No data available.'}
        />
    );
}

function typeLabel(type, i18n) {
    if (type === 'agriculture') return i18n.type_agriculture;
    if (type === 'veterinary') return i18n.type_veterinary;
    return i18n.type_default;
}
