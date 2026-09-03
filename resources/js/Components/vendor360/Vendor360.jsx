import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { Activity, BadgeDollarSign, Banknote, Boxes, CalendarRange, Check, CheckCircle2, Download, FileDown, Package, Pencil, RotateCcw, Search, ShoppingBag, Store } from 'lucide-react';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { HorizontalRankingChart } from '@/Components/shared/dashboard/HorizontalRankingChart';
import { Input } from '@/Components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency, formatDate as formatDateShared, formatNumber } from '@/lib/date-time';
import { translatedStatus } from '@/lib/translated-enum';

const MONEY_KEYS = new Set(['gross_sales', 'refunds', 'commission', 'net_earnings', 'settled', 'outstanding', 'average_completed_order_value']);
const KPI_KEYS = ['total_products', 'active_products', 'pending_products', 'total_orders', 'completed_orders', 'cancelled_orders', 'units_sold', 'gross_sales', 'refunds', 'average_completed_order_value'];
const KPI_ICONS = { total_products: Package, active_products: Activity, pending_products: Boxes, total_orders: ShoppingBag, completed_orders: ShoppingBag, cancelled_orders: RotateCcw, units_sold: Boxes, gross_sales: BadgeDollarSign, refunds: RotateCcw, average_completed_order_value: BadgeDollarSign };

export function Vendor360({ vendorId, mode = 'admin' }) {
    const { vendorAnalytics: t = {}, common = {}, lang = {} } = useI18n();
    const locale = useLocale();
    const [range, setRange] = useState('30_days');
    const [custom, setCustom] = useState({ date_from: '', date_to: '' });
    const [overview, setOverview] = useState(null);
    const [status, setStatus] = useState('loading');
    const [tab, setTab] = useState(mode === 'admin' ? 'products' : 'overview');
    const [reportOpen, setReportOpen] = useState(false);
    const base = `/api/${mode === 'admin' ? 'admin' : 'syndicate'}/vendors/${vendorId}`;
    const params = useMemo(() => ({ range, ...(range === 'custom' ? custom : {}) }), [range, custom]);

    const load = useCallback(() => {
        if (range === 'custom' && (!custom.date_from || !custom.date_to)) return;
        setStatus('loading');
        window.axios.get(`${base}/analytics/overview`, { params, silent: true }).then((response) => {
            setOverview(response.data.data); setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [base, custom.date_from, custom.date_to, params, range]);
    useEffect(load, [load]);

    const approve = () => window.axios.patch(`/api/admin/vendors/${vendorId}/approve`, {}, { silent: true }).then(load);

    const vendor = overview?.vendor;
    const exportReport = async (reportParams) => {
        const reportHref = `${base}/report.pdf?${new URLSearchParams(reportParams).toString()}`;
        const response = await window.axios.get(reportHref, { responseType: 'blob' });
        const disposition = response.headers['content-disposition'] ?? '';
        const filename = disposition.match(/filename="?([^";]+)"?/i)?.[1] ?? `vetora-vendor-report-${vendorId}.pdf`;
        const downloadUrl = URL.createObjectURL(response.data);
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 60_000);
    };
    const finance = overview?.finance?.all_time ?? overview?.finance ?? {};
    const tabs = mode === 'admin'
        ? ['products', 'orders', 'sales']
        : ['overview', 'products', 'orders', 'returns', 'categories'];

    return (
        <>
            <PageHeader
                breadcrumb={mode === 'admin' ? [{ label: t.title, href: route('admin.vendors.index') }] : [{ label: t.title, href: route('syndicate.vendors') }]}
                title={<Identity vendor={vendor} loading={status === 'loading'} common={common} labels={t} />}
                actions={vendor ? <>{mode === 'admin' && vendor.status === 'pending' && <Button size="sm" onClick={approve}><CheckCircle2 className="size-4" aria-hidden="true" />{common.approve}</Button>}<Button type="button" variant="outline" size="sm" onClick={() => setReportOpen(true)}><FileDown className="size-4" />{t.export_pdf}</Button>{mode === 'admin' && <Button asChild variant="outline" size="sm"><Link href={route('admin.vendors.edit', vendor.id)}><Pencil className="size-4" aria-hidden="true" />{common.edit}</Link></Button>}</> : null}
            />
            <ReportDialog open={reportOpen} onOpenChange={setReportOpen} labels={t} languages={lang} onGenerate={exportReport} />
            <PeriodFilter range={range} onRange={setRange} custom={custom} onCustom={setCustom} labels={t} />
            {status === 'error' && <div role="alert" className="rounded-lg border border-destructive/30 bg-destructive/5 p-5 text-sm text-destructive">{t.load_failed}</div>}
            <Tabs value={tab} onValueChange={setTab}>
                <div className="max-w-full overflow-x-auto border-b border-border" role="region" aria-label={t.title} tabIndex={0}>
                    <TabsList variant="line" className="h-12 min-w-max">{tabs.map((name) => <TabsTrigger key={name} value={name} className="min-h-11 px-4">{t[name]}</TabsTrigger>)}</TabsList>
                </div>
                <TabsContent value="overview" className="space-y-5 pt-5">
                    <KpiGrid values={overview?.kpis} labels={t} locale={locale} loading={status === 'loading'} />
                    <FinanceStrip values={finance} labels={t} locale={locale} title={mode === 'admin' ? t.all_time_finance : t.period_analytics} />
                    {mode === 'syndicate' && overview?.finance?.attribution_complete === false && <p role="note" className="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-foreground">{t.attribution_unavailable}</p>}
                    <div className="grid gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
                        <TrendCard rows={overview?.trend ?? []} title={t.sales_trend} empty={t.no_data} locale={locale} ordersLabel={t.orders} />
                        <RankedList rows={overview?.top_products ?? []} title={t.top_products} empty={t.no_data} locale={locale} />
                    </div>
                    <RankedList rows={overview?.category_performance ?? []} title={t.category_performance} empty={t.no_data} locale={locale} horizontal />
                    <OrdersCards rows={overview?.recent_orders ?? []} title={t.recent_orders} empty={t.no_data} locale={locale} mode={mode} labels={t} common={common} />
                </TabsContent>
                {tabs.filter((name) => name !== 'overview' && name !== 'categories').map((name) => (
                    <TabsContent key={name} value={name} className="pt-5"><RemoteTab active={tab === name} name={name} base={base} params={params} vendorId={vendorId} mode={mode} labels={t} common={common} locale={locale} finance={finance} onChanged={load} /></TabsContent>
                ))}
                <TabsContent value="categories" className="pt-5"><RankedList rows={overview?.category_performance ?? []} title={t.category_performance} empty={t.no_data} locale={locale} /></TabsContent>
            </Tabs>
        </>
    );
}

function Identity({ vendor, loading, common, labels }) {
    if (loading && !vendor) return <span className="text-muted-foreground">•••</span>;
    return <span className="flex min-w-0 items-center gap-3"><Avatar className="size-14"><AvatarImage src={vendor?.logo_url} alt={common.logo_alt} /><AvatarFallback><Store className="size-5" /></AvatarFallback></Avatar><span className="min-w-0"><span className="block break-words">{vendor?.store_name ?? '—'}</span><span className="mt-1 flex flex-wrap items-center gap-2 text-sm font-normal text-muted-foreground"><StatusBadge tone={vendor?.is_active ? 'success' : 'danger'}>{vendor?.is_active ? common.active : common.inactive}</StatusBadge><span>{labels[vendor?.business_type] ?? common.not_available}</span><span>{vendor?.city?.name}</span>{vendor?.categories?.map((category) => <StatusBadge key={category.id} tone="brand">{category.name}</StatusBadge>)}</span></span></span>;
}

function ReportDialog({ open, onOpenChange, labels, languages, onGenerate }) {
    const today = new Date().toISOString().slice(0, 10);
    const monthAgo = new Date(Date.now() - 29 * 86_400_000).toISOString().slice(0, 10);
    const [form, setForm] = useState({ date_from: monthAgo, date_to: today, locale: 'ar' });
    const valid = form.date_from && form.date_to && form.date_from <= form.date_to;
    return <Dialog open={open} onOpenChange={onOpenChange}><DialogContent><DialogHeader><DialogTitle>{labels.export_pdf}</DialogTitle><DialogDescription>{labels.report_dialog_copy}</DialogDescription></DialogHeader><div className="grid gap-4 sm:grid-cols-2"><label className="grid gap-1.5 text-sm font-medium">{labels.from}<Input type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} /></label><label className="grid gap-1.5 text-sm font-medium">{labels.to}<Input type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} /></label><label className="grid gap-1.5 text-sm font-medium sm:col-span-2">{labels.report_language}<select className="h-11 rounded-md border border-input bg-background px-3" value={form.locale} onChange={(e) => setForm({ ...form, locale: e.target.value })}><option value="ar">{languages.arabic}</option><option value="en">{languages.english}</option></select></label></div><DialogFooter><Button type="button" disabled={!valid} onClick={() => { onGenerate({ range: 'custom', ...form }); onOpenChange(false); }}><FileDown className="size-4" />{labels.generate_report}</Button></DialogFooter></DialogContent></Dialog>;
}

function PeriodFilter({ range, onRange, custom, onCustom, labels }) {
    return <div className="flex flex-col gap-3 rounded-lg border border-border bg-card p-3 sm:flex-row sm:items-end"><label className="grid gap-1 text-sm font-semibold"><span className="flex items-center gap-1.5"><CalendarRange className="size-4" />{labels.period_analytics}</span><select value={range} onChange={(e) => onRange(e.target.value)} className="h-11 rounded-md border border-input bg-background px-3 text-base">{['today', '7_days', '30_days', '90_days', 'this_year', 'custom', 'all'].map((key) => <option key={key} value={key}>{labels[key]}</option>)}</select></label>{range === 'custom' && <><label className="grid gap-1 text-sm"><span>{labels.from}</span><Input type="date" value={custom.date_from} onChange={(e) => onCustom({ ...custom, date_from: e.target.value })} /></label><label className="grid gap-1 text-sm"><span>{labels.to}</span><Input type="date" value={custom.date_to} onChange={(e) => onCustom({ ...custom, date_to: e.target.value })} /></label></>}</div>;
}

function KpiGrid({ values = {}, labels, locale, loading }) {
    return <div className="grid grid-cols-1 gap-px overflow-hidden rounded-lg border border-border bg-border min-[380px]:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5">{KPI_KEYS.map((key) => { const Icon = KPI_ICONS[key]; return <div key={key} className="min-h-24 bg-card p-4"><div className="flex items-start justify-between gap-3"><p className="text-xs font-medium text-muted-foreground">{labels[key]}</p><Icon className="size-4 shrink-0 text-primary" strokeWidth={1.5} aria-hidden="true" /></div><p className="mt-2 text-xl font-bold tabular-nums text-foreground">{loading ? '—' : formatValue(values?.[key], MONEY_KEYS.has(key), locale)}</p></div>; })}</div>;
}

function FinanceStrip({ values = {}, labels, locale, title }) { const keys = ['gross_sales', 'commission', 'refunds', 'adjustments', 'net_earnings', 'settled', 'outstanding'].filter((key) => values?.[key] !== undefined); return keys.length ? <section aria-labelledby="finance-summary-title"><h2 id="finance-summary-title" className="mb-2 text-sm font-bold text-foreground">{title}</h2><div className="grid gap-px overflow-hidden rounded-lg border border-border bg-border sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">{keys.map((key) => <div key={key} className="bg-muted/30 px-4 py-3"><p className="text-xs text-muted-foreground">{labels[key]}</p><p className="mt-1 font-bold tabular-nums">{formatValue(values[key], true, locale)}</p></div>)}</div></section> : null; }

function TrendCard({ rows, title, empty, locale, ordersLabel }) {
    return <Card className="border-border/80 shadow-none"><CardHeader><CardTitle className="text-base">{title}</CardTitle></CardHeader><CardContent>{rows.length ? <><ChartContainer config={{ sales: { label: title, color: 'var(--chart-1)' } }} className="h-64 w-full aspect-auto"><AreaChart data={rows} margin={{ left: 4, right: 8 }}><CartesianGrid vertical={false} /><XAxis dataKey="date" tickLine={false} axisLine={false} minTickGap={24} /><YAxis hide /><ChartTooltip content={<ChartTooltipContent formatter={(value) => formatValue(value, true, locale)} />} /><Area type="monotone" dataKey="sales" stroke="var(--color-sales)" fill="var(--color-sales)" fillOpacity={0.16} isAnimationActive={false} /></AreaChart></ChartContainer><DataSummary rows={rows} locale={locale} ordersLabel={ordersLabel} /></> : <Empty text={empty} />}</CardContent></Card>;
}

function DataSummary({ rows, locale, ordersLabel }) { const sales = rows.reduce((sum, row) => sum + Number(row.sales || 0), 0); const orders = rows.reduce((sum, row) => sum + Number(row.orders || 0), 0); return <p className="mt-3 text-sm text-muted-foreground">{formatValue(sales, true, locale)} · {formatNumber(orders, locale)} {ordersLabel}</p>; }
function RankedList({ rows, title, empty, locale }) { return <Card className="border-border/80 shadow-none"><CardHeader><CardTitle className="text-base">{title}</CardTitle></CardHeader><CardContent>{rows.length ? <HorizontalRankingChart rows={rows} valueKey="sales" valueLabel={title} formatValue={(value) => formatValue(value, true, locale)} maxItems={8} /> : <Empty text={empty} />}</CardContent></Card>; }

function OrdersCards({ rows, title, empty, locale, mode, labels, common }) { return <Card className="border-border/80 shadow-none"><CardHeader><CardTitle className="text-base">{title}</CardTitle></CardHeader><CardContent className="flex flex-col gap-2">{rows.length ? rows.map((row) => <OrderRow key={row.id} row={row} locale={locale} mode={mode} labels={labels} common={common} />) : <Empty text={empty} />}</CardContent></Card>; }
function OrderRow({ row, locale, mode, labels, common }) { const href = mode === 'admin' ? route('admin.orders.show', row.id) : null; const content = <><span className="font-semibold">{row.order_number}</span><span className="text-sm tabular-nums text-muted-foreground">{formatValue(row.scoped_sales ?? row.subtotal, true, locale)}</span><StatusBadge tone={row.status === 'completed' ? 'success' : row.status === 'cancelled' ? 'danger' : 'warning'}>{translatedStatus(row.status, common)}</StatusBadge></>; return href ? <Link href={href} className="flex min-h-12 items-center justify-between gap-3 rounded-md border border-border px-3 transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">{content}</Link> : <div className="flex min-h-12 items-center justify-between gap-3 rounded-md border border-border px-3">{content}</div>; }

function RemoteTab({ active, name, base, params, vendorId, mode, labels, common, locale, finance, onChanged }) {
    const [state, setState] = useState({ status: 'idle', rows: [], meta: null }); const [page, setPage] = useState(1); const [search, setSearch] = useState('');
    const [paymentOpen, setPaymentOpen] = useState(false);
    const endpoint = ['finance', 'sales'].includes(name) ? `${base}/ledger` : name === 'staff' ? `${base}/staff` : name === 'documents' ? `/api/admin/vendor-documents` : `${base}/analytics/${name}`;
    const load = useCallback(() => { if (!active) return; setState((s) => ({ ...s, status: 'loading' })); window.axios.get(endpoint, { params: { ...params, page, search, ...(name === 'documents' ? { vendor_id: vendorId } : {}) }, silent: true }).then((res) => { const data = res.data.data; setState({ status: 'ready', rows: name === 'staff' ? [data.owner, ...(data.members || [])].filter(Boolean) : Array.isArray(data) ? data : [], meta: res.data.meta }); }).catch(() => setState({ status: 'error', rows: [], meta: null })); }, [active, endpoint, name, page, params, search, vendorId]);
    useEffect(load, [load]);
    const exportHref = mode === 'admin' && name === 'products' ? `/api/admin/exports/products?vendor_id=${vendorId}` : mode === 'admin' && name === 'orders' ? `/api/admin/exports/orders?vendor_id=${vendorId}` : mode === 'admin' && ['finance', 'sales'].includes(name) ? `${base}/analytics/export-summary?${new URLSearchParams(params)}` : null;
    const handlePaymentRecorded = () => { load(); onChanged?.(); };
    return <div className="space-y-4">{name === 'sales' && <><FinanceStrip values={finance} labels={labels} locale={locale} title={labels.all_time_finance} /><div className="flex justify-end"><Button type="button" onClick={() => setPaymentOpen(true)} disabled={Number(finance?.outstanding || 0) <= 0}><Banknote className="size-4" />{labels.add_payment}</Button></div><PaymentDialog open={paymentOpen} onOpenChange={setPaymentOpen} vendorId={vendorId} outstanding={Number(finance?.outstanding || 0)} labels={labels} locale={locale} onRecorded={handlePaymentRecorded} /></>}<Card className="border-border/80 shadow-none"><CardHeader className="gap-3 sm:flex-row sm:items-center sm:justify-between"><CardTitle className="text-base">{labels[name]}</CardTitle><div className="flex flex-wrap items-center gap-2">{exportHref && <Button asChild variant="outline" size="sm"><a href={exportHref}><Download className="size-4" aria-hidden="true" />CSV</a></Button>}{['products', 'orders'].includes(name) && <label className="relative"><Search className="pointer-events-none absolute start-3 top-3 size-4 text-muted-foreground" /><Input aria-label={labels.search} value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} className="min-h-11 ps-9" placeholder={labels.search} /></label>}</div></CardHeader><CardContent><ResponsiveRecords rows={state.rows} status={state.status} type={name} locale={locale} mode={mode} empty={labels.no_data} labels={labels} common={common} /></CardContent>{state.meta && <div className="px-6 pb-4"><Pagination meta={state.meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} /></div>}</Card></div>;
}

function PaymentDialog({ open, onOpenChange, vendorId, outstanding, labels, locale, onRecorded }) {
    const today = new Date().toISOString().slice(0, 10);
    const [form, setForm] = useState({ amount: '', payment_date: today, method: 'bank_transfer', reference: '', notes: '' });
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const amount = Number(form.amount || 0);
    const remaining = Math.max(outstanding - amount, 0);
    const submit = async () => {
        setSubmitting(true); setError('');
        try {
            const idempotencyKey = globalThis.crypto?.randomUUID?.() ?? 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
                const random = Math.floor(Math.random() * 16);
                return (character === 'x' ? random : (random & 0x3) | 0x8).toString(16);
            });
            await window.axios.post(`/api/admin/vendors/${vendorId}/settlements`, { ...form, amount, idempotency_key: idempotencyKey }, { silent: true });
            setForm({ amount: '', payment_date: today, method: 'bank_transfer', reference: '', notes: '' });
            onOpenChange(false); onRecorded();
        } catch (requestError) {
            setError(requestError.response?.data?.message ?? labels.payment_failed);
        } finally { setSubmitting(false); }
    };
    return <Dialog open={open} onOpenChange={onOpenChange}><DialogContent><DialogHeader><DialogTitle>{labels.add_payment}</DialogTitle><DialogDescription>{labels.payment_dialog_copy}</DialogDescription></DialogHeader><div className="grid gap-4"><label className="grid gap-1.5 text-sm font-medium">{labels.amount}<Input type="number" min="0.01" step="0.01" value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} /></label><label className="grid gap-1.5 text-sm font-medium">{labels.payment_date}<Input type="date" max={today} value={form.payment_date} onChange={(e) => setForm({ ...form, payment_date: e.target.value })} /></label><label className="grid gap-1.5 text-sm font-medium">{labels.method}<select className="h-11 rounded-md border border-input bg-background px-3" value={form.method} onChange={(e) => setForm({ ...form, method: e.target.value })}><option value="bank_transfer">{labels.bank_transfer}</option><option value="cash">{labels.cash}</option><option value="other">{labels.other}</option></select></label><label className="grid gap-1.5 text-sm font-medium">{labels.reference}<Input value={form.reference} onChange={(e) => setForm({ ...form, reference: e.target.value })} /></label><label className="grid gap-1.5 text-sm font-medium">{labels.notes}<Input value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} /></label><div className="rounded-md border border-border bg-muted/40 p-4 text-sm"><div className="flex justify-between"><span>{labels.outstanding}</span><strong>{formatValue(outstanding, true, locale)}</strong></div><div className="mt-2 flex justify-between"><span>{labels.payment}</span><strong>{formatValue(amount, true, locale)}</strong></div><div className="mt-2 flex justify-between border-t border-border pt-2"><span>{labels.after_payment}</span><strong>{formatValue(remaining, true, locale)}</strong></div></div>{error && <p role="alert" className="text-sm text-destructive">{error}</p>}</div><DialogFooter><Button type="button" disabled={submitting || amount <= 0 || amount > outstanding || !form.payment_date} onClick={submit}><Check className="size-4" aria-hidden="true" />{submitting ? labels.saving : labels.confirm_payment}</Button></DialogFooter></DialogContent></Dialog>;
}

function ResponsiveRecords({ rows, status, type, locale, mode, empty, labels, common }) {
    if (status === 'loading' || status === 'idle') return <div className="space-y-2" aria-busy="true">{[1,2,3,4].map((i) => <div key={i} className="h-14 animate-pulse rounded-md bg-muted" />)}</div>;
    if (!rows.length) return <Empty text={empty} />;
    if (type === 'orders') return <div className="space-y-2">{rows.map((row, index) => <OrderDetailRow key={row?.id ?? index} row={row} locale={locale} labels={labels} common={common} />)}</div>;
    return <div className="space-y-2">{rows.map((row, index) => <div key={row?.id ?? index} className="grid gap-2 rounded-lg border border-border p-3 text-sm md:grid-cols-[minmax(12rem,2fr)_repeat(4,minmax(7rem,1fr))] md:items-center"><Record row={row} type={type} locale={locale} mode={mode} labels={labels} common={common} /></div>)}</div>;
}
function Record({ row, type, locale, mode, labels, common }) {
    if (type === 'products') return <><Link href={mode === 'admin' ? route('admin.products.show', row.id) : '#'} className="flex items-center gap-3 font-semibold"><Avatar className="size-10 rounded-md"><AvatarImage src={row.image_url} alt={row.name} /><AvatarFallback><Package className="size-4" /></AvatarFallback></Avatar>{row.name}</Link><span>{row.category?.name}</span><span>{formatNumber(row.units_sold, locale)} {labels.units}</span><span>{formatValue(row.completed_sales_amount, true, locale)}</span><span>{translatedStatus(row.status, common)} · {row.is_active ? common.active : common.inactive}</span></>;
    if (type === 'returns') return <><span className="font-semibold">{row.return_number}</span><span>{row.order?.order_number}</span><span>{row.reason}</span><span>{formatValue(row.refund_amount ?? row.scoped_return_amount, true, locale)}</span><span>{translatedStatus(row.status, common)}</span></>;
    if (['finance', 'sales'].includes(type)) return <><span className="font-semibold">{labels[`ledger_${row.type}`] ?? common.not_available}</span><span>{row.order?.order_number ?? '—'}</span><span>{row.direction === 'credit' ? labels.credit : labels.debit}</span><span>{formatValue(row.amount, true, locale)}</span><span>{formatDate(row.created_at, locale)}</span></>;
    if (type === 'documents') return <><span className="font-semibold">{row.title}</span><span>{row.type_name}</span><span>{row.status_name}</span><span>{row.reviewed_by ?? '—'}</span><span>{formatDate(row.created_at, locale)}</span></>;
    if (type === 'staff') return <><span className="font-semibold">{row.name}</span><span>{row.email}</span><span>{row.role_name}</span><span>{row.status ? translatedStatus(row.status, common) : labels.staff_owner}</span><span>{formatDate(row.joined_at, locale)}</span></>;
    return <><span className="font-semibold">{labels[`action_${row.action}`] ?? common.not_available}</span><span>{row.actor?.name ?? '—'}</span><span>{labels[`entity_${row.entity_type}`] ?? common.not_available}</span><span>{row.entity_id}</span><span>{formatDate(row.created_at, locale)}</span></>;
}

/**
 * Full order detail, expanded on demand - admin and syndicate viewers both
 * get the complete picture (what was sold, to whom, address, payment,
 * coupon, and full status history), not a restricted summary.
 */
function OrderDetailRow({ row, locale, labels, common }) {
    const [open, setOpen] = useState(false);
    const address = row.shipping_address ?? {};
    const addressLine = [address.street, address.district, address.city, address.governorate].filter(Boolean).join(', ');

    return (
        <div className="rounded-lg border border-border p-3 text-sm">
            <button type="button" onClick={() => setOpen((o) => !o)} className="grid w-full gap-2 text-start md:grid-cols-[minmax(12rem,2fr)_repeat(4,minmax(7rem,1fr))] md:items-center">
                <span className="font-semibold">{row.order_number}</span>
                <span>{row.customer?.name}</span>
                <span>{labels.items_count.replace(':count', formatNumber(row.item_count, locale))}</span>
                <span>{formatValue(row.scoped_sales ?? row.subtotal, true, locale)}</span>
                <StatusBadge tone={row.status === 'completed' ? 'success' : row.status === 'cancelled' ? 'danger' : 'warning'}>{translatedStatus(row.status, common)}</StatusBadge>
            </button>

            {open && (
                <div className="mt-3 space-y-4 border-t border-border pt-3">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <section>
                            <h4 className="mb-1.5 text-xs font-bold uppercase tracking-wide text-muted-foreground">{labels.shipping_address}</h4>
                            <p>{address.recipient_name ?? common.not_specified}</p>
                            <p className="text-muted-foreground">{[address.phone, address.alternate_phone].filter(Boolean).join(' / ') || common.not_specified}</p>
                            <p className="text-muted-foreground">{addressLine || common.not_specified}</p>
                            {address.building && <p className="text-muted-foreground">{labels.building}: {address.building}{address.floor ? ` · ${labels.floor}: ${address.floor}` : ''}</p>}
                            {address.landmark && <p className="text-muted-foreground">{labels.landmark}: {address.landmark}</p>}
                            {address.notes && <p className="text-muted-foreground">{labels.notes}: {address.notes}</p>}
                        </section>

                        <section>
                            <h4 className="mb-1.5 text-xs font-bold uppercase tracking-wide text-muted-foreground">{labels.payment_info}</h4>
                            {row.payment ? (
                                <>
                                    <p>{labels[`provider_${row.payment.provider}`] ?? common.not_available} · {labels[`payment_method_${row.payment.method}`] ?? common.not_available}</p>
                                    <p className="text-muted-foreground">{labels.payment_amount}: {formatValue(row.payment.amount, true, locale)}{Number(row.payment.refunded_amount) > 0 ? ` · ${labels.refunded_amount}: ${formatValue(row.payment.refunded_amount, true, locale)}` : ''}</p>
                                    <StatusBadge tone={row.payment.status === 'paid' ? 'success' : row.payment.status === 'failed' ? 'danger' : 'warning'}>{translatedStatus(row.payment.status, common)}</StatusBadge>
                                    {row.payment.provider_reference && <p className="mt-1 text-muted-foreground">{labels.reference}: {row.payment.provider_reference}</p>}
                                    {row.payment.failure_reason && <p className="text-muted-foreground">{labels.failure_reason}: {row.payment.failure_reason}</p>}
                                </>
                            ) : <p className="text-muted-foreground">{common.not_specified}</p>}
                            {row.coupon && <p className="mt-2 text-muted-foreground">{labels.coupon}: {row.coupon.code} ({labels[`coupon_${row.coupon.type}`] ?? common.not_available} {row.coupon.value})</p>}
                        </section>
                    </div>

                    <section>
                        <h4 className="mb-1.5 text-xs font-bold uppercase tracking-wide text-muted-foreground">{labels.products}</h4>
                        <ul className="space-y-1">
                            {row.products?.map((item) => (
                                <li key={item.id} className="flex items-center justify-between gap-3 rounded-md bg-muted/30 px-3 py-1.5">
                                    <span className="min-w-0 truncate">{item.name} × {item.quantity}</span>
                                    <span className="shrink-0 tabular-nums text-muted-foreground">{item.has_discount ? <><s className="me-1.5">{formatValue(item.original_unit_price, true, locale)}</s>{formatValue(item.unit_price, true, locale)}</> : formatValue(item.unit_price, true, locale)} · {formatValue(item.line_total, true, locale)}</span>
                                </li>
                            ))}
                        </ul>
                    </section>

                    {row.status_history?.length > 0 && (
                        <section>
                            <h4 className="mb-1.5 text-xs font-bold uppercase tracking-wide text-muted-foreground">{labels.status_history}</h4>
                            <ul className="space-y-1 text-muted-foreground">
                                {row.status_history.map((h, i) => (
                                    <li key={i}>{h.from ? `${translatedStatus(h.from, common)} → ${translatedStatus(h.to, common)}` : translatedStatus(h.to, common)} — {formatDate(h.created_at, locale)}{h.changed_by ? ` · ${labels.changed_by}: ${h.changed_by}` : ''}{h.reason ? ` · ${h.reason}` : ''}</li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {row.cancellation && (
                        <section>
                            <h4 className="mb-1.5 text-xs font-bold uppercase tracking-wide text-muted-foreground">{labels.cancellation}</h4>
                            <p className="text-muted-foreground">{labels.reason}: {row.cancellation.reason ?? common.not_specified}{row.cancellation.cancelled_by ? ` · ${labels.cancelled_by}: ${row.cancellation.cancelled_by}` : ''}{row.cancellation.cancelled_at ? ` · ${formatDate(row.cancellation.cancelled_at, locale)}` : ''}</p>
                            {row.cancellation.notes && <p className="text-muted-foreground">{row.cancellation.notes}</p>}
                        </section>
                    )}
                </div>
            )}
        </div>
    );
}

function Empty({ text }) { return <div className="col-span-full rounded-md border border-dashed border-border py-10 text-center text-sm text-muted-foreground">{text}</div>; }
function formatValue(value, money, locale) { if (value === null || value === undefined) return '—'; return money ? formatCurrency(value, locale, 'SYP') : formatNumber(value, locale); }
function formatDate(value, locale) { return value ? formatDateShared(value, locale) : '—'; }
