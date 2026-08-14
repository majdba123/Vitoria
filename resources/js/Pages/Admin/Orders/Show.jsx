import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { DetailCard } from '@/Components/admin/DetailCard';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';

const STATUS_TONE = { pending: 'warning', confirmed: 'success', preparing: 'success', shipped: 'brand', out_for_delivery: 'brand', completed: 'brand', cancelled: 'danger' };

function money(v) {
    return `${Number.parseFloat(v || 0).toLocaleString()} SYP`;
}

export default function OrdersShow({ orderId }) {
    const [status, setStatus] = useState('loading');
    const [order, setOrder] = useState(null);
    const [isCompleting, setIsCompleting] = useState(false);
    const [actionMessage, setActionMessage] = useState(null);

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
            setActionMessage({ tone: 'success', text: res.data?.message ?? 'Order marked as completed.' });
            setIsCompleting(false);
            load();
        }).catch((error) => {
            setActionMessage({ tone: 'danger', text: error.response?.data?.message ?? 'Failed to update order status.' });
            setIsCompleting(false);
        });
    };

    if (status === 'loading') {
        return (
            <AdminLayout title="Order details">
                <Skeleton className="h-96 w-full" />
            </AdminLayout>
        );
    }

    if (status === 'error' || !order) {
        return (
            <AdminLayout title="Order details">
                <p className="text-sm font-medium text-[var(--color-danger-strong)]">Failed to load order details.</p>
            </AdminLayout>
        );
    }

    return (
        <AdminLayout title={order.order_number || `Order #${order.id}`}>
            <PageHeader breadcrumb={[{ label: 'Orders', href: route('admin.orders.index') }, { label: 'Details' }]} title={order.order_number || `Order #${order.id}`} copy={`${order.created_at ? new Date(order.created_at).toLocaleDateString() : '—'} · Last update: ${order.updated_at ? new Date(order.updated_at).toLocaleDateString() : '—'}`} />

            <Card className="border-border/80 shadow-none">
                <CardContent className="space-y-4 p-5 sm:p-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge tone={STATUS_TONE[order.status] ?? 'warning'}>{order.status}</StatusBadge>
                        <StatusBadge tone="brand">{order.payment_way || 'cash'}</StatusBadge>
                    </div>

                    {order.status === 'out_for_delivery' && (
                        <div className="flex items-center gap-3">
                            <Button size="sm" onClick={markCompleted} disabled={isCompleting} className="bg-[var(--color-success-500)] text-white hover:bg-[var(--color-success-600)]">
                                {isCompleting && <Loader2 className="size-4 animate-spin" />}
                                Mark as completed
                            </Button>
                            {actionMessage && (
                                <p className={`text-xs font-semibold ${actionMessage.tone === 'success' ? 'text-[var(--color-success-strong)]' : 'text-[var(--color-danger-strong)]'}`}>{actionMessage.text}</p>
                            )}
                        </div>
                    )}

                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            { label: 'Order ID', value: order.id },
                            { label: 'User ID', value: order.user_id },
                            { label: 'Vendor ID', value: order.vendor_id },
                            { label: 'Items count', value: order.items_count ?? (order.items || []).length },
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
                <DetailCard title="Customer info" fields={[{ label: 'Name', value: order.user?.name }, { label: 'Email', value: order.user?.email }]} />
                <DetailCard title="Vendor info" fields={[{ label: 'Store', value: order.vendor?.store_name }]} />
            </div>

            <Card className="border-border/80 shadow-none">
                <CardHeader className="flex-row items-center justify-between border-b border-border/80">
                    <CardTitle className="text-base font-bold">Items details</CardTitle>
                    <StatusBadge tone="brand">Full snapshot</StatusBadge>
                </CardHeader>
                <CardContent className="space-y-3 p-5">
                    {(order.items || []).map((item, index) => (
                        <div key={item.id ?? index} className="rounded-md border border-border p-4">
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p className="text-sm font-bold text-foreground">{index + 1}. {item.product_name || 'Product'}</p>
                                    <p className="text-[11px] text-muted-foreground">Item #{item.id ?? '—'} · Product #{item.product_id ?? '—'} · Qty {item.quantity ?? 0}</p>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <StatusBadge tone={item.has_discount ? 'success' : 'brand'}>{item.has_discount ? 'Discount applied' : 'Standard price'}</StatusBadge>
                                    <StatusBadge tone="brand">{money(item.line_total)}</StatusBadge>
                                </div>
                            </div>
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
                    <CardTitle className="text-base font-bold">Totals & coupon</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-2 p-5 text-sm sm:grid-cols-2">
                    {[
                        { label: 'Subtotal', value: money(order.subtotal_amount) },
                        { label: 'Coupon code', value: order.coupon_code || '—' },
                        { label: 'Coupon type', value: order.coupon_type || '—' },
                        { label: 'Coupon value', value: order.coupon_value ? Number.parseFloat(order.coupon_value).toLocaleString() : '—' },
                        { label: 'Coupon discount', value: `- ${money(order.coupon_discount_amount)}` },
                    ].map((row) => (
                        <div key={row.label} className="flex items-center justify-between rounded-md bg-muted px-3 py-2">
                            <span className="text-muted-foreground">{row.label}</span>
                            <span className="font-semibold text-foreground">{row.value}</span>
                        </div>
                    ))}
                    <div className="flex items-center justify-between rounded-md border border-primary/30 bg-accent px-3 py-2 font-bold text-accent-foreground">
                        <span>Total</span>
                        <span>{money(order.total_amount)}</span>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
