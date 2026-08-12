@extends('layouts.admin')

@section('title', 'Cities - Vetora Admin')
@section('page-title', __('admin.cities'))

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.manage_cities_copy') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-csv-import
                id="cities"
                label="Cities"
                template-url="/api/admin/cities/import/template"
                import-url="/api/admin/cities/import"
            />
            <a href="{{ route('admin.cities.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.add_city') }}
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex flex-col gap-2 lg:flex-row">
                <input type="text" id="search-input" placeholder="{{ __('admin.search_cities_placeholder') }}" class="form-input min-w-0 flex-1">
                <button id="search-btn" class="btn-primary btn-sm w-full lg:w-auto">{{ __('admin.search') }}</button>
                <button id="clear-search" class="btn-secondary btn-sm hidden w-full lg:w-auto">{{ __('admin.clear') }}</button>
            </div>
        </div>
    </div>

    <x-alert type="error" id="cities-alert" />
    <x-alert type="success" id="cities-success" />

    <div id="cities-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700 dark:border-t-brand-400"></div>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.loading_cities') }}</p>
    </div>

    <div id="cities-empty" class="empty-state hidden">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('admin.no_cities_found') }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.create_first_city') }}</p>
    </div>

    <div id="cities-table-wrap" class="admin-table-wrap table-responsive hidden">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('admin.cities') }}</th>
                    <th scope="col" class="text-end">{{ __('admin.vendors') }}</th>
                    <th scope="col" class="text-end">{{ __('admin.th_actions') }}</th>
                </tr>
            </thead>
            <tbody id="cities-table-body"></tbody>
        </table>
    </div>
</div>

<div id="delete-modal" class="mobile-dialog">
    <div class="mobile-dialog-card">
        <h3 id="delete-modal-title" class="text-base font-semibold text-gray-900 dark:text-white">{{ __('admin.delete_city_title') }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.delete_city_warning') }}</p>
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
        'vendorsAssignedSuffix' => __('admin.vendors_assigned_suffix'),
        'viewDetails' => __('common.view_details'),
        'edit' => __('common.edit'),
        'delete' => __('common.delete'),
        'failedLoadCities' => __('admin.js_failed_load_cities'),
        'cityDeleted' => __('admin.js_city_deleted'),
        'failedDeleteCity' => __('admin.js_failed_delete_city'),
    ]) !!};
    const loading = document.getElementById('cities-loading');
    const empty = document.getElementById('cities-empty');
    const tableWrap = document.getElementById('cities-table-wrap');
    const tbody = document.getElementById('cities-table-body');
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const clearSearch = document.getElementById('clear-search');
    const deleteModal = document.getElementById('delete-modal');
    const deleteCancel = document.getElementById('delete-cancel');
    const deleteConfirm = document.getElementById('delete-confirm');
    let cityToDelete = null;
    let searchTerm = '';
    const deleteDialog = window.wireAccessibleDialog(deleteModal, closeDeleteModal, { labelledBy: 'delete-modal-title' });

    searchBtn.addEventListener('click', function () {
        searchTerm = searchInput.value.trim();
        loadCities();
    });

    searchInput.addEventListener('keypress', function (event) {
        if (event.key === 'Enter') {
            searchTerm = searchInput.value.trim();
            loadCities();
        }
    });

    clearSearch.addEventListener('click', function () {
        searchInput.value = '';
        searchTerm = '';
        clearSearch.classList.add('hidden');
        loadCities();
    });

    async function loadCities() {
        try {
            loading.classList.remove('hidden');
            empty.classList.add('hidden');
            tableWrap.classList.add('hidden');

            const params = new URLSearchParams({ per_page: '100' });
            if (searchTerm) {
                params.append('search', searchTerm);
                clearSearch.classList.remove('hidden');
            } else {
                clearSearch.classList.add('hidden');
            }

            const response = await window.axios.get('/api/admin/cities?' + params.toString());
            const cities = response.data.data || [];

            loading.classList.add('hidden');

            if (cities.length === 0) {
                empty.classList.remove('hidden');
                return;
            }

            tableWrap.classList.remove('hidden');
            tbody.innerHTML = cities.map(function (city) {
                return `
                    <tr>
                        <td class="flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center text-brand-600 dark:text-brand-400" style="border-radius: var(--radius-control); background: var(--color-brand-soft)"><i class="fa-solid fa-city text-xs" aria-hidden="true"></i></span>
                            <a href="/admin/cities/${city.id}" class="font-semibold text-gray-900 dark:text-white">${esc(city.name)}</a>
                        </td>
                        <td class="text-end tabular-nums text-gray-600 dark:text-gray-300">${Number(city.vendors_count || 0)}</td>
                        <td class="text-end">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="/admin/cities/${city.id}" class="btn-ghost btn-xs" aria-label="${esc(i18n.viewDetails)}">${esc(i18n.viewDetails)}</a>
                                <a href="/admin/cities/${city.id}/edit" class="btn-secondary btn-xs" aria-label="${esc(i18n.edit)}">${esc(i18n.edit)}</a>
                                <button type="button" onclick="confirmDeleteCity(${city.id})" class="btn-danger btn-xs" aria-label="${esc(i18n.delete)}: ${esc(city.name)}">${esc(i18n.delete)}</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        } catch (error) {
            loading.classList.add('hidden');
            showAlert('cities-alert', error.response?.data?.message || i18n.failedLoadCities);
        }
    }

    window.confirmDeleteCity = function (cityId) {
        cityToDelete = cityId;
        deleteModal.classList.remove('hidden');
        deleteModal.classList.add('flex');
        deleteDialog.open();
    };

    function closeDeleteModal() {
        cityToDelete = null;
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
        deleteDialog.close();
    }

    deleteCancel.addEventListener('click', closeDeleteModal);

    deleteConfirm.addEventListener('click', async function () {
        if (!cityToDelete) {
            return;
        }

        deleteConfirm.disabled = true;

        try {
            await window.axios.delete('/api/admin/cities/' + cityToDelete);
            showAlert('cities-success', i18n.cityDeleted);
            closeDeleteModal();
            loadCities();
        } catch (error) {
            showAlert('cities-alert', error.response?.data?.message || i18n.failedDeleteCity);
        } finally {
            deleteConfirm.disabled = false;
        }
    });

    function showAlert(id, message) {
        const alert = document.getElementById(id);
        const messageElement = document.getElementById(id + '-message');

        if (!alert || !messageElement) {
            return;
        }

        messageElement.textContent = message;
        alert.classList.remove('hidden');
        setTimeout(function () {
            alert.classList.add('hidden');
        }, 5000);
    }

    function esc(value) {
        const element = document.createElement('div');
        element.textContent = value || '';
        return element.innerHTML;
    }

    window.addEventListener('csv-import:done', function (event) {
        if (event.detail && event.detail.id === 'cities') {
            loadCities();
        }
    });

    loadCities();
});
</script>
@endpush
