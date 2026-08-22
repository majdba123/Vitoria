import { useEffect, useState } from 'react';
import VendorLayout from '@/Layouts/VendorLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { StatCard } from '@/Components/admin/dashboard/StatCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Wallet, DollarSign, HandCoins, TrendingDown } from 'lucide-react';
import { useI18n } from '@/hooks/use-i18n';

function formatMoney(amount) {
    return `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(Number(amount || 0))} SYP`;
}

const SUMMARY_METRICS = [
    { key: 'projected_order_total', labelKey: 'completed_orders_total', icon: DollarSign },
    { key: 'commission_total', labelKey: 'commission_total_label', icon: Wallet },
    { key: 'paid_amount', labelKey: 'paid_to_you', icon: HandCoins, tone: 'success' },
    { key: 'remaining_amount', labelKey: 'remaining_label', icon: TrendingDown, tone: 'danger' },
];

export default function VendorCommission() {
    const { vendor } = useI18n();
    const [status, setStatus] = useState('loading');
    const [data, setData] = useState(null);

    const load = () => {
        setStatus('loading');
        window.axios.get('/api/vendor/commission-stats', { silent: true }).then((res) => {
            setData(res.data?.data ?? {});
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, []);

    const vendorInfo = data?.vendor ?? {};
    const financials = data?.financials ?? {};
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
                        <p className="text-sm font-medium text-[var(--color-danger-strong)]">Failed to load commission stats.</p>
                        <Button variant="outline" size="sm" className="mt-3" onClick={load}>Retry</Button>
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

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {SUMMARY_METRICS.map(({ key, labelKey, icon, tone }) => (
                    <StatCard
                        key={key}
                        label={vendor[labelKey]}
                        value={formatMoney(financials[key])}
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
                            <p className="mt-1 text-lg font-bold text-[var(--color-success-strong)]">{formatMoney(financials.paid_amount)}</p>
                        </div>
                        <div className="rounded-md border border-border bg-muted/40 p-3">
                            <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">{vendor.remaining_amount_label}</p>
                            <p className="mt-1 text-lg font-bold text-[var(--color-danger-strong)]">{formatMoney(financials.remaining_amount)}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

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
                                    <TableCell colSpan={4} className="py-6 text-center text-sm text-muted-foreground">No completed orders found.</TableCell>
                                </TableRow>
                            ) : (
                                categoryBreakdown.map((row, index) => (
                                    <TableRow key={index}>
                                        <TableCell className="font-semibold">{row.category_name ?? 'Unknown'}</TableCell>
                                        <TableCell>{Number(row.commission_rate || 0).toFixed(2)}%</TableCell>
                                        <TableCell className="font-semibold">{formatMoney(row.sales_total)}</TableCell>
                                        <TableCell className="font-semibold text-primary">{formatMoney(row.commission_amount)}</TableCell>
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
                        <p className="col-span-7 text-sm text-muted-foreground">No trend data available.</p>
                    ) : (
                        trend.map((point, index) => {
                            const value = Number(point.count || 0);
                            const date = new Date(point.date);
                            const label = Number.isNaN(date.getTime()) ? point.date : date.toLocaleDateString(undefined, { weekday: 'short' });
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
