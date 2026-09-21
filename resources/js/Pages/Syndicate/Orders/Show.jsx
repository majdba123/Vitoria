import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import SyndicateLayout from '@/Layouts/SyndicateLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency as money, formatDateTime } from '@/lib/date-time';
import { ORDER_STATUS_TONE as STATUS_TONE } from '@/lib/order-status';
import { translatedStatus } from '@/lib/translated-enum';

function Field({ label, children }) {
    return (
        <div className="rounded-md border border-border bg-muted/30 px-3 py-2.5">
            <dt className="text-xs font-semibold text-muted-foreground">{label}</dt>
            <dd className="mt-1 break-words text-sm font-semibold text-foreground">{children ?? '—'}</dd>
        </div>
    );
}

export default function SyndicateOrderShow({ orderId }) {
    const { syndicate: t, common } = useI18n();
    const locale = useLocale();
    const [state, setState] = useState({ status: 'loading', order: null, notFound: false });

    const load = () => {
        setState({ status: 'loading', order: null, notFound: false });
        window.axios.get(`/api/syndicate/orders/${orderId}`, { silent: true })
            .then((res) => setState({ status: 'ready', order: res.data?.data ?? null, notFound: false }))
            .catch((error) => setState({ status: 'error', order: null, notFound: error.response?.status === 404 }));
    };

    useEffect(load, [orderId]);

    const { order, status } = state;
    const backHref = route('syndicate.orders');
    const totals = order?.totals;
    const paymentLabel = order?.payment_way === 'cash' ? t.payment_cash : (order?.payment_way ?? null);

    return (
        <SyndicateLayout title={t.order_details}>
            <PageHeader
                breadcrumb={[{ label: t.orders, href: backHref }, { label: order?.order_number ?? t.order_details }]}
                title={status === 'ready' ? `${t.order_details}: ${order.order_number}` : t.order_details}
                actions={<Button asChild variant="outline" size="sm"><Link href={backHref}><ArrowLeft className="size-4 rtl:rotate-180" aria-hidden="true" />{t.back_to_orders}</Link></Button>}
            />

            {status === 'loading' && <div className="space-y-3" aria-busy="true"><Skeleton className="h-32 w-full" /><Skeleton className="h-48 w-full" /></div>}

            {status === 'error' && (
                <Card className="border-border/80 shadow-none"><CardContent className="py-14 text-center" role="alert">
                    <p className="text-sm font-semibold text-[var(--color-danger-strong)]">{state.notFound ? t.detail_not_found : t.detail_load_failed}</p>
                    {!state.notFound && <Button variant="outline" size="sm" className="mt-3" onClick={load}>{common.retry}</Button>}
                </CardContent></Card>
            )}

            {status === 'ready' && order && (
                <div className="space-y-5">
                    <Card className="border-border/80 shadow-none">
                        <CardHeader><CardTitle className="text-base">{t.order_summary}</CardTitle></CardHeader>
                        <CardContent>
                            <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <Field label={t.th_order}><span dir="ltr">{order.order_number}</span></Field>
                                <Field label={t.th_status}><StatusBadge tone={STATUS_TONE[order.status] ?? 'warning'}>{t[`status_${order.status}`] ?? translatedStatus(order.status, common)}</StatusBadge></Field>
                                <Field label={t.order_date}>{formatDateTime(order.created_at, locale, { dateStyle: 'medium', timeStyle: 'short' })}</Field>
                                <Field label={t.order_vendor}>{order.vendor?.store_name}</Field>
                                <Field label={t.order_customer}>{order.customer_name}</Field>
                                <Field label={t.order_payment}>{paymentLabel}</Field>
                                <Field label={t.order_region}>{order.ship_governorate}</Field>
                            </dl>
                            <p className="mt-3 text-xs text-muted-foreground">{t.order_privacy_note}</p>
                        </CardContent>
                    </Card>

                    {order.is_partial && <p role="note" className="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-foreground">{t.order_partial_notice}</p>}

                    <Card className="border-border/80 shadow-none">
                        <CardHeader><CardTitle className="text-base">{t.order_items}</CardTitle></CardHeader>
                        <CardContent>
                            <ul className="divide-y divide-border rounded-lg border border-border">
                                <li aria-hidden="true" className="hidden bg-muted/40 px-4 py-2 text-xs font-bold text-muted-foreground sm:grid sm:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))]">
                                    <span>{t.th_product}</span><span>{t.th_quantity}</span><span>{t.th_unit_price}</span><span>{t.th_line_total}</span>
                                </li>
                                {order.items.map((item) => (
                                    <li key={item.id} className="grid gap-2 px-4 py-3 text-sm sm:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))] sm:items-center">
                                        <div className="min-w-0 font-semibold text-foreground">
                                            {item.product_id ? <Link href={route('syndicate.products.show', item.product_id)} className="hover:text-primary hover:underline">{item.name}</Link> : item.name}
                                        </div>
                                        <div className="flex justify-between gap-2 sm:block"><span className="text-xs text-muted-foreground sm:hidden">{t.th_quantity}</span><span className="tabular-nums">{item.quantity}</span></div>
                                        <div className="flex justify-between gap-2 sm:block"><span className="text-xs text-muted-foreground sm:hidden">{t.th_unit_price}</span><span className="tabular-nums">{item.original_unit_price != null && <s className="me-1.5 text-muted-foreground">{money(item.original_unit_price, locale)}</s>}{money(item.unit_price, locale)}</span></div>
                                        <div className="flex justify-between gap-2 sm:block"><span className="text-xs text-muted-foreground sm:hidden">{t.th_line_total}</span><span className="font-semibold tabular-nums">{money(item.line_total, locale)}</span></div>
                                    </li>
                                ))}
                            </ul>
                            <dl className="mt-4 ms-auto max-w-sm space-y-2 text-sm">
                                <div className="flex justify-between gap-4"><dt className="text-muted-foreground">{t.order_scoped_total}</dt><dd className="font-bold tabular-nums">{money(order.scoped_total, locale)}</dd></div>
                                {totals && (
                                    <>
                                        <div className="flex justify-between gap-4 border-t border-border pt-2"><dt className="text-muted-foreground">{t.order_subtotal}</dt><dd className="tabular-nums">{money(totals.subtotal, locale)}</dd></div>
                                        {totals.coupon_discount > 0 && <div className="flex justify-between gap-4"><dt className="text-muted-foreground">{t.order_coupon}</dt><dd className="tabular-nums">{money(totals.coupon_discount, locale)}</dd></div>}
                                        <div className="flex justify-between gap-4"><dt className="text-muted-foreground">{t.order_shipping}</dt><dd className="tabular-nums">{money(totals.shipping, locale)}</dd></div>
                                        <div className="flex justify-between gap-4"><dt className="text-muted-foreground">{t.order_tax}</dt><dd className="tabular-nums">{money(totals.tax, locale)}</dd></div>
                                        <div className="flex justify-between gap-4 border-t border-border pt-2 font-bold"><dt>{t.order_total}</dt><dd className="tabular-nums">{money(totals.total, locale)}</dd></div>
                                    </>
                                )}
                            </dl>
                        </CardContent>
                    </Card>
                </div>
            )}
        </SyndicateLayout>
    );
}
