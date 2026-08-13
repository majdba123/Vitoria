@extends('layouts.vendor')

@section('title', 'Products')
@section('page-title', __('vendor.total_products'))

@section('content')
<div class="content-stack">
    <div class="page-header mb-0">
        <p class="text-sm text-gray-500">{{ __('vendor.manage_products_copy') }}</p>
        <div class="flex flex-wrap items-center gap-2">
            <x-csv-import
                id="products"
                label="Products"
                template-url="/api/vendor/products/import/template"
                import-url="/api/vendor/products/import"
            />
            <a href="{{ route('vendor.products.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">{{ __('vendor.add_product') }}</a>
        </div>
    </div>

    <div class="filter-panel">
        <div class="filter-grid-wide">
            <div>
                <label for="filter-category" class="form-label">{{ __('vendor.filter_by_category') }}</label>
                <select id="filter-category" class="form-select">
                    <option value="">{{ __('nav.all_categories') }}</option>
                </select>
            </div>
            <div>
                <label for="filter-status" class="form-label">{{ __('vendor.filter_by_active') }}</label>
                <select id="filter-status" class="form-select">
                    <option value="">{{ __('vendor.all') }}</option>
                    <option value="1">{{ __('common.active') }}</option>
                    <option value="0">{{ __('common.inactive') }}</option>
                </select>
            </div>
            <div>
                <label for="filter-discount" class="form-label">{{ __('vendor.filter_by_discount') }}</label>
                <select id="filter-discount" class="form-select">
                    <option value="">{{ __('vendor.all') }}</option>
                    <option value="1">{{ __('vendor.with_discount') }}</option>
                    <option value="0">{{ __('nav.without_discount') }}</option>
                </select>
            </div>
            <div class="filter-actions">
                <button id="apply-filters" class="btn-primary btn-sm w-full sm:w-auto">{{ __('nav.apply_filters') }}</button>
                <button id="clear-filters" class="btn-secondary btn-sm w-full sm:w-auto">{{ __('nav.clear_filters') }}</button>
            </div>
        </div>
    </div>

    <x-alert type="error" id="products-alert" />
    <x-alert type="success" id="products-success" />

    <div id="products-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">{{ __('vendor.loading_products') }}</p>
    </div>

    <div id="products-empty" class="hidden">
        <div class="card py-16 text-center">
            <h3 class="mt-3 text-sm font-semibold text-gray-900">{{ __('vendor.no_products_yet') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('vendor.add_product_hint') }}</p>
        </div>
    </div>

    <div id="products-grid-wrapper" class="hidden">
        <div id="products-grid" class="responsive-product-grid"></div>
        <div class="mt-4 flex flex-col items-center gap-3 border-t border-gray-100 px-4 py-3 sm:flex-row sm:justify-between">
            <p id="products-info" class="text-xs text-gray-500"></p>
            <div class="flex gap-2">
                <button id="prev-page" class="btn-secondary btn-xs" disabled>{{ __('nav.prev') }}</button>
                <button id="next-page" class="btn-secondary btn-xs" disabled>{{ __('nav.next') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const i18n = {!! json_encode([
        'active' => __('common.active'),
        'inactive' => __('common.inactive'),
        'show' => __('vendor.show'),
        'edit' => __('common.edit'),
        'failedLoad' => __('vendor.failed_load_products'),
        'page' => __('nav.page'),
        'of' => __('nav.of'),
        'total' => __('vendor.total_label'),
        'outOfStock' => __('nav.out_of_stock'),
    ]) !!};
    let currentPage = 1;
    const categorySelect = document.getElementById('filter-category');
    const statusSelect = document.getElementById('filter-status');
    const discountSelect = document.getElementById('filter-discount');

    try {
        const res = await window.axios.get('/api/vendor/categories');
        const categories = res.data.data || [];
        categorySelect.innerHTML = '<option value="">{{ __('nav.all_categories') }}</option>' +
            categories.map(category => `<option value="${category.id}">${esc(category.name)}</option>`).join('');
    } catch (e) {}

    loadProducts();

    window.addEventListener('csv-import:done', function (event) {
        if (event.detail && event.detail.id === 'products') {
            loadProducts();
        }
    });

    document.getElementById('prev-page').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadProducts(); } });
    document.getElementById('next-page').addEventListener('click', () => { currentPage++; loadProducts(); });
    document.getElementById('apply-filters').addEventListener('click', () => { currentPage = 1; loadProducts(); });
    document.getElementById('clear-filters').addEventListener('click', () => {
        categorySelect.value = '';
        statusSelect.value = '';
        discountSelect.value = '';
        currentPage = 1;
        loadProducts();
    });

    async function loadProducts() {
        showLoading(true);
        try {
            const params = new URLSearchParams({ page: currentPage });
            if (categorySelect.value) params.append('category_id', categorySelect.value);
            if (statusSelect.value !== '') params.append('is_active', statusSelect.value);
            if (discountSelect.value !== '') params.append('has_discount', discountSelect.value);
            const res = await window.axios.get('/api/vendor/products?' + params.toString());
            renderProducts(res.data.data);
            renderPagination(res.data.meta);
        } catch (e) {
            showAlert('products-alert', e.response?.data?.message || i18n.failedLoad);
        } finally {
            showLoading(false);
        }
    }

    function renderProducts(products) {
        const grid = document.getElementById('products-grid');
        const gridW = document.getElementById('products-grid-wrapper');
        const empty = document.getElementById('products-empty');
        if (!products || products.length === 0) {
            gridW.classList.add('hidden');
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');
        gridW.classList.remove('hidden');
        grid.innerHTML = products.map(p => {
            const photo = p.first_photo_url || p.fallback_photo_url;
            const priceHtml = p.has_active_discount
                ? `<span class="text-lg font-bold text-gray-900">${fmtMoney(p.discounted_price)}</span> <span class="text-xs text-gray-400 line-through">${fmtMoney(p.price)}</span>`
                : `<span class="text-lg font-bold text-gray-900">${fmtMoney(p.price)}</span>`;
            const outOfStock = Number(p.quantity || 0) <= 0;

            return `<div class="card"><div class="aspect-square overflow-hidden bg-gray-50"><img src="${esc(photo)}" alt="${esc(p.name)}" class="h-full w-full object-contain p-3" loading="lazy" onerror="this.onerror=null;this.src='${esc(p.fallback_photo_url)}'"></div><div class="card-body"><h3 class="text-base font-semibold text-gray-900">${esc(p.name)}</h3><p class="mt-1 text-sm text-gray-500">${esc(p.commercial_name || p.category?.name || '')}</p><div class="mt-3 flex items-center justify-between gap-2"><span>${priceHtml}</span><span class="badge ${p.is_active ? 'badge-success' : 'badge-danger'}">${p.is_active ? esc(i18n.active) : esc(i18n.inactive)}</span></div>${outOfStock ? `<p class="mt-1.5 text-xs font-semibold text-danger-600">${esc(i18n.outOfStock)}</p>` : ''}<div class="mt-4 flex gap-2"><a href="/vendor/products/${p.id}" class="btn-secondary btn-xs flex-1">${esc(i18n.show)}</a><a href="/vendor/products/${p.id}/edit" class="btn-primary btn-xs flex-1">${esc(i18n.edit)}</a></div></div></div>`;
        }).join('');
    }

    function renderPagination(meta) {
        currentPage = meta.current_page;
        document.getElementById('products-info').textContent = `${i18n.page} ${meta.current_page} ${i18n.of} ${meta.last_page} · ${meta.total} ${i18n.total}`;
        document.getElementById('prev-page').disabled = meta.current_page <= 1;
        document.getElementById('next-page').disabled = meta.current_page >= meta.last_page;
    }

    function showLoading(s) { document.getElementById('products-loading').classList.toggle('hidden', !s); }
    function fmtMoney(v) { return Number(v || 0).toLocaleString() + ' SYP'; }
    function showAlert(id, msg) {
        const b = document.getElementById(id);
        document.getElementById(id + '-message').textContent = msg;
        b.classList.remove('hidden');
        setTimeout(() => b.classList.add('hidden'), 4000);
    }
    function esc(t) {
        if (!t) return '';
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }
});
</script>
@endpush
