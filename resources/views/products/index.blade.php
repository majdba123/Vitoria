@extends('layouts.app')

@section('title', __('nav.products') . ' - Vetora')

@section('content')
<div class="bg-transparent">
    <div class="catalog-page-band">
        <div class="page-shell py-3">
            <nav class="page-breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
                <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="page-breadcrumb-current">{{ __('nav.products') }}</span>
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
                <div class="min-w-0">
                    <label for="f-search" class="sr-only">{{ __('nav.search_products') }}</label>
                    <input id="f-search" type="search" class="form-input w-full" placeholder="{{ __('nav.search_products') }}">
                </div>
                <div class="min-w-0">
                    <label for="f-category-type" class="sr-only">{{ __('nav.all_types') }}</label>
                    <select id="f-category-type" class="form-select w-full">
                        <option value="">{{ __('nav.all_types') }}</option>
                        <option value="agriculture">{{ __('home.type_agriculture_short') }}</option>
                        <option value="veterinary">{{ __('home.type_veterinary_short') }}</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label for="f-category" class="sr-only">{{ __('nav.all_categories') }}</label>
                    <select id="f-category" class="form-select w-full">
                        <option value="">{{ __('nav.all_categories') }}</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label for="f-subcategory" class="sr-only">{{ __('nav.all_subcategories') }}</label>
                    <select id="f-subcategory" class="form-select w-full">
                        <option value="">{{ __('nav.all_subcategories') }}</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label for="f-sort" class="sr-only">{{ __('nav.all_sorting') }}</label>
                    <select id="f-sort" class="form-select w-full">
                        <option value="">{{ __('nav.all_sorting') }}</option>
                        <option value="latest">{{ __('nav.sort_latest') }}</option>
                        <option value="best_selling">{{ __('nav.sort_best_selling') }}</option>
                        <option value="most_favorited">{{ __('nav.sort_most_favorited') }}</option>
                        <option value="top_rated">{{ __('nav.sort_top_rated') }}</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label for="f-discount" class="sr-only">{{ __('nav.all_discounts') }}</label>
                    <select id="f-discount" class="form-select w-full">
                        <option value="">{{ __('nav.all_discounts') }}</option>
                        <option value="1">{{ __('nav.discounted_only') }}</option>
                        <option value="0">{{ __('nav.without_discount') }}</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label for="f-stock" class="sr-only">{{ __('nav.all_stock') }}</label>
                    <select id="f-stock" class="form-select w-full">
                        <option value="">{{ __('nav.all_stock') }}</option>
                        <option value="1">{{ __('nav.in_stock') }}</option>
                        <option value="0">{{ __('nav.out_of_stock') }}</option>
                    </select>
                </div>
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
        inStock: @json(__('nav.in_stock')),
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

    function productCard(product) {
        const subcategoryName = subcategoryLabel(product.subcategory);
        const context = product.vendor?.store_name || subcategoryName || categoryTypeLabel(product.category?.type);

        return window.renderProductCard(product, {
            href: '/products/' + product.id,
            context,
            placeholder: '{{ asset('images/product-placeholder.svg') }}',
            soldOutLabel: t.soldOut || '',
            inStockLabel: t.inStock || '',
            addToCartLabel: t.addCart || '',
            favoriteLabel: product.name || '',
            reviewsLabel: (count) => (t.reviewsCount || '').replace(':count', String(count)),
        });
    }

    function renderPagination(meta) {
        if (!meta || meta.last_page <= 1) return;
        const current = meta.current_page;
        const last = meta.last_page;
        const navBtn = (label, target, disabled) => `<button onclick="goP(${target})" class="flex h-10 items-center rounded-lg border px-4 text-xs font-bold ${disabled ? 'pointer-events-none opacity-40' : ''}" style="border-color: var(--color-border); background: var(--color-surface); color: var(--color-text-secondary);" ${disabled ? 'disabled' : ''}>${esc(label)}</button>`;
        let html = navBtn(@json(__('nav.prev')), current - 1, current === 1);
        getRange(current, last).forEach(item => {
            html += item === '...'
                ? '<span class="px-2" style="color: var(--color-text-muted);">…</span>'
                : `<button onclick="goP(${item})" class="flex h-10 w-10 items-center justify-center rounded-lg border text-xs font-bold ${item === current ? 'page-active' : ''}" style="${item === current ? '' : 'border-color: var(--color-border); background: var(--color-surface); color: var(--color-text-secondary);'}">${item}</button>`;
        });
        html += navBtn(@json(__('nav.next')), current + 1, current === last);
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
