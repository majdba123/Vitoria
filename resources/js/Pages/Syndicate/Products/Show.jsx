import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Package } from 'lucide-react';
import SyndicateLayout from '@/Layouts/SyndicateLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency as money } from '@/lib/date-time';
import { translatedStatus } from '@/lib/translated-enum';

function Field({ label, children }) {
    return (
        <div className="rounded-md border border-border bg-muted/30 px-3 py-2.5">
            <dt className="text-xs font-semibold text-muted-foreground">{label}</dt>
            <dd className="mt-1 break-words text-sm font-semibold text-foreground">{children ?? '—'}</dd>
        </div>
    );
}

export default function SyndicateProductShow({ productId }) {
    const { syndicate: t, common } = useI18n();
    const locale = useLocale();
    const [state, setState] = useState({ status: 'loading', product: null, notFound: false });

    const load = () => {
        setState({ status: 'loading', product: null, notFound: false });
        window.axios.get(`/api/syndicate/products/${productId}`, { silent: true })
            .then((res) => setState({ status: 'ready', product: res.data?.data ?? null, notFound: false }))
            .catch((error) => setState({ status: 'error', product: null, notFound: error.response?.status === 404 }));
    };

    useEffect(load, [productId]);

    const { product, status } = state;
    const shared = product?.shared_detail ?? {};
    const photos = product?.photos ?? [];
    const backHref = route('syndicate.products');

    return (
        <SyndicateLayout title={t.product_details}>
            <PageHeader
                breadcrumb={[{ label: t.products, href: backHref }, { label: product?.name ?? t.product_details }]}
                title={status === 'ready' ? product.name : t.product_details}
                actions={<Button asChild variant="outline" size="sm"><Link href={backHref}><ArrowLeft className="size-4 rtl:rotate-180" aria-hidden="true" />{t.back_to_products}</Link></Button>}
            />

            {status === 'loading' && <div className="space-y-3" aria-busy="true"><Skeleton className="h-40 w-full" /><Skeleton className="h-40 w-full" /></div>}

            {status === 'error' && (
                <Card className="border-border/80 shadow-none"><CardContent className="py-14 text-center" role="alert">
                    <p className="text-sm font-semibold text-[var(--color-danger-strong)]">{state.notFound ? t.detail_not_found : t.detail_load_failed}</p>
                    {!state.notFound && <Button variant="outline" size="sm" className="mt-3" onClick={load}>{common.retry}</Button>}
                </CardContent></Card>
            )}

            {status === 'ready' && product && (
                <div className="grid grid-cols-1 gap-5 xl:grid-cols-3">
                    <Card className="border-border/80 shadow-none xl:col-span-1">
                        <CardHeader><CardTitle className="text-base">{t.product_photos}</CardTitle></CardHeader>
                        <CardContent>
                            {photos.length > 0 ? (
                                <ul className="grid grid-cols-2 gap-3">
                                    {photos.map((photo) => <li key={photo.id}><img src={photo.url} alt={product.name} loading="lazy" className="aspect-square w-full rounded-md border border-border object-cover" /></li>)}
                                </ul>
                            ) : (
                                <div className="flex aspect-video items-center justify-center rounded-md border border-dashed border-border text-muted-foreground"><Package className="size-8" aria-hidden="true" /></div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="space-y-5 xl:col-span-2">
                        <Card className="border-border/80 shadow-none">
                            <CardHeader><CardTitle className="text-base">{t.product_details}</CardTitle></CardHeader>
                            <CardContent>
                                <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <Field label={t.product_vendor}>{product.vendor?.store_name}</Field>
                                    <Field label={t.product_category}>{product.category?.name}</Field>
                                    <Field label={t.product_subcategory}>{locale === 'ar' ? product.subcategory?.name_ar : product.subcategory?.name_en}</Field>
                                    <Field label={t.product_price}>{money(product.price, locale)}</Field>
                                    {product.has_active_discount && <Field label={t.product_discounted_price}>{money(product.discounted_price, locale)}</Field>}
                                    <Field label={t.product_quantity}>{product.quantity}</Field>
                                    <Field label={t.product_min_order}>{product.minimum_order_quantity}</Field>
                                    <Field label={t.product_status}><StatusBadge tone={product.status === 'approved' ? 'success' : product.status === 'rejected' ? 'danger' : 'warning'}>{translatedStatus(product.status, common)}</StatusBadge></Field>
                                    <Field label={t.product_activity}><StatusBadge tone={product.is_active ? 'success' : 'danger'}>{product.is_active ? common.active : common.inactive}</StatusBadge></Field>
                                </dl>
                                {product.vendor?.id && <Button asChild variant="outline" size="sm" className="mt-4"><Link href={route('syndicate.vendors.show', product.vendor.id)}>{common.view_details}: {product.vendor.store_name}</Link></Button>}
                            </CardContent>
                        </Card>

                        {product.description && (
                            <Card className="border-border/80 shadow-none">
                                <CardHeader><CardTitle className="text-base">{t.product_description}</CardTitle></CardHeader>
                                <CardContent><p className="whitespace-pre-line text-sm leading-6 text-foreground">{product.description}</p></CardContent>
                            </Card>
                        )}

                        {(shared.commercial_name || shared.sku || shared.country_of_origin || shared.package_size || shared.registration_number) && (
                            <Card className="border-border/80 shadow-none">
                                <CardHeader><CardTitle className="text-base">{t.product_specs}</CardTitle></CardHeader>
                                <CardContent>
                                    <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        {shared.commercial_name && <Field label={t.product_commercial_name}>{shared.commercial_name}</Field>}
                                        {shared.sku && <Field label={t.product_sku}>{shared.sku}</Field>}
                                        {shared.country_of_origin && <Field label={t.product_origin}>{shared.country_of_origin}</Field>}
                                        {shared.package_size && <Field label={t.product_package}>{[shared.package_size, shared.package_unit].filter(Boolean).join(' ')}</Field>}
                                        {shared.registration_number && <Field label={t.product_registration}>{shared.registration_number}</Field>}
                                    </dl>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            )}
        </SyndicateLayout>
    );
}
