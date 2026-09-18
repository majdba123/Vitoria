import { useCallback, useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Wallet, DollarSign, HandCoins, TrendingDown, Truck, Undo2, Store } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatCard } from '@/Components/shared/dashboard/StatCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency } from '@/lib/date-time';

export default function AdminFinancials() {
    const { admin, vendor: vendorCopy } = useI18n();
    const locale = useLocale();
    const [status, setStatus] = useState('loading');
    const [data, setData] = useState(null);

    const load = useCallback((signal) => {
        setStatus('loading');
        window.axios.get('/api/admin/financials/summary', { silent: true, signal }).then((res) => {
            setData(res.data?.data ?? {});
            setStatus('ready');
        }).catch((error) => {
            if (error.name !== 'CanceledError') {
                setStatus('error');
            }
        });
    }, []);

    useEffect(() => {
        const controller = new AbortController();
        load(controller.signal);

        return () => controller.abort();
    }, [load]);

    const topVendors = data?.top_vendors_by_commission ?? [];

    return (
        <AdminLayout title={admin.financials_title}>
            <PageHeader title={admin.financials_title} copy={admin.financials_copy} />

            <section className="space-y-3">
                <h3 className="text-sm font-bold text-foreground">{admin.financials_section_commissions}</h3>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <StatCard label={admin.total_commission} value={formatCurrency(data?.commission_total, locale)} icon={Wallet} status={status} onRetry={load} />
                    <StatCard label={admin.total_settled} value={formatCurrency(data?.settled_total, locale)} icon={HandCoins} status={status} onRetry={load} tone="success" />
                    <StatCard label={admin.total_outstanding} value={formatCurrency(data?.outstanding_total, locale)} icon={TrendingDown} status={status} onRetry={load} tone="danger" />
                </div>
            </section>

            <section className="space-y-3">
                <h3 className="text-sm font-bold text-foreground">{admin.financials_section_sales}</h3>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <StatCard label={admin.total_gross_sales} value={formatCurrency(data?.gross_sales_total, locale)} icon={DollarSign} status={status} onRetry={load} />
                    <StatCard label={admin.total_refunds} value={formatCurrency(data?.refunds_total, locale)} icon={Undo2} status={status} onRetry={load} tone="danger" />
                    <StatCard label={admin.total_net_earnings} value={formatCurrency(data?.net_earnings_total, locale)} icon={Store} status={status} onRetry={load} />
                </div>
            </section>

            <section className="space-y-3">
                <h3 className="text-sm font-bold text-foreground">{admin.financials_section_shipping}</h3>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <StatCard label={admin.total_shipping_fees} value={formatCurrency(data?.shipping_fees_total, locale)} icon={Truck} status={status} onRetry={load} />
                    <StatCard label={admin.vendors_with_commission} value={data?.vendor_count ?? 0} icon={Store} status={status} onRetry={load} />
                </div>
            </section>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">{admin.top_vendors_by_commission}</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{admin.th_store}</TableHead>
                                <TableHead>{vendorCopy.th_commission_amount}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {topVendors.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={2} className="py-6 text-center text-sm text-muted-foreground">{admin.financials_no_commission_yet}</TableCell>
                                </TableRow>
                            ) : (
                                topVendors.map((row) => (
                                    <TableRow key={row.vendor_id}>
                                        <TableCell className="font-semibold">
                                            <Link href={route('admin.vendors.commission', row.vendor_id)} className="hover:underline">
                                                {row.store_name ?? `${admin.vendor_label} #${row.vendor_id}`}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="font-semibold text-primary">{formatCurrency(row.commission, locale)}</TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
