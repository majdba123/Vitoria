import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import VendorLayout from '@/Layouts/VendorLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { DetailCard } from '@/Components/shared/DetailCard';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency, formatDateTime, formatPercent } from '@/lib/date-time';
import { ORDER_STATUS_TONE as STATUS_TONE } from '@/lib/order-status';
import { translatedEnum, translatedStatus } from '@/lib/translated-enum';

const TRANSITIONS = {
    pending: ['confirmed', 'cancelled'],
    confirmed: ['preparing', 'cancelled'],
    preparing: ['shipped', 'cancelled'],
    shipped: ['out_for_delivery'],
    out_for_delivery: ['completed'],
    completed: [],
    cancelled: [],
};
const CANCELLABLE_STATUSES = ['pending', 'confirmed', 'preparing'];
const CANCEL_REASONS = ['customer_changed_mind', 'wrong_order', 'unavailable_product', 'vendor_issue', 'delivery_issue', 'payment_issue', 'duplicate_order', 'other'];

export default function VendorOrdersShow({ orderId }) {
    const { vendor, common, orders } = useI18n();
    const locale = useLocale();
    const money = (v) => formatCurrency(v, locale, 'SYP');
    const [status, setStatus] = useState('loading');
    const [order, setOrder] = useState(null);
    const [nextStatus, setNextStatus] = useState('');
    const [isUpdating, setIsUpdating] = useState(false);
    const [isCancelling, setIsCancelling] = useState(false);
    const [message, setMessage] = useState(null);

    const load = () => {
        window.axios.get(`/api/vendor/orders/${orderId}`, { silent: true }).then((res) => {
            const data = res.data.data;
            setOrder(data);
            const options = (TRANSITIONS[data.status] || []).filter((s) => s !== 'cancelled');
            setNextStatus(options[0] ?? '');
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, [orderId]);

    const updateStatus = () => {
        if (!nextStatus) return;
        setIsUpdating(true);
        setMessage(null);
        window.axios.patch(`/api/vendor/orders/${orderId}/status`, { status: nextStatus }, { silent: true }).then(() => {
            setMessage({ tone: 'success', text: orders.transition_success });
            setIsUpdating(false);
            load();
        }).catch((error) => {
            setMessage({ tone: 'danger', text: error.response?.data?.message ?? orders.update_status_failed });
            setIsUpdating(false);
        });
    };

    const cancelOrder = () => {
        const reasonLabels = CANCEL_REASONS.map((r) => orders.cancel_reason?.[r] ?? r);
        const reason = window.prompt(`${orders.cancellation_reason_prompt} (${reasonLabels.join(', ')}):`, reasonLabels[0]);
        if (reason === null) return;
        const matchedIndex = reasonLabels.indexOf(reason);
        const matchedReason = matchedIndex >= 0 ? CANCEL_REASONS[matchedIndex] : 'other';

        setIsCancelling(true);
        setMessage(null);
        window.axios.patch(`/api/vendor/orders/${orderId}/cancel`, { reason: matchedReason }, { silent: true }).then(() => {
            setMessage({ tone: 'success', text: orders.cancelled_success });
            setIsCancelling(false);
            load();
        }).catch((error) => {
            setMessage({ tone: 'danger', text: error.response?.data?.message ?? orders.cancel_failed });
            setIsCancelling(false);
        });
    };

    if (status === 'loading') {
        return (
            <VendorLayout title={orders.details}>
                <Skeleton className="h-96 w-full" />
            </VendorLayout>
        );
    }

    if (status === 'error' || !order) {
        return (
            <VendorLayout title={orders.details}>
                <p className="text-sm font-medium text-[var(--color-danger-strong)]">{orders.load_failed}</p>
            </VendorLayout>
        );
    }

    const nextOptions = (TRANSITIONS[order.status] || []).filter((s) => s !== 'cancelled');
    const canCancel = CANCELLABLE_STATUSES.includes(order.status);
    const shippingLines = [
        order.ship_recipient_name,
        [order.ship_street, order.ship_building].filter(Boolean).join(', '),
        [order.ship_district, order.ship_city, order.ship_governorate].filter(Boolean).join(', '),
        order.ship_phone,
        order.ship_notes,
    ].filter((line) => line && String(line).trim() !== '');

    return (
        <VendorLayout title={order.order_number || `Order #${order.id}`}>
            <PageHeader
                breadcrumb={[{ label: vendor.orders, href: route('vendor.orders.index') }, { label: orders.details }]}
                title={order.order_number || `Order #${order.id}`}
                copy={order.created_at ? formatDateTime(order.created_at, locale) : ''}
                actions={
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('vendor.orders.index')}>{common.back}</Link>
                    </Button>
                }
            />

            <Card className="border-border/80 shadow-none">
                <CardContent className="space-y-4 p-5 sm:p-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge tone={STATUS_TONE[order.status] ?? 'warning'}>{translatedStatus(order.status, common)}</StatusBadge>
                    </div>

                    {message && (
                        <p className={`text-xs font-semibold ${message.tone === 'success' ? 'text-[var(--color-success-strong)]' : 'text-[var(--color-danger-strong)]'}`}>{message.text}</p>
                    )}

                    {(nextOptions.length > 0 || canCancel) && (
                        <div className="flex flex-wrap items-center gap-2 border-t border-border pt-4">
                            {nextOptions.length > 0 && (
                                <>
                                    <Select value={nextStatus} onValueChange={setNextStatus}>
                                        <SelectTrigger size="sm" className="w-40"><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            {nextOptions.map((s) => <SelectItem key={s} value={s}>{translatedStatus(s, common)}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                    <Button size="sm" onClick={updateStatus} disabled={isUpdating}>
                                        {isUpdating && <Loader2 className="size-4 animate-spin" />}
                                        {orders.update_status_btn}
                                    </Button>
                                </>
                            )}
                            {canCancel && (
                                <Button size="sm" variant="destructive" onClick={cancelOrder} disabled={isCancelling}>
                                    {isCancelling && <Loader2 className="size-4 animate-spin" />}
                                    {orders.cancel_order_btn}
                                </Button>
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>

            <div className="grid gap-4 lg:grid-cols-2">
                <DetailCard title={orders.customer} fields={[{ label: orders.name, value: order.user?.name || orders.unknown_customer }, { label: orders.email, value: order.user?.email }]} />
                <Card className="border-border/80 shadow-none">
                    <CardHeader className="border-b border-border/80">
                        <CardTitle className="text-base font-bold">{orders.shipping_address}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-1 p-5 text-sm text-muted-foreground">
                        {shippingLines.length ? shippingLines.map((line, i) => <p key={i}>{line}</p>) : <p>{orders.no_shipping_address}</p>}
                    </CardContent>
                </Card>
            </div>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">{orders.items_details}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 p-5">
                    {(order.items || []).map((item, index) => (
                        <div key={item.id ?? index} className="rounded-md border border-border p-4">
                            <p className="text-sm font-bold text-foreground">{item.product_name || orders.product}</p>
                            <div className="mt-3 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3">
                                {[
                                    { label: common.category, value: item.product?.category?.name || '—' },
                                    { label: orders.original_unit_price, value: money(item.original_unit_price) },
                                    { label: orders.applied_unit_price, value: money(item.unit_price) },
                                    { label: orders.discount_percent, value: item.has_discount ? formatPercent(item.applied_discount_percentage, locale) : '—' },
                                    { label: orders.saved_amount, value: item.has_discount ? money(item.discount_amount) : money(0) },
                                    { label: orders.line_total, value: money(item.line_total) },
                                ].map((param) => (
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
                    <CardTitle className="text-base font-bold">{orders.payment_and_totals}</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-2 p-5 text-sm sm:grid-cols-2">
                    {[
                        { label: orders.products_subtotal, value: money(order.subtotal_amount) },
                        { label: orders.coupon_discount, value: order.coupon_discount_amount > 0 ? `- ${money(order.coupon_discount_amount)}` : null },
                        { label: orders.shipping, value: money(order.shipping_total) },
                        { label: orders.tax, value: order.tax_total > 0 ? money(order.tax_total) : null },
                        { label: orders.payment_method, value: translatedEnum(order.payment_way || 'cash', common.not_available, orders) },
                    ].filter((row) => row.value !== null && row.value !== undefined).map((row) => (
                        <div key={row.label} className="flex items-center justify-between rounded-md bg-muted px-3 py-2">
                            <span className="text-muted-foreground">{row.label}</span>
                            <span className="font-semibold text-foreground">{row.value}</span>
                        </div>
                    ))}
                    <div className="flex items-center justify-between rounded-md border border-primary/30 bg-accent px-3 py-2 font-bold text-accent-foreground sm:col-span-2">
                        <span>{orders.grand_total}</span>
                        <span>{money(order.grand_total)}</span>
                    </div>
                </CardContent>
            </Card>
        </VendorLayout>
    );
}
