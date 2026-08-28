import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight, PackageX, SlidersHorizontal } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { ProductCard } from '@/Components/public/ProductCard';
import { PublicPagination } from '@/Components/public/PublicPagination';
import { DataState } from '@/Components/public/DataState';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n, useLocale } from '@/hooks/use-i18n';

function subLabel(sub, locale) {
    return locale === 'ar' ? (sub.name_ar || sub.name_en || '') : (sub.name_en || sub.name_ar || '');
}

function categoryTypeLabel(type, home) {
    if (type === 'agriculture') return home.type_agriculture_short;
    if (type === 'veterinary') return home.type_veterinary_short;
    return '';
}

function readFilters(queryString) {
    const params = new URLSearchParams(queryString);
    return {
        search: params.get('search') || '',
        categoryType: params.get('category_type') || params.get('type') || '',
        categoryId: params.get('category_id') || '',
        subcategoryId: params.get('subcategory_id') || '',
        discount: params.get('has_discount') ?? '',
        stock: params.get('in_stock') ?? '',
        sort: params.get('sort') || '',
        page: Number(params.get('page') || 1),
    };
}

export default function ProductsIndex() {
    const { nav, home } = useI18n();
    const locale = useLocale();
    const { url } = usePage();
    const [filters, setFilters] = useState(() => readFilters(url.split('?')[1] ?? ''));
    const [searchDraft, setSearchDraft] = useState(filters.search);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [allCategories, setAllCategories] = useState([]);
    const [status, setStatus] = useState('loading');
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState(null);
    const requestSeq = useRef(0);
    const isFirstRun = useRef(true);
    const skipNextPush = useRef(false);

    useEffect(() => {
        window.axios.get('/api/categories', { params: { per_page: 100 }, silent: true }).then((res) => {
            setAllCategories(res.data?.data ?? []);
        }).catch(() => {});
    }, []);

    // Sync browser back/forward navigation into filter state without re-pushing a history entry.
    useEffect(() => {
        const onPopState = () => {
            skipNextPush.current = true;
            setFilters(readFilters(window.location.search.slice(1)));
        };
        window.addEventListener('popstate', onPopState);
        return () => window.removeEventListener('popstate', onPopState);
    }, []);

    const loadProducts = () => {
        const seq = ++requestSeq.current;
        setStatus('loading');
        const params = {
            page: filters.page,
            per_page: 24,
            category_type: filters.categoryType || undefined,
            category_id: filters.categoryId || undefined,
            subcategory_id: filters.subcategoryId || undefined,
            has_discount: filters.discount !== '' ? filters.discount : undefined,
            in_stock: filters.stock !== '' ? filters.stock : undefined,
            sort: filters.sort || undefined,
            search: filters.search || undefined,
        };

        const url = new URL(window.location.href);
        Object.entries({ category_type: filters.categoryType, category_id: filters.categoryId, subcategory_id: filters.subcategoryId, has_discount: filters.discount, in_stock: filters.stock, sort: filters.sort, search: filters.search }).forEach(([key, value]) => {
            if (value) url.searchParams.set(key, value); else url.searchParams.delete(key);
        });
        url.searchParams.delete('type');
        if (filters.page > 1) url.searchParams.set('page', String(filters.page)); else url.searchParams.delete('page');

        // Push a new history entry for genuine filter changes so browser Back/Forward can step
        // through them; the initial mount and popstate-driven syncs only replace, never push.
        if (isFirstRun.current || skipNextPush.current) {
            window.history.replaceState({}, '', url.pathname + url.search);
        } else {
            window.history.pushState({}, '', url.pathname + url.search);
        }
        isFirstRun.current = false;
        skipNextPush.current = false;

        window.axios.get('/api/products', { params, silent: true }).then((res) => {
            if (seq !== requestSeq.current) return;
            setRows(res.data?.data ?? []);
            setMeta(res.data?.meta ?? null);
            setStatus('ready');
        }).catch(() => {
            if (seq !== requestSeq.current) return;
            setStatus('error');
        });
    };

    useEffect(loadProducts, [filters]);

    const visibleCategories = useMemo(
        () => (filters.categoryType ? allCategories.filter((c) => c.type === filters.categoryType) : allCategories),
        [allCategories, filters.categoryType],
    );
    const activeCategory = allCategories.find((c) => String(c.id) === String(filters.categoryId));
    const subcategories = activeCategory?.subcategories ?? [];

    const update = (patch) => setFilters((f) => ({ ...f, ...patch, page: patch.page ?? 1 }));

    const activeSummary = [
        filters.search,
        filters.categoryType ? categoryTypeLabel(filters.categoryType, home) : '',
        activeCategory?.name || '',
        filters.subcategoryId ? subLabel(subcategories.find((s) => String(s.id) === String(filters.subcategoryId)) ?? {}, locale) : '',
        filters.sort ? { latest: nav.sort_latest, best_selling: nav.sort_best_selling, most_favorited: nav.sort_most_favorited, top_rated: nav.sort_top_rated }[filters.sort] : '',
    ].filter(Boolean).join(' · ');

    const clearFilters = () => {
        setSearchDraft('');
        setFilters({ search: '', categoryType: '', categoryId: '', subcategoryId: '', discount: '', stock: '', sort: '', page: 1 });
    };

    const productListJsonLd = status === 'ready' && rows.length > 0 ? {
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        itemListElement: rows.map((product, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            url: route('products.show', product.id),
            name: product.name,
        })),
    } : null;

    return (
        <PublicLayout title={nav.products} description={home.hero_subtitle} jsonLd={productListJsonLd ? [productListJsonLd] : undefined}>
            <div className="bg-transparent">
                <div className="catalog-page-band">
                    <div className="page-shell py-3">
                        <nav className="page-breadcrumb">
                            <Link href={route('home')} className="hover:text-primary">{nav.home}</Link>
                            <ChevronRight className="h-3 w-3 rtl:-scale-x-100" />
                            <span className="page-breadcrumb-current">{nav.products}</span>
                        </nav>
                    </div>
                </div>

                <div className="page-shell">
                    <div className="page-header mb-5">
                        <div className="min-w-0">
                            <h1 className="commerce-title">{nav.products}</h1>
                            {meta && <p className="mt-1 text-sm text-muted-foreground">{(nav.results_count ?? '').replace(':count', String(meta.total))}</p>}
                        </div>
                    </div>

                    <div className="catalog-toolbar">
                        <button type="button" onClick={() => setMobileOpen((v) => !v)} className="catalog-filter-toggle" aria-expanded={mobileOpen}>
                            <span>{nav.apply_filters}</span>
                            <SlidersHorizontal className="h-4 w-4" />
                        </button>
                        <div className="catalog-filter-drawer" data-mobile-collapsed={mobileOpen ? 'false' : 'true'}>
                            <div className="min-w-0">
                                <label htmlFor="f-search" className="sr-only">{nav.search_products}</label>
                                <input
                                    id="f-search"
                                    type="search"
                                    className="form-input w-full"
                                    placeholder={nav.search_products}
                                    value={searchDraft}
                                    onChange={(e) => setSearchDraft(e.target.value)}
                                    onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); update({ search: searchDraft.trim() }); } }}
                                />
                            </div>
                            <div className="min-w-0">
                                <label htmlFor="f-category-type" className="sr-only">{nav.all_types}</label>
                                <select id="f-category-type" className="form-select w-full" value={filters.categoryType} onChange={(e) => update({ categoryType: e.target.value, categoryId: '', subcategoryId: '' })}>
                                    <option value="">{nav.all_types}</option>
                                    <option value="agriculture">{home.type_agriculture_short}</option>
                                    <option value="veterinary">{home.type_veterinary_short}</option>
                                </select>
                            </div>
                            <div className="min-w-0">
                                <label htmlFor="f-category" className="sr-only">{nav.all_categories}</label>
                                <select id="f-category" className="form-select w-full" value={filters.categoryId} onChange={(e) => update({ categoryId: e.target.value, subcategoryId: '' })}>
                                    <option value="">{nav.all_categories}</option>
                                    {visibleCategories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                </select>
                            </div>
                            <div className="min-w-0">
                                <label htmlFor="f-subcategory" className="sr-only">{nav.all_subcategories}</label>
                                <select id="f-subcategory" className="form-select w-full" value={filters.subcategoryId} onChange={(e) => update({ subcategoryId: e.target.value })}>
                                    <option value="">{nav.all_subcategories}</option>
                                    {subcategories.map((s) => <option key={s.id} value={s.id}>{subLabel(s, locale)}</option>)}
                                </select>
                            </div>
                            <div className="min-w-0">
                                <label htmlFor="f-sort" className="sr-only">{nav.all_sorting}</label>
                                <select id="f-sort" className="form-select w-full" value={filters.sort} onChange={(e) => update({ sort: e.target.value })}>
                                    <option value="">{nav.all_sorting}</option>
                                    <option value="latest">{nav.sort_latest}</option>
                                    <option value="best_selling">{nav.sort_best_selling}</option>
                                    <option value="most_favorited">{nav.sort_most_favorited}</option>
                                    <option value="top_rated">{nav.sort_top_rated}</option>
                                </select>
                            </div>
                            <div className="min-w-0">
                                <label htmlFor="f-discount" className="sr-only">{nav.all_discounts}</label>
                                <select id="f-discount" className="form-select w-full" value={filters.discount} onChange={(e) => update({ discount: e.target.value })}>
                                    <option value="">{nav.all_discounts}</option>
                                    <option value="1">{nav.discounted_only}</option>
                                    <option value="0">{nav.without_discount}</option>
                                </select>
                            </div>
                            <div className="min-w-0">
                                <label htmlFor="f-stock" className="sr-only">{nav.all_stock}</label>
                                <select id="f-stock" className="form-select w-full" value={filters.stock} onChange={(e) => update({ stock: e.target.value })}>
                                    <option value="">{nav.all_stock}</option>
                                    <option value="1">{nav.in_stock}</option>
                                    <option value="0">{nav.out_of_stock}</option>
                                </select>
                            </div>
                            <button type="button" onClick={() => update({ search: searchDraft.trim() })} className="btn-primary w-full sm:w-auto">{nav.apply_filters}</button>
                            <button type="button" onClick={clearFilters} className="btn-secondary w-full sm:w-auto">{nav.clear_filters}</button>
                        </div>
                        <div className="catalog-active-filters">{activeSummary}</div>
                    </div>

                    <DataState
                        status={status}
                        onRetry={loadProducts}
                        isEmpty={rows.length === 0}
                        errorMessage={(
                            <>
                                <PackageX className="mx-auto h-16 w-16 text-muted-foreground/20" />
                                <p className="mt-4 font-bold text-muted-foreground">{nav.products_error}</p>
                            </>
                        )}
                        loadingSkeleton={(
                            <div className="responsive-shop-grid mt-6">
                                {Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="aspect-square rounded-lg" />)}
                            </div>
                        )}
                        emptyContent={(
                            <div className="empty-state py-20">
                                <PackageX className="mx-auto h-16 w-16 text-muted-foreground/20" />
                                <p className="mt-4 font-bold text-muted-foreground">{nav.products_empty}</p>
                            </div>
                        )}
                    >
                        <div className="responsive-shop-grid mt-6">
                            {rows.map((product) => {
                                const context = product.vendor?.store_name || subLabel(product.subcategory ?? {}, locale) || categoryTypeLabel(product.category?.type, home);
                                return <ProductCard key={product.id} product={product} context={context} />;
                            })}
                        </div>
                    </DataState>

                    <PublicPagination meta={meta} onPageChange={(p) => { update({ page: p }); window.scrollTo({ top: 0, behavior: 'smooth' }); }} />
                </div>
            </div>
        </PublicLayout>
    );
}
