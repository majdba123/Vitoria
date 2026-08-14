import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { Plus, Package } from 'lucide-react';
import VendorLayout from '@/Layouts/VendorLayout';
import { PageHeader } from '@/Components/admin/PageHeader';
import { Pagination } from '@/Components/admin/Pagination';
import { CsvImportButton } from '@/Components/admin/CsvImportButton';
import { StatusBadge } from '@/Components/admin/dashboard/ListRow';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
import { useAdminList } from '@/hooks/use-admin-list';
import { useI18n } from '@/hooks/use-i18n';

export default function VendorProductsIndex({ discountOnly = false }) {
    const { vendor, common, nav } = useI18n();
    const [page, setPage] = useState(1);
    const [categoryId, setCategoryId] = useState('all');
    const [activeFilter, setActiveFilter] = useState('all');
    const [discountFilter, setDiscountFilter] = useState(discountOnly ? '1' : 'all');
    const [categories, setCategories] = useState([]);

    const { status, rows, meta, errorMessage, reload } = useAdminList('/api/vendor/products', {
        page,
        category_id: categoryId === 'all' ? undefined : categoryId,
        is_active: activeFilter === 'all' ? undefined : activeFilter,
        has_discount: discountFilter === 'all' ? undefined : discountFilter,
    });

    useEffect(() => {
        window.axios.get('/api/vendor/categories', { silent: true }).then((res) => setCategories(res.data?.data ?? []));
    }, []);

    return (
        <VendorLayout title={vendor.total_products}>
            <PageHeader
                title={vendor.total_products}
                copy={vendor.manage_products_copy}
                actions={
                    <>
                        <CsvImportButton label={vendor.products} templateUrl="/api/vendor/products/import/template" importUrl="/api/vendor/products/import" onImported={reload} />
                        <Button asChild size="sm">
                            <Link href={route('vendor.products.create')}>
                                <Plus className="size-4" />
                                {vendor.add_product}
                            </Link>
                        </Button>
                    </>
                }
            />

            <Card className="border-border/80 shadow-none">
                <CardContent className="grid gap-4 p-4 sm:grid-cols-3">
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">{vendor.filter_by_category}</label>
                        <Select value={categoryId} onValueChange={(v) => { setCategoryId(v); setPage(1); }}>
                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{nav.all_categories}</SelectItem>
                                {categories.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">{vendor.filter_by_active}</label>
                        <Select value={activeFilter} onValueChange={(v) => { setActiveFilter(v); setPage(1); }}>
                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{vendor.all}</SelectItem>
                                <SelectItem value="1">{common.active}</SelectItem>
                                <SelectItem value="0">{common.inactive}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">{vendor.filter_by_discount}</label>
                        <Select value={discountFilter} onValueChange={(v) => { setDiscountFilter(v); setPage(1); }}>
                            <SelectTrigger className="w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{vendor.all}</SelectItem>
                                <SelectItem value="1">{vendor.with_discount}</SelectItem>
                                <SelectItem value="0">{nav.without_discount}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            {status === 'loading' && (
                <div className="grid grid-cols-1 gap-4 min-[430px]:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {Array.from({ length: 8 }).map((_, i) => <Skeleton key={i} className="aspect-square w-full" />)}
                </div>
            )}

            {status === 'error' && (
                <div className="rounded-lg border border-dashed border-border py-14 text-center">
                    <p className="text-sm font-medium text-[var(--color-danger-strong)]">{errorMessage ?? vendor.failed_load_products}</p>
                    <Button variant="outline" size="sm" className="mt-3" onClick={reload}>{common.refresh}</Button>
                </div>
            )}

            {status === 'ready' && rows.length === 0 && (
                <div className="rounded-lg border border-dashed border-border py-14 text-center">
                    <p className="text-sm font-semibold text-foreground">{vendor.no_products_yet}</p>
                    <p className="mt-1 text-sm text-muted-foreground">{vendor.add_product_hint}</p>
                </div>
            )}

            {status === 'ready' && rows.length > 0 && (
                <>
                    <div className="grid grid-cols-1 gap-4 min-[430px]:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {rows.map((product) => {
                            const outOfStock = Number(product.quantity || 0) <= 0;
                            const photo = product.first_photo_url || product.fallback_photo_url;

                            return (
                                <Card key={product.id} className="gap-0 overflow-hidden border-border/80 py-0 shadow-none">
                                    <div className="flex aspect-square items-center justify-center overflow-hidden bg-muted">
                                        {photo ? <img src={photo} alt={product.name} className="size-full object-contain p-3" loading="lazy" /> : <Package className="size-8 text-muted-foreground/40" />}
                                    </div>
                                    <CardContent className="space-y-1 p-4">
                                        <h3 className="line-clamp-1 text-base font-semibold text-foreground">{product.name}</h3>
                                        <p className="line-clamp-1 text-sm text-muted-foreground">{product.commercial_name || product.category?.name || ''}</p>
                                        <div className="mt-2 flex items-center justify-between gap-2">
                                            {product.has_active_discount ? (
                                                <span>
                                                    <span className="text-lg font-bold text-foreground">{Number(product.discounted_price || 0).toLocaleString()} SYP</span>{' '}
                                                    <span className="text-xs text-muted-foreground line-through">{Number(product.price || 0).toLocaleString()}</span>
                                                </span>
                                            ) : (
                                                <span className="text-lg font-bold text-foreground">{Number(product.price || 0).toLocaleString()} SYP</span>
                                            )}
                                            <StatusBadge tone={product.is_active ? 'success' : 'danger'}>{product.is_active ? common.active : common.inactive}</StatusBadge>
                                        </div>
                                        {outOfStock && <p className="text-xs font-semibold text-[var(--color-danger-strong)]">{nav.out_of_stock}</p>}
                                        <div className="mt-3 flex gap-2">
                                            <Button asChild variant="outline" size="sm" className="flex-1">
                                                <Link href={route('vendor.products.show', product.id)}>{vendor.show}</Link>
                                            </Button>
                                            <Button asChild size="sm" className="flex-1">
                                                <Link href={route('vendor.products.edit', product.id)}>{common.edit}</Link>
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                    <div className="rounded-lg border border-border">
                        <Pagination meta={meta} onPrev={() => setPage((p) => p - 1)} onNext={() => setPage((p) => p + 1)} />
                    </div>
                </>
            )}
        </VendorLayout>
    );
}
