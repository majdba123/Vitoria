@extends('layouts.vendor')
@php($discountOnly = $discountOnly ?? false)

@section('title', 'Products — SyriaZone Vendor')
@section('page-title', 'Products')

@section('content')
<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-500">Manage your store products.</p>
        <a href="{{ route('vendor.products.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Product
        </a>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-body">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="filter-product-status" class="form-label">Filter by Product Status</label>
                    <select id="filter-product-status" class="form-input">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label for="filter-category" class="form-label">Filter by Category</label>
                    <select id="filter-category" class="form-input">
                        <option value="">All Categories</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label for="filter-subcategory" class="form-label">Filter by Subcategory</label>
                    <select id="filter-subcategory" class="form-input" disabled>
                        <option value="">Select category first...</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label for="filter-status" class="form-label">Filter by Active</label>
                    <select id="filter-status" class="form-input">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label for="filter-discount" class="form-label">Filter by Discount</label>
                    <select id="filter-discount" class="form-input">
                        <option value="">All</option>
                        <option value="1">With Discount</option>
                        <option value="0">Without Discount</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button id="apply-filters" class="btn-primary btn-sm">Apply Filters</button>
                    <button id="clear-filters" class="btn-secondary btn-sm">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <x-alert type="error" id="products-alert" />
    <x-alert type="success" id="products-success" />

    {{-- Loading --}}
    <div id="products-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-emerald-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading products...</p>
    </div>

    {{-- Empty State --}}
    <div id="products-empty" class="hidden">
        <div class="card py-16 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            <h3 class="mt-3 text-sm font-semibold text-gray-900">No products yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding your first product.</p>
            <div class="mt-5">
                <a href="{{ route('vendor.products.create') }}" class="btn-primary btn-sm">Add Product</a>
            </div>
        </div>
    </div>

    {{-- Products Grid --}}
    <div id="products-grid-wrapper" class="hidden">
        <div id="products-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"></div>

        <div class="mt-4 flex flex-col items-center gap-3 border-t border-gray-100 px-4 py-3 sm:flex-row sm:justify-between">
            <p id="products-info" class="text-xs text-gray-500"></p>
            <div class="flex gap-2">
                <button id="prev-page" class="btn-secondary btn-xs" disabled>Prev</button>
                <button id="next-page" class="btn-secondary btn-xs" disabled>Next</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900">Delete Product</h3>
                <p class="mt-0.5 text-sm text-gray-500">This action cannot be undone.</p>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button id="delete-cancel" class="btn-secondary btn-sm">Cancel</button>
            <button id="delete-confirm" class="btn-danger btn-sm">Delete</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let deleteId = null;
    const productStatusSelect = document.getElementById('filter-product-status');
    const categorySelect = document.getElementById('filter-category');
    const subcategorySelect = document.getElementById('filter-subcategory');
    const statusSelect = document.getElementById('filter-status');
    const discountSelect = document.getElementById('filter-discount');
    const defaultDiscountOnly = {{ $discountOnly ? 'true' : 'false' }};
    if (defaultDiscountOnly && discountSelect) {
        discountSelect.value = '1';
    }
    loadCategories();

    loadProducts();

    document.getElementById('prev-page').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadProducts(); } });
    document.getElementById('next-page').addEventListener('click', () => { currentPage++; loadProducts(); });
    document.getElementById('delete-cancel').addEventListener('click', closeDeleteModal);
    document.getElementById('delete-confirm').addEventListener('click', confirmDelete);
    document.getElementById('apply-filters').addEventListener('click', () => {
        currentPage = 1;
        loadProducts();
    });
    categorySelect.addEventListener('change', async function () {
        await loadSubcategories(categorySelect.value);
    });

    document.getElementById('clear-filters').addEventListener('click', () => {
        productStatusSelect.value = '';
        categorySelect.value = '';
        subcategorySelect.innerHTML = '<option value="">Select category first...</option>';
        subcategorySelect.disabled = true;
        statusSelect.value = '';
        discountSelect.value = defaultDiscountOnly ? '1' : '';
        currentPage = 1;
        loadProducts();
    });

    // Remove auto-apply on change - user must click "Apply Filters"

    async function loadProducts() {
        showLoading(true);
        try {
            const params = new URLSearchParams({ page: currentPage });
            if (productStatusSelect && productStatusSelect.value) {
                params.append('status', productStatusSelect.value);
            }
            if (categorySelect && categorySelect.value) {
                params.append('category_id', categorySelect.value);
            }
            if (subcategorySelect && subcategorySelect.value) {
                params.append('subcategory_id', subcategorySelect.value);
            }
            if (statusSelect && statusSelect.value !== '') {
                params.append('is_active', statusSelect.value);
            }
            if (discountSelect && discountSelect.value !== '') {
                params.append('has_discount', discountSelect.value);
            }

            const res = await window.axios.get('/api/vendor/products?' + params.toString());
            renderProducts(res.data.data);
            renderPagination(res.data.meta);
        } catch (e) {
            console.error('Failed to load products:', e);
            showAlert('products-alert', e.response?.data?.message || 'Failed to load products.');
        } finally {
            showLoading(false);
        }
    }

    function renderProducts(products) {
        const grid = document.getElementById('products-grid');
        const gridW = document.getElementById('products-grid-wrapper');
        const empty = document.getElementById('products-empty');

        if (!products || products.length === 0) { gridW.classList.add('hidden'); empty.classList.remove('hidden'); return; }
        empty.classList.add('hidden'); gridW.classList.remove('hidden');

        grid.innerHTML = products.map(p => {
            const photoUrl = p.first_photo_url || null;
            return `<div class="group card overflow-hidden border border-gray-200/70 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl">
                <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 sm:aspect-square">
                    ${photoUrl
                        ? `<div class="flex h-full w-full items-center justify-center p-2">
                            <img src="${photoUrl}" class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-105" alt="${esc(p.name)}">
                        </div>`
                        : `<div class="flex h-full w-full items-center justify-center text-gray-300">
                            <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                        </div>`
                    }
                    <div class="absolute right-2 top-2">
                        <span class="badge ${p.is_active ? 'badge-success' : 'badge-danger'} shadow-lg">
                            <span class="mr-1 inline-block h-1.5 w-1.5 rounded-full ${p.is_active ? 'bg-emerald-500' : 'bg-red-500'}"></span>
                            ${p.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>
                    ${p.has_active_discount ? `<div class="absolute left-2 top-2 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-lg">-${parseFloat(p.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                </div>
                <div class="card-body">
                    <h3 class="text-base font-semibold text-gray-900 line-clamp-1">${esc(p.name)}</h3>
                    <p class="mt-1 text-xs text-gray-500 line-clamp-2">${esc(p.description || 'No description')}</p>
                    <div class="mt-3 flex items-center justify-between">
                        <div>
                            ${p.has_active_discount
                                ? `<p class="text-lg font-bold text-red-600">$${parseFloat(p.discounted_price || p.price || 0).toFixed(2)}</p><p class="text-xs text-gray-400 line-through">$${parseFloat(p.price || 0).toFixed(2)}</p>`
                                : `<p class="text-lg font-bold text-gray-900">$${parseFloat(p.price || 0).toFixed(2)}</p>`
                            }
                            <p class="text-xs text-gray-500">Qty: ${p.quantity}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-3">
                        <a href="/vendor/products/${p.id}" class="btn-secondary btn-xs flex-1 text-center min-w-0">
                            <svg class="h-3.5 w-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Show
                        </a>
                        <a href="/vendor/products/${p.id}/reviews" class="btn-secondary btn-xs flex-1 text-center min-w-0" title="Reviews">
                            <svg class="h-3.5 w-3.5 inline" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            Reviews
                        </a>
                        <a href="/vendor/products/${p.id}/edit" class="btn-primary btn-xs flex-1 text-center min-w-0">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            Edit
                        </a>
                        <button onclick="openDeleteModal(${p.id})" class="btn-danger btn-xs flex-1">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            Remove
                        </button>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function renderPagination(meta) {
        currentPage = meta.current_page; // Sync currentPage with server response
        document.getElementById('products-info').textContent = `Page ${meta.current_page} of ${meta.last_page} · ${meta.total} total`;
        document.getElementById('prev-page').disabled = meta.current_page <= 1;
        document.getElementById('next-page').disabled = meta.current_page >= meta.last_page;
    }

    window.openDeleteModal = function (id) {
        deleteId = id;
        const m = document.getElementById('delete-modal'); m.classList.remove('hidden'); m.classList.add('flex');
    };

    function closeDeleteModal() {
        deleteId = null;
        const m = document.getElementById('delete-modal'); m.classList.add('hidden'); m.classList.remove('flex');
    }

    async function confirmDelete() {
        if (!deleteId) return;
        try {
            await window.axios.delete('/api/vendor/products/' + deleteId);
            closeDeleteModal();
            showAlert('products-success', 'Product deleted.');
            loadProducts();
        } catch (e) {
            closeDeleteModal();
            showAlert('products-alert', e.response?.data?.message || 'Failed to delete.');
        }
    }

    function showLoading(s) { document.getElementById('products-loading').classList.toggle('hidden', !s); }
    function showAlert(id, msg) {
        const b = document.getElementById(id);
        document.getElementById(id + '-message').textContent = msg;
        b.classList.remove('hidden');
        setTimeout(() => b.classList.add('hidden'), 4000);
    }
    function esc(t) { if (!t) return ''; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    async function loadCategories() {
        try {
            const res = await window.axios.get('/api/vendor/allowed-categories');
            const categories = res.data.data || [];
            categorySelect.innerHTML = '<option value="">All Categories</option>' +
                categories.map(category => `<option value="${category.id}">${esc(category.name)}</option>`).join('');
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    async function loadSubcategories(categoryId) {
        if (!categoryId) {
            subcategorySelect.innerHTML = '<option value="">Select category first...</option>';
            subcategorySelect.disabled = true;

            return;
        }

        subcategorySelect.disabled = false;
        subcategorySelect.innerHTML = '<option value="">Loading subcategories...</option>';

        try {
            const res = await window.axios.get('/api/vendor/subcategories?category_id=' + categoryId);
            const subcategories = res.data.data || [];
            subcategorySelect.innerHTML = '<option value="">All Subcategories</option>' +
                subcategories.map(subcategory => `<option value="${subcategory.id}">${esc(subcategory.name)}</option>`).join('');
        } catch (error) {
            subcategorySelect.innerHTML = '<option value="">Failed to load subcategories</option>';
            subcategorySelect.disabled = true;
            console.error('Failed to load subcategories:', error);
        }
    }
});
</script>
@endpush
