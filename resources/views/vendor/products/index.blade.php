@extends('layouts.vendor')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
<div class="content-stack">
    <div class="page-header mb-0">
        <p class="text-sm text-gray-500">Manage your store products.</p>
        <a href="{{ route('vendor.products.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">Add Product</a>
    </div>

    <div class="filter-panel">
        <div class="filter-grid-wide">
            <div>
                <label for="filter-category" class="form-label">Filter by Category</label>
                <select id="filter-category" class="form-select">
                    <option value="">All Categories</option>
                </select>
            </div>
            <div>
                <label for="filter-status" class="form-label">Filter by Active</label>
                <select id="filter-status" class="form-select">
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div>
                <label for="filter-discount" class="form-label">Filter by Discount</label>
                <select id="filter-discount" class="form-select">
                    <option value="">All</option>
                    <option value="1">With Discount</option>
                    <option value="0">Without Discount</option>
                </select>
            </div>
            <div class="filter-actions">
                <button id="apply-filters" class="btn-primary btn-sm w-full sm:w-auto">Apply Filters</button>
                <button id="clear-filters" class="btn-secondary btn-sm w-full sm:w-auto">Clear</button>
            </div>
        </div>
    </div>

    <x-alert type="error" id="products-alert" />
    <x-alert type="success" id="products-success" />

    <div id="products-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading products...</p>
    </div>

    <div id="products-empty" class="hidden">
        <div class="card py-16 text-center">
            <h3 class="mt-3 text-sm font-semibold text-gray-900">No products yet</h3>
            <p class="mt-1 text-sm text-gray-500">Add a product to get started.</p>
        </div>
    </div>

    <div id="products-grid-wrapper" class="hidden">
        <div id="products-grid" class="responsive-product-grid"></div>
        <div class="mt-4 flex flex-col items-center gap-3 border-t border-gray-100 px-4 py-3 sm:flex-row sm:justify-between">
            <p id="products-info" class="text-xs text-gray-500"></p>
            <div class="flex gap-2">
                <button id="prev-page" class="btn-secondary btn-xs" disabled>Prev</button>
                <button id="next-page" class="btn-secondary btn-xs" disabled>Next</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    let currentPage = 1;
    const categorySelect = document.getElementById('filter-category');
    const statusSelect = document.getElementById('filter-status');
    const discountSelect = document.getElementById('filter-discount');

    try {
        const res = await window.axios.get('/api/vendor/categories');
        const categories = res.data.data || [];
        categorySelect.innerHTML = '<option value="">All Categories</option>' +
            categories.map(category => `<option value="${category.id}">${esc(category.name)}</option>`).join('');
    } catch (e) {}

    loadProducts();

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
            showAlert('products-alert', e.response?.data?.message || 'Failed to load products.');
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
        grid.innerHTML = products.map(p => `<div class="card"><div class="card-body"><h3 class="text-base font-semibold text-gray-900">${esc(p.name)}</h3><p class="mt-1 text-sm text-gray-500">${esc(p.category?.name || '')}</p><div class="mt-3 flex items-center justify-between"><span class="text-lg font-bold text-gray-900">$${parseFloat(p.price || 0).toFixed(2)}</span><span class="badge ${p.is_active ? 'badge-success' : 'badge-danger'}">${p.is_active ? 'Active' : 'Inactive'}</span></div><div class="mt-4 flex gap-2"><a href="/vendor/products/${p.id}" class="btn-secondary btn-xs flex-1">Show</a><a href="/vendor/products/${p.id}/edit" class="btn-primary btn-xs flex-1">Edit</a></div></div></div>`).join('');
    }

    function renderPagination(meta) {
        currentPage = meta.current_page;
        document.getElementById('products-info').textContent = `Page ${meta.current_page} of ${meta.last_page} · ${meta.total} total`;
        document.getElementById('prev-page').disabled = meta.current_page <= 1;
        document.getElementById('next-page').disabled = meta.current_page >= meta.last_page;
    }

    function showLoading(s) { document.getElementById('products-loading').classList.toggle('hidden', !s); }
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
