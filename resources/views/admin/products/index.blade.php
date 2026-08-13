@extends('layouts.admin')
@php
    $discountOnly = $discountOnly ?? false;
@endphp

@section('title', 'Products — Vetora Admin')
@section('page-title', __('admin.manage_products_title'))

@section('content')
<div class="content-stack">
    {{-- Page Header --}}
    <div class="page-header mb-0">
        <p class="text-sm text-gray-500">{{ __('admin.manage_products_copy') }}</p>
        <div class="flex flex-wrap items-center gap-2">
            <x-csv-import
                id="products"
                label="Products"
                template-url="/api/admin/products/import/template"
                import-url="/api/admin/products/import"
            />
            <a href="{{ route('admin.products.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.add_product') }}
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="filter-panel">
        <div class="filter-grid-wide">
                <div>
                    <label for="filter-vendor" class="form-label">{{ __('admin.filter_by_vendor') }}</label>
                    <select id="filter-vendor" class="form-select">
                        <option value="">{{ __('admin.all_vendors') }}</option>
                    </select>
                </div>
                <div>
                    <label for="filter-product-status" class="form-label">{{ __('admin.filter_by_product_status') }}</label>
                    <select id="filter-product-status" class="form-select">
                        <option value="">{{ __('admin.all_status') }}</option>
                        <option value="pending">{{ __('admin.status_pending') }}</option>
                        <option value="approved">{{ __('admin.status_approved') }}</option>
                        <option value="rejected">{{ __('admin.status_rejected') }}</option>
                    </select>
                </div>
                <div>
                    <label for="filter-category-type" class="form-label">{{ __('admin.filter_by_type') }}</label>
                    <select id="filter-category-type" class="form-select">
                        <option value="">{{ __('admin.all_types') }}</option>
                        <option value="agriculture">{{ __('admin.type_agriculture') }}</option>
                        <option value="veterinary">{{ __('admin.type_veterinary') }}</option>
                    </select>
                </div>
                <div>
                    <label for="filter-category" class="form-label">{{ __('admin.filter_by_category') }}</label>
                    <select id="filter-category" class="form-select">
                        <option value="">{{ __('admin.all_categories') }}</option>
                    </select>
                </div>
                <div>
                    <label for="filter-status" class="form-label">{{ __('admin.filter_by_active') }}</label>
                    <select id="filter-status" class="form-select">
                        <option value="">{{ __('admin.all') }}</option>
                        <option value="1">{{ __('common.active') }}</option>
                        <option value="0">{{ __('common.inactive') }}</option>
                    </select>
                </div>
                <div>
                    <label for="filter-discount" class="form-label">{{ __('admin.filter_by_discount') }}</label>
                    <select id="filter-discount" class="form-select">
                        <option value="">{{ __('admin.all') }}</option>
                        <option value="1">{{ __('admin.with_discount') }}</option>
                        <option value="0">{{ __('admin.without_discount') }}</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button id="apply-filters" class="btn-primary btn-sm w-full sm:w-auto">{{ __('admin.apply_filters') }}</button>
                    <button id="clear-filters" class="btn-secondary btn-sm w-full sm:w-auto">{{ __('admin.clear_filters') }}</button>
                </div>
        </div>
    </div>

    <x-alert type="error" id="products-alert" />
    <x-alert type="success" id="products-success" />

    {{-- Loading --}}
    <div id="products-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">{{ __('admin.loading_products') }}</p>
    </div>

    {{-- Empty State --}}
    <div id="products-empty" class="hidden">
        <div class="card py-16 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            <h3 class="mt-3 text-sm font-semibold text-gray-900">{{ __('admin.no_products_yet') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('admin.add_product_for_vendor_hint') }}</p>
            <div class="mt-5">
                <a href="{{ route('admin.products.create') }}" class="btn-primary btn-sm">{{ __('admin.add_product') }}</a>
            </div>
        </div>
    </div>

    {{-- Products Table --}}
    <div id="products-grid-wrapper" class="hidden card overflow-hidden">
        <div class="admin-table-wrap table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('admin.name_label') }}</th>
                        <th scope="col" class="text-end">{{ __('products.fields.price') }}</th>
                        <th scope="col" class="text-end">{{ __('admin.qty_label') }}</th>
                        <th scope="col">{{ __('common.active') }}</th>
                        <th scope="col">{{ __('admin.approval_status_label') }}</th>
                        <th scope="col" class="text-end">{{ __('admin.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody id="products-grid"></tbody>
            </table>
        </div>

        <div class="flex flex-col items-center gap-3 border-t border-gray-100 px-4 py-3 dark:border-gray-800 sm:flex-row sm:justify-between">
            <p id="products-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div class="flex gap-2">
                <button id="prev-page" class="btn-secondary btn-xs" disabled>{{ __('nav.prev') }}</button>
                <button id="next-page" class="btn-secondary btn-xs" disabled>{{ __('nav.next') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div id="delete-modal" class="mobile-dialog">
    <div class="mobile-dialog-card">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/10">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <div>
                <h3 id="delete-modal-title" class="text-base font-semibold text-gray-900 dark:text-white">{{ __('admin.delete_product_title') }}</h3>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.action_cannot_be_undone') }}</p>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button id="delete-cancel" class="btn-secondary btn-sm">{{ __('common.cancel') }}</button>
            <button id="delete-confirm" class="btn-danger btn-sm">{{ __('admin.delete') }}</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const i18n = {!! json_encode([
        'allVendors' => __('admin.all_vendors'),
        'allCategories' => __('admin.all_categories'),
        'active' => __('common.active'),
        'inactive' => __('common.inactive'),
        'noCommercialName' => __('admin.no_commercial_name'),
        'noDescription' => __('admin.no_description'),
        'qty' => __('admin.qty_label'),
        'approvalStatus' => __('admin.approval_status_label'),
        'statusPending' => __('admin.status_pending'),
        'statusApproved' => __('admin.status_approved'),
        'statusRejected' => __('admin.status_rejected'),
        'show' => __('admin.show'),
        'reviews' => __('admin.reviews'),
        'edit' => __('common.edit'),
        'remove' => __('admin.remove'),
        'page' => __('nav.page'),
        'of' => __('nav.of'),
        'total' => __('vendor.total_label'),
        'failedLoadVendors' => __('admin.js_failed_load_vendors'),
        'failedLoadProducts' => __('admin.js_failed_load_products'),
        'failedToggleStatus' => __('admin.js_failed_toggle_status'),
        'statusUpdated' => __('admin.js_status_updated'),
        'failedUpdateStatus' => __('admin.js_failed_update_status'),
        'productDeleted' => __('admin.js_product_deleted'),
        'failedDeleteProduct' => __('admin.js_failed_delete_product'),
    ]) !!};
    let currentPage = 1;
    let deleteId = null;
    const deleteDialog = window.wireAccessibleDialog(document.getElementById('delete-modal'), closeDeleteModal, { labelledBy: 'delete-modal-title' });
    const vendorSelect = document.getElementById('filter-vendor');
    const productStatusSelect = document.getElementById('filter-product-status');
    const categoryTypeSelect = document.getElementById('filter-category-type');
    const categorySelect = document.getElementById('filter-category');
    const statusSelect = document.getElementById('filter-status');
    const discountSelect = document.getElementById('filter-discount');
    const defaultDiscountOnly = {{ $discountOnly ? 'true' : 'false' }};

    if (defaultDiscountOnly && discountSelect) {
        discountSelect.value = '1';
    }
    const initialParams = new URLSearchParams(window.location.search);
    if (initialParams.get('category_type')) {
        categoryTypeSelect.value = initialParams.get('category_type');
    }

    // Load vendors for filter
    try {
        const vendorsRes = await window.axios.get('/api/admin/vendors?per_page=100');
        const vendors = vendorsRes.data.data || [];
        vendorSelect.innerHTML = `<option value="">${esc(i18n.allVendors)}</option>` +
            vendors.map(v => `<option value="${v.id}">${esc(v.store_name)}</option>`).join('');
    } catch (e) {
        console.error(i18n.failedLoadVendors, e);
    }
    await loadCategories();

    loadProducts();

    window.addEventListener('csv-import:done', function (event) {
        if (event.detail && event.detail.id === 'products') {
            loadProducts();
        }
    });

    document.getElementById('prev-page').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadProducts(); } });
    document.getElementById('next-page').addEventListener('click', () => { currentPage++; loadProducts(); });
    document.getElementById('delete-cancel').addEventListener('click', closeDeleteModal);
    document.getElementById('delete-confirm').addEventListener('click', confirmDelete);
    document.getElementById('apply-filters').addEventListener('click', () => {
        currentPage = 1;
        loadProducts();
    });
    categorySelect.addEventListener('change', function () {});
    categoryTypeSelect.addEventListener('change', async function () {
        categorySelect.value = '';
        await loadCategories();
    });

    document.getElementById('clear-filters').addEventListener('click', () => {
        vendorSelect.value = '';
        productStatusSelect.value = '';
        categoryTypeSelect.value = '';
        categorySelect.value = '';
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

            // Apply filters only if they have values
            if (vendorSelect && vendorSelect.value) {
                params.append('vendor_id', vendorSelect.value);
            }
            if (productStatusSelect && productStatusSelect.value) {
                params.append('status', productStatusSelect.value);
            }
            if (categoryTypeSelect && categoryTypeSelect.value) {
                params.append('category_type', categoryTypeSelect.value);
            }
            if (categorySelect && categorySelect.value) {
                params.append('category_id', categorySelect.value);
            }
            if (statusSelect && statusSelect.value !== '') {
                params.append('is_active', statusSelect.value);
            }
            if (discountSelect && discountSelect.value !== '') {
                params.append('has_discount', discountSelect.value);
            }

            const res = await window.axios.get('/api/admin/products?' + params.toString());
            renderProducts(res.data.data);
            renderPagination(res.data.meta);
        } catch (e) {
            console.error(i18n.failedLoadProducts, e);
            showAlert('products-alert', e.response?.data?.message || i18n.failedLoadProducts);
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
            return `<tr>
                <td>
                    <a href="/admin/products/${p.id}" class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden bg-gray-100 dark:bg-gray-800" style="border-radius: var(--radius-control)">
                            ${photoUrl ? `<img src="${photoUrl}" class="h-full w-full object-contain p-1" alt="">` : `<svg class="h-5 w-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>`}
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate font-semibold text-gray-900 dark:text-white">${esc(p.name)}</span>
                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">${esc(p.commercial_name || p.category?.name || i18n.noCommercialName)}</span>
                        </span>
                    </a>
                </td>
                <td class="text-end tabular-nums">
                    ${p.has_active_discount
                        ? `<span class="badge badge-danger align-middle">-${parseFloat(p.discount_percentage || 0).toFixed(0)}%</span> <span class="font-semibold" style="color: var(--color-danger-strong)">${parseFloat(p.discounted_price || p.price || 0).toLocaleString()} SYP</span> <span class="text-xs text-gray-400 line-through">${parseFloat(p.price || 0).toLocaleString()} SYP</span>`
                        : `<span class="font-semibold text-gray-900 dark:text-white">${parseFloat(p.price || 0).toLocaleString()} SYP</span>`
                    }
                </td>
                <td class="text-end tabular-nums text-gray-600 dark:text-gray-300">${p.quantity}</td>
                <td>
                    <button onclick="toggleProductStatus(${p.id})" class="badge ${p.is_active ? 'badge-success' : 'badge-danger'}">
                        ${p.is_active ? esc(i18n.active) : esc(i18n.inactive)}
                    </button>
                </td>
                <td>
                    <select onchange="updateProductStatus(${p.id}, this.value)" data-original-value="${p.status || 'pending'}" class="form-select text-xs" style="min-height: 2rem; padding-top: 0.25rem; padding-bottom: 0.25rem;" aria-label="${esc(i18n.approvalStatus)}">
                        <option value="pending" ${p.status === 'pending' ? 'selected' : ''}>${esc(i18n.statusPending)}</option>
                        <option value="approved" ${p.status === 'approved' ? 'selected' : ''}>${esc(i18n.statusApproved)}</option>
                        <option value="rejected" ${p.status === 'rejected' ? 'selected' : ''}>${esc(i18n.statusRejected)}</option>
                    </select>
                </td>
                <td class="text-end">
                    <div class="row-actions-menu">
                        <button type="button" class="row-actions-trigger" aria-label="${esc(i18n.show)} ${esc(p.name)}">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                        </button>
                        <div class="row-actions-panel dropdown-panel">
                            <a href="/admin/products/${p.id}">${esc(i18n.show)}</a>
                            <a href="/admin/products/${p.id}/reviews">${esc(i18n.reviews)}</a>
                            <a href="/admin/products/${p.id}/edit">${esc(i18n.edit)}</a>
                            <button type="button" class="is-danger" onclick="openDeleteModal(${p.id})">${esc(i18n.remove)}</button>
                        </div>
                    </div>
                </td>
            </tr>`;
        }).join('');

        grid.querySelectorAll('.row-actions-menu').forEach((menu) => {
            VetoraWorkspace.wireDropdown(menu.querySelector('.row-actions-trigger'), menu.querySelector('.row-actions-panel'));
        });
    }

    function renderPagination(meta) {
        currentPage = meta.current_page; // Sync currentPage with server response
        document.getElementById('products-info').textContent = `${i18n.page} ${meta.current_page} ${i18n.of} ${meta.last_page} · ${meta.total} ${i18n.total}`;
        document.getElementById('prev-page').disabled = meta.current_page <= 1;
        document.getElementById('next-page').disabled = meta.current_page >= meta.last_page;
    }

    window.toggleProductStatus = async function (id) {
        try {
            const res = await window.axios.patch('/api/admin/products/' + id + '/toggle-active');
            showAlert('products-success', res.data.message);
            loadProducts();
        } catch (e) {
            showAlert('products-alert', e.response?.data?.message || i18n.failedToggleStatus);
        }
    };
    
    window.updateProductStatus = async function (id, status) {
        const select = event.target;
        const originalValue = select.dataset.originalValue || status;
        select.disabled = true;
        
        try {
            const res = await window.axios.patch('/api/admin/products/' + id + '/status', {
                status: status
            });
            showAlert('products-success', res.data.message || i18n.statusUpdated);
            select.dataset.originalValue = status;
            // Update badge class
            select.className = select.className.replace(/badge-(success|danger|warning)/g, '');
            select.classList.add(getStatusBadgeClass(status));
        } catch (e) {
            showAlert('products-alert', e.response?.data?.message || i18n.failedUpdateStatus);
            select.value = originalValue; // Reset to original value
            loadProducts(); // Reload on error to reset dropdown
        } finally {
            select.disabled = false;
        }
    };
    
    function getStatusBadgeClass(status) {
        if (status === 'approved') return 'badge-success';
        if (status === 'rejected') return 'badge-danger';
        return 'badge-warning';
    }

    window.openDeleteModal = function (id) {
        deleteId = id;
        const m = document.getElementById('delete-modal'); m.classList.remove('hidden'); m.classList.add('flex');
        deleteDialog.open();
    };

    function closeDeleteModal() {
        deleteId = null;
        const m = document.getElementById('delete-modal'); m.classList.add('hidden'); m.classList.remove('flex');
        deleteDialog.close();
    }

    async function confirmDelete() {
        if (!deleteId) return;
        const confirmButton = document.getElementById('delete-confirm');
        confirmButton.disabled = true;
        try {
            await window.axios.delete('/api/admin/products/' + deleteId);
            closeDeleteModal();
            showAlert('products-success', i18n.productDeleted);
            loadProducts();
        } catch (e) {
            closeDeleteModal();
            showAlert('products-alert', e.response?.data?.message || i18n.failedDeleteProduct);
        } finally {
            confirmButton.disabled = false;
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
            const params = new URLSearchParams();
            if (categoryTypeSelect && categoryTypeSelect.value) {
                params.append('type', categoryTypeSelect.value);
            }
            const res = await window.axios.get('/api/admin/categories' + (params.toString() ? '?' + params.toString() : ''));
            const categories = res.data.data || [];
            categorySelect.innerHTML = `<option value="">${esc(i18n.allCategories)}</option>` +
                categories.map(category => `<option value="${category.id}">${esc(category.name)}</option>`).join('');
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

});
</script>
@endpush
