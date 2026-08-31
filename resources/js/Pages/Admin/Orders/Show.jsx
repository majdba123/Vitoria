import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DetailCard } from '@/Components/shared/DetailCard';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency, formatDate, formatPercent } from '@/lib/date-time';
import { ORDER_STATUS_TONE as STATUS_TONE } from '@/lib/order-status';
import { translatedEnum, translatedStatus } from '@/lib/translated-enum';

export default function OrdersShow({ orderId }) {
    const { orders: copy, common } = useI18n();
    const locale = useLocale();
    const [status, setStatus] = useState('loading');
    const [order, setOrder] = useState(null);
    const [isCompleting, setIsCompleting] = useState(false);
    const [actionMessage, setActionMessage] = useState(null);
    const money = (value) => formatCurrency(value || 0, locale);

    const load = () => {
        window.axios.get(`/api/admin/orders/${orderId}`, { silent: true }).then((res) => {
            setOrder(res.data.data);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, [orderId]);

    const markCompleted = () => {
        setIsCompleting(true);
        window.axios.patch(`/api/admin/orders/${orderId}/complete`, {}, { silent: true }).then((res) => {
            setActionMessage({ tone: 'success', text: res.data?.message ?? copy.order_marked_completed });
            setIsCompleting(false);
            load();
        }).catch((error) => {
            setActionMessage({ tone: 'danger', text: error.response?.data?.message ?? copy.status_update_failed });
            setIsCompleting(false);
        });
    };

    if (status === 'loading') {
        return (
            <AdminLayout title={copy.details}>
                <Skeleton className="h-96 w-full" />
            </AdminLayout>
        );
    }

    if (status === 'error' || !order) {
        return (
            <AdminLayout title={copy.details}>
                <p className="text-sm font-medium text-[var(--color-danger-strong)]">{copy.load_failed}</p>
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title={order.order_number || copy.order_number_fallback.replace(':id', String(order.id))}>
            <PageHeader breadcrumb={[{ label: copy.orders, href: route('admin.orders.index') }, { label: copy.details }]} title={order.order_number || `${copy.order} #${order.id}`} copy={`${order.created_at ? formatDate(order.created_at, locale) : '—'} · ${copy.last_update}: ${order.updated_at ? formatDate(order.updated_at, locale) : '—'}`} />

            <Card className="border-border/80 shadow-none">
                <CardContent className="space-y-4 p-5 sm:p-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge tone={STATUS_TONE[order.status] ?? 'warning'}>{translatedStatus(order.status, common)}</StatusBadge>
                        <StatusBadge tone="brand">{translatedEnum(order.payment_way || 'cash', common.not_available, copy)}</StatusBadge>
                    </div>

                    {order.status === 'out_for_delivery' && (
                        <div className="flex items-center gap-3">
                            <Button size="sm" onClick={markCompleted} disabled={isCompleting} className="bg-[var(--color-success-500)] text-white hover:bg-[var(--color-success-600)]">
                                {isCompleting && <Loader2 className="size-4 animate-spin" />}
                                {copy.mark_completed}
                            </Button>
                            {actionMessage && (
                                <p className={`text-xs font-semibold ${actionMessage.tone === 'success' ? 'text-[var(--color-success-strong)]' : 'text-[var(--color-danger-strong)]'}`}>{actionMessage.text}</p>
                            )}
                        </div>
                    )}

                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            { label: copy.order_number, value: order.order_number },
                            { label: copy.vendor, value: order.vendor?.store_name },
                            { label: copy.items_count, value: order.items_count ?? (order.items || []).length },
                            { label: copy.payment_method, value: translatedEnum(order.payment_way || 'cash', common.not_available, copy) },
                        ].map((item) => (
                            <div key={item.label} className="rounded-md border border-border bg-muted/40 p-3">
                                <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{item.label}</p>
                                <p className="mt-1 text-sm font-bold text-foreground">{item.value ?? '—'}</p>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>

            <div className="grid gap-4 lg:grid-cols-2">
                <DetailCard title={copy.customer_info} fields={[{ label: copy.name, value: order.user?.name }, { label: copy.email, value: order.user?.email }]} />
                <DetailCard title={copy.vendor_info} fields={[{ label: copy.store, value: order.vendor?.store_name }]} />
            </div>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="flex-row items-center justify-between border-b border-border/80">
                    <CardTitle className="text-base font-bold">{copy.items_details}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 p-5">
                    {(order.items || []).map((item, index) => (
                        <div key={item.id ?? index} className="rounded-md border border-border p-4">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p className="text-sm font-bold text-foreground">{index + 1}. {item.product_name || copy.product}</p>
                                    <p className="mt-1 text-sm font-semibold text-primary">{copy.quantity}: <span className="tabular-nums">{item.quantity ?? 0}</span></p>
                                    <p className="mt-1 text-xs text-muted-foreground">{copy.vendor}: {order.vendor?.store_name || '—'}</p>
                                </div>
                                <StatusBadge tone="brand">{copy.line_total}: {money(item.line_total)}</StatusBadge>
                            </div>
                            <div className="mt-3 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3">
                                {[
                                    { label: copy.unit_price, value: money(item.original_unit_price ?? item.unit_price) },
                                    item.has_discount ? { label: copy.discount, value: formatPercent(item.applied_discount_percentage || 0, locale) } : null,
                                    item.has_discount && Number(item.original_unit_price) !== Number(item.unit_price) ? { label: copy.final_unit_price, value: money(item.unit_price) } : null,
                                    { label: copy.line_total, value: money(item.line_total) },
                                ].filter(Boolean).map((param) => (
                                    <div key={param.label} className="rounded-md border border-border bg-muted/40 px-2.5 py-2">
                                        <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{param.label}</p>
                                        <p className="mt-0.5 font-semibold text-foreground">{param.value}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">{copy.financial_summary}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-2 p-5 text-sm">
                    {[
                        { label: copy.products_subtotal, value: money(order.subtotal_amount), visible: true },
                        { label: order.coupon_code ? `${copy.coupon_discount} (${order.coupon_code})` : copy.discount, value: `- ${money(order.coupon_discount_amount)}`, visible: Number(order.coupon_discount_amount) > 0 },
                        { label: copy.shipping, value: money(order.shipping_total), visible: Number(order.shipping_total) > 0 },
                        { label: copy.tax, value: money(order.tax_total), visible: Number(order.tax_total) > 0 },
                    ].filter((row) => row.visible).map((row) => (
                        <div key={row.label} className="flex items-center justify-between rounded-md bg-muted px-3 py-2">
                            <span className="text-muted-foreground">{row.label}</span>
                            <span className="font-semibold text-foreground">{row.value}</span>
                        </div>
                    ))}
                    <div className="flex items-center justify-between rounded-md border border-primary/30 bg-accent px-3 py-2 font-bold text-accent-foreground">
                        <span>{copy.grand_total}</span>
                        <span>{money(order.grand_total ?? order.total_amount)}</span>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
