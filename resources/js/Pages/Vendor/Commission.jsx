import { useEffect, useState } from 'react';
import VendorLayout from '@/Layouts/VendorLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { StatCard } from '@/Components/admin/dashboard/StatCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Wallet, DollarSign, HandCoins, TrendingDown, ShoppingBag } from 'lucide-react';
import { useI18n, useLocale } from '@/hooks/use-i18n';

function formatMoney(amount, locale, currency) {
    return `${new Intl.NumberFormat(locale === 'ar' ? 'ar-SY' : 'en-US', { maximumFractionDigits: 2 }).format(Number(amount || 0))} ${currency}`;
}

const SUMMARY_METRICS = [
    { key: 'gross_sales', labelKey: 'gross_sales', icon: DollarSign },
    { key: 'completed_orders', labelKey: 'status_completed', icon: ShoppingBag, count: true },
    { key: 'net_earnings', labelKey: 'net_earnings', icon: Wallet },
    { key: 'commission', labelKey: 'commission_total_label', icon: TrendingDown },
    { key: 'settled', labelKey: 'paid_to_you', icon: HandCoins, tone: 'success' },
    { key: 'outstanding', labelKey: 'remaining_label', icon: TrendingDown, tone: 'danger' },
];

export default function VendorCommission() {
    const { vendor } = useI18n();
    const locale = useLocale();
    const [status, setStatus] = useState('loading');
    const [data, setData] = useState(null);

    const load = () => {
        setStatus('loading');
        Promise.all([
            window.axios.get('/api/vendor/commission-stats', { silent: true }),
            window.axios.get('/api/vendor/ledger/summary', { silent: true }),
            window.axios.get('/api/vendor/ledger', { silent: true }),
        ]).then(([stats, summary, ledger]) => {
            setData({
                ...(stats.data?.data ?? {}),
                ledgerSummary: summary.data?.data ?? {},
                ledgerEntries: ledger.data?.data ?? [],
            });
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, []);

    const vendorInfo = data?.vendor ?? {};
    const ledgerSummary = data?.ledgerSummary ?? {};
    const ledgerEntries = data?.ledgerEntries ?? [];
    const orders = data?.orders ?? {};
    const statusCounts = orders.status_counts ?? {};
    const total = Number(orders.total || 0);
    const categoryBreakdown = data?.category_breakdown ?? [];
    const trend = data?.recent_orders_last_7_days ?? [];
    const trendMax = Math.max(...trend.map((p) => Number(p.count || 0)), 1);

    if (status === 'error') {
        return (
            <VendorLayout title={vendor.commission_title}>
                <PageHeader title={vendor.commission_dashboard_heading} copy={vendor.commission_dashboard_copy} />
                <Card className="border-border/80 shadow-none">
                    <CardContent className="py-14 text-center">
                        <p className="text-sm font-medium text-[var(--color-danger-strong)]">{vendor.js_failed_load_commission_stats}</p>
                        <Button variant="outline" size="sm" className="mt-3" onClick={load}>{vendor.retry}</Button>
                    </CardContent>
                </Card>
            </VendorLayout>
        );
    }

    return (
        <VendorLayout title={vendor.commission_title}>
            <PageHeader
                title={vendorInfo.store_name ? `${vendorInfo.store_name} — ${vendor.commission_dashboard_suffix}` : vendor.commission_dashboard_heading}
                copy={vendor.commission_dashboard_copy}
            />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                {SUMMARY_METRICS.map(({ key, labelKey, icon, tone, count }) => (
                    <StatCard
                        key={key}
                        label={vendor[labelKey]}
                        value={count ? Number(statusCounts.completed || 0).toLocaleString(locale) : formatMoney(ledgerSummary[key], locale, vendor.currency_syp)}
                        icon={icon}
                        status={status}
                        tone={tone}
                    />
                ))}
            </div>

            <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <Card className="border-border/80 shadow-none">
                    <CardHeader className="border-b border-border/80">
                        <CardTitle className="text-base font-bold">{vendor.order_status_statistics}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 p-5">
                        {[
                            { key: 'pending', label: vendor.status_pending, color: 'var(--color-warning-500)' },
                            { key: 'completed', label: vendor.status_completed, color: 'var(--color-success-500)' },
                            { key: 'cancelled', label: vendor.status_cancelled, color: 'var(--color-danger-500)' },
                        ].map((row) => {
                            const value = Number(statusCounts[row.key] || 0);
                            const pct = total > 0 ? Math.round((value / total) * 100) : 0;
                            return (
                                <div key={row.key}>
                                    <div className="mb-1 flex items-center justify-between text-xs">
                                        <span className="font-semibold text-foreground">{row.label}</span>
                                        <span className="font-bold" style={{ color: row.color }}>{value} ({pct}%)</span>
                                    </div>
                                    <div className="h-2.5 overflow-hidden rounded-full bg-muted">
                                        <div className="h-full rounded-full transition-all duration-500" style={{ width: `${pct}%`, background: row.color }} />
                                    </div>
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>

                <Card className="border-border/80 shadow-none">
                    <CardHeader className="border-b border-border/80">
                        <CardTitle className="text-base font-bold">{vendor.payment_summary}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 p-5">
                        <p className="text-xs text-muted-foreground">{vendor.payment_summary_copy}</p>
                        <div className="rounded-md border border-border bg-muted/40 p-3">
                            <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">{vendor.paid_amount_label}</p>
                            <p className="mt-1 text-lg font-bold text-[var(--color-success-strong)]">{formatMoney(ledgerSummary.settled, locale, vendor.currency_syp)}</p>
                        </div>
                        <div className="rounded-md border border-border bg-muted/40 p-3">
                            <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">{vendor.remaining_amount_label}</p>
                            <p className="mt-1 text-lg font-bold text-[var(--color-danger-strong)]">{formatMoney(ledgerSummary.outstanding, locale, vendor.currency_syp)}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">{vendor.financial_history}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{vendor.th_date}</TableHead>
                                <TableHead>{vendor.th_type}</TableHead>
                                <TableHead>{vendor.th_direction}</TableHead>
                                <TableHead>{vendor.th_amount}</TableHead>
                                <TableHead>{vendor.th_description}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {ledgerEntries.length === 0 ? (
                                <TableRow><TableCell colSpan={5} className="py-8 text-center text-muted-foreground">{vendor.no_financial_movements}</TableCell></TableRow>
                            ) : ledgerEntries.map((entry) => (
                                <TableRow key={entry.id}>
                                    <TableCell>{new Date(entry.created_at).toLocaleDateString(locale)}</TableCell>
                                    <TableCell className="font-medium">{entry.type_name}</TableCell>
                                    <TableCell><span className={entry.direction === 'credit' ? 'text-[var(--color-success-strong)]' : 'text-[var(--color-danger-strong)]'}>{entry.direction === 'credit' ? vendor.credit : vendor.debit}</span></TableCell>
                                    <TableCell className="tabular-nums">{formatMoney(entry.amount, locale, vendor.currency_syp)}</TableCell>
                                    <TableCell className="whitespace-normal text-muted-foreground">{entry.description}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">{vendor.commission_by_category}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{vendor.th_category}</TableHead>
                                <TableHead>{vendor.th_commission_percent}</TableHead>
                                <TableHead>{vendor.th_sales_total}</TableHead>
                                <TableHead>{vendor.th_commission_amount}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categoryBreakdown.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} className="py-6 text-center text-sm text-muted-foreground">{vendor.js_no_completed_orders_found}</TableCell>
                                </TableRow>
                            ) : (
                                categoryBreakdown.map((row, index) => (
                                    <TableRow key={index}>
                                        <TableCell className="font-semibold">{row.category_name ?? vendor.js_unknown_category}</TableCell>
                                        <TableCell>{Number(row.commission_rate || 0).toFixed(2)}%</TableCell>
                                        <TableCell className="font-semibold">{formatMoney(row.sales_total, locale, vendor.currency_syp)}</TableCell>
                                        <TableCell className="font-semibold text-primary">{formatMoney(row.commission_amount, locale, vendor.currency_syp)}</TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">{vendor.last_7_days_completed_orders}</CardTitle>
                </CardHeader>
                <CardContent className="grid grid-cols-7 gap-2 p-5">
                    {trend.length === 0 ? (
                        <p className="col-span-7 text-sm text-muted-foreground">{vendor.js_no_trend_data}</p>
                    ) : (
                        trend.map((point, index) => {
                            const value = Number(point.count || 0);
                            const date = new Date(point.date);
                            const label = Number.isNaN(date.getTime()) ? point.date : date.toLocaleDateString(locale, { weekday: 'short' });
                            const height = Math.max(Math.round((value / trendMax) * 88), 10);
                            return (
                                <div key={index} className="flex flex-col items-center gap-2 rounded-md border border-border bg-muted p-2">
                                    <div className="flex h-24 items-end">
                                        <div className="w-5 rounded-t bg-primary/90" style={{ height }} />
                                    </div>
                                    <p className="text-[11px] font-bold text-foreground">{label}</p>
                                    <p className="text-[11px] font-semibold text-muted-foreground">{value}</p>
                                </div>
                            );
                        })
                    )}
                </CardContent>
            </Card>
        </VendorLayout>
    );
}
