@extends('layouts.admin')

@section('title', __('admin.manage_syndicates_title'))
@section('page-title', __('admin.manage_syndicates_title'))

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('admin.syndicate_agents_heading') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.syndicate_agents_copy') }}</p>
        </div>
        <a href="{{ route('admin.syndicates.create') }}" class="btn-primary btn-sm">{{ __('admin.add_syndicate_agent') }}</a>
    </div>

    <div class="card card-body">
        <div class="grid gap-3 lg:grid-cols-4">
            <div>
                <label class="form-label">{{ __('admin.type_label') }}</label>
                <select id="filter-type" class="form-input">
                    <option value="">{{ __('admin.all_types_plain') }}</option>
                    <option value="agriculture">{{ __('admin.type_agriculture') }}</option>
                    <option value="veterinary">{{ __('admin.type_veterinary') }}</option>
                </select>
            </div>
            <div>
                <label class="form-label">{{ __('admin.status_label') }}</label>
                <select id="filter-status" class="form-input">
                    <option value="">{{ __('admin.all_statuses') }}</option>
                    <option value="active">{{ __('common.active') }}</option>
                    <option value="inactive">{{ __('common.inactive') }}</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="form-label">{{ __('admin.search') }}</label>
                <input id="filter-search" class="form-input" placeholder="{{ __('admin.search_by_arabic_english_name') }}">
            </div>
        </div>
        <div class="mt-4 flex flex-wrap justify-end gap-2">
            <button id="clear-filters" class="btn-secondary btn-sm">{{ __('admin.clear_filters') }}</button>
            <button id="apply-filters" class="btn-primary btn-sm">{{ __('admin.apply_filters') }}</button>
        </div>
    </div>

    <div id="syndicates-alert" class="hidden"></div>

    <div class="card overflow-hidden">
        <div class="admin-table-wrap table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('admin.name_label') }}</th>
                        <th scope="col">{{ __('admin.th_account') }}</th>
                        <th scope="col">{{ __('admin.type_label') }}</th>
                        <th scope="col">{{ __('admin.status_label') }}</th>
                        <th scope="col">{{ __('admin.th_data') }}</th>
                        <th scope="col">{{ __('admin.th_created_at') }}</th>
                        <th scope="col" class="text-end">{{ __('admin.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody id="syndicates-body">
                    <tr>
                        <td colspan="7" class="text-center text-gray-400">{{ __('admin.js_loading_syndicates') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="pagination" class="hidden items-center justify-between border-t border-gray-100 px-4 py-3 text-sm dark:border-gray-800">
            <p id="page-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div class="flex gap-2">
                <button id="prev-page" class="btn-secondary btn-xs">{{ __('nav.prev') }}</button>
                <button id="next-page" class="btn-secondary btn-xs">{{ __('nav.next') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const i18n = {!! json_encode([
        'loadingSyndicates' => __('admin.js_loading_syndicates'),
        'noPhone' => __('admin.no_phone'),
        'typeAgriculture' => __('admin.type_agriculture'),
        'typeVeterinary' => __('admin.type_veterinary'),
        'active' => __('common.active'),
        'inactive' => __('common.inactive'),
        'categoriesCountSuffix' => __('admin.categories_count_suffix'),
        'vendorsCountSuffix' => __('admin.vendors_count_suffix'),
        'productsCountSuffix' => __('admin.products_count_suffix_short'),
        'ordersCountSuffix' => __('admin.orders_count_suffix'),
        'view' => __('admin.view'),
        'edit' => __('common.edit'),
        'disable' => __('admin.disable'),
        'enable' => __('admin.enable'),
        'delete' => __('common.delete'),
        'page' => __('nav.page'),
        'of' => __('nav.of'),
        'failedLoadSyndicates' => __('admin.js_failed_load_syndicates_table'),
        'failedLoadData' => __('admin.js_failed_load_data'),
        'noSyndicatesMatchFilters' => __('admin.js_no_syndicates_match_filters'),
        'syndicateStatusUpdated' => __('admin.js_syndicate_status_updated'),
        'failedUpdateSyndicateStatus' => __('admin.js_failed_update_syndicate_status'),
        'confirmDeleteSyndicate' => __('admin.js_confirm_delete_syndicate'),
        'syndicateDeleted' => __('admin.js_syndicate_deleted'),
        'failedDeleteSyndicate' => __('admin.js_failed_delete_syndicate'),
        'dateLocale' => app()->getLocale() === 'ar' ? 'ar-SY' : 'en-US',
    ]) !!};
    const body = document.getElementById('syndicates-body');
    const alertBox = document.getElementById('syndicates-alert');
    const pagination = document.getElementById('pagination');
    let currentPage = 1;
    let lastPage = 1;
    let searchTimer = null;

    const esc = (v) => { const d = document.createElement('div'); d.textContent = v == null ? '' : String(v); return d.innerHTML; };
    const fmtDate = (v) => v ? new Date(v).toLocaleDateString(i18n.dateLocale, { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
    const typeText = (type) => type === 'agriculture' ? i18n.typeAgriculture : (type === 'veterinary' ? i18n.typeVeterinary : type);
    const statusBadge = (row) => `<span class="badge ${row.is_active ? 'badge-success' : 'badge-danger'}">${row.is_active ? esc(i18n.active) : esc(i18n.inactive)}</span>`;

    document.getElementById('apply-filters').addEventListener('click', () => { currentPage = 1; load(); });
    document.getElementById('clear-filters').addEventListener('click', () => {
        document.getElementById('filter-type').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-search').value = '';
        currentPage = 1;
        load();
    });
    document.getElementById('filter-search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 1; load(); }, 350);
    });
    document.getElementById('prev-page').addEventListener('click', () => { if (currentPage > 1) { currentPage--; load(); } });
    document.getElementById('next-page').addEventListener('click', () => { if (currentPage < lastPage) { currentPage++; load(); } });

    load();

    async function load() {
        hideAlert();
        body.innerHTML = `<tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-400">${esc(i18n.loadingSyndicates)}</td></tr>`;
        const params = new URLSearchParams({ page: String(currentPage), per_page: '15' });
        ['type', 'status', 'search'].forEach(key => {
            const value = document.getElementById('filter-' + key).value;
            if (value) params.append(key, value);
        });

        try {
            const res = await window.axios.get('/api/admin/syndicates?' + params.toString(), { silent: true });
            const rows = res.data.data || [];
            const meta = res.data.meta || {};
            currentPage = meta.current_page || 1;
            lastPage = meta.last_page || 1;
            renderRows(rows);
            renderPagination(meta);
        } catch (error) {
            showAlert(window.showApiError ? window.showApiError(error).generalMessage : i18n.failedLoadSyndicates, 'error');
            body.innerHTML = `<tr><td colspan="7" class="px-4 py-12 text-center text-sm text-red-500">${esc(i18n.failedLoadData)}</td></tr>`;
        }
    }

    function renderRows(rows) {
        if (!rows.length) {
            body.innerHTML = `<tr><td colspan="7" class="text-center text-gray-400">${esc(i18n.noSyndicatesMatchFilters)}</td></tr>`;
            return;
        }

        body.innerHTML = rows.map(row => `
            <tr>
                <td>
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden text-sm font-bold text-brand-700 dark:text-brand-300" style="border-radius: var(--radius-control); background: var(--color-brand-soft)">
                            ${row.logo_url ? `<img src="${esc(row.logo_url)}" class="h-full w-full object-cover" alt="">` : esc((row.name || '?').charAt(0))}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-900 dark:text-white">${esc(row.name)}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">${esc(row.phone || i18n.noPhone)}</p>
                        </div>
                    </div>
                </td>
                <td class="text-gray-700 dark:text-gray-200">
                    <p class="font-semibold">${esc(row.email || row.user?.email || '—')}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">#${esc(row.user_id)}</p>
                </td>
                <td><span class="badge badge-brand">${esc(typeText(row.type))}</span></td>
                <td>${statusBadge(row)}</td>
                <td>
                    <div class="grid min-w-48 grid-cols-2 gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <span>${Number(row.categories_count || 0)} ${esc(i18n.categoriesCountSuffix)}</span>
                        <span>${Number(row.vendors_count || 0)} ${esc(i18n.vendorsCountSuffix)}</span>
                        <span>${Number(row.products_count || 0)} ${esc(i18n.productsCountSuffix)}</span>
                        <span>${Number(row.orders_count || 0)} ${esc(i18n.ordersCountSuffix)}</span>
                    </div>
                </td>
                <td class="tabular-nums text-gray-500 dark:text-gray-400">${fmtDate(row.created_at)}</td>
                <td class="text-end">
                    <div class="row-actions-menu">
                        <button type="button" class="row-actions-trigger" aria-label="${esc(i18n.view)} ${esc(row.name)}">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                        </button>
                        <div class="row-actions-panel dropdown-panel">
                            <a href="/admin/syndicates/${row.id}">${esc(i18n.view)}</a>
                            <a href="/admin/syndicates/${row.id}/edit">${esc(i18n.edit)}</a>
                            <button type="button" onclick="toggleSyndicate(${row.id})">${row.is_active ? esc(i18n.disable) : esc(i18n.enable)}</button>
                            <button type="button" class="is-danger" onclick="deleteSyndicate(${row.id})">${esc(i18n.delete)}</button>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('');

        body.querySelectorAll('.row-actions-menu').forEach((menu) => {
            VetoraWorkspace.wireDropdown(menu.querySelector('.row-actions-trigger'), menu.querySelector('.row-actions-panel'));
        });
    }

    function renderPagination(meta) {
        if ((meta.last_page || 1) <= 1) {
            pagination.classList.add('hidden');
            pagination.classList.remove('flex');
            return;
        }

        pagination.classList.remove('hidden');
        pagination.classList.add('flex');
        document.getElementById('page-info').textContent = `${i18n.page} ${meta.current_page} ${i18n.of} ${meta.last_page} (${meta.total})`;
        document.getElementById('prev-page').disabled = meta.current_page <= 1;
        document.getElementById('next-page').disabled = meta.current_page >= meta.last_page;
    }

    function showAlert(message, type = 'success') {
        alertBox.textContent = message;
        alertBox.className = type === 'success' ? 'alert-shell alert-success' : 'alert-shell alert-error';
        alertBox.classList.remove('hidden');
    }

    function hideAlert() {
        alertBox.classList.add('hidden');
    }

    window.toggleSyndicate = async function (id) {
        try {
            await window.axios.patch('/api/admin/syndicates/' + id + '/toggle-active', {}, { silent: true });
            showAlert(i18n.syndicateStatusUpdated);
            load();
        } catch (error) {
            showAlert(window.showApiError ? window.showApiError(error).generalMessage : i18n.failedUpdateSyndicateStatus, 'error');
        }
    };

    window.deleteSyndicate = async function (id) {
        if (!confirm(i18n.confirmDeleteSyndicate)) return;
        try {
            await window.axios.delete('/api/admin/syndicates/' + id, { silent: true });
            showAlert(i18n.syndicateDeleted);
            load();
        } catch (error) {
            showAlert(window.showApiError ? window.showApiError(error).generalMessage : i18n.failedDeleteSyndicate, 'error');
        }
    };
});
</script>
@endpush
