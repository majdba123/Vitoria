import { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight, ArrowRight, Layers } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { ProductCard } from '@/Components/public/ProductCard';
import { PublicPagination } from '@/Components/public/PublicPagination';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n } from '@/hooks/use-i18n';

function categoryImageUrl(category) {
    if (category.image_url) return category.image_url;
    if (category.logo) return `/storage/${category.logo}`;
    if (category.icon) return `/storage/${category.icon}`;
    return null;
}

export default function CategoriesShow({ categoryId, category }) {
    const { nav, category: categoryI18n } = useI18n();
    const { url } = usePage();
    const params = new URLSearchParams(url.split('?')[1] ?? '');
    const selectedType = params.get('type') ?? '';
    const [page, setPage] = useState(1);
    const [productsStatus, setProductsStatus] = useState('loading');
    const [products, setProducts] = useState([]);
    const [meta, setMeta] = useState(null);

    useEffect(() => {
        setProductsStatus('loading');
        window.axios.get('/api/products', { params: { category_id: categoryId, page, type: selectedType || undefined }, silent: true }).then((res) => {
            setProducts(res.data?.data ?? []);
            setMeta(res.data?.meta ?? null);
            setProductsStatus('ready');
        }).catch(() => setProductsStatus('error'));
    }, [categoryId, page]);

    const typedHref = (path) => (selectedType ? `${path}?type=${encodeURIComponent(selectedType)}` : path);
    const categoriesHref = selectedType ? `/categories?type=${encodeURIComponent(selectedType)}` : '/categories';
    const productsHref = `/products?category_id=${categoryId}${selectedType ? `&type=${encodeURIComponent(selectedType)}` : ''}`;

    if (!category) {
        return (
            <PublicLayout title={categoryI18n.not_found} noindex>
                <div className="page-shell py-16">
                    <h1 className="text-2xl font-bold text-foreground sm:text-3xl">{categoryI18n.not_found}</h1>
                </div>
            </PublicLayout>
        );
    }

    const description = [
        (categoryI18n.products_count ?? '').replace(':count', String(category.products_count || 0)),
        (categoryI18n.commission_meta ?? '').replace(':count', String(category.commission)),
    ].filter(Boolean).join(' · ') || undefined;

    const breadcrumbJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            { '@type': 'ListItem', position: 1, name: 'Home', item: route('home') },
            { '@type': 'ListItem', position: 2, name: categoryI18n.breadcrumb_categories, item: route('categories.index') },
            { '@type': 'ListItem', position: 3, name: category.name, item: route('categories.show', category.id) },
        ],
    };

    const productListJsonLd = productsStatus === 'ready' && products.length > 0 ? {
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        itemListElement: products.map((product, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            url: route('products.show', product.id),
            name: product.name,
        })),
    } : null;

    return (
        <PublicLayout title={category.name} description={description} image={categoryImageUrl(category) || undefined} jsonLd={[breadcrumbJsonLd, ...(productListJsonLd ? [productListJsonLd] : [])]}>
            <div className="bg-transparent">
                <div className="catalog-page-band">
                    <div className="page-shell py-3">
                        <nav className="page-breadcrumb">
                            <Link href={route('home')} className="hover:text-primary">{nav.home}</Link>
                            <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                            <Link href={categoriesHref} className="hover:text-primary">{categoryI18n.breadcrumb_categories}</Link>
                            <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                            <span className="page-breadcrumb-current">{category.name}</span>
                        </nav>
                    </div>
                </div>

                <div className="page-shell">
                    <div className="mb-10 grid items-center gap-5 border-b border-border pb-8 sm:grid-cols-[9rem_1fr]">
                        <div className="aspect-[4/3] overflow-hidden rounded-lg bg-muted">
                            {categoryImageUrl(category) ? (
                                <img src={categoryImageUrl(category)} alt="" className="size-full object-cover" loading="lazy" />
                            ) : (
                                <div className="flex size-full items-center justify-center text-primary"><Layers className="size-7" /></div>
                            )}
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-foreground sm:text-3xl">{category.name}</h1>
                            {description && <p className="mt-1 text-sm text-muted-foreground">{description}</p>}
                        </div>
                    </div>

                    <div>
                        <div className="commerce-section-header">
                            <h2 className="commerce-title text-lg">{categoryI18n.products_heading}</h2>
                            <Link href={productsHref} className="btn-secondary btn-sm">
                                {categoryI18n.view_all}
                                <ArrowRight className="h-4 w-4 rtl:-scale-x-100" />
                            </Link>
                        </div>

                        {productsStatus === 'loading' && (
                            <div className="responsive-shop-grid">
                                {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="aspect-square rounded-lg" />)}
                            </div>
                        )}

                        {productsStatus === 'ready' && products.length === 0 && (
                            <div className="empty-state py-16 text-center text-sm text-muted-foreground">{categoryI18n.no_products}</div>
                        )}

                        {productsStatus === 'ready' && products.length > 0 && (
                            <div className="responsive-shop-grid">
                                {products.map((product) => (
                                    <ProductCard key={product.id} product={product} href={typedHref(`/products/${product.id}`)} />
                                ))}
                            </div>
                        )}

                        <PublicPagination meta={meta} onPageChange={(p) => { setPage(p); window.scrollTo({ top: 0, behavior: 'smooth' }); }} />
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
