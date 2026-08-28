import { useCallback, useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { StatCard } from '@/Components/admin/dashboard/StatCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { TextField } from '@/Components/admin/form/FormField';
import { Wallet, DollarSign, HandCoins, TrendingDown } from 'lucide-react';
import { useI18n, useLocale } from '@/hooks/use-i18n';

function formatMoney(amount, locale) {
    return `${new Intl.NumberFormat(locale === 'ar' ? 'ar-SY' : 'en-US', { maximumFractionDigits: 2 }).format(Number(amount || 0))} SYP`;
}

export default function VendorsCommission({ vendorId }) {
    const { admin, vendor: vendorCopy } = useI18n();
    const locale = useLocale();
    const [status, setStatus] = useState('loading');
    const [data, setData] = useState(null);
    const [paidAmount, setPaidAmount] = useState('');
    const [isSaving, setIsSaving] = useState(false);
    const [message, setMessage] = useState(null);

    const load = useCallback((signal) => {
        setStatus('loading');
        window.axios.get(`/api/admin/vendors/${vendorId}/commission-stats`, { silent: true, signal }).then((res) => {
            const payload = res.data?.data ?? {};
            setData(payload);
            setPaidAmount(String(payload.financials?.paid_amount ?? 0));
            setStatus('ready');
        }).catch((error) => {
            if (error.name !== 'CanceledError') {
                setStatus('error');
            }
        });
    }, [vendorId]);

    useEffect(() => {
        const controller = new AbortController();
        load(controller.signal);

        return () => controller.abort();
    }, [load]);

    const savePaid = (event) => {
        event.preventDefault();
        const amount = Number(paidAmount);
        if (Number.isNaN(amount) || amount < 0) {
            setMessage({ tone: 'danger', text: admin.commission_paid_non_negative });
            return;
        }

        // The actual cap the ledger enforces is `remaining_amount` (outstanding),
        // not `commission_total` — commission is the platform's cut, not the
        // amount owed to the vendor. Warn against the real cap the backend
        // will reject against, not a different, unrelated figure.
        const alreadySettled = Number(data?.financials?.paid_amount ?? 0);
        const trueOutstandingCap = alreadySettled + Number(data?.financials?.remaining_amount ?? 0);
        const capWarning = admin.commission_paid_exceeds_outstanding
            .replace(':amount', formatMoney(amount, locale))
            .replace(':outstanding', formatMoney(trueOutstandingCap, locale));
        if (amount > trueOutstandingCap && !window.confirm(capWarning)) {
            return;
        }

        setIsSaving(true);
        window.axios.post(`/api/admin/vendors/${vendorId}/commission-paid`, { paid_amount: amount }, { silent: true }).then((res) => {
            setMessage({ tone: 'success', text: res.data?.message ?? admin.commission_paid_updated });
            setIsSaving(false);
            load();
        }).catch((error) => {
            setMessage({ tone: 'danger', text: error.response?.data?.message ?? admin.commission_paid_update_failed });
            setIsSaving(false);
        });
    };

    const vendor = data?.vendor ?? {};
    const financials = data?.financials ?? {};
    const orders = data?.orders ?? {};
    const statusCounts = orders.status_counts ?? {};
    const total = Number(orders.total || 0);
    const categoryBreakdown = data?.category_breakdown ?? [];
    const trend = data?.recent_orders_last_7_days ?? [];
    const trendMax = Math.max(...trend.map((p) => Number(p.count || 0)), 1);

    return (
        <AdminLayout title={vendorCopy.commission_title}>
            <PageHeader
                breadcrumb={[{ label: admin.vendors, href: route('admin.vendors.index') }]}
                title={vendor.store_name ? `${vendor.store_name} — ${vendorCopy.commission_dashboard_suffix}` : `${admin.vendor_label} #${vendorId}`}
                copy={vendorCopy.commission_dashboard_copy}
                actions={
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('admin.vendors.index')}>{admin.commission_back_to_vendors}</Link>
                    </Button>
                }
            />

            {message && (
                <p className={`rounded-md border px-4 py-2.5 text-sm font-medium ${message.tone === 'success' ? 'border-[var(--color-success-200)] bg-[var(--color-success-soft)] text-[var(--color-success-strong)]' : 'border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]'}`}>
                    {message.text}
                </p>
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label={vendorCopy.completed_orders_total} value={formatMoney(financials.projected_order_total, locale)} icon={DollarSign} status={status} />
                <StatCard label={vendorCopy.commission_total_label} value={formatMoney(financials.commission_total, locale)} icon={Wallet} status={status} />
                <StatCard label={admin.commission_paid_to_vendor} value={formatMoney(financials.paid_amount, locale)} icon={HandCoins} status={status} tone="success" />
                <StatCard label={vendorCopy.remaining_label} value={formatMoney(financials.remaining_amount, locale)} icon={TrendingDown} status={status} tone="danger" />
            </div>

            <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <Card className="border-border/80 shadow-none">
                    <CardHeader className="border-b border-border/80">
                        <CardTitle className="text-base font-bold">{vendorCopy.order_status_statistics}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 p-5">
                        {[
                            { key: 'pending', label: vendorCopy.status_pending, color: 'var(--color-warning-500)' },
                            { key: 'completed', label: vendorCopy.status_completed, color: 'var(--color-success-500)' },
                            { key: 'cancelled', label: vendorCopy.status_cancelled, color: 'var(--color-danger-500)' },
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
                        <CardTitle className="text-base font-bold">{admin.commission_update_paid_amount}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-5">
                        <p className="mb-3 text-xs text-muted-foreground">{admin.commission_update_paid_hint}</p>
                        <form onSubmit={savePaid} className="space-y-3">
                            <TextField id="paid_amount" label={vendorCopy.paid_amount_label} type="number" min="0" step="0.01" value={paidAmount} onChange={(e) => setPaidAmount(e.target.value)} />
                            <Button type="submit" size="sm" disabled={isSaving}>
                                {isSaving && <Loader2 className="size-4 animate-spin" />}
                                {admin.commission_save_paid_amount}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">{vendorCopy.commission_by_category}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{vendorCopy.th_category}</TableHead>
                                <TableHead>{vendorCopy.th_commission_percent}</TableHead>
                                <TableHead>{vendorCopy.th_sales_total}</TableHead>
                                <TableHead>{vendorCopy.th_commission_amount}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categoryBreakdown.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={4} className="py-6 text-center text-sm text-muted-foreground">{vendorCopy.js_no_completed_orders_found}</TableCell>
                                </TableRow>
                            ) : (
                                categoryBreakdown.map((row, index) => (
                                    <TableRow key={index}>
                                        <TableCell className="font-semibold">{row.category_name ?? vendorCopy.js_unknown_category}</TableCell>
                                        <TableCell>{Number(row.commission_rate || 0).toFixed(2)}%</TableCell>
                                        <TableCell className="font-semibold">{formatMoney(row.sales_total, locale)}</TableCell>
                                        <TableCell className="font-semibold text-primary">{formatMoney(row.commission_amount, locale)}</TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">{vendorCopy.last_7_days_completed_orders}</CardTitle>
                </CardHeader>
                <CardContent className="grid grid-cols-7 gap-2 p-5">
                    {trend.length === 0 ? (
                        <p className="col-span-7 text-sm text-muted-foreground">{vendorCopy.js_no_trend_data}</p>
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
        </AdminLayout>
    );
}
