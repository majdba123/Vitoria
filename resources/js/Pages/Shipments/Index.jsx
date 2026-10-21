import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, XAxis } from 'recharts';
import { Plus, Package, Boxes, Wallet, Tag, Receipt, BadgeCheck, ShieldCheck } from 'lucide-react';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DataTable } from '@/Components/shared/DataTable';
import { Pagination } from '@/Components/shared/Pagination';
import { StatCard } from '@/Components/shared/dashboard/StatCard';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { HorizontalRankingChart } from '@/Components/shared/dashboard/HorizontalRankingChart';
import { DeleteConfirmDialog } from '@/Components/admin/DeleteConfirmDialog';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { useAdminList } from '@/hooks/use-admin-list';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency, formatDate, formatNumber } from '@/lib/date-time';
import { shipmentsWorkspace } from '@/lib/shipments-workspace';

const EMPTY_FILTERS = { search: '', port_id: 'all', syndicate_id: 'all', sticker_price: 'all', released: 'all', country: '', date_from: '', date_to: '' };

function toParams(filters) {
    return Object.fromEntries(
        Object.entries(filters)
            .filter(([, value]) => value !== '' && value !== 'all')
            .map(([key, value]) => [key, value]),
    );
}

function MonthlyChart({ rows, label, locale }) {
    const data = rows.map((row) => ({ month: row.month, count: row.shipments_count }));
    const monthLabel = (value) => formatDate(`${value}-01`, locale, { dateStyle: undefined, month: 'short', year: '2-digit' }) || value;

    return (
        <>
            <ChartContainer config={{ count: { label, color: 'var(--chart-1)' } }} className="aspect-auto h-56 w-full">
                <BarChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
                    <CartesianGrid vertical={false} strokeDasharray="3 3" />
                    <XAxis dataKey="month" tickLine={false} axisLine={false} tickMargin={8} tickFormatter={monthLabel} interval="preserveStartEnd" />
                    <ChartTooltip content={<ChartTooltipContent labelKey="month" labelFormatter={monthLabel} />} cursor={{ fill: 'var(--color-accent)' }} />
                    <Bar dataKey="count" fill="var(--color-count)" radius={[4, 4, 0, 0]} maxBarSize={28} />
                </BarChart>
            </ChartContainer>
            <p className="sr-only">{data.map((row) => `${row.month}: ${row.count}`).join('. ')}</p>
        </>
    );
}

function ChartCard({ title, isEmpty, emptyLabel, children }) {
    return (
        <Card className="border-border/80 shadow-none">
            <CardHeader className="pb-2">
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>{isEmpty ? <p className="py-10 text-center text-sm text-muted-foreground">{emptyLabel}</p> : children}</CardContent>
        </Card>
    );
}

export default function ShipmentsIndex({ workspace, sector = 'combined' }) {
    const { importShipments: t, common, admin } = useI18n();
    const locale = useLocale();
    const { Layout, apiBase, routeName, href, readOnly, sectorParams } = shipmentsWorkspace(workspace, sector);

    const [page, setPage] = useState(1);
    const [filters, setFilters] = useState(EMPTY_FILTERS);
    const [options, setOptions] = useState({ ports: [], syndicates: [], sticker_prices: [200, 400], can_pick_syndicate: false });
    const [stats, setStats] = useState(null);
    const [statsStatus, setStatsStatus] = useState('loading');
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const params = { ...toParams(filters), ...sectorParams };
    const paramsKey = JSON.stringify(params);
    const { status, rows, meta, errorMessage, reload } = useAdminList(apiBase, { ...params, page });

    useEffect(() => {
        window.axios.get(`${apiBase}/options`, { silent: true }).then((res) => setOptions(res.data?.data ?? options));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [apiBase]);

    const loadStats = () => {
        setStatsStatus('loading');
        window.axios
            .get(`${apiBase}/stats`, { params, silent: true })
            .then((res) => {
                setStats(res.data?.data ?? null);
                setStatsStatus('ready');
            })
            .catch(() => setStatsStatus('error'));
    };

    useEffect(loadStats, [apiBase, paramsKey]); // eslint-disable-line react-hooks/exhaustive-deps

    const setFilter = (key, value) => {
        setFilters((current) => ({ ...current, [key]: value }));
        setPage(1);
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setIsDeleting(true);
        window.axios
            .delete(`${apiBase}/${deleteTarget.id}`, { silent: true })
            .then(() => {
                setDeleteTarget(null);
                reload();
                loadStats();
            })
            .finally(() => setIsDeleting(false));
    };

    const totals = stats?.totals ?? {};
    const columns = [
        {
            key: 'merchant',
            label: t.merchant_name,
            render: (row) => (
                <div className="min-w-0">
                    <p className="font-semibold text-foreground">{row.merchant_name}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground" dir="auto">
                        {t.merchant_register_number}: {row.merchant_register_number} · {t.invoice_number}: {row.invoice_number}
                    </p>
                </div>
            ),
        },
        { key: 'port', label: t.port, render: (row) => row.port?.name ?? '—' },
        ...(options.can_pick_syndicate ? [{ key: 'syndicate', label: t.syndicate, render: (row) => row.syndicate?.name ?? '—' }] : []),
        { key: 'items', label: t.items_count, align: 'end', render: (row) => formatNumber(row.items_count ?? 0, locale) },
        { key: 'value', label: t.items_value, align: 'end', render: (row) => <span dir="auto">{formatCurrency(row.items_sum_total_price ?? 0, locale)}</span> },
        { key: 'stickers', label: t.stickers_total, align: 'end', render: (row) => <span dir="auto">{formatNumber(row.stickers_count, locale)} × {formatNumber(row.sticker_price, locale)} = {formatNumber(row.stickers_total, locale)}</span> },
        {
            key: 'release',
            label: t.release_decision,
            render: (row) => (row.release_decision ? <StatusBadge tone="success">{t.released}</StatusBadge> : <StatusBadge tone="warning">{t.not_released}</StatusBadge>),
        },
        { key: 'date', label: t.created_at, render: (row) => <span dir="auto">{formatDate(row.created_at, locale) || '—'}</span> },
        {
            key: 'actions',
            label: admin.th_actions,
            align: 'end',
            render: (row) => (
                <div className="inline-flex items-center gap-1.5">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={href('show', row.id)}>{common.view_details}</Link>
                    </Button>
                    {!readOnly && (
                        <>
                            <Button asChild variant="outline" size="sm">
                                <Link href={route(routeName('edit'), row.id)}>{common.edit}</Link>
                            </Button>
                            <Button variant="outline" size="sm" className="text-[var(--color-danger-strong)]" onClick={() => setDeleteTarget(row)}>
                                {common.delete}
                            </Button>
                        </>
                    )}
                </div>
            ),
        },
    ];

    const hasFilters = Object.keys(toParams(filters)).length > 0;

    return (
        <Layout title={t.title}>
            <PageHeader
                title={t.title}
                copy={t.subtitle}
                actions={
                    readOnly ? null : (
                        <Button asChild size="sm">
                            <Link href={route(routeName('create'))}>
                                <Plus className="size-4" />
                                {t.add}
                            </Link>
                        </Button>
                    )
                }
            />

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="sm:col-span-2">
                        <Label htmlFor="shipment-search" className="sr-only">{t.search_placeholder}</Label>
                        <Input id="shipment-search" placeholder={t.search_placeholder} value={filters.search} onChange={(e) => setFilter('search', e.target.value)} />
                    </div>
                    <Select value={filters.port_id} onValueChange={(v) => setFilter('port_id', v)}>
                        <SelectTrigger className="w-full" aria-label={t.port}><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{t.filter_all_ports}</SelectItem>
                            {options.ports.map((port) => <SelectItem key={port.id} value={String(port.id)}>{port.name}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.sticker_price} onValueChange={(v) => setFilter('sticker_price', v)}>
                        <SelectTrigger className="w-full" aria-label={t.sticker_price}><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{t.filter_all_prices}</SelectItem>
                            {options.sticker_prices.map((price) => <SelectItem key={price} value={String(price)}>{formatNumber(price, locale)}</SelectItem>)}
                        </SelectContent>
                    </Select>
                    <Select value={filters.released} onValueChange={(v) => setFilter('released', v)}>
                        <SelectTrigger className="w-full" aria-label={t.release_decision}><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{t.filter_all_release}</SelectItem>
                            <SelectItem value="1">{t.released}</SelectItem>
                            <SelectItem value="0">{t.not_released}</SelectItem>
                        </SelectContent>
                    </Select>
                    {options.can_pick_syndicate && (
                        <Select value={filters.syndicate_id} onValueChange={(v) => setFilter('syndicate_id', v)}>
                            <SelectTrigger className="w-full" aria-label={t.syndicate}><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{t.filter_all_syndicates}</SelectItem>
                                {options.syndicates.map((syndicate) => <SelectItem key={syndicate.id} value={String(syndicate.id)}>{syndicate.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                    )}
                    <Input aria-label={t.filter_country} placeholder={t.filter_country} value={filters.country} onChange={(e) => setFilter('country', e.target.value)} />
                    <div className="flex items-center gap-2">
                        <Label htmlFor="shipment-date-from" className="shrink-0 text-xs text-muted-foreground">{t.date_from}</Label>
                        <Input id="shipment-date-from" type="date" dir="ltr" value={filters.date_from} onChange={(e) => setFilter('date_from', e.target.value)} />
                    </div>
                    <div className="flex items-center gap-2">
                        <Label htmlFor="shipment-date-to" className="shrink-0 text-xs text-muted-foreground">{t.date_to}</Label>
                        <Input id="shipment-date-to" type="date" dir="ltr" value={filters.date_to} onChange={(e) => setFilter('date_to', e.target.value)} />
                    </div>
                    {hasFilters && (
                        <Button variant="ghost" size="sm" className="justify-self-start" onClick={() => { setFilters(EMPTY_FILTERS); setPage(1); }}>
                            {t.reset_filters}
                        </Button>
                    )}
                </CardContent>
            </Card>

            <section aria-label={t.stats_title} className="flex flex-col gap-4">
                <h2 className="text-base font-bold text-foreground">{t.stats_title}</h2>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard label={t.stat_shipments} value={totals.shipments_count ?? 0} icon={Package} status={statsStatus} onRetry={loadStats} />
                    <StatCard label={t.stat_items} value={totals.items_count ?? 0} icon={Boxes} status={statsStatus} onRetry={loadStats} />
                    <StatCard label={t.stat_total_value} value={formatCurrency(totals.total_value ?? 0, locale)} icon={Wallet} status={statsStatus} onRetry={loadStats} />
                    <StatCard label={t.stat_stickers} value={totals.stickers_count ?? 0} icon={Tag} status={statsStatus} onRetry={loadStats} />
                    <StatCard label={t.stat_stickers_total} value={formatCurrency(totals.stickers_total ?? 0, locale)} icon={Receipt} status={statsStatus} onRetry={loadStats} />
                    <StatCard label={t.stat_cleared} value={totals.cleared_count ?? 0} icon={ShieldCheck} status={statsStatus} onRetry={loadStats} />
                    <StatCard label={t.stat_released} value={totals.released_count ?? 0} icon={BadgeCheck} status={statsStatus} onRetry={loadStats} tone="success" />
                </div>

                {statsStatus === 'ready' && stats && (
                    <div className="grid gap-4 lg:grid-cols-2">
                        <ChartCard title={t.chart_monthly} isEmpty={!totals.shipments_count} emptyLabel={t.empty_title}>
                            <MonthlyChart rows={stats.monthly} label={t.stat_shipments} locale={locale} />
                        </ChartCard>
                        <ChartCard title={t.chart_by_port} isEmpty={stats.by_port.length === 0} emptyLabel={t.empty_title}>
                            <HorizontalRankingChart rows={stats.by_port} labelKey="name" valueKey="shipments_count" valueLabel={t.stat_shipments} />
                        </ChartCard>
                        <ChartCard title={t.chart_by_country} isEmpty={stats.by_country.length === 0} emptyLabel={t.empty_title}>
                            <HorizontalRankingChart rows={stats.by_country} labelKey="country" valueKey="total_value" valueLabel={t.stat_total_value} />
                        </ChartCard>
                        <ChartCard title={t.chart_by_sticker_price} isEmpty={stats.by_sticker_price.length === 0} emptyLabel={t.empty_title}>
                            <HorizontalRankingChart
                                rows={stats.by_sticker_price.map((row) => ({ ...row, label: formatNumber(row.sticker_price, locale) }))}
                                labelKey="label"
                                valueKey="shipments_count"
                                valueLabel={t.stat_shipments}
                            />
                        </ChartCard>
                    </div>
                )}
            </section>

            <div>
                <DataTable
                    columns={columns}
                    rows={rows}
                    status={status}
                    errorMessage={errorMessage}
                    onRetry={reload}
                    rowHref={(row) => href('show', row.id)}
                    emptyTitle={t.empty_title}
                    emptyHint={hasFilters ? null : t.empty_hint}
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
                title={t.delete_title}
                description={t.delete_warning}
                isDeleting={isDeleting}
                onConfirm={confirmDelete}
            />
        </Layout>
    );
}
