import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import VendorLayout from '@/Layouts/VendorLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { SpecGrid } from '@/Components/products/SpecGrid';
import { SHARED_LABELS, AGRICULTURAL_LABELS, VETERINARY_LABELS } from '@/lib/product-detail-labels';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { Button } from '@/Components/ui/button';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { formatCurrency, formatDate, formatNumber, formatPercent } from '@/lib/date-time';

export default function VendorProductsShow({ productId }) {
    const { vendor, common, products } = useI18n();
    const locale = useLocale();
    const [status, setStatus] = useState('loading');
    const [product, setProduct] = useState(null);

    useEffect(() => {
        window.axios.get(`/api/vendor/products/${productId}`, { silent: true }).then((res) => {
            setProduct(res.data.data);
            setStatus('ready');
        }).catch(() => setStatus('error'));
    }, [productId]);

    if (status === 'loading') {
        return (
            <VendorLayout title={common.loading}>
                <Skeleton className="h-96 w-full" />
            </VendorLayout>
        );
    }

    if (status === 'error' || !product) {
        return (
            <VendorLayout title={vendor.products}>
                <p className="text-sm font-medium text-[var(--color-danger-strong)]">{products.load_failed}</p>
            </VendorLayout>
        );
    }

    const primaryPhoto = product.photos?.find((p) => p.is_primary) ?? product.photos?.[0];
    const galleryPhotos = (product.photos ?? []).filter((p) => p.id !== primaryPhoto?.id);

    return (
        <VendorLayout title={product.name}>
            <PageHeader
                breadcrumb={[{ label: vendor.products, href: route('vendor.products.index') }]}
                title={product.name}
                copy={product.commercial_name}
                actions={
                    <Button asChild size="sm">
                        <Link href={route('vendor.products.edit', product.id)}>
                            <Pencil className="size-4" />
                            {common.edit}
                        </Link>
                    </Button>
                }
            />

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_300px]">
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusBadge tone="brand">{product.category?.name ?? '—'}</StatusBadge>
                            {product.subcategory && <StatusBadge tone="brand">{product.subcategory.name_ar || product.subcategory.name_en}</StatusBadge>}
                            <StatusBadge tone={product.status === 'approved' ? 'success' : product.status === 'rejected' ? 'danger' : 'warning'}>
                                {product.status === 'approved' ? common.approved : product.status === 'rejected' ? common.rejected : common.pending}
                            </StatusBadge>
                            <StatusBadge tone={product.is_active ? 'success' : 'danger'}>{product.is_active ? common.active : common.inactive}</StatusBadge>
                        </div>

                        <div className="flex flex-wrap gap-y-2 border-t border-border pt-4">
                            {[
                                { label: products.fields.price, value: formatCurrency(product.price, locale) },
                                { label: products.fields.quantity, value: formatNumber(product.quantity, locale) },
                                { label: products.fields.commission, value: product.category?.commission ? formatPercent(product.category.commission, locale) : '—' },
                                { label: products.fields.created, value: product.created_at ? formatDate(product.created_at, locale) : '—' },
                            ].map((item, index) => (
                                <div key={item.label} className={`flex flex-col gap-1 px-4 py-1 ${index === 0 ? 'ps-0' : 'border-s border-border'}`}>
                                    <span className="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{item.label}</span>
                                    <span className="text-base font-bold tabular-nums text-foreground">{item.value}</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="space-y-3 border-border lg:border-s lg:ps-6">
                        <p className="text-[11px] font-bold uppercase tracking-[0.24em] text-muted-foreground">{products.vendor_snapshot_badge}</p>
                        <div className="grid gap-2.5">
                            <div className="rounded-md border border-border bg-muted/40 p-3">
                                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">{products.fields.discount}</p>
                                <p className="mt-1 text-sm font-bold text-foreground">{product.has_active_discount ? formatPercent(product.discount_percentage, locale) : '—'}</p>
                            </div>
                            <div className="rounded-md border border-border bg-muted/40 p-3">
                                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">{products.fields.product_type}</p>
                                <p className="mt-1 truncate text-sm font-semibold text-foreground">{product.agricultural_detail?.agricultural_product_type || '—'}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {product.status === 'rejected' && product.rejection_reason && (
                <div className="rounded-md border border-[var(--color-danger-200)] bg-[var(--color-danger-soft)] px-4 py-3 text-sm font-medium text-[var(--color-danger-strong)]">
                    <p className="text-xs font-bold uppercase tracking-[0.2em]">{products.rejection_badge}</p>
                    <p className="mt-1">{product.rejection_reason}</p>
                </div>
            )}

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.85fr)]">
                <div className="space-y-4">
                    <Card className="border-border/80 shadow-none">
                        <CardHeader className="border-b border-border/80">
                            <CardTitle className="text-base font-bold">{products.gallery_title}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 p-5">
                            <div className="flex aspect-[16/11] items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
                                {primaryPhoto ? <img src={primaryPhoto.url} alt="" className="size-full object-contain" /> : <p className="text-sm text-muted-foreground">{products.no_primary_photo}</p>}
                            </div>
                            {galleryPhotos.length > 0 && (
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                    {galleryPhotos.map((photo) => (
                                        <div key={photo.id} className="aspect-square overflow-hidden rounded-md border border-border bg-muted">
                                            <img src={photo.url} alt="" className="size-full object-cover" />
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-border/80 shadow-none">
                        <CardHeader className="border-b border-border/80">
                            <CardTitle className="text-base font-bold">{products.description_badge}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-5">
                            <p className="whitespace-pre-wrap text-sm leading-7 text-muted-foreground">{product.description || products.no_description}</p>
                        </CardContent>
                    </Card>

                    <Card className="border-border/80 shadow-none">
                        <CardHeader className="border-b border-border/80">
                            <CardTitle className="text-base font-bold">{products.shared_title}</CardTitle>
                        </CardHeader>
                        <CardContent className="p-5">
                            <SpecGrid values={product.shared_detail} labels={SHARED_LABELS} />
                        </CardContent>
                    </Card>

                    {product.category?.type === 'agriculture' && (
                        <Card className="border-border/80 shadow-none">
                            <CardHeader className="border-b border-border/80">
                                <CardTitle className="text-base font-bold">{products.agriculture_title}</CardTitle>
                            </CardHeader>
                            <CardContent className="p-5">
                                <SpecGrid values={product.agricultural_detail} labels={AGRICULTURAL_LABELS} />
                            </CardContent>
                        </Card>
                    )}

                    {product.category?.type === 'veterinary' && (
                        <Card className="border-border/80 shadow-none">
                            <CardHeader className="border-b border-border/80">
                                <CardTitle className="text-base font-bold">{products.veterinary_title}</CardTitle>
                            </CardHeader>
                            <CardContent className="p-5">
                                <SpecGrid values={product.veterinary_detail} labels={VETERINARY_LABELS} />
                            </CardContent>
                        </Card>
                    )}
                </div>

                <aside>
                    <Card className="border-border/80 shadow-none">
                        <CardHeader className="border-b border-border/80">
                            <CardTitle className="text-base font-bold">{products.summary_title}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 p-5">
                            {[
                                { label: common.category, value: product.category?.name },
                                { label: products.fields.subcategory, value: product.subcategory?.name_ar || product.subcategory?.name_en || products.no_subcategory },
                                { label: products.fields.product_type, value: product.agricultural_detail?.agricultural_product_type || '—' },
                            ].map((item) => (
                                <div key={item.label} className="rounded-md border border-border bg-muted/40 px-4 py-3">
                                    <p className="text-[11px] font-bold uppercase tracking-[0.24em] text-muted-foreground">{item.label}</p>
                                    <p className="mt-1 text-sm font-semibold text-foreground">{item.value ?? '—'}</p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </VendorLayout>
    );
}
