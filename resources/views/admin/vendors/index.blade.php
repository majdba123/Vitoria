@extends('layouts.admin')

@section('title', 'Vendors — Vetora Admin')
@section('page-title', __('admin.manage_vendors_title'))

@section('content')
<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">{{ __('admin.manage_vendor_accounts_copy') }}</p>
        </div>
        <a href="{{ route('admin.vendors.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            {{ __('admin.add_vendor') }}
        </a>
    </div>

    {{-- Alerts --}}
    <x-alert type="error" id="vendors-alert" />
    <x-alert type="success" id="vendors-success" />

    {{-- Filters --}}
    <div class="card">
        <div class="grid gap-3 p-4 sm:grid-cols-7">
            <div>
                <label for="filter-status" class="form-label">{{ __('admin.status_label') }}</label>
                <select id="filter-status" class="form-input">
                    <option value="">{{ __('admin.all_statuses') }}</option>
                    <option value="pending">{{ __('common.pending') }}</option>
                    <option value="active">{{ __('common.active') }}</option>
                    <option value="inactive">{{ __('common.inactive') }}</option>
                </select>
            </div>
            <div>
                <label for="filter-business-type" class="form-label">{{ __('admin.business_type_label') }}</label>
                <select id="filter-business-type" class="form-input">
                    <option value="">{{ __('admin.all_business_types') }}</option>
                    <option value="agriculture">{{ __('admin.type_agriculture') }}</option>
                    <option value="veterinary">{{ __('admin.type_veterinary') }}</option>
                    <option value="both">{{ __('admin.both') }}</option>
                </select>
            </div>
            <div>
                <label for="filter-category-type" class="form-label">{{ __('admin.category_type_label') }}</label>
                <select id="filter-category-type" class="form-input">
                    <option value="">{{ __('admin.all_category_types') }}</option>
                    <option value="agriculture">{{ __('admin.type_agriculture') }}</option>
                    <option value="veterinary">{{ __('admin.type_veterinary') }}</option>
                </select>
            </div>
            <div>
                <label for="filter-category" class="form-label">{{ __('admin.category_label') }}</label>
                <select id="filter-category" class="form-input">
                    <option value="">{{ __('admin.all_categories') }}</option>
                </select>
            </div>
            <div>
                <label for="filter-name" class="form-label">{{ __('admin.name_label') }}</label>
                <input id="filter-name" type="search" class="form-input" placeholder="{{ __('admin.store_or_owner_name') }}">
            </div>
            <div>
                <label for="filter-email" class="form-label">{{ __('admin.email_label') }}</label>
                <input id="filter-email" type="search" class="form-input" placeholder="owner@example.com">
            </div>
            <div class="flex items-end gap-2">
                <button id="apply-filters" class="btn-primary btn-sm flex-1">{{ __('admin.filter') }}</button>
                <button id="reset-filters" class="btn-secondary btn-sm">{{ __('admin.reset') }}</button>
            </div>
        </div>
    </div>

    {{-- Loading --}}
    <div id="vendors-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">{{ __('admin.loading_vendors') }}</p>
    </div>

    {{-- Empty State --}}
    <div id="vendors-empty" class="hidden">
        <div class="card py-16 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35"/></svg>
            <h3 class="mt-3 text-sm font-semibold text-gray-900">{{ __('admin.no_vendors_yet') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('admin.get_started_create_vendor') }}</p>
            <div class="mt-5">
                <a href="{{ route('admin.vendors.create') }}" class="btn-primary btn-sm">{{ __('admin.add_vendor') }}</a>
            </div>
        </div>
    </div>

    {{-- Desktop Table --}}
    <div id="vendors-table-wrapper" class="hidden">
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>{{ __('admin.th_store') }}</th>
                            <th class="hidden md:table-cell">{{ __('admin.th_owner') }}</th>
                            <th class="hidden xl:table-cell">{{ __('admin.th_source') }}</th>
                            <th class="hidden xl:table-cell">{{ __('admin.th_type') }}</th>
                            <th class="hidden xl:table-cell">{{ __('admin.th_category') }}</th>
                            <th class="hidden lg:table-cell">{{ __('admin.th_national_id') }}</th>
                            <th>{{ __('admin.th_status') }}</th>
                            <th class="text-right">{{ __('admin.th_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="vendors-tbody"></tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="flex flex-col items-center gap-3 border-t border-gray-100 px-4 py-3 sm:flex-row sm:justify-between">
                <p id="vendors-info" class="text-xs text-gray-500"></p>
                <div class="flex gap-2">
                    <button id="prev-page" class="btn-secondary btn-xs" disabled>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        {{ __('nav.prev') }}
                    </button>
                    <button id="next-page" class="btn-secondary btn-xs" disabled>
                        {{ __('nav.next') }}
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </button>
                </div>
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
                <h3 id="delete-modal-title" class="text-base font-semibold text-gray-900 dark:text-white">{{ __('admin.delete_vendor_title') }}</h3>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.delete_vendor_warning') }}</p>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button id="delete-cancel" class="btn-secondary btn-sm">{{ __('common.cancel') }}</button>
            <button id="delete-confirm" class="btn-danger btn-sm">{{ __('common.delete') }}</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const i18n = {!! json_encode([
        'failedLoadVendors' => __('admin.js_failed_load_vendors_list'),
        'failedLoadCategories' => __('common.failed_load_categories'),
        'selfRegistration' => __('admin.self_registration'),
        'adminSource' => __('admin.admin_registration'),
        'both' => __('admin.both'),
        'notAssigned' => __('admin.not_assigned'),
        'approveVendor' => __('admin.title_approve_vendor'),
        'viewProfile' => __('admin.title_view_profile'),
        'viewOrderHistory' => __('admin.title_view_order_history'),
        'viewCommissionDashboard' => __('admin.title_view_commission_dashboard'),
        'edit' => __('common.edit'),
        'delete' => __('common.delete'),
        'clickToToggle' => __('admin.title_click_to_toggle'),
        'active' => __('common.active'),
        'inactive' => __('common.inactive'),
        'pending' => __('common.pending'),
        'page' => __('nav.page'),
        'of' => __('nav.of'),
        'total' => __('vendor.total_label'),
        'failedToggleStatus' => __('admin.js_failed_toggle_vendor_status'),
        'failedApproveVendor' => __('admin.js_failed_approve_vendor'),
        'vendorDeleted' => __('admin.js_vendor_deleted'),
        'failedDeleteVendor' => __('admin.js_failed_delete_vendor'),
    ]) !!};
    let currentPage = 1;
    let deleteVendorId = null;
    const deleteDialog = window.wireAccessibleDialog(document.getElementById('delete-modal'), closeDeleteModal, { labelledBy: 'delete-modal-title' });
    const initialParams = new URLSearchParams(window.location.search);
    let filters = {
        status: initialParams.get('status') || '',
        business_type: initialParams.get('business_type') || '',
        category_type: initialParams.get('category_type') || '',
        category_id: initialParams.get('category_id') || '',
        name: initialParams.get('name') || '',
        email: initialParams.get('email') || '',
    };

    document.getElementById('filter-status').value = filters.status;
    document.getElementById('filter-business-type').value = filters.business_type;
    document.getElementById('filter-category-type').value = filters.category_type;
    document.getElementById('filter-name').value = filters.name;
    document.getElementById('filter-email').value = filters.email;
    loadCategoryFilter();
    loadVendors();

    document.getElementById('prev-page').addEventListener('click', () => { currentPage--; loadVendors(); });
    document.getElementById('next-page').addEventListener('click', () => { currentPage++; loadVendors(); });
    document.getElementById('delete-cancel').addEventListener('click', closeDeleteModal);
    document.getElementById('delete-confirm').addEventListener('click', confirmDelete);
    document.getElementById('apply-filters').addEventListener('click', applyFilters);
    document.getElementById('reset-filters').addEventListener('click', resetFilters);
    ['filter-name', 'filter-email'].forEach(id => {
        document.getElementById(id).addEventListener('keydown', event => {
            if (event.key === 'Enter') applyFilters();
        });
    });

    async function loadVendors() {
        showLoading(true);
        try {
            const params = new URLSearchParams({ page: currentPage });
            Object.entries(filters).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });
            const response = await window.axios.get('/api/admin/vendors?' + params.toString());
            const { data, meta } = response.data;
            renderVendors(data);
            renderPagination(meta);
        } catch (error) {
            showAlert('vendors-alert', parseBackendError(error, i18n.failedLoadVendors));
        } finally {
            showLoading(false);
        }
    }

    function renderVendors(vendors) {
        const tbody = document.getElementById('vendors-tbody');
        const tableWrapper = document.getElementById('vendors-table-wrapper');
        const emptyState = document.getElementById('vendors-empty');

        if (!vendors || vendors.length === 0) {
            tableWrapper.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        tableWrapper.classList.remove('hidden');

        tbody.innerHTML = vendors.map(vendor => `
            <tr>
                <td class="font-medium text-gray-400">${vendor.id}</td>
                <td>
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand-600">
                            ${escapeHtml((vendor.store_name || '?').charAt(0).toUpperCase())}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">${escapeHtml(vendor.store_name)}</p>
                            <p class="truncate text-xs text-gray-500 md:hidden">${escapeHtml(vendor.user?.name || '—')}</p>
                        </div>
                    </div>
                </td>
                <td class="hidden md:table-cell">
                    <p class="text-sm text-gray-900">${escapeHtml(vendor.user?.name || '—')}</p>
                    <p class="text-xs text-gray-500">${escapeHtml(vendor.user?.phone_number || '')}</p>
                </td>
                <td class="hidden xl:table-cell">
                    <span class="badge ${vendor.registration_source === 'self' ? 'badge-warning' : 'badge-info'}">${vendor.registration_source === 'self' ? escapeHtml(i18n.selfRegistration) : escapeHtml(i18n.adminSource)}</span>
                </td>
                <td class="hidden xl:table-cell">
                    <span class="badge badge-brand">${escapeHtml(vendor.business_type_label || vendor.business_type || i18n.both)}</span>
                </td>
                <td class="hidden xl:table-cell">
                    ${renderCategoryBadges(vendor)}
                </td>
                <td class="hidden lg:table-cell">
                    <span class="font-mono text-xs text-gray-500">${escapeHtml(vendor.user?.national_id || '—')}</span>
                </td>
                <td>
                    ${renderStatus(vendor)}
                </td>
                <td class="text-end">
                    <div class="flex items-center justify-end gap-1.5">
                        ${vendor.status === 'pending' ? `
                            <button onclick="approveVendor(${vendor.id}, this)" class="btn-primary btn-xs" aria-label="${escapeHtml(i18n.approveVendor)}: ${escapeHtml(vendor.store_name)}">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                ${escapeHtml(i18n.approveVendor)}
                            </button>
                        ` : ''}
                        <div class="row-actions-menu">
                            <button type="button" class="row-actions-trigger" aria-label="${escapeHtml(i18n.viewProfile)}: ${escapeHtml(vendor.store_name)}">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                            </button>
                            <div class="row-actions-panel dropdown-panel">
                                <a href="/admin/vendors/${vendor.id}">${escapeHtml(i18n.viewProfile)}</a>
                                <a href="/admin/orders?vendor_id=${vendor.id}">${escapeHtml(i18n.viewOrderHistory)}</a>
                                <a href="/admin/vendors/${vendor.id}/commission">${escapeHtml(i18n.viewCommissionDashboard)}</a>
                                <a href="/admin/vendors/${vendor.id}/edit">${escapeHtml(i18n.edit)}</a>
                                <button type="button" class="is-danger" onclick="openDeleteModal(${vendor.id})">${escapeHtml(i18n.delete)}</button>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('');

        tbody.querySelectorAll('.row-actions-menu').forEach((menu) => {
            VetoraWorkspace.wireDropdown(menu.querySelector('.row-actions-trigger'), menu.querySelector('.row-actions-panel'));
        });
    }

    function renderPagination(meta) {
        document.getElementById('vendors-info').textContent = `${i18n.page} ${meta.current_page} ${i18n.of} ${meta.last_page} · ${meta.total} ${i18n.total}`;
        document.getElementById('prev-page').disabled = meta.current_page <= 1;
        document.getElementById('next-page').disabled = meta.current_page >= meta.last_page;
    }

    function renderStatus(vendor) {
        if (vendor.status === 'pending') {
            return `<span class="badge badge-warning"><span class="me-1 inline-block h-1.5 w-1.5 rounded-full bg-current"></span>${escapeHtml(i18n.pending)}</span>`;
        }

        const isActive = vendor.status === 'active' || vendor.is_active;

        return `
            <button type="button" onclick="toggleVendorStatus(${vendor.id})" class="badge cursor-pointer transition-opacity hover:opacity-80 ${isActive ? 'badge-success' : 'badge-danger'}" aria-label="${escapeHtml(i18n.clickToToggle)}: ${isActive ? escapeHtml(i18n.active) : escapeHtml(i18n.inactive)}">
                <span class="me-1 inline-block h-1.5 w-1.5 rounded-full bg-current"></span>
                ${isActive ? escapeHtml(i18n.active) : escapeHtml(i18n.inactive)}
            </button>
        `;
    }

    function applyFilters() {
        filters = {
            status: document.getElementById('filter-status').value,
            business_type: document.getElementById('filter-business-type').value,
            category_type: document.getElementById('filter-category-type').value,
            category_id: document.getElementById('filter-category').value,
            name: document.getElementById('filter-name').value.trim(),
            email: document.getElementById('filter-email').value.trim(),
        };
        currentPage = 1;
        updateFilterUrl();
        loadVendors();
    }

    function resetFilters() {
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-business-type').value = '';
        document.getElementById('filter-category-type').value = '';
        document.getElementById('filter-category').value = '';
        document.getElementById('filter-name').value = '';
        document.getElementById('filter-email').value = '';
        filters = { status: '', business_type: '', category_type: '', category_id: '', name: '', email: '' };
        currentPage = 1;
        updateFilterUrl();
        loadVendors();
    }

    async function loadCategoryFilter() {
        try {
            const response = await window.axios.get('/api/admin/categories');
            const categories = response.data.data || [];
            const select = document.getElementById('filter-category');
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                select.appendChild(option);
            });
            select.value = filters.category_id;
        } catch (error) {
            showAlert('vendors-alert', parseBackendError(error, i18n.failedLoadCategories));
        }
    }

    function updateFilterUrl() {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => {
            if (value) params.set(key, value);
        });
        const query = params.toString();
        window.history.replaceState({}, '', query ? `${window.location.pathname}?${query}` : window.location.pathname);
    }

    function renderCategoryBadges(vendor) {
        if (!vendor.categories || !vendor.categories.length) {
            return `<span class="text-xs text-gray-400">${escapeHtml(i18n.notAssigned)}</span>`;
        }

        return vendor.categories.map(category =>
            `<span class="badge badge-info">${escapeHtml(category.name)}</span>`
        ).join(' ');
    }

    // Toggle active status
    window.toggleVendorStatus = async function (id) {
        try {
            const response = await window.axios.patch('/api/admin/vendors/' + id + '/toggle-active');
            showAlert('vendors-success', response.data.message);
            loadVendors();
        } catch (error) {
            showAlert('vendors-alert', parseBackendError(error, i18n.failedToggleStatus));
        }
    };

    window.approveVendor = async function (id, button) {
        if (button) button.disabled = true;
        try {
            const response = await window.axios.patch('/api/admin/vendors/' + id + '/approve');
            showAlert('vendors-success', response.data.message);
            loadVendors();
        } catch (error) {
            showAlert('vendors-alert', parseBackendError(error, i18n.failedApproveVendor));
            if (button) button.disabled = false;
        }
    };

    window.openDeleteModal = function (id) {
        deleteVendorId = id;
        const modal = document.getElementById('delete-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        deleteDialog.open();
    };

    function closeDeleteModal() {
        deleteVendorId = null;
        const modal = document.getElementById('delete-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        deleteDialog.close();
    }

    async function confirmDelete() {
        if (!deleteVendorId) return;
        const confirmButton = document.getElementById('delete-confirm');
        confirmButton.disabled = true;
        try {
            await window.axios.delete('/api/admin/vendors/' + deleteVendorId);
            closeDeleteModal();
            showAlert('vendors-success', i18n.vendorDeleted);
            loadVendors();
        } catch (error) {
            closeDeleteModal();
            showAlert('vendors-alert', parseBackendError(error, i18n.failedDeleteVendor));
        } finally {
            confirmButton.disabled = false;
        }
    }

    function showLoading(show) { document.getElementById('vendors-loading').classList.toggle('hidden', !show); }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        document.getElementById(id + '-message').textContent = message;
        box.classList.remove('hidden');
        setTimeout(() => box.classList.add('hidden'), 4000);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function parseBackendError(error, fallback) {
        if (window.ApiErrors?.parse) {
            return window.ApiErrors.parse(error).generalMessage || fallback;
        }

        return error.response?.data?.message || fallback;
    }
});
</script>
@endpush
