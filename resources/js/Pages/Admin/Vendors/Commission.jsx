import { useEffect, useState } from 'react';
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

function formatMoney(amount) {
    return `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(Number(amount || 0))} SYP`;
}

export default function VendorsCommission({ vendorId }) {
    const [status, setStatus] = useState('loading');
    const [data, setData] = useState(null);
    const [paidAmount, setPaidAmount] = useState('');
    const [isSaving, setIsSaving] = useState(false);
    const [message, setMessage] = useState(null);

    const load = () => {
        setStatus('loading');
        window.axios.get(`/api/admin/vendors/${vendorId}/commission-stats`, { silent: true }).then((res) => {
            const payload = res.data?.data ?? {};
            setData(payload);
            setPaidAmount(String(payload.financials?.paid_amount ?? 0));
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, [vendorId]);

    const savePaid = (event) => {
        event.preventDefault();
        const amount = Number(paidAmount);
        if (Number.isNaN(amount) || amount < 0) {
            setMessage({ tone: 'danger', text: 'Paid amount must be zero or greater.' });
            return;
        }

        const commissionTotal = Number(data?.financials?.commission_total ?? 0);
        if (amount > commissionTotal && !window.confirm(`This paid amount (${formatMoney(amount)}) exceeds the commission total (${formatMoney(commissionTotal)}) and will result in a negative remaining amount. Continue anyway?`)) {
            return;
        }

        setIsSaving(true);
        window.axios.post(`/api/admin/vendors/${vendorId}/commission-paid`, { paid_amount: amount }, { silent: true }).then((res) => {
            setMessage({ tone: 'success', text: res.data?.message ?? 'Paid amount updated.' });
            setIsSaving(false);
            load();
        }).catch((error) => {
            setMessage({ tone: 'danger', text: error.response?.data?.message ?? 'Failed to update paid amount.' });
            setIsSaving(false);
        });
    };

    const vendor = data?.vendor ?? {};
    const financials = data?.financials ?? {};
    const orders = data?.orders ?? {};
    const statusCounts = orders.status_counts ?? {};
    const total = Number(orders.total || 0);
    const categoryBreakdown = data?.category_breakdown ?? [];
    const trend = data?.completed_orders_last_7_days ?? [];
    const trendMax = Math.max(...trend.map((p) => Number(p.count || 0)), 1);

    return (
        <AdminLayout title="Vendor commission dashboard">
            <PageHeader
                breadcrumb={[{ label: 'Vendors', href: route('admin.vendors.index') }]}
                title={vendor.store_name ? `${vendor.store_name} — Commission dashboard` : `Vendor #${vendorId}`}
                copy="Commission is calculated by category commission for completed orders only."
                actions={
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('admin.vendors.index')}>Back to vendors</Link>
                    </Button>
                }
            />

            {message && (
                <p className={`rounded-md border px-4 py-2.5 text-sm font-medium ${message.tone === 'success' ? 'border-[var(--color-success-200)] bg-[var(--color-success-soft)] text-[var(--color-success-strong)]' : 'border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] text-[var(--color-danger-strong)]'}`}>
                    {message.text}
                </p>
            )}

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Completed orders total" value={formatMoney(financials.completed_order_total)} icon={DollarSign} status={status} />
                <StatCard label="Commission total" value={formatMoney(financials.commission_total)} icon={Wallet} status={status} />
                <StatCard label="Paid to vendor" value={formatMoney(financials.paid_amount)} icon={HandCoins} status={status} tone="success" />
                <StatCard label="Remaining" value={formatMoney(financials.remaining_amount)} icon={TrendingDown} status={status} tone="danger" />
            </div>

            <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <Card className="border-border/80 shadow-none">
                    <CardHeader className="border-b border-border/80">
                        <CardTitle className="text-base font-bold">Order status statistics</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 p-5">
                        {[
                            { key: 'pending', label: 'Pending', color: 'var(--color-warning-500)' },
                            { key: 'completed', label: 'Completed', color: 'var(--color-success-500)' },
                            { key: 'cancelled', label: 'Cancelled', color: 'var(--color-danger-500)' },
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
                        <CardTitle className="text-base font-bold">Update paid amount</CardTitle>
                    </CardHeader>
                    <CardContent className="p-5">
                        <p className="mb-3 text-xs text-muted-foreground">Set total paid to vendor. Remaining amount updates automatically.</p>
                        <form onSubmit={savePaid} className="space-y-3">
                            <TextField id="paid_amount" label="Paid amount" type="number" min="0" step="0.01" value={paidAmount} onChange={(e) => setPaidAmount(e.target.value)} />
                            <Button type="submit" size="sm" disabled={isSaving}>
                                {isSaving && <Loader2 className="size-4 animate-spin" />}
                                Save paid amount
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">Commission by category</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Category</TableHead>
                                <TableHead>Commission %</TableHead>
                                <TableHead>Sales total</TableHead>
                                <TableHead>Commission amount</TableHead>
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
                    <CardTitle className="text-base font-bold">Last 7 days completed orders</CardTitle>
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
        </AdminLayout>
    );
}
