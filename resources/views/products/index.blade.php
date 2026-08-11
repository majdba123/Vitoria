@extends('layouts.app')

@section('title', __('nav.products') . ' - Vetora')

@section('content')
<div class="bg-transparent">
    <div class="catalog-page-band">
        <div class="page-shell py-3">
            <nav class="page-breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
                <svg class="h-3 w-3 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">{{ __('nav.products') }}</span>
            </nav>
        </div>
    </div>

    <div class="page-shell">
        <div class="page-header mb-5">
            <div class="min-w-0">
                <p class="commerce-kicker">Vetora Marketplace</p>
                <h1 class="commerce-title mt-1">{{ __('nav.products') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" id="result-count"></p>
            </div>
        </div>

        <div class="catalog-toolbar">
            <button id="mobile-filter-toggle" type="button" class="catalog-filter-toggle" aria-expanded="false" aria-controls="catalog-filters">
                <span>{{ __('nav.apply_filters') }}</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18M6.75 12h10.5M10.5 19.5h3"/></svg>
            </button>
            <div id="catalog-filters" class="catalog-filter-drawer" data-mobile-collapsed="true">
                <input id="f-search" type="search" class="form-input min-w-0" placeholder="{{ __('nav.search_products') }}">
                <select id="f-category-type" class="form-select min-w-0">
                    <option value="">{{ __('nav.all_types') }}</option>
                    <option value="agriculture">{{ __('home.type_agriculture_short') }}</option>
                    <option value="veterinary">{{ __('home.type_veterinary_short') }}</option>
                </select>
                <select id="f-category" class="form-select min-w-0">
                    <option value="">{{ __('nav.all_categories') }}</option>
                </select>
                <select id="f-subcategory" class="form-select min-w-0">
                    <option value="">{{ __('nav.all_subcategories') }}</option>
                </select>
                <select id="f-sort" class="form-select min-w-0">
                    <option value="">{{ __('nav.all_sorting') }}</option>
                    <option value="latest">{{ __('nav.sort_latest') }}</option>
                    <option value="best_selling">{{ __('nav.sort_best_selling') }}</option>
                    <option value="most_favorited">{{ __('nav.sort_most_favorited') }}</option>
                    <option value="top_rated">{{ __('nav.sort_top_rated') }}</option>
                </select>
                <select id="f-discount" class="form-select min-w-0">
                    <option value="">{{ __('nav.all_discounts') }}</option>
                    <option value="1">{{ __('nav.discounted_only') }}</option>
                    <option value="0">{{ __('nav.without_discount') }}</option>
                </select>
                <select id="f-stock" class="form-select min-w-0">
                    <option value="">{{ __('nav.all_stock') }}</option>
                    <option value="1">{{ __('nav.in_stock') }}</option>
                    <option value="0">{{ __('nav.out_of_stock') }}</option>
                </select>
                <button id="btn-apply" class="btn-primary w-full sm:w-auto">{{ __('nav.apply_filters') }}</button>
                <button id="btn-clear" class="btn-secondary w-full sm:w-auto">{{ __('nav.clear_filters') }}</button>
            </div>
            <div id="active-filter-summary" class="catalog-active-filters" aria-live="polite"></div>
        </div>

        <div id="loading" class="responsive-shop-grid mt-6">
            <div class="skeleton aspect-square rounded-lg"></div><div class="skeleton aspect-square rounded-lg"></div><div class="skeleton aspect-square rounded-lg"></div><div class="skeleton aspect-square rounded-lg"></div><div class="skeleton hidden aspect-square rounded-lg xl:block"></div>
        </div>
        <div id="grid" class="responsive-shop-grid mt-6"></div>
        <div id="empty" class="empty-state hidden py-20">
            <svg class="mx-auto h-16 w-16 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5"/></svg>
            <p class="mt-4 font-bold text-gray-600 dark:text-gray-400" id="empty-message">{{ __('nav.products_empty') }}</p>
        </div>
        <div id="pagination" class="mt-10 flex flex-wrap items-center justify-center gap-1.5"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const $ = id => document.getElementById(id);
    const preferredType = @json(auth()->user()?->preferred_product_type ?? session('preferred_product_type', request()->cookie('preferred_product_type', '')));
    const locale = @json(app()->getLocale());
    const t = {
        results: @json(__('nav.results_count')),
        empty: @json(__('nav.products_empty')),
        error: @json(__('nav.products_error')),
        soldOut: @json(__('nav.sold_out')),
        reviewsCount: @json(__('nav.reviews_count')),
        addCart: @json(__('products.add_to_cart_btn')),
    };

    let page = 1;
    let categoryType = '';
    let categoryId = '';
    let subcategoryId = '';
    let discountFilter = '';
    let stockFilter = '';
    let sort = '';
    let search = '';
    let allCategories = [];

    const params = new URLSearchParams(window.location.search);
    categoryType = params.get('category_type') || params.get('type') || preferredType || '';
    categoryId = params.get('category_id') || '';
    subcategoryId = params.get('subcategory_id') || '';
    discountFilter = params.get('has_discount') || '';
    stockFilter = params.get('in_stock') || '';
    sort = params.get('sort') || '';
    search = params.get('search') || '';
    page = Number(params.get('page') || 1);

    $('mobile-filter-toggle')?.addEventListener('click', function () {
        const filters = $('catalog-filters');
        const collapsed = filters.dataset.mobileCollapsed === 'true';
        filters.dataset.mobileCollapsed = collapsed ? 'false' : 'true';
        this.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
    });

    function esc(value) {
        if (!value) return '';
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    function subcategoryLabel(subcategory) {
        if (!subcategory) return '';
        if (locale === 'ar') {
            return subcategory.name_ar || subcategory.name_en || '';
        }
        return subcategory.name_en || subcategory.name_ar || '';
    }

    function categoryTypeLabel(type) {
        if (type === 'agriculture') return @json(__('home.type_agriculture_short'));
        if (type === 'veterinary') return @json(__('home.type_veterinary_short'));
        return '';
    }

    async function init() {
        try {
            const response = await axios.get('/api/categories?per_page=100');
            allCategories = response.data.data || [];
            $('f-search').value = search;
            $('f-category-type').value = categoryType;
            $('f-discount').value = discountFilter;
            $('f-stock').value = stockFilter;
            $('f-sort').value = sort;
            populateCategories();
            populateSubcategories();
            if (categoryId) $('f-category').value = categoryId;
            if (subcategoryId) $('f-subcategory').value = subcategoryId;
            await load();
        } catch (error) {
            showEmpty(t.error);
        }
    }

    function populateCategories() {
        const categories = categoryType ? allCategories.filter(category => category.type === categoryType) : allCategories;
        $('f-category').innerHTML = `<option value="">${esc(@json(__('nav.all_categories')))}</option>` + categories.map(category => `<option value="${category.id}">${esc(category.name)}</option>`).join('');
        if (categoryId && categories.some(category => String(category.id) === String(categoryId))) {
            $('f-category').value = categoryId;
        }
    }

    function populateSubcategories() {
        const category = allCategories.find(item => String(item.id) === String(categoryId));
        const subcategories = Array.isArray(category?.subcategories) ? category.subcategories : [];
        $('f-subcategory').innerHTML = `<option value="">${esc(@json(__('nav.all_subcategories')))}</option>` + subcategories.map(subcategory => `<option value="${subcategory.id}">${esc(subcategoryLabel(subcategory))}</option>`).join('');
        if (subcategoryId && subcategories.some(subcategory => String(subcategory.id) === String(subcategoryId))) {
            $('f-subcategory').value = subcategoryId;
        } else {
            subcategoryId = '';
            $('f-subcategory').value = '';
        }
    }

    $('f-category-type').addEventListener('change', function() {
        categoryType = this.value;
        categoryId = '';
        subcategoryId = '';
        populateCategories();
        populateSubcategories();
        page = 1;
        load();
    });

    $('f-category').addEventListener('change', function() {
        categoryId = this.value;
        subcategoryId = '';
        populateSubcategories();
        page = 1;
        load();
    });

    $('f-subcategory').addEventListener('change', function() {
        subcategoryId = this.value;
        page = 1;
        load();
    });

    $('f-discount').addEventListener('change', function() {
        discountFilter = this.value;
        page = 1;
        load();
    });

    $('f-stock').addEventListener('change', function() {
        stockFilter = this.value;
        page = 1;
        load();
    });

    $('f-sort').addEventListener('change', function() {
        sort = this.value;
        page = 1;
        load();
    });

    $('f-search').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            search = this.value.trim();
            page = 1;
            load();
        }
    });

    function syncUrl() {
        const url = new URL(window.location.href);
        categoryType ? url.searchParams.set('category_type', categoryType) : url.searchParams.delete('category_type');
        url.searchParams.delete('type');
        categoryId ? url.searchParams.set('category_id', categoryId) : url.searchParams.delete('category_id');
        subcategoryId ? url.searchParams.set('subcategory_id', subcategoryId) : url.searchParams.delete('subcategory_id');
        discountFilter !== '' ? url.searchParams.set('has_discount', discountFilter) : url.searchParams.delete('has_discount');
        stockFilter !== '' ? url.searchParams.set('in_stock', stockFilter) : url.searchParams.delete('in_stock');
        sort ? url.searchParams.set('sort', sort) : url.searchParams.delete('sort');
        search ? url.searchParams.set('search', search) : url.searchParams.delete('search');
        page > 1 ? url.searchParams.set('page', String(page)) : url.searchParams.delete('page');
        window.history.replaceState({}, '', url.pathname + url.search);
    }

    async function load() {
        $('loading').classList.remove('hidden');
        $('grid').innerHTML = '';
        $('empty').classList.add('hidden');
        $('pagination').innerHTML = '';

        const query = new URLSearchParams({ page: String(page), per_page: '24' });
        if (categoryType) query.append('category_type', categoryType);
        if (categoryId) query.append('category_id', categoryId);
        if (subcategoryId) query.append('subcategory_id', subcategoryId);
        if (discountFilter !== '') query.append('has_discount', discountFilter);
        if (stockFilter !== '') query.append('in_stock', stockFilter);
        if (sort) query.append('sort', sort);
        if (search) query.append('search', search);

        syncUrl();
        updateActiveFilterSummary();

        try {
            const response = await axios.get('/api/products?' + query.toString());
            const { data, meta } = response.data;
            $('result-count').textContent = (t.results || '').replace(':count', String(meta.total));

            if (!data.length) {
                showEmpty(t.empty);
            } else {
                $('grid').innerHTML = data.map(productCard).join('');
            }

            renderPagination(meta);
        } catch (error) {
            showEmpty(t.error);
        }

        $('loading').classList.add('hidden');
    }

    function updateActiveFilterSummary() {
        const labels = [
            search,
            $('f-category-type').selectedOptions[0]?.textContent && categoryType ? $('f-category-type').selectedOptions[0].textContent : '',
            $('f-category').selectedOptions[0]?.textContent && categoryId ? $('f-category').selectedOptions[0].textContent : '',
            $('f-subcategory').selectedOptions[0]?.textContent && subcategoryId ? $('f-subcategory').selectedOptions[0].textContent : '',
            $('f-sort').selectedOptions[0]?.textContent && sort ? $('f-sort').selectedOptions[0].textContent : '',
        ].filter(Boolean);
        $('active-filter-summary').textContent = labels.join(' · ');
    }

    function showEmpty(message) {
        $('empty-message').textContent = message;
        $('empty').classList.remove('hidden');
        $('loading').classList.add('hidden');
    }

    function starStars(rating) {
        const r = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
        const filled = '<svg class="h-3.5 w-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        const empty = '<svg class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        let html = '';
        for (let i = 0; i < 5; i++) html += i < r ? filled : empty;
        return html;
    }

    function productCard(product) {
        const photo = product.first_photo_url || product.fallback_photo_url || '{{ asset('images/product-placeholder.svg') }}';
        const inStock = Number(product.quantity || 0) > 0;
        const isFav = window._favIds && window._favIds.has(product.id);
        const reviewCount = parseInt(product.review_count, 10) || 0;
        const subcategoryName = subcategoryLabel(product.subcategory);
        const commercialName = product.commercial_name || '';
        const barcode = Array.isArray(product.barcodes) && product.barcodes.length ? product.barcodes[0] : '';
        const displayPrice = parseFloat(product.has_active_discount ? product.discounted_price : product.price || 0).toLocaleString();

        const context = product.vendor?.store_name || subcategoryName || commercialName || categoryTypeLabel(product.category?.type);
        return `<article class="commerce-product-card">
            <div class="relative">
                <a href="/products/${product.id}"><div class="commerce-product-media">
                    <img src="${esc(photo)}" alt="${esc(product.name)}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}'">
                    ${!inStock ? '<div class="absolute inset-x-0 bottom-0 bg-red-700 px-3 py-1.5 text-center text-[11px] font-semibold text-white">' + esc(t.soldOut || '') + '</div>' : ''}
                    ${product.has_active_discount ? `<div class="absolute start-2.5 top-2.5 z-10 bg-red-700 px-2 py-1 text-[10px] font-semibold text-white">-${parseFloat(product.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                </div></a>
                <button type="button" data-fav-btn="${product.id}" onclick="event.stopPropagation();window.toggleFav(${product.id},this)" aria-label="${esc(product.name)}" aria-pressed="${isFav ? 'true' : 'false'}" class="absolute end-2.5 top-2.5 z-10 flex h-11 w-11 items-center justify-center rounded-full border bg-white/95 dark:bg-gray-900/95 ${isFav ? 'text-red-500' : 'text-gray-500 dark:text-gray-400'}" style="border-color:var(--color-border)"><svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav ? 'currentColor' : 'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button>
            </div>
            <div class="commerce-product-body">
                <p class="commerce-product-context">${esc(context)}</p>
                <a href="/products/${product.id}"><h3 class="commerce-product-title">${esc(product.name)}</h3></a>
                ${commercialName && commercialName !== context ? `<p class="mt-1 line-clamp-1 text-xs text-gray-500 dark:text-gray-400">${esc(commercialName)}</p>` : ''}
                ${barcode ? `<p class="mt-1 truncate text-[11px] text-gray-400" dir="auto">${esc(barcode)}</p>` : ''}
                <div class="mt-2 flex items-center gap-1.5 text-amber-400">${starStars(product.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${reviewCount ? esc((t.reviewsCount || '').replace(':count', String(reviewCount))) : ''}</span></div>
                <div class="mt-auto pt-4">
                  <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5" dir="auto">
                    <span class="commerce-product-price ${product.has_active_discount ? 'text-red-700 dark:text-red-400' : ''}">${displayPrice}</span><span class="text-xs text-gray-500">SYP</span>
                    ${product.has_active_discount ? `<span class="text-[11px] text-gray-400 line-through">${parseFloat(product.price || 0).toLocaleString()} SYP</span>` : ''}
                  </div>
                  <div class="mt-3 flex items-center justify-between gap-2">
                    <span class="text-xs font-medium ${inStock ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'}">${inStock ? esc(@json(__('nav.in_stock'))) : esc(t.soldOut || '')}</span>
                    <button type="button" onclick="window.addToCart&&window.addToCart(${product.id},\`${esc(product.name)}\`,${product.has_active_discount ? product.discounted_price : product.price},\`${esc(photo)}\`)" class="commerce-product-action" ${!inStock ? 'disabled' : ''} aria-label="${esc(t.addCart || '')}: ${esc(product.name)}">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                    </button>
                  </div>
                </div>
            </div>
        </article>`;
    }

    function renderPagination(meta) {
        if (!meta || meta.last_page <= 1) return;
        const current = meta.current_page;
        const last = meta.last_page;
        let html = `<button onclick="goP(${current - 1})" class="flex h-10 items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 text-xs font-bold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${current === 1 ? 'pointer-events-none opacity-40' : ''}" ${current === 1 ? 'disabled' : ''}>${esc(@json(__('nav.prev')))}</button>`;
        getRange(current, last).forEach(item => {
            html += item === '...'
                ? '<span class="px-2 text-gray-400">...</span>'
                : `<button onclick="goP(${item})" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-xs font-bold shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${item === current ? 'page-active' : ''}">${item}</button>`;
        });
        html += `<button onclick="goP(${current + 1})" class="flex h-10 items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 text-xs font-bold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${current === last ? 'pointer-events-none opacity-40' : ''}" ${current === last ? 'disabled' : ''}>${esc(@json(__('nav.next')))}</button>`;
        $('pagination').innerHTML = html;
    }

    function getRange(current, last) {
        if (last <= 7) return Array.from({ length: last }, (_, index) => index + 1);
        const pages = [1];
        if (current > 3) pages.push('...');
        for (let pageNumber = Math.max(2, current - 1); pageNumber <= Math.min(last - 1, current + 1); pageNumber++) pages.push(pageNumber);
        if (current < last - 2) pages.push('...');
        pages.push(last);
        return pages;
    }

    window.goP = function(targetPage) {
        page = targetPage;
        load();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    $('btn-apply').addEventListener('click', () => {
        page = 1;
        search = $('f-search').value.trim();
        categoryType = $('f-category-type').value;
        categoryId = $('f-category').value;
        subcategoryId = $('f-subcategory').value;
        discountFilter = $('f-discount').value;
        stockFilter = $('f-stock').value;
        sort = $('f-sort').value;
        load();
    });

    $('btn-clear').addEventListener('click', () => {
        $('f-search').value = '';
        $('f-category-type').value = '';
        $('f-category').value = '';
        $('f-subcategory').value = '';
        $('f-discount').value = '';
        $('f-stock').value = '';
        $('f-sort').value = '';
        search = '';
        categoryType = '';
        categoryId = '';
        subcategoryId = '';
        discountFilter = '';
        stockFilter = '';
        sort = '';
        page = 1;
        populateCategories();
        populateSubcategories();
        load();
    });

    init();
});
</script>
@endpush
