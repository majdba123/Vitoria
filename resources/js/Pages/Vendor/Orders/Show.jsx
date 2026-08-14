import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import VendorLayout from '@/Layouts/VendorLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DetailCard } from '@/Components/admin/DetailCard';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
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
import { useI18n } from '@/hooks/use-i18n';

const STATUS_TONE = { pending: 'warning', confirmed: 'success', preparing: 'success', shipped: 'brand', out_for_delivery: 'brand', completed: 'brand', cancelled: 'danger' };

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

function money(v) {
    return `${Number.parseFloat(v || 0).toLocaleString()} SYP`;
}

export default function VendorOrdersShow({ orderId }) {
    const { vendor, common } = useI18n();
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
            setMessage({ tone: 'success', text: 'Order status updated.' });
            setIsUpdating(false);
            load();
        }).catch((error) => {
            setMessage({ tone: 'danger', text: error.response?.data?.message ?? 'Failed to update order status.' });
            setIsUpdating(false);
        });
    };

    const cancelOrder = () => {
        const reason = window.prompt(`Cancellation reason (${CANCEL_REASONS.join(', ')}):`, CANCEL_REASONS[0]);
        if (reason === null) return;
        const matchedReason = CANCEL_REASONS.includes(reason) ? reason : 'other';

        setIsCancelling(true);
        setMessage(null);
        window.axios.patch(`/api/vendor/orders/${orderId}/cancel`, { reason: matchedReason }, { silent: true }).then(() => {
            setMessage({ tone: 'success', text: 'Order cancelled.' });
            setIsCancelling(false);
            load();
        }).catch((error) => {
            setMessage({ tone: 'danger', text: error.response?.data?.message ?? 'Failed to cancel order.' });
            setIsCancelling(false);
        });
    };

    if (status === 'loading') {
        return (
            <VendorLayout title="Order details">
                <Skeleton className="h-96 w-full" />
            </VendorLayout>
        );
    }

    if (status === 'error' || !order) {
        return (
            <VendorLayout title="Order details">
                <p className="text-sm font-medium text-[var(--color-danger-strong)]">Failed to load order details.</p>
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
                breadcrumb={[{ label: vendor.orders, href: route('vendor.orders.index') }, { label: 'Details' }]}
                title={order.order_number || `Order #${order.id}`}
                copy={order.created_at ? new Date(order.created_at).toLocaleString() : ''}
                actions={
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('vendor.orders.index')}>{common.back ?? 'Back'}</Link>
                    </Button>
                }
            />

            <Card className="border-border/80 shadow-none">
                <CardContent className="space-y-4 p-5 sm:p-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge tone={STATUS_TONE[order.status] ?? 'warning'}>{order.status}</StatusBadge>
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
                                            {nextOptions.map((s) => <SelectItem key={s} value={s}>{s}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                    <Button size="sm" onClick={updateStatus} disabled={isUpdating}>
                                        {isUpdating && <Loader2 className="size-4 animate-spin" />}
                                        Update status
                                    </Button>
                                </>
                            )}
                            {canCancel && (
                                <Button size="sm" variant="destructive" onClick={cancelOrder} disabled={isCancelling}>
                                    {isCancelling && <Loader2 className="size-4 animate-spin" />}
                                    Cancel order
                                </Button>
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>

            <div className="grid gap-4 lg:grid-cols-2">
                <DetailCard title="Customer" fields={[{ label: 'Name', value: order.user?.name || 'Unknown customer' }, { label: 'Email', value: order.user?.email }]} />
                <Card className="border-border/80 shadow-none">
                    <CardHeader className="border-b border-border/80">
                        <CardTitle className="text-base font-bold">Shipping address</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-1 p-5 text-sm text-muted-foreground">
                        {shippingLines.length ? shippingLines.map((line, i) => <p key={i}>{line}</p>) : <p>No shipping address on file.</p>}
                    </CardContent>
                </Card>
            </div>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="border-b border-border/80">
                    <CardTitle className="text-base font-bold">Items</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 p-5">
                    {(order.items || []).map((item, index) => (
                        <div key={item.id ?? index} className="rounded-md border border-border p-4">
                            <p className="text-sm font-bold text-foreground">{item.product_name || 'Product'}</p>
                            <div className="mt-3 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3">
                                {[
                                    { label: 'Category', value: item.product?.category?.name || '—' },
                                    { label: 'Original unit price', value: money(item.original_unit_price) },
                                    { label: 'Applied unit price', value: money(item.unit_price) },
                                    { label: 'Discount %', value: item.has_discount ? `${Number.parseFloat(item.applied_discount_percentage || 0).toLocaleString()}%` : '—' },
                                    { label: 'Saved amount', value: item.has_discount ? money(item.discount_amount) : '0 SYP' },
                                    { label: 'Line total', value: money(item.line_total) },
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
                    <CardTitle className="text-base font-bold">Payment & totals</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-2 p-5 text-sm sm:grid-cols-2">
                    {[
                        { label: 'Subtotal', value: money(order.subtotal_amount) },
                        { label: 'Coupon discount', value: order.coupon_discount_amount > 0 ? `- ${money(order.coupon_discount_amount)}` : null },
                        { label: 'Shipping', value: money(order.shipping_total) },
                        { label: 'Tax', value: order.tax_total > 0 ? money(order.tax_total) : null },
                        { label: 'Payment method', value: order.payment_way },
                    ].filter((row) => row.value !== null && row.value !== undefined).map((row) => (
                        <div key={row.label} className="flex items-center justify-between rounded-md bg-muted px-3 py-2">
                            <span className="text-muted-foreground">{row.label}</span>
                            <span className="font-semibold text-foreground">{row.value}</span>
                        </div>
                    ))}
                    <div className="flex items-center justify-between rounded-md border border-primary/30 bg-accent px-3 py-2 font-bold text-accent-foreground sm:col-span-2">
                        <span>Grand total</span>
                        <span>{money(order.grand_total)}</span>
                    </div>
                </CardContent>
            </Card>
        </VendorLayout>
    );
}
