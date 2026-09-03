import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import SyndicateLayout from '@/Layouts/SyndicateLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { Pagination } from '@/Components/shared/Pagination';
import { StatCard } from '@/Components/shared/dashboard/StatCard';
import { InsightPanel } from '@/Components/shared/dashboard/InsightPanel';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { DashboardVendorMap } from '@/Components/maps/DashboardVendorMap';
import { BarChart3, Eye, FileChartColumn, FileText, Package, ShoppingBag, Store } from 'lucide-react';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency as money, formatDate } from '@/lib/date-time';
import { ORDER_STATUS_TONE as STATUS_TONE } from '@/lib/order-status';
import { translatedStatus } from '@/lib/translated-enum';
import { GrowthChart } from '@/Components/shared/dashboard/GrowthChart';
import { HorizontalRankingChart } from '@/Components/shared/dashboard/HorizontalRankingChart';
import { DonutChart } from '@/Components/shared/dashboard/DonutChart';
import { Input } from '@/Components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const TABLE_SECTIONS = ['categories', 'vendors', 'products', 'orders'];
const TYPE_LABEL_KEY = { agriculture: 'type_agriculture', veterinary: 'type_veterinary' };

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
    const { syndicate, common, admin, lang } = useI18n();
    const locale = useLocale();
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
    const { status: tableStatus, rows: tableRows, meta: tableMeta, errorMessage: tableError, reload: reloadTable } = useSyndicateTable(section, isTableSection, page);

    const [reportsStatus, setReportsStatus] = useState('loading');
    const [reports, setReports] = useState(null);
    const [reportTarget, setReportTarget] = useState(null);
    const [generalReportOpen, setGeneralReportOpen] = useState(false);
    const [reportForm, setReportForm] = useState({ date_from: new Date(Date.now() - 29 * 86_400_000).toISOString().slice(0, 10), date_to: new Date().toISOString().slice(0, 10), locale: 'ar' });

    const generateVendorReport = async () => {
        if (!reportTarget) return;
        const response = await window.axios.get(`/api/syndicate/vendors/${reportTarget.id}/report.pdf`, { params: { range: 'custom', ...reportForm }, responseType: 'blob' });
        const url = URL.createObjectURL(response.data);
        window.open(url, '_blank', 'noopener,noreferrer');
        window.setTimeout(() => URL.revokeObjectURL(url), 60_000);
        setReportTarget(null);
    };

    const generateGeneralReport = async () => {
        const response = await window.axios.get('/api/syndicate/reports.pdf', { params: { range: 'custom', ...reportForm }, responseType: 'blob' });
        const url = URL.createObjectURL(response.data);
        window.open(url, '_blank', 'noopener,noreferrer');
        window.setTimeout(() => URL.revokeObjectURL(url), 60_000);
        setGeneralReportOpen(false);
    };

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
    const topPerformanceRows = topRows.map((row) => ({ name: row.store_name || row.name || '—', value: row.sales_total ?? row.products_count ?? row.count ?? 0 }));
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
                            <Link href={route('syndicate.sales')}><BarChart3 className="size-4" aria-hidden="true" />{syndicate.sales_cta}</Link>
                        </Button>
                        <Button asChild size="sm">
                            <Link href={route('syndicate.reports')}><FileChartColumn className="size-4" aria-hidden="true" />{syndicate.reports_cta}</Link>
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

            {isTableSection && (
                <Card className="gap-0 border-border/80 py-0 shadow-none">
                    <CardHeader className="flex-row items-center justify-between border-b border-border/80 py-4">
                        <div>
                            <CardTitle className="text-base font-bold">{syndicate.records_title}</CardTitle>
                            <p className="mt-1 text-xs text-muted-foreground">{syndicate.records_subtitle}</p>
                        </div>
                        <div className="flex items-center gap-3">
                            {tableStatus === 'ready' && <StatusBadge tone="brand">{tableMeta.total}</StatusBadge>}
                        </div>
                    </CardHeader>
                    <CardContent className="p-4">
                        <SyndicateTable section={section} rows={tableRows} status={tableStatus} errorMessage={tableError} onRetry={reloadTable} i18n={syndicate} common={common} onReport={setReportTarget} locale={locale} />
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
                    <div className="col-span-full flex justify-end"><Button type="button" onClick={() => setGeneralReportOpen(true)}><FileChartColumn className="size-4" />{syndicate.generate_general_report}</Button></div>
                    {reportsStatus === 'loading' && <p className="col-span-full py-8 text-center text-sm text-muted-foreground">{syndicate.loading_data}</p>}
                    {reportsStatus === 'error' && <p className="col-span-full py-8 text-center text-sm text-[var(--color-danger-strong)]">{syndicate.load_failed_section}</p>}
                    {reportsStatus === 'ready' && reports && (
                        <>
                            <ReportCard title={syndicate.reports_sales_title} rows={[
                                [syndicate.total_sales, money(reports.sales?.total_sales, locale)],
                                [syndicate.completed_sales, money(reports.sales?.completed_sales, locale)],
                                [syndicate.average_order_value, money(reports.sales?.average_order_value, locale)],
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
                        <DashboardVendorMap endpoint="/api/syndicate/vendors/map" />
                    </div>
                    <div>
                        <InsightPanel
                            title={syndicate.top_performance}
                            copy={syndicate.top_performance_copy}
                            status={overviewStatus}
                            isEmpty={topPerformanceRows.length === 0}
                            emptyMessage={syndicate.noData}
                            onRetry={loadOverview}
                        >
                            <HorizontalRankingChart rows={topPerformanceRows} valueKey="value" valueLabel={syndicate.completed_sales} formatValue={(value) => money(value, locale)} />
                        </InsightPanel>
                    </div>
                </div>
            )}

            {isOverview && (
                <div className="grid grid-cols-1 gap-5 xl:grid-cols-2">
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
                <InsightPanel title={syndicate.vendor_status_title} copy={syndicate.vendor_status_copy} status={overviewStatus} isEmpty={(overview.total_merchants ?? 0) === 0} emptyMessage={syndicate.noData} onRetry={loadOverview}>
                    <DonutChart rows={[
                        { key: 'active', label: syndicate.active, value: merchantStats.active_merchants, color: 'var(--color-success-500)' },
                        { key: 'inactive', label: syndicate.inactive, value: merchantStats.inactive_merchants, color: 'var(--color-danger-500)' },
                    ]} total={overview.total_merchants} totalLabel={syndicate.vendors} />
                </InsightPanel>
                </div>
            )}
            <Dialog open={!!reportTarget} onOpenChange={(open) => { if (!open) setReportTarget(null); }}><DialogContent><DialogHeader><DialogTitle>{syndicate.vendor_report}</DialogTitle><DialogDescription>{reportTarget?.store_name}</DialogDescription></DialogHeader><div className="grid gap-4 sm:grid-cols-2"><label className="grid gap-1.5 text-sm font-medium">{syndicate.from}<Input type="date" value={reportForm.date_from} onChange={(e) => setReportForm({ ...reportForm, date_from: e.target.value })} /></label><label className="grid gap-1.5 text-sm font-medium">{syndicate.to}<Input type="date" value={reportForm.date_to} onChange={(e) => setReportForm({ ...reportForm, date_to: e.target.value })} /></label><label className="grid gap-1.5 text-sm font-medium sm:col-span-2">{syndicate.report_language}<select className="h-11 rounded-md border border-input bg-background px-3" value={reportForm.locale} onChange={(e) => setReportForm({ ...reportForm, locale: e.target.value })}><option value="ar">{lang.arabic}</option><option value="en">{lang.english}</option></select></label></div><DialogFooter><Button type="button" disabled={!reportForm.date_from || !reportForm.date_to || reportForm.date_from > reportForm.date_to} onClick={generateVendorReport}><FileText className="size-4" />{syndicate.generate_report}</Button></DialogFooter></DialogContent></Dialog>
            <Dialog open={generalReportOpen} onOpenChange={setGeneralReportOpen}><DialogContent><DialogHeader><DialogTitle>{syndicate.general_report}</DialogTitle><DialogDescription>{syndicate.general_report_copy}</DialogDescription></DialogHeader><div className="grid gap-4 sm:grid-cols-2"><label className="grid gap-1.5 text-sm font-medium">{syndicate.from}<Input type="date" value={reportForm.date_from} onChange={(e) => setReportForm({ ...reportForm, date_from: e.target.value })} /></label><label className="grid gap-1.5 text-sm font-medium">{syndicate.to}<Input type="date" value={reportForm.date_to} onChange={(e) => setReportForm({ ...reportForm, date_to: e.target.value })} /></label><label className="grid gap-1.5 text-sm font-medium sm:col-span-2">{syndicate.report_language}<select className="h-11 rounded-md border border-input bg-background px-3" value={reportForm.locale} onChange={(e) => setReportForm({ ...reportForm, locale: e.target.value })}><option value="ar">{lang.arabic}</option><option value="en">{lang.english}</option></select></label></div><DialogFooter><Button type="button" disabled={!reportForm.date_from || !reportForm.date_to || reportForm.date_from > reportForm.date_to} onClick={generateGeneralReport}><FileChartColumn className="size-4" />{syndicate.generate_report}</Button></DialogFooter></DialogContent></Dialog>
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

function SyndicateTable({ section, rows, status, errorMessage, onRetry, i18n, common, onReport, locale }) {
    const columnsBySection = {
        categories: [
            { key: 'name', label: i18n.th_category, truncate: true, render: (r) => <span className="font-semibold text-foreground">{r.name}</span> },
            { key: 'type', label: i18n.th_type, render: (r) => typeLabel(r.type, i18n) },
            { key: 'vendors_count', label: i18n.th_vendors, align: 'end', render: (r) => Number(r.vendors_count || 0) },
            { key: 'products_count', label: i18n.th_products, align: 'end', render: (r) => Number(r.products_count || 0) },
        ],
        vendors: [
            { key: 'store_name', label: i18n.th_store, truncate: true, render: (r) => <span className="font-semibold text-foreground">{r.store_name}</span> },
            { key: 'business_type', label: i18n.th_type, render: (r) => typeLabel(r.business_type, i18n) },
            { key: 'city', label: i18n.th_city, render: (r) => r.city?.name || '—' },
            { key: 'products_count', label: i18n.th_products, align: 'end', render: (r) => Number(r.products_count || 0) },
            { key: 'completed_orders_count', label: i18n.completed_orders, align: 'end', render: (r) => Number(r.completed_orders_count || 0) },
            { key: 'domain_sales', label: i18n.completed_sales, align: 'end', render: (r) => money(r.domain_sales, locale) },
            { key: 'last_activity_at', label: i18n.last_activity, render: (r) => r.last_activity_at ? formatDate(r.last_activity_at, locale) : '—' },
            { key: 'status', label: i18n.th_status, align: 'center', render: (r) => <StatusBadge tone={r.is_active ? 'success' : 'danger'}>{r.is_active ? common.active : common.inactive}</StatusBadge> },
            { key: 'actions', label: i18n.th_actions, align: 'center', render: (r) => <div className="flex items-center justify-center gap-1"><Button asChild size="sm" variant="ghost"><Link href={route('syndicate.vendors.show', r.id)}><Eye className="size-4" />{common.view_details}</Link></Button><Button type="button" size="sm" variant="outline" onClick={() => onReport(r)}><FileText className="size-4" />{i18n.report}</Button></div> },
        ],
        products: [
            { key: 'name', label: i18n.th_products, width: '38%', truncate: true, render: (r) => <span className="font-semibold text-foreground">{r.name}</span> },
            { key: 'store', label: i18n.th_store, width: '24%', truncate: true, render: (r) => r.vendor?.store_name || '—' },
            { key: 'category', label: i18n.th_category, width: '23%', truncate: true, render: (r) => r.category?.name || '—' },
            { key: 'status', label: i18n.th_status, width: '15%', align: 'center', render: (r) => <StatusBadge tone={r.is_active ? 'success' : 'danger'}>{r.is_active ? common.active : common.inactive}</StatusBadge> },
        ],
        orders: [
            { key: 'order', label: i18n.th_order, truncate: true, render: (r) => <span className="font-semibold text-foreground">{r.order_number || `#${r.id}`}</span> },
            { key: 'customer', label: i18n.th_customer, truncate: true, render: (r) => r.user?.name || '—' },
            { key: 'store', label: i18n.th_store, truncate: true, render: (r) => r.vendor?.store_name || '—' },
            { key: 'total', label: i18n.th_total, align: 'end', render: (r) => money(r.total_amount, locale) },
            { key: 'status', label: i18n.th_status, align: 'center', render: (r) => <StatusBadge tone={STATUS_TONE[r.status] ?? 'warning'}>{i18n[`status_${r.status}`] ?? translatedStatus(r.status, common)}</StatusBadge> },
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
            emptyTitle={common.no_data}
        />
    );
}

function typeLabel(type, i18n) {
    if (type === 'agriculture') return i18n.type_agriculture;
    if (type === 'veterinary') return i18n.type_veterinary;
    return i18n.type_default;
}
