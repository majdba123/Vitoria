import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Pencil, Star, Store } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/dashboard/ListRow';
import { SpecGrid } from '@/Components/products/SpecGrid';
import { SHARED_LABELS, AGRICULTURAL_LABELS, VETERINARY_LABELS } from '@/lib/product-detail-labels';
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
import { formatCurrency, formatDate, formatNumber, formatPercent } from '@/lib/date-time';

export default function ProductsShow({ productId }) {
    const { admin, common, products } = useI18n();
    const locale = useLocale();
    const [status, setStatus] = useState('loading');
    const [product, setProduct] = useState(null);
    const [statusValue, setStatusValue] = useState('pending');
    const [isSavingStatus, setIsSavingStatus] = useState(false);
    const [flash, setFlash] = useState(null);

    const load = () => {
        window.axios.get(`/api/admin/products/${productId}`, { silent: true }).then((res) => {
            setProduct(res.data.data);
            setStatusValue(res.data.data.status || 'pending');
            setStatus('ready');
        }).catch(() => setStatus('error'));
    };

    useEffect(load, [productId]);

    const saveStatus = () => {
        setIsSavingStatus(true);
        window.axios.patch(`/api/admin/products/${productId}/status`, { status: statusValue }, { silent: true }).then((res) => {
            setProduct(res.data.data);
            setFlash(products.status_updated);
            setIsSavingStatus(false);
        }).catch(() => setIsSavingStatus(false));
    };

    if (status === 'loading') {
        return (
            <AdminLayout title={common.loading}>
                <Skeleton className="h-96 w-full" />
            </AdminLayout>
        );
    }

    if (status === 'error' || !product) {
        return (
            <AdminLayout title={admin.manage_products_title}>
                <p className="text-sm font-medium text-[var(--color-danger-strong)]">{products.load_failed}</p>
            </AdminLayout>
        );
    }

    const primaryPhoto = product.photos?.find((p) => p.is_primary) ?? product.photos?.[0];
    const galleryPhotos = (product.photos ?? []).filter((p) => p.id !== primaryPhoto?.id);
    const localizedSubcategory = locale === 'ar'
        ? (product.subcategory?.name_ar || product.subcategory?.name_en)
        : (product.subcategory?.name_en || product.subcategory?.name_ar);
    const agriculturalProductType = product.agricultural_detail?.agricultural_product_type;
    const localizedAgriculturalProductType = agriculturalProductType
        ? (products.form[`type_${agriculturalProductType}`] ?? common.not_available)
        : common.not_available;

    return (
        <AdminLayout title={product.name}>
            <PageHeader
                breadcrumb={[{ label: admin.manage_products_title, href: route('admin.products.index') }]}
                title={product.name}
                copy={product.commercial_name}
                actions={
                    <>
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('admin.products.reviews', product.id)}>{admin.reviews}</Link>
                        </Button>
                        <Button asChild size="sm">
                            <Link href={route('admin.products.edit', product.id)}>
                                <Pencil className="size-4" />
                                {common.edit}
                            </Link>
                        </Button>
                    </>
                }
            />

            {flash && <p className="rounded-md border border-[var(--color-success-200)] bg-[var(--color-success-soft)] px-4 py-2.5 text-sm font-medium text-[var(--color-success-strong)]">{flash}</p>}

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_300px]">
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusBadge tone="brand">{product.category?.name ?? admin.not_assigned}</StatusBadge>
                            {product.subcategory && <StatusBadge tone="brand">{localizedSubcategory}</StatusBadge>}
                            <StatusBadge tone={product.status === 'approved' ? 'success' : product.status === 'rejected' ? 'danger' : 'warning'}>
                                {product.status === 'approved' ? admin.status_approved : product.status === 'rejected' ? admin.status_rejected : admin.status_pending}
                            </StatusBadge>
                            <StatusBadge tone={product.is_active ? 'success' : 'danger'}>{product.is_active ? common.active : common.inactive}</StatusBadge>
                        </div>

                        <div className="flex flex-wrap gap-y-2 border-t border-border pt-4">
                            {[
                                { label: products.fields.price, value: formatCurrency(product.price, locale) },
                                { label: admin.qty_label, value: formatNumber(product.quantity, locale) },
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
                        <p className="text-[11px] font-bold uppercase tracking-[0.24em] text-muted-foreground">{products.admin_controls_badge}</p>
                        <div className="rounded-md border border-border bg-muted/40 p-3">
                            <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">{admin.approval_status_label}</p>
                            <div className="mt-2 flex items-center gap-2">
                                <Select value={statusValue} onValueChange={setStatusValue}>
                                    <SelectTrigger size="sm" className="w-36">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="pending">{admin.status_pending}</SelectItem>
                                        <SelectItem value="approved">{admin.status_approved}</SelectItem>
                                        <SelectItem value="rejected">{admin.status_rejected}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button type="button" size="sm" onClick={saveStatus} disabled={isSavingStatus || statusValue === product.status}>
                                    {common.save}
                                </Button>
                            </div>
                        </div>

                        <div className="grid gap-2.5 sm:grid-cols-2">
                            <div className="rounded-md border border-border bg-muted/40 p-3">
                                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">{products.fields.discount}</p>
                                <p className="mt-1 text-sm font-bold text-foreground">{product.has_active_discount ? formatPercent(product.discount_percentage, locale) : '—'}</p>
                            </div>
                            <div className="rounded-md border border-border bg-muted/40 p-3">
                                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">{products.fields.vendor}</p>
                                <p className="mt-1 truncate text-sm font-semibold text-foreground">{product.vendor?.store_name ?? '—'}</p>
                            </div>
                        </div>

                        {product.vendor?.id && (
                            <Button asChild variant="outline" size="sm" className="w-full">
                                <Link href={route('admin.vendors.show', product.vendor.id)}>
                                    <Store className="size-4" />
                                    {products.view_vendor_profile}
                                </Link>
                            </Button>
                        )}
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
                                { label: products.fields.subcategory, value: localizedSubcategory || products.no_subcategory },
                                { label: products.fields.product_type, value: localizedAgriculturalProductType },
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
        </AdminLayout>
    );
}
