import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Loader2 } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency, formatNumber, formatPercent } from '@/lib/date-time';
import { ORDER_STATUS_TONE as STATUS_TONE } from '@/lib/order-status';
import { translatedEnum, translatedStatus } from '@/lib/translated-enum';

const CANCELLABLE_STATUSES = ['pending', 'confirmed', 'preparing'];

export default function OrdersShow({ orderId }) {
    const { nav, common, orders } = useI18n();
    const locale = useLocale();
    const money = (v) => formatCurrency(v, locale, 'SYP');
    const [status, setStatus] = useState('loading');
    const [order, setOrder] = useState(null);
    const [errorMessage, setErrorMessage] = useState(null);
    const [isCancelling, setIsCancelling] = useState(false);
    const [actionMessage, setActionMessage] = useState(null);

    const load = () => {
        window.axios.get(`/api/orders/${orderId}`, { silent: true }).then((res) => {
            setOrder(res.data.data);
            setStatus('ready');
        }).catch((err) => {
            setErrorMessage(err.response?.data?.message || orders.load_failed);
            setStatus('error');
        });
    };

    useEffect(load, [orderId]);

    const cancelOrder = () => {
        setIsCancelling(true);
        window.axios.patch(`/api/orders/${orderId}/cancel`, {}, { silent: true }).then((res) => {
            setActionMessage({ tone: 'success', text: res.data?.message || orders.cancelled_success });
            setTimeout(load, 500);
        }).catch((err) => {
            setActionMessage({ tone: 'error', text: err.response?.data?.message || orders.cancel_failed });
        }).finally(() => setIsCancelling(false));
    };

    return (
        <PublicLayout title={orders.details} noindex>
            <div className="bg-transparent">
                <div className="catalog-page-band">
                    <div className="page-shell py-3">
                        <nav className="page-breadcrumb">
                            <Link href={route('home')} className="hover:text-primary">{nav.home}</Link>
                            <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                            <Link href={route('profile')} className="hover:text-primary">{common.profile}</Link>
                            <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                            <span className="font-medium text-foreground">{orders.details}</span>
                        </nav>
                    </div>
                </div>

                <div className="page-shell">
                    {status === 'loading' && (
                        <div className="py-16 text-center">
                            <div className="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-border border-t-primary" />
                            <p className="mt-3 text-sm text-muted-foreground">{orders.loading_order}</p>
                        </div>
                    )}

                    {status === 'error' && (
                        <div className="rounded-lg border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-3 text-sm font-medium text-[var(--color-danger-strong)]">{errorMessage}</div>
                    )}

                    {status === 'ready' && order && (
                        <div className="space-y-6">
                            <div className="overflow-hidden rounded-lg border border-border bg-card">
                                <div className="border-b border-border p-4">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="min-w-0">
                                            <h1 className="text-xl font-bold text-foreground">{order.order_number || `Order #${order.id}`}</h1>
                                            <p className="mt-1 text-sm text-muted-foreground">{order.created_at ? new Date(order.created_at).toLocaleDateString() : '—'}</p>
                                            {order.vendor && (
                                                <p className="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground">
                                                    {orders.sold_by}
                                                    <Link href={route('vendors.show', order.vendor.id)} className="font-medium text-foreground hover:text-primary hover:underline">
                                                        {order.vendor.store_name}
                                                    </Link>
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                                            <StatusBadge tone={STATUS_TONE[order.status] ?? 'warning'}>{translatedStatus(order.status, common)}</StatusBadge>
                                            <StatusBadge tone="brand">{translatedEnum(order.payment_way || 'cash', common.not_available, orders)}</StatusBadge>
                                        </div>
                                    </div>
                                    <div className="mt-3 flex flex-wrap items-center gap-2">
                                        {CANCELLABLE_STATUSES.includes(order.status) && (
                                            <button type="button" onClick={cancelOrder} disabled={isCancelling} className="btn-danger btn-xs inline-flex items-center gap-1.5">
                                                {isCancelling && <Loader2 className="size-3 animate-spin" />}
                                                {orders.cancel_order_btn}
                                            </button>
                                        )}
                                        {actionMessage && (
                                            <p className={`text-xs font-semibold ${actionMessage.tone === 'success' ? 'text-[var(--color-success-strong)]' : 'text-[var(--color-danger-strong)]'}`}>{actionMessage.text}</p>
                                        )}
                                    </div>
                                    <Link href={route('profile')} className="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                                        <ChevronLeft className="h-3.5 w-3.5 rtl:rotate-180" />
                                        {orders.back_to_profile}
                                    </Link>
                                </div>
                                <div className="grid gap-3 p-4 sm:grid-cols-2">
                                    <div className="rounded-md border border-border bg-muted/40 p-3">
                                        <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{orders.order_id_label}</p>
                                        <p className="mt-1 text-sm font-bold text-foreground">{order.id}</p>
                                    </div>
                                    <div className="rounded-md border border-border bg-muted/40 p-3">
                                        <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{orders.items_count}</p>
                                        <p className="mt-1 text-sm font-bold text-foreground">{formatNumber(order.items_count ?? (order.items || []).length, locale)}</p>
                                    </div>
                                </div>
                            </div>

                            <div className="grid gap-6 lg:grid-cols-12">
                                <section className="lg:col-span-8">
                                    <div className="rounded-lg border border-border bg-card p-4">
                                        <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <h3 className="text-base font-bold text-foreground">{orders.items_details}</h3>
                                            <StatusBadge tone="brand">{orders.detailed_view}</StatusBadge>
                                        </div>
                                        <div className="space-y-3">
                                            {(order.items || []).map((item, index) => (
                                                <article key={item.id ?? index} className="rounded-lg border border-border p-4">
                                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                                        <div>
                                                            <p className="text-sm font-bold text-foreground">{index + 1}. {item.product_name || orders.product}</p>
                                                            <p className="text-[11px] text-muted-foreground">{(orders.item_meta ?? '').replace(':item', String(item.id ?? '—')).replace(':product', String(item.product_id ?? '—')).replace(':qty', String(item.quantity ?? 0))}</p>
                                                        </div>
                                                        <div className="flex items-center gap-1.5">
                                                            <StatusBadge tone={item.has_discount ? 'success' : 'brand'}>{item.has_discount ? orders.discount_applied : orders.standard_price}</StatusBadge>
                                                            <StatusBadge tone="brand">{money(item.line_total)}</StatusBadge>
                                                        </div>
                                                    </div>
                                                    <div className="mt-3 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3">
                                                        {[
                                                            { label: common.category, value: item.category_name || '—' },
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
                                                </article>
                                            ))}
                                        </div>
                                    </div>
                                </section>

                                <aside className="space-y-4 lg:col-span-4">
                                    <div className="rounded-lg border border-border bg-card p-4">
                                        <h3 className="text-sm font-bold text-foreground">{orders.totals_summary}</h3>
                                        <div className="mt-3 space-y-2 text-sm">
                                            <div className="flex items-center justify-between text-muted-foreground"><span>{orders.products_subtotal}</span><span>{money(order.subtotal_amount)}</span></div>
                                            <div className="flex items-center justify-between text-muted-foreground"><span>{orders.coupon_code}</span><span>{order.coupon?.code || '—'}</span></div>
                                            <div className="flex items-center justify-between text-muted-foreground"><span>{orders.coupon_type}</span><span>{order.coupon?.type || '—'}</span></div>
                                            <div className="flex items-center justify-between text-muted-foreground"><span>{orders.coupon_value}</span><span>{order.coupon?.value ? formatNumber(order.coupon.value, locale) : '—'}</span></div>
                                            <div className="flex items-center justify-between text-muted-foreground"><span>{orders.coupon_discount}</span><span>- {money(order.coupon_discount_amount)}</span></div>
                                            <div className="mt-2 flex items-center justify-between rounded-md border border-primary/30 bg-accent px-3 py-2 text-base font-bold text-accent-foreground">
                                                <span>{orders.grand_total}</span><span>{money(order.total_amount)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
