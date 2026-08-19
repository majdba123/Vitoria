import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { CalendarRange, Package, Search, Store } from 'lucide-react';
import { PageHeader } from '@/Components/admin/PageHeader';
import { Pagination } from '@/Components/admin/Pagination';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { Input } from '@/Components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { useI18n, useLocale } from '@/hooks/use-i18n';

const MONEY_KEYS = new Set(['gross_sales', 'refunds', 'commission', 'net_earnings', 'settled', 'outstanding', 'average_completed_order_value']);
const KPI_KEYS = ['total_products', 'active_products', 'pending_products', 'total_orders', 'completed_orders', 'cancelled_orders', 'units_sold', 'gross_sales', 'refunds', 'average_completed_order_value'];

export function Vendor360({ vendorId, mode = 'admin' }) {
    const { vendorAnalytics: t = {}, common = {} } = useI18n();
    const locale = useLocale();
    const [range, setRange] = useState('30_days');
    const [custom, setCustom] = useState({ date_from: '', date_to: '' });
    const [overview, setOverview] = useState(null);
    const [status, setStatus] = useState('loading');
    const [tab, setTab] = useState('overview');
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
    const finance = overview?.finance?.all_time ?? overview?.finance ?? {};
    const tabs = mode === 'admin'
        ? ['overview', 'products', 'orders', 'finance', 'returns', 'staff', 'documents', 'activity']
        : ['overview', 'products', 'orders', 'returns', 'categories'];

    return (
        <>
            <PageHeader
                breadcrumb={mode === 'admin' ? [{ label: t.title, href: route('admin.vendors.index') }] : [{ label: t.title, href: route('syndicate.vendors') }]}
                title={<Identity vendor={vendor} loading={status === 'loading'} common={common} locale={locale} />}
                copy={t.period_note}
                actions={mode === 'admin' && vendor ? <>{vendor.status === 'pending' && <Button size="sm" onClick={approve}>{common.approve}</Button>}<Button asChild variant="outline" size="sm"><Link href={route('admin.vendors.edit', vendor.id)}>{common.edit}</Link></Button></> : null}
            />
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
                        <TrendCard rows={overview?.trend ?? []} title={t.sales_trend} empty={t.no_data} locale={locale} />
                        <RankedList rows={overview?.top_products ?? []} title={t.top_products} empty={t.no_data} locale={locale} />
                    </div>
                    <RankedList rows={overview?.category_performance ?? []} title={t.category_performance} empty={t.no_data} locale={locale} horizontal />
                    <OrdersCards rows={overview?.recent_orders ?? []} title={t.recent_orders} empty={t.no_data} locale={locale} mode={mode} />
                </TabsContent>
                {tabs.filter((name) => name !== 'overview' && name !== 'categories').map((name) => (
                    <TabsContent key={name} value={name} className="pt-5"><RemoteTab active={tab === name} name={name} base={base} params={params} vendorId={vendorId} mode={mode} labels={t} common={common} locale={locale} /></TabsContent>
                ))}
                <TabsContent value="categories" className="pt-5"><RankedList rows={overview?.category_performance ?? []} title={t.category_performance} empty={t.no_data} locale={locale} /></TabsContent>
            </Tabs>
        </>
    );
}

function Identity({ vendor, loading, common, locale }) {
    if (loading && !vendor) return <span className="text-muted-foreground">•••</span>;
    return <span className="flex min-w-0 items-center gap-3"><Avatar className="size-14"><AvatarImage src={vendor?.logo_url} alt={common.logo_alt} /><AvatarFallback><Store className="size-5" /></AvatarFallback></Avatar><span className="min-w-0"><span className="block truncate">{vendor?.store_name ?? '—'}</span><span className="mt-1 flex flex-wrap items-center gap-2 text-sm font-normal text-muted-foreground"><span>{vendor?.owner?.name}</span><StatusBadge tone={vendor?.is_active ? 'success' : 'danger'}>{vendor?.is_active ? common.active : common.inactive}</StatusBadge><span>{vendor?.business_type}</span><span>{vendor?.city?.name}</span><span>{vendor?.registration_source}</span><span>{formatDate(vendor?.joined_at, locale)}</span>{vendor?.categories?.map((category) => <StatusBadge key={category.id} tone="brand">{category.name}</StatusBadge>)}</span></span></span>;
}

function PeriodFilter({ range, onRange, custom, onCustom, labels }) {
    return <div className="flex flex-col gap-3 rounded-lg border border-border bg-card p-3 sm:flex-row sm:items-end"><label className="grid gap-1 text-xs font-semibold"><span className="flex items-center gap-1.5"><CalendarRange className="size-3.5" />{labels.period_analytics}</span><select value={range} onChange={(e) => onRange(e.target.value)} className="h-11 rounded-md border border-input bg-background px-3 text-sm">{['today', '7_days', '30_days', '90_days', 'this_year', 'custom', 'all'].map((key) => <option key={key} value={key}>{labels[key]}</option>)}</select></label>{range === 'custom' && <><label className="grid gap-1 text-xs"><span>From</span><Input type="date" value={custom.date_from} onChange={(e) => onCustom({ ...custom, date_from: e.target.value })} /></label><label className="grid gap-1 text-xs"><span>To</span><Input type="date" value={custom.date_to} onChange={(e) => onCustom({ ...custom, date_to: e.target.value })} /></label></>}</div>;
}

function KpiGrid({ values = {}, labels, locale, loading }) {
    return <div className="grid grid-cols-1 gap-px overflow-hidden rounded-lg border border-border bg-border min-[380px]:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-5">{KPI_KEYS.map((key) => <div key={key} className="min-h-24 bg-card p-4"><p className="text-xs font-medium text-muted-foreground">{labels[key]}</p><p className="mt-2 text-xl font-bold tabular-nums text-foreground">{loading ? '—' : formatValue(values?.[key], MONEY_KEYS.has(key), locale)}</p></div>)}</div>;
}

function FinanceStrip({ values = {}, labels, locale, title }) { const keys = ['gross_sales', 'commission', 'refunds', 'adjustments', 'net_earnings', 'settled', 'outstanding'].filter((key) => values?.[key] !== undefined); return keys.length ? <section aria-labelledby="finance-summary-title"><h2 id="finance-summary-title" className="mb-2 text-sm font-bold text-foreground">{title}</h2><div className="grid gap-px overflow-hidden rounded-lg border border-border bg-border sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">{keys.map((key) => <div key={key} className="bg-muted/30 px-4 py-3"><p className="text-xs text-muted-foreground">{labels[key]}</p><p className="mt-1 font-bold tabular-nums">{formatValue(values[key], true, locale)}</p></div>)}</div></section> : null; }

function TrendCard({ rows, title, empty, locale }) {
    return <Card className="border-border/80 shadow-none"><CardHeader><CardTitle className="text-base">{title}</CardTitle></CardHeader><CardContent>{rows.length ? <><ChartContainer config={{ sales: { label: title, color: 'var(--chart-1)' } }} className="h-64 w-full aspect-auto"><AreaChart data={rows} margin={{ left: 4, right: 8 }}><CartesianGrid vertical={false} /><XAxis dataKey="date" tickLine={false} axisLine={false} minTickGap={24} /><YAxis hide /><ChartTooltip content={<ChartTooltipContent formatter={(value) => formatValue(value, true, locale)} />} /><Area type="monotone" dataKey="sales" stroke="var(--color-sales)" fill="var(--color-sales)" fillOpacity={0.16} isAnimationActive={false} /></AreaChart></ChartContainer><DataSummary rows={rows} locale={locale} /></> : <Empty text={empty} />}</CardContent></Card>;
}

function DataSummary({ rows, locale }) { const sales = rows.reduce((sum, row) => sum + Number(row.sales || 0), 0); const orders = rows.reduce((sum, row) => sum + Number(row.orders || 0), 0); return <p className="mt-3 text-sm text-muted-foreground">{formatValue(sales, true, locale)} · {orders.toLocaleString(locale)} orders</p>; }
function RankedList({ rows, title, empty, locale, horizontal = false }) { return <Card className="border-border/80 shadow-none"><CardHeader><CardTitle className="text-base">{title}</CardTitle></CardHeader><CardContent className={horizontal ? 'grid gap-2 sm:grid-cols-2 xl:grid-cols-4' : 'space-y-2'}>{rows.length ? rows.map((row, index) => <div key={row.id ?? index} className="flex items-center justify-between gap-3 rounded-md border border-border bg-muted/30 px-3 py-2.5"><span className="min-w-0 truncate text-sm font-semibold">{row.name}</span><span className="shrink-0 text-sm tabular-nums text-muted-foreground">{formatValue(row.sales, true, locale)}</span></div>) : <Empty text={empty} />}</CardContent></Card>; }

function OrdersCards({ rows, title, empty, locale, mode }) { return <Card className="border-border/80 shadow-none"><CardHeader><CardTitle className="text-base">{title}</CardTitle></CardHeader><CardContent className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">{rows.length ? rows.map((row) => <OrderRow key={row.id} row={row} locale={locale} mode={mode} />) : <Empty text={empty} />}</CardContent></Card>; }
function OrderRow({ row, locale, mode }) { const href = mode === 'admin' ? route('admin.orders.show', row.id) : null; const content = <><span className="font-semibold">{row.order_number}</span><span className="text-sm tabular-nums text-muted-foreground">{formatValue(row.scoped_sales ?? row.subtotal, true, locale)}</span><StatusBadge tone={row.status === 'completed' ? 'success' : row.status === 'cancelled' ? 'danger' : 'warning'}>{row.status}</StatusBadge></>; return href ? <Link href={href} className="flex min-h-12 items-center justify-between gap-3 rounded-md border border-border px-3 transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">{content}</Link> : <div className="flex min-h-12 items-center justify-between gap-3 rounded-md border border-border px-3">{content}</div>; }

function RemoteTab({ active, name, base, params, vendorId, mode, labels, common, locale }) {
    const [state, setState] = useState({ status: 'idle', rows: [], meta: null }); const [page, setPage] = useState(1); const [search, setSearch] = useState('');
    const endpoint = name === 'finance' ? `${base}/ledger` : name === 'staff' ? `${base}/staff` : name === 'documents' ? `/api/admin/vendor-documents` : `${base}/analytics/${name}`;
    const load = useCallback(() => { if (!active) return; setState((s) => ({ ...s, status: 'loading' })); window.axios.get(endpoint, { params: { ...params, page, search, ...(name === 'documents' ? { vendor_id: vendorId } : {}) }, silent: true }).then((res) => { const data = res.data.data; setState({ status: 'ready', rows: name === 'staff' ? [data.owner, ...(data.members || [])].filter(Boolean) : Array.isArray(data) ? data : [], meta: res.data.meta }); }).catch(() => setState({ status: 'error', rows: [], meta: null })); }, [active, endpoint, name, page, params, search, vendorId]);
    useEffect(load, [load]);
    const exportHref = mode === 'admin' && name === 'products' ? `/api/admin/exports/products?vendor_id=${vendorId}` : mode === 'admin' && name === 'orders' ? `/api/admin/exports/orders?vendor_id=${vendorId}` : mode === 'admin' && name === 'finance' ? `${base}/analytics/export-summary?${new URLSearchParams(params)}` : null;
    return <Card className="border-border/80 shadow-none"><CardHeader className="gap-3 sm:flex-row sm:items-center sm:justify-between"><CardTitle className="text-base">{labels[name]}</CardTitle><div className="flex flex-wrap items-center gap-2">{exportHref && <Button asChild variant="outline" size="sm"><a href={exportHref}>CSV</a></Button>}{['products', 'orders'].includes(name) && <label className="relative"><Search className="pointer-events-none absolute start-3 top-3 size-4 text-muted-foreground" /><Input aria-label={labels.search} value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} className="min-h-11 ps-9" placeholder={labels.search} /></label>}</div></CardHeader><CardContent><ResponsiveRecords rows={state.rows} status={state.status} type={name} locale={locale} mode={mode} empty={labels.no_data} labels={labels} common={common} /></CardContent>{state.meta && <div className="px-6 pb-4"><Pagination meta={state.meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} /></div>}</Card>;
}

function ResponsiveRecords({ rows, status, type, locale, mode, empty, labels, common }) {
    if (status === 'loading' || status === 'idle') return <div className="space-y-2" aria-busy="true">{[1,2,3,4].map((i) => <div key={i} className="h-14 animate-pulse rounded-md bg-muted" />)}</div>;
    if (!rows.length) return <Empty text={empty} />;
    if (type === 'orders') return <div className="space-y-2">{rows.map((row, index) => <OrderDetailRow key={row?.id ?? index} row={row} locale={locale} labels={labels} common={common} />)}</div>;
    return <div className="space-y-2">{rows.map((row, index) => <div key={row?.id ?? index} className="grid gap-2 rounded-lg border border-border p-3 text-sm md:grid-cols-[minmax(12rem,2fr)_repeat(4,minmax(7rem,1fr))] md:items-center"><Record row={row} type={type} locale={locale} mode={mode} /></div>)}</div>;
}
function Record({ row, type, locale, mode }) {
    if (type === 'products') return <><Link href={mode === 'admin' ? route('admin.products.show', row.id) : '#'} className="flex items-center gap-3 font-semibold"><Avatar className="size-10 rounded-md"><AvatarImage src={row.image_url} /><AvatarFallback><Package className="size-4" /></AvatarFallback></Avatar>{row.name}</Link><span>{row.category?.name}</span><span>{row.units_sold} units</span><span>{formatValue(row.completed_sales_amount, true, locale)}</span><span>{row.status} · {row.is_active ? 'active' : 'inactive'}</span></>;
    if (type === 'returns') return <><span className="font-semibold">{row.return_number}</span><span>{row.order?.order_number}</span><span>{row.reason}</span><span>{formatValue(row.refund_amount ?? row.scoped_return_amount, true, locale)}</span><span>{row.status}</span></>;
    if (type === 'finance') return <><span className="font-semibold">{row.type}</span><span>{row.order?.order_number ?? '—'}</span><span>{row.direction}</span><span>{formatValue(row.amount, true, locale)}</span><span>{formatDate(row.created_at, locale)}</span></>;
    if (type === 'documents') return <><span className="font-semibold">{row.title}</span><span>{row.type_name}</span><span>{row.status_name}</span><span>{row.reviewed_by ?? '—'}</span><span>{formatDate(row.created_at, locale)}</span></>;
    if (type === 'staff') return <><span className="font-semibold">{row.name}</span><span>{row.email}</span><span>{row.role_name}</span><span>{row.status ?? 'owner'}</span><span>{formatDate(row.joined_at, locale)}</span></>;
    return <><span className="font-semibold">{row.action}</span><span>{row.actor?.name ?? '—'}</span><span>{row.entity_type}</span><span>{row.entity_id}</span><span>{formatDate(row.created_at, locale)}</span></>;
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
                <span>{row.item_count} items</span>
                <span>{formatValue(row.scoped_sales ?? row.subtotal, true, locale)}</span>
                <StatusBadge tone={row.status === 'completed' ? 'success' : row.status === 'cancelled' ? 'danger' : 'warning'}>{row.status}</StatusBadge>
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
                                    <p>{row.payment.provider} · {row.payment.method}</p>
                                    <p className="text-muted-foreground">{labels.payment_amount}: {formatValue(row.payment.amount, true, locale)}{Number(row.payment.refunded_amount) > 0 ? ` · ${labels.refunded_amount}: ${formatValue(row.payment.refunded_amount, true, locale)}` : ''}</p>
                                    <StatusBadge tone={row.payment.status === 'paid' ? 'success' : row.payment.status === 'failed' ? 'danger' : 'warning'}>{row.payment.status}</StatusBadge>
                                    {row.payment.provider_reference && <p className="mt-1 text-muted-foreground">{labels.reference}: {row.payment.provider_reference}</p>}
                                    {row.payment.failure_reason && <p className="text-muted-foreground">{labels.failure_reason}: {row.payment.failure_reason}</p>}
                                </>
                            ) : <p className="text-muted-foreground">{common.not_specified}</p>}
                            {row.coupon && <p className="mt-2 text-muted-foreground">{labels.coupon}: {row.coupon.code} ({row.coupon.type} {row.coupon.value})</p>}
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
                                    <li key={i}>{h.from ? `${h.from} → ${h.to}` : h.to} — {formatDate(h.created_at, locale)}{h.changed_by ? ` · ${labels.changed_by}: ${h.changed_by}` : ''}{h.reason ? ` · ${h.reason}` : ''}</li>
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
function formatValue(value, money, locale) { if (value === null || value === undefined) return '—'; const formatted = Number(value || 0).toLocaleString(locale, { maximumFractionDigits: money ? 2 : 0 }); return money ? `${formatted} SYP` : formatted; }
function formatDate(value, locale) { return value ? new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(new Date(value)) : '—'; }
