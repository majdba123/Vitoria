import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight, MapPin, Store } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { ProductCard } from '@/Components/public/ProductCard';
import { PublicPagination } from '@/Components/public/PublicPagination';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n } from '@/hooks/use-i18n';

export default function VendorsShow({ vendorId, vendor }) {
    const { nav, vendors } = useI18n();
    const [page, setPage] = useState(1);
    const [productsStatus, setProductsStatus] = useState('loading');
    const [products, setProducts] = useState([]);
    const [meta, setMeta] = useState(null);

    useEffect(() => {
        if (!vendor) return;
        const controller = new AbortController();
        setProductsStatus('loading');
        window.axios.get('/api/products', { params: { vendor_id: vendorId, page }, signal: controller.signal, silent: true }).then((res) => {
            setProducts(res.data?.data ?? []);
            setMeta(res.data?.meta ?? null);
            setProductsStatus('ready');
        }).catch((error) => {
            if (!controller.signal.aborted && error?.code !== 'ERR_CANCELED') setProductsStatus('error');
        });

        return () => controller.abort();
    }, [vendorId, page, vendor]);

    if (!vendor) {
        return (
            <PublicLayout title={vendors.not_found} noindex>
                <div className="page-shell py-16">
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">{vendors.not_found}</h1>
                </div>
            </PublicLayout>
        );
    }

    const vendorUrl = route('vendors.show', vendorId);
    const storeJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Store',
        name: vendor.store_name,
        url: vendorUrl,
        description: vendor.description || undefined,
        image: vendor.logo_url || undefined,
        address: vendor.address || vendor.city?.name ? {
            '@type': 'PostalAddress',
            streetAddress: vendor.address || undefined,
            addressLocality: vendor.city?.name || undefined,
        } : undefined,
    };
    const breadcrumbJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: nav.home, item: route('home') },
            { '@type': 'ListItem', position: 2, name: vendor.store_name, item: vendorUrl },
        ],
    };

    return (
        <PublicLayout title={vendor.store_name} description={vendor.description || undefined} image={vendor.logo_url || undefined} jsonLd={[storeJsonLd, breadcrumbJsonLd]}>
            <div className="bg-transparent">
                <div className="catalog-page-band">
                    <div className="page-shell py-3">
                        <nav className="page-breadcrumb">
                            <Link href={route('home')} className="hover:text-primary">{nav.home}</Link>
                            <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                            <span className="page-breadcrumb-current">{vendor.store_name}</span>
                        </nav>
                    </div>
                </div>

                <div className="page-shell">
                    <div className="mb-10 grid items-center gap-5 border-b border-border pb-8 sm:grid-cols-[9rem_1fr]">
                        <div className="aspect-square overflow-hidden rounded-lg bg-muted">
                            {vendor.logo_url ? (
                                <img src={vendor.logo_url} alt="" className="size-full object-cover" loading="lazy" />
                            ) : (
                                <div className="flex size-full items-center justify-center text-primary"><Store className="size-8" /></div>
                            )}
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-foreground sm:text-3xl">{vendor.store_name}</h1>
                            {vendor.business_type_label && <p className="mt-1 text-sm text-muted-foreground">{vendor.business_type_label}</p>}
                            {vendor.address && (
                                <p className="mt-2 flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <MapPin className="h-4 w-4 shrink-0" />
                                    {[vendor.address, vendor.city?.name].filter(Boolean).join(' · ')}
                                </p>
                            )}
                        </div>
                    </div>

                    {vendor.description && (
                        <div className="mb-10 border-b border-border pb-8">
                            <h2 className="commerce-title text-lg">{vendors.about_heading}</h2>
                            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{vendor.description}</p>
                        </div>
                    )}

                    <div>
                        <div className="commerce-section-header">
                            <h2 className="commerce-title text-lg">{vendors.products_heading}</h2>
                        </div>

                        {productsStatus === 'loading' && (
                            <div className="responsive-shop-grid">
                                {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="aspect-square rounded-lg" />)}
                            </div>
                        )}

                        {productsStatus === 'ready' && products.length === 0 && (
                            <div className="empty-state py-10 text-center text-sm text-muted-foreground sm:py-12">{vendors.no_products}</div>
                        )}

                        {productsStatus === 'ready' && products.length > 0 && (
                            <div className="responsive-shop-grid">
                                {products.map((product) => <ProductCard key={product.id} product={product} />)}
                            </div>
                        )}

                        <PublicPagination meta={meta} onPageChange={(p) => { setPage(p); window.scrollTo({ top: 0, behavior: 'smooth' }); }} />
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
