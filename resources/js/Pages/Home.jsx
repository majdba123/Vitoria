import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, Layers } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { ProductCard } from '@/Components/public/ProductCard';
import { DataState } from '@/Components/public/DataState';
import { Skeleton } from '@/Components/ui/skeleton';
import { useI18n, useLocale } from '@/hooks/use-i18n';
import { useReducedMotion } from '@/hooks/use-reduced-motion';
import { useInView } from '@/hooks/use-in-view';
import { categoryImageUrl } from '@/lib/category-image';

const TYPE_META = {
    agriculture: { labelKey: 'type_agriculture_label' },
    veterinary: { labelKey: 'type_veterinary_label' },
};

const PARTNER_LOGOS = [
    { src: '/images/partners/syrian-veterinarians-syndicate.png', altKey: 'partner_veterinarians_alt' },
    { src: '/images/partners/pharmacists-agricultural-syndicate.png', altKey: 'partner_agriculture_alt' },
    { src: '/images/partners/cli-tool.png', altKey: 'partner_technology_alt' },
];

function subLabel(sub, locale) {
    return locale === 'ar' ? (sub.name_ar || sub.name_en || '') : (sub.name_en || sub.name_ar || '');
}

// The visible window is capped to ~3 logos wide (see .logo-marquee in app.css), so the
// list is duplicated only once - the minimum needed for the seamless-scroll technique
// (animate exactly one list-width so the loop point is invisible) - without ever showing
// two full passes of the (short) list side by side, at any viewport width.
function PartnerLogoMarquee({ reducedMotion }) {
    const { home } = useI18n();
    const logos = [...PARTNER_LOGOS, ...PARTNER_LOGOS];

    return (
        <div className="logo-marquee mt-10">
            <div className="logo-marquee-track" style={reducedMotion ? { animation: 'none' } : undefined}>
                {logos.map((logo, index) => (
                    <img
                        key={`${logo.src}-${index}`}
                        src={logo.src}
                        alt={home[logo.altKey]}
                        loading="lazy"
                        className="h-16 w-auto shrink-0 object-contain opacity-90 transition duration-300 hover:opacity-100 sm:h-20"
                    />
                ))}
            </div>
        </div>
    );
}

// The agriculture/veterinary picker: a full-bleed background card (photo or brand-toned
// fallback) with a scroll-triggered, staggered entrance. Each card needs its own
// intersection state, so this is a real component rather than inline map body.
function TypeCard({ value, meta, index, isSelected, home }) {
    const reducedMotion = useReducedMotion();
    const [ref, inView] = useInView({ skip: reducedMotion });

    return (
        <Link
            ref={ref}
            href={route('product-type.select', { preferred_product_type: value, redirect_to: 'home' })}
            className={`home-type-card home-type-card--${value} ${isSelected ? 'is-selected' : ''} ${reducedMotion ? '' : 'reveal-up'} ${inView ? 'is-in-view' : ''}`}
            style={reducedMotion ? undefined : { '--reveal-delay': `${index * 140}ms` }}
            aria-current={isSelected ? 'true' : undefined}
        >
            <span className="home-type-card-title">{home[meta.labelKey]}</span>
        </Link>
    );
}

// One shared observer for the whole category grid rather than one per card - the grid
// can hold a couple dozen categories, and they should settle into view together with a
// light stagger, not each pay for their own IntersectionObserver instance.
function CategoryGridReveal({ children }) {
    const reducedMotion = useReducedMotion();
    const [ref, inView] = useInView({ skip: reducedMotion });

    return (
        <div
            ref={ref}
            className={`grid grid-cols-1 gap-4 min-[520px]:grid-cols-2 xl:grid-cols-4 ${reducedMotion ? '' : 'reveal-stagger'} ${inView ? 'is-in-view' : ''}`}
        >
            {children}
        </div>
    );
}

function HeroMedia({ alt }) {
    const reducedMotion = useReducedMotion();
    const videoRef = useRef(null);

    // Belt-and-suspenders for the `autoPlay` attribute: on a hard reload some
    // browsers start the element paused for a moment (buffering, tab not yet
    // painted) and never receive the implicit nudge a SPA navigation gives
    // them. Kicking play() explicitly once data is available - and again if
    // the tab becomes visible - keeps the loop running even in those cases.
    // play() on a muted element is allowed by every browser autoplay policy.
    useEffect(() => {
        if (reducedMotion) return;
        const video = videoRef.current;
        if (!video) return;

        const tryPlay = () => {
            if (video.paused) video.play().catch(() => {});
        };

        tryPlay();
        video.addEventListener('loadeddata', tryPlay);
        video.addEventListener('canplay', tryPlay);
        document.addEventListener('visibilitychange', tryPlay);

        return () => {
            video.removeEventListener('loadeddata', tryPlay);
            video.removeEventListener('canplay', tryPlay);
            document.removeEventListener('visibilitychange', tryPlay);
        };
    }, [reducedMotion]);

    if (reducedMotion) {
        return (
            <img
                src="/images/vetora-hero-poster.webp"
                alt={alt}
                width={1280}
                height={720}
                fetchPriority="high"
                className="storefront-hero-video"
            />
        );
    }

    return (
        <video
            ref={videoRef}
            className="storefront-hero-video"
            autoPlay
            muted
            loop
            playsInline
            poster="/images/vetora-hero-poster.webp"
            preload="auto"
            aria-label={alt}
        >
            <source src="/videos/vetora-hero-loop.mp4" type="video/mp4" />
        </video>
    );
}

function BannerStrip() {
    const [banners, setBanners] = useState([]);
    const locale = useLocale();

    useEffect(() => {
        window.axios.get('/api/banners', { silent: true }).then((res) => setBanners(res.data?.data ?? [])).catch(() => {});
    }, []);

    if (banners.length === 0) return null;

    return (
        <section className="page-shell pt-2 sm:pt-3">
            <div className="flex gap-3 overflow-x-auto pb-1" style={{ scrollSnapType: 'x mandatory' }}>
                {banners.map((banner) => {
                    const title = locale === 'ar' ? (banner.title_ar || banner.title_en) : (banner.title_en || banner.title_ar);
                    const image = <img src={`/storage/${banner.image_path}`} alt={title || ''} className="aspect-[16/7] h-auto w-full rounded-lg bg-muted object-contain" loading="lazy" />;
                    return banner.link_url ? (
                        <a key={banner.id} href={banner.link_url} className="block w-[88vw] shrink-0 sm:w-[32rem]" style={{ scrollSnapAlign: 'start' }}>{image}</a>
                    ) : (
                        <div key={banner.id} className="w-[88vw] shrink-0 sm:w-[32rem]" style={{ scrollSnapAlign: 'start' }}>{image}</div>
                    );
                })}
            </div>
        </section>
    );
}

function ProductGrid({ status, rows, emptyMessage, rank = false, onRetry }) {
    const { home } = useI18n();
    return (
        <DataState
            status={status}
            onRetry={onRetry}
            isEmpty={rows.length === 0}
            errorMessage={<p className="py-12 text-center text-sm text-muted-foreground">{home.products_error ?? home.no_products_yet}</p>}
            loadingSkeleton={(
                <div className="responsive-shop-grid">
                    {Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-[25rem]" />)}
                </div>
            )}
            emptyContent={<p className="py-12 text-center text-sm text-muted-foreground">{emptyMessage ?? home.no_products_yet}</p>}
        >
            <div className="responsive-shop-grid">
                {rows.map((product, index) => (
                    <ProductCard key={product.id} product={product} rank={rank ? index + 1 : null} />
                ))}
            </div>
        </DataState>
    );
}

export default function Home({ selectedType }) {
    const { home, common } = useI18n();
    const locale = useLocale();
    const reducedMotion = useReducedMotion();
    const { url, props: pageProps } = usePage();
    const origin = pageProps.origin ?? '';
    const params = new URLSearchParams(url.split('?')[1] ?? '');

    const [categories, setCategories] = useState([]);
    const [categoriesStatus, setCategoriesStatus] = useState(selectedType ? 'loading' : 'idle');
    const [categoryId, setCategoryId] = useState(params.get('category_id') ?? null);
    const [subcategoryId, setSubcategoryId] = useState(params.get('subcategory_id') ?? null);

    const [latest, setLatest] = useState({ status: 'idle', rows: [] });
    const [bestSelling, setBestSelling] = useState({ status: 'idle', rows: [] });
    const [mostFavorited, setMostFavorited] = useState({ status: 'idle', rows: [] });

    useEffect(() => {
        if (!selectedType) return;
        const controller = new AbortController();
        window.axios.get('/api/categories', { params: { per_page: 100, type: selectedType }, signal: controller.signal, silent: true }).then((res) => {
            setCategories(res.data?.data ?? []);
            setCategoriesStatus('ready');
        }).catch((error) => {
            if (!controller.signal.aborted && error?.code !== 'ERR_CANCELED') setCategoriesStatus('error');
        });

        return () => controller.abort();
    }, [selectedType]);

    const activeCategory = useMemo(() => categories.find((c) => String(c.id) === String(categoryId)) ?? null, [categories, categoryId]);

    useEffect(() => {
        const url = new URL(window.location.href);
        if (selectedType) url.searchParams.set('type', selectedType); else url.searchParams.delete('type');
        if (categoryId) url.searchParams.set('category_id', categoryId); else url.searchParams.delete('category_id');
        if (subcategoryId) url.searchParams.set('subcategory_id', subcategoryId); else url.searchParams.delete('subcategory_id');
        window.history.replaceState({}, '', url.pathname + url.search);
    }, [selectedType, categoryId, subcategoryId]);

    const loadProducts = (signal) => {
        if (!categoryId) return;
        const params2 = { category_id: categoryId, subcategory_id: subcategoryId || undefined, category_type: selectedType, per_page: 24 };
        setLatest({ status: 'loading', rows: [] });
        window.axios.get('/api/products', { params: params2, signal, silent: true }).then((res) => setLatest({ status: 'ready', rows: res.data?.data ?? [] })).catch((error) => {
            if (!signal?.aborted && error?.code !== 'ERR_CANCELED') setLatest({ status: 'error', rows: [] });
        });

        setBestSelling({ status: 'loading', rows: [] });
        window.axios.get('/api/products', { params: { ...params2, per_page: 5, sort: 'best_selling' }, signal, silent: true }).then((res) => setBestSelling({ status: 'ready', rows: res.data?.data ?? [] })).catch((error) => {
            if (!signal?.aborted && error?.code !== 'ERR_CANCELED') setBestSelling({ status: 'error', rows: [] });
        });

        setMostFavorited({ status: 'loading', rows: [] });
        window.axios.get('/api/products', { params: { ...params2, per_page: 5, sort: 'most_favorited' }, signal, silent: true }).then((res) => setMostFavorited({ status: 'ready', rows: res.data?.data ?? [] })).catch((error) => {
            if (!signal?.aborted && error?.code !== 'ERR_CANCELED') setMostFavorited({ status: 'error', rows: [] });
        });
    };

    useEffect(() => {
        const controller = new AbortController();
        loadProducts(controller.signal);

        return () => controller.abort();
    }, [categoryId, subcategoryId, selectedType]);

    const selectCategory = (id, subId = null) => {
        setCategoryId(id ? String(id) : null);
        setSubcategoryId(subId ? String(subId) : null);
        if (id) {
            setTimeout(() => document.getElementById('sz-category-bar')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
        }
    };

    const subcategories = activeCategory?.subcategories ?? [];
    const productsHref = (sort) => {
        const p = new URLSearchParams();
        if (categoryId) p.set('category_id', categoryId);
        if (subcategoryId) p.set('subcategory_id', subcategoryId);
        if (sort) p.set('sort', sort);
        if (selectedType) p.set('type', selectedType);
        return `/products${p.toString() ? `?${p.toString()}` : ''}`;
    };

    const footerSettings = pageProps.footerSettings ?? {};
    const sameAs = [footerSettings.facebook_url, footerSettings.instagram_url, footerSettings.twitter_url].filter(Boolean);
    const organizationJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: 'Vetora',
        url: origin || undefined,
        logo: origin ? `${origin}/images/vetora-logo-transparent.png` : undefined,
        email: footerSettings.contact_email || undefined,
        address: footerSettings.contact_address ? { '@type': 'PostalAddress', streetAddress: footerSettings.contact_address } : undefined,
        ...(sameAs.length > 0 ? { sameAs } : {}),
    };
    const websiteJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: 'Vetora',
        url: origin || undefined,
        potentialAction: {
            '@type': 'SearchAction',
            target: `${origin}${route('products.index')}?search={search_term_string}`,
            'query-input': 'required name=search_term_string',
        },
    };
    return (
        <PublicLayout title="Vetora" description={home.hero_subtitle} jsonLd={[organizationJsonLd, websiteJsonLd]}>
            <section className="storefront-hero">
                <div className="storefront-hero-frame">
                    <HeroMedia alt={home.hero_image_alt} />
                    <div className="storefront-hero-scrim" aria-hidden="true" />
                    <div className="storefront-hero-copy">
                        <span className="storefront-hero-eyebrow">{home.hero_badge}</span>
                        <h1 className="storefront-hero-title">
                            {home.hero_title_line_one}
                            <span className="storefront-hero-title-highlight">{home.hero_title_highlight}</span>
                        </h1>
                        <p className="storefront-hero-subtitle">{home.hero_subtitle}</p>
                        <div className="mt-7 flex flex-wrap items-center gap-x-6 gap-y-3">
                            <a href="#products" className="btn-primary">
                                {home.hero_primary_cta}
                                <ArrowRight className="h-4 w-4 rtl:-scale-x-100" />
                            </a>
                            <a href="#categories" className="storefront-hero-secondary-cta">
                                {home.hero_secondary_cta}
                                <ArrowRight className="h-3.5 w-3.5 rtl:-scale-x-100" />
                            </a>
                        </div>
                        <p className="storefront-hero-note">{home.hero_context_note}</p>
                    </div>
                </div>
            </section>

            <BannerStrip />

            <section id="home-type-selector" className="page-shell py-0 home-type-selector" aria-labelledby="home-type-heading">
                <div className="home-type-intro">
                    <h2 id="home-type-heading" className="commerce-title">{home.type_selector_title}</h2>
                </div>
                <div className="home-type-grid">
                    {Object.entries(TYPE_META).map(([value, meta], index) => (
                        <TypeCard key={value} value={value} meta={meta} index={index} isSelected={selectedType === value} home={home} />
                    ))}
                </div>
            </section>

            {selectedType ? (
                <>
                    <section id="categories" className="storefront-section scroll-mt-24 bg-transparent">
                        <div className="page-shell py-0">
                            <div className="commerce-section-header">
                                <div className="min-w-0">
                                    <h2 className="commerce-title">{home.choose_category_title}</h2>
                                </div>
                            </div>

                            {categoriesStatus === 'loading' && (
                                <div className="grid grid-cols-1 gap-4 min-[520px]:grid-cols-2 xl:grid-cols-4">
                                    {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-64" />)}
                                </div>
                            )}

                            {categoriesStatus === 'error' && <p className="text-sm text-muted-foreground">{home.could_not_load}</p>}

                            {categoriesStatus === 'ready' && (
                                <CategoryGridReveal>
                                    {categories.map((category) => {
                                        const subs = category.subcategories ?? [];
                                        const preview = subs.slice(0, 3);
                                        const moreCount = Math.max(subs.length - 3, 0);
                                        const isActive = String(category.id) === String(categoryId);
                                        return (
                                            <article key={category.id} className={`storefront-category-card group flex h-full flex-col ${isActive ? 'is-selected' : ''}`}>
                                                <button type="button" onClick={() => selectCategory(category.id)} className="flex flex-1 flex-col text-start focus:outline-none">
                                                    <div className="shop-card-media">
                                                        {categoryImageUrl(category) ? (
                                                            <img src={categoryImageUrl(category)} alt={category.name} className="shop-card-media-img" loading="lazy" />
                                                        ) : (
                                                            <div className="shop-card-media-fallback"><Layers className="h-8 w-8 text-primary/60" /></div>
                                                        )}
                                                    </div>
                                                    <div className="category-card-body flex flex-1 flex-col gap-1 p-4">
                                                        <div className="flex items-center gap-2">
                                                            <h3 className="text-base font-bold leading-snug text-foreground">{category.name}</h3>
                                                            {category.type && <span className="badge badge-brand shrink-0">{category.type === 'agriculture' ? home.type_agriculture_short : home.type_veterinary_short}</span>}
                                                        </div>
                                                        <p className="text-[13px] leading-6 text-muted-foreground">{subs.length ? (home.category_has_subcategories ?? '').replace(':count', String(subs.length)) : home.category_direct_products}</p>
                                                    </div>
                                                </button>
                                                {subs.length > 0 && (
                                                    <div className="category-card-footer flex flex-wrap items-center gap-1.5 border-t border-border px-4 py-2.5">
                                                        {preview.map((sub) => (
                                                            <button key={sub.id} type="button" onClick={() => selectCategory(category.id, sub.id)} className="inline-flex min-h-11 items-center rounded-md border border-border px-2.5 py-1 text-xs font-medium text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20">
                                                                {subLabel(sub, locale)}
                                                            </button>
                                                        ))}
                                                        {moreCount > 0 && (
                                                            <span className="inline-flex min-h-11 items-center rounded-md bg-muted px-2 py-1 text-xs font-semibold text-muted-foreground">
                                                                {(home.category_has_subcategories_more ?? '').replace(':count', String(moreCount))}
                                                            </span>
                                                        )}
                                                    </div>
                                                )}
                                            </article>
                                        );
                                    })}
                                </CategoryGridReveal>
                            )}
                        </div>
                    </section>

                    {categoryId && (
                        <div>
                            <section id="sz-category-bar" className="page-shell pt-0">
                                <div className="surface-card-muted px-5 py-4 sm:px-6">
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="flex min-w-0 items-center gap-4">
                                            {activeCategory && categoryImageUrl(activeCategory) && (
                                                <div className="shop-thumb-box hidden h-14 w-14 shrink-0 sm:flex">
                                                    <img src={categoryImageUrl(activeCategory)} alt={activeCategory.name} />
                                                </div>
                                            )}
                                            <div className="min-w-0">
                                                <h2 className="text-xl font-bold text-foreground sm:text-2xl">{activeCategory?.name ?? '—'}</h2>
                                                <p className="mt-1 text-sm text-muted-foreground">{subcategories.length ? (home.category_has_subcategories ?? '').replace(':count', String(subcategories.length)) : home.category_direct_products}</p>
                                            </div>
                                        </div>
                                        <button type="button" onClick={() => selectCategory(null)} className="btn-secondary btn-xs shrink-0">{home.change_category}</button>
                                    </div>

                                    {subcategories.length > 0 && (
                                        <div className="mt-5 border-t border-border pt-4">
                                            <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p className="text-sm font-bold text-foreground">{home.subcategory_filter_title}</p>
                                                    <p className="mt-1 text-xs text-muted-foreground">{home.subcategory_filter_hint}</p>
                                                </div>
                                            </div>
                                            <div className="flex gap-2 overflow-x-auto pb-1 lg:flex-wrap">
                                                <button
                                                    type="button"
                                                    onClick={() => setSubcategoryId(null)}
                                                    aria-current={!subcategoryId ? 'true' : undefined}
                                                    className={`inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 py-2 text-sm font-bold transition ${!subcategoryId ? 'border-primary bg-primary text-primary-foreground shadow-sm' : 'border-border bg-card text-foreground hover:border-primary/50 hover:text-primary'}`}
                                                >
                                                    {home.all_subcategories}
                                                </button>
                                                {subcategories.map((sub) => {
                                                    const isActive = String(subcategoryId) === String(sub.id);
                                                    return (
                                                        <button
                                                            key={sub.id}
                                                            type="button"
                                                            onClick={() => setSubcategoryId(String(sub.id))}
                                                            aria-current={isActive ? 'true' : undefined}
                                                            className={`inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 py-2 text-start text-sm font-bold transition ${isActive ? 'border-primary bg-primary text-primary-foreground shadow-sm' : 'border-border bg-card text-foreground hover:border-primary/50 hover:text-primary'}`}
                                                        >
                                                            {subLabel(sub, locale)}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </section>

                            <section id="products" className="storefront-section scroll-mt-24">
                                <div className="page-shell py-0">
                                    <div className="commerce-section-header">
                                        <div>
                                            <h2 className="commerce-title">{home.latest_products_title}</h2>
                                        </div>
                                        <Link href={productsHref(null)} className="btn-secondary btn-sm hidden sm:inline-flex">
                                            {common.view_all}
                                            <ArrowRight className="h-4 w-4 rtl:-scale-x-100" />
                                        </Link>
                                    </div>
                                    <ProductGrid status={latest.status} rows={latest.rows} emptyMessage={subcategoryId ? home.no_products_in_subcategory : home.no_products_in_category} onRetry={() => loadProducts()} />
                                    <div className="mt-8 text-center sm:hidden">
                                        <Link href={productsHref(null)} className="btn-secondary btn-sm">
                                            {home.view_all_products_arrow}
                                            <ArrowRight className="h-4 w-4 rtl:-scale-x-100" />
                                        </Link>
                                    </div>
                                </div>
                            </section>

                            <section id="best-selling" className="storefront-section scroll-mt-24">
                                <div className="page-shell py-0">
                                    <div className="commerce-section-header">
                                        <div>
                                            <h2 className="commerce-title">{home.bestselling_title}</h2>
                                        </div>
                                        <Link href={productsHref('best_selling')} className="btn-secondary btn-sm hidden sm:inline-flex">
                                            {common.view_all}
                                            <ArrowRight className="h-4 w-4 rtl:-scale-x-100" />
                                        </Link>
                                    </div>
                                    <ProductGrid status={bestSelling.status} rows={bestSelling.rows} rank onRetry={() => loadProducts()} />
                                </div>
                            </section>

                            <section id="most-favorited" className="storefront-section scroll-mt-24 bg-muted/30">
                                <div className="page-shell py-0">
                                    <div className="commerce-section-header">
                                        <div>
                                            <h2 className="commerce-title">{home.most_favorited_title}</h2>
                                        </div>
                                        <Link href={productsHref('most_favorited')} className="btn-secondary btn-sm hidden sm:inline-flex">
                                            {common.view_all}
                                            <ArrowRight className="h-4 w-4 rtl:-scale-x-100" />
                                        </Link>
                                    </div>
                                    <ProductGrid status={mostFavorited.status} rows={mostFavorited.rows} onRetry={() => loadProducts()} />
                                </div>
                            </section>
                        </div>
                    )}

                    <section className="storefront-section border-t border-border/60 bg-muted/20">
                        <div className="page-shell py-0" aria-label={home.partners_title}>
                            <PartnerLogoMarquee reducedMotion={reducedMotion} />
                        </div>
                    </section>
                </>
            ) : (
                <section className="page-shell">
                    <div className="mx-auto max-w-3xl text-center">
                        <p className="state-panel text-sm font-semibold text-muted-foreground">{home.choose_type_first}</p>
                    </div>
                </section>
            )}
        </PublicLayout>
    );
}
