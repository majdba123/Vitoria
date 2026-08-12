@extends('layouts.app')
@section('title', __('category.page_title') . ' - Vetora')

@section('content')
@php
    $pageSelectedType = request()->query('type') ?: app(\App\Services\SelectedProductTypeService::class)->resolve(request());
@endphp
<div class="bg-transparent">
    <div class="catalog-page-band">
        <div class="page-shell py-3">
            <nav id="breadcrumb" class="page-breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
                <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <a href="{{ route('categories.index', array_filter(['type' => $pageSelectedType])) }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('category.breadcrumb_categories') }}</a>
                <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span id="bc-name" class="page-breadcrumb-current"></span>
            </nav>
        </div>
    </div>

    <div class="page-shell">
        <div id="cat-header" class="mb-10 grid items-center gap-5 border-b pb-8 sm:grid-cols-[9rem_1fr]" style="border-color: var(--color-border);">
            <div id="cat-logo" class="aspect-[4/3] overflow-hidden rounded-[var(--radius-card)]" style="background: var(--color-surface-muted);"></div>
            <div>
                <h1 id="cat-name" class="text-2xl font-bold sm:text-3xl" style="color: var(--color-text);"></h1>
                <p id="cat-meta" class="mt-1 text-sm" style="color: var(--color-text-secondary);"></p>
            </div>
        </div>

        <div>
            <div class="commerce-section-header">
                <h2 class="commerce-title text-lg">{{ __('category.products_heading') }}</h2>
                <a href="{{ route('products.index', array_filter(['type' => $pageSelectedType, 'category_id' => $categoryId])) }}" class="btn-secondary btn-sm">{{ __('category.view_all') }} <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg></a>
            </div>
            <div id="p-loading" class="responsive-shop-grid"><div class="skeleton aspect-square rounded-lg"></div><div class="skeleton aspect-square rounded-lg"></div><div class="skeleton aspect-square rounded-lg"></div><div class="skeleton hidden aspect-square rounded-lg xl:block"></div></div>
            <div id="p-grid" class="responsive-shop-grid"></div>
            <div id="p-empty" class="empty-state hidden py-16 text-center text-sm" style="color: var(--color-text-muted);">{{ __('category.no_products') }}</div>
            <div id="p-pagination" class="mt-8 flex flex-wrap items-center justify-center gap-1.5"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $categoryShowI18n = [
        'notFound' => __('category.not_found'),
        'productsCount' => __('category.products_count'),
        'commissionMeta' => __('category.commission_meta'),
        'soldOut' => __('nav.sold_out'),
        'inStock' => __('nav.in_stock'),
        'addCart' => __('products.add_to_cart_btn'),
        'reviewsCount' => __('nav.reviews_count'),
        'prev' => __('nav.prev'),
        'next' => __('nav.next'),
    ];
@endphp
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const catId = {{ $categoryId }};
    const selectedType = @json($pageSelectedType);
    const t = @json($categoryShowI18n);
    const $ = id => document.getElementById(id);
    let page = 1;
    const withSelectedType = (url) => selectedType ? `${url}${url.includes('?') ? '&' : '?'}type=${encodeURIComponent(selectedType)}` : url;
    const typedPageHref = (path) => selectedType ? `${path}${path.includes('?') ? '&' : '?'}type=${encodeURIComponent(selectedType)}` : path;

    function esc(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function categoryImageUrl(category) {
        if (category.image_url) return category.image_url;
        if (category.logo) return '/storage/' + category.logo;
        if (category.icon) return '/storage/' + category.icon;
        return '';
    }

    function categoryHeroInner(cat) {
        const imageUrl = categoryImageUrl(cat);
        if (imageUrl) {
            return `<img src="${esc(imageUrl)}" class="h-full w-full object-cover" alt="" loading="lazy">`;
        }
        return `<div class="shop-thumb-fallback"><svg class="h-7 w-7 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581"/></svg></div>`;
    }

    try {
        const res = await axios.get(withSelectedType('/api/categories/' + catId));
        const cat = res.data.data;
        document.title = cat.name + ' - Vetora';
        $('bc-name').textContent = cat.name;
        $('cat-name').textContent = cat.name;
        const metaParts = [
            (t.productsCount || '').replace(':count', String(cat.products_count || 0)),
            (t.commissionMeta || '').replace(':count', String(cat.commission)),
        ].filter(Boolean);
        $('cat-meta').textContent = metaParts.join(' · ');
        $('cat-logo').innerHTML = categoryHeroInner(cat);
    } catch (e) {
        $('cat-name').textContent = t.notFound || '';
    }

    loadProducts();

    async function loadProducts() {
        $('p-loading').classList.remove('hidden');
        $('p-grid').innerHTML = '';
        $('p-empty').classList.add('hidden');
        $('p-pagination').innerHTML = '';
        try {
            const res = await axios.get(withSelectedType('/api/products?category_id=' + catId + '&page=' + page));
            const { data, meta } = res.data;
            if (!data.length) {
                $('p-empty').classList.remove('hidden');
            } else {
                $('p-grid').innerHTML = data.map(p => pCard(p)).join('');
            }
            renderPag(meta);
        } catch (e) {
            $('p-empty').classList.remove('hidden');
        }
        $('p-loading').classList.add('hidden');
    }

    function pCard(p) {
        return window.renderProductCard(p, {
            href: typedPageHref('/products/' + p.id),
            placeholder: '{{ asset('images/product-placeholder.svg') }}',
            soldOutLabel: t.soldOut || '',
            inStockLabel: t.inStock || '',
            addToCartLabel: t.addCart || '',
            favoriteLabel: p.name || '',
            reviewsLabel: (count) => (t.reviewsCount || '').replace(':count', String(count)),
        });
    }

    function renderPag(meta) {
        if (!meta || meta.last_page <= 1) return;
        const c = meta.current_page;
        const l = meta.last_page;
        const navBtn = (label, target, disabled) => `<button onclick="window._goP(${target})" class="flex h-10 items-center rounded-lg border px-4 text-xs font-bold ${disabled ? 'pointer-events-none opacity-40' : ''}" style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-text-secondary);" ${disabled ? 'disabled' : ''}>${esc(label)}</button>`;
        let h = navBtn(t.prev || '', c - 1, c === 1);
        getR(c, l).forEach(p => {
            h += p === '...' ? '<span class="px-2" style="color: var(--color-text-muted);">…</span>' : `<button onclick="window._goP(${p})" class="flex h-10 w-10 items-center justify-center rounded-lg border text-xs font-bold ${p === c ? 'page-active' : ''}" style="${p === c ? '' : 'border-color: var(--color-border); background: var(--color-surface); color: var(--color-text-secondary);'}">${p}</button>`;
        });
        h += navBtn(t.next || '', c + 1, c === l);
        $('p-pagination').innerHTML = h;
    }

    function getR(c, l) {
        if (l <= 7) return Array.from({ length: l }, (_, i) => i + 1);
        const p = [1];
        if (c > 3) p.push('...');
        for (let i = Math.max(2, c - 1); i <= Math.min(l - 1, c + 1); i++) p.push(i);
        if (c < l - 2) p.push('...');
        p.push(l);
        return p;
    }

    window._goP = function(p) {
        page = p;
        loadProducts();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
});
</script>
@endpush
