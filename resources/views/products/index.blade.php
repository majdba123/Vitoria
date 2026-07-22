@extends('layouts.app')

@section('title', __('nav.products') . ' - Vetora')

@section('content')
<div class="bg-transparent">
    <div class="border-b border-white/40 bg-white/60 backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
        <div class="page-shell py-3">
            <nav class="page-breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
                <svg class="h-3 w-3 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">{{ __('nav.products') }}</span>
            </nav>
        </div>
    </div>

    <div class="page-shell">
        <div class="page-header">
            <div>
                <h1 class="text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">{{ __('nav.products') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" id="result-count"></p>
            </div>
            <div class="filter-panel grid w-full gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5">
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
        </div>

        <div id="loading" class="responsive-shop-grid">
            <div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton hidden h-80 rounded-2xl xl:block"></div>
        </div>
        <div id="grid" class="responsive-shop-grid"></div>
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

        return `<div class="product-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="relative">
                <a href="/products/${product.id}"><div class="shop-card-media">
                    <img src="${esc(photo)}" alt="${esc(product.name)}" class="shop-card-media-img" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}'">
                    ${!inStock ? '<div class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70"><span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400">' + esc(t.soldOut || '') + '</span></div>' : ''}
                    ${product.has_active_discount ? `<div class="absolute left-2.5 top-2.5 z-10 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">-${parseFloat(product.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                </div></a>
                <button data-fav-btn="${product.id}" onclick="event.stopPropagation();window.toggleFav(${product.id},this)" class="absolute right-2.5 top-2.5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-all hover:scale-110 dark:bg-gray-900/90 ${isFav ? 'text-red-500' : 'text-gray-400 dark:text-gray-500'}"><svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav ? 'currentColor' : 'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button>
            </div>
            <div class="p-3 sm:p-4">
                <a href="/products/${product.id}"><h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">${esc(product.name)}</h3></a>
                ${commercialName ? `<p class="mt-1.5 line-clamp-1 text-xs font-semibold text-gray-500 dark:text-gray-400">${esc(commercialName)}</p>` : ''}
                <div class="mt-3 flex flex-wrap gap-2">
                    ${product.category?.type ? `<span class="inline-flex rounded-full bg-brand-50 px-2.5 py-1 text-[10px] font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">${esc(categoryTypeLabel(product.category.type))}</span>` : ''}
                    ${subcategoryName ? `<span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">${esc(subcategoryName)}</span>` : ''}
                    ${barcode ? `<span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">${esc(barcode)}</span>` : ''}
                </div>
                <div class="mt-2 flex items-center gap-1.5 text-amber-400">${starStars(product.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${reviewCount ? esc((t.reviewsCount || '').replace(':count', String(reviewCount))) : ''}</span></div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-lg font-black ${product.has_active_discount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${displayPrice}</span><span class="text-[11px] text-gray-400">SYP</span>
                    ${product.has_active_discount ? `<span class="text-[11px] text-gray-400 line-through">${parseFloat(product.price || 0).toLocaleString()} SYP</span>` : ''}
                </div>
                <button onclick="window.addToCart&&window.addToCart(${product.id},\`${esc(product.name)}\`,${product.has_active_discount ? product.discounted_price : product.price},\`${esc(photo)}\`)" class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold ${inStock ? 'bg-gray-900 text-white hover:bg-brand-600 dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white active:scale-[.97]' : 'cursor-not-allowed bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-600'}" ${!inStock ? 'disabled' : ''}>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                    ${inStock ? esc(t.addCart || '') : esc(t.soldOut || '')}
                </button>
            </div>
        </div>`;
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
