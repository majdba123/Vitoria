@extends('layouts.admin')

@section('title', 'Subcategories - Vetora Admin')
@section('page-title', __('admin.subcategories_heading'))

@section('content')
<div class="content-stack">
    <div class="page-header mb-0">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.manage_subcategories_copy') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-csv-import
                id="subcategories"
                label="Subcategories"
                template-url="/api/admin/subcategories/import/template"
                import-url="/api/admin/subcategories/import"
            />
            <a href="{{ route('admin.subcategories.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">{{ __('admin.add_subcategory') }}</a>
        </div>
    </div>

    <x-alert type="error" id="subcategories-alert" />

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-4 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="subcategory-search" class="form-label">{{ __('admin.search_label') }}</label>
                <input id="subcategory-search" type="text" class="form-input" placeholder="{{ __('admin.search_by_arabic_english_name') }}">
            </div>
            <div>
                <label for="subcategory-type" class="form-label">{{ __('admin.category_type_label') }}</label>
                <select id="subcategory-type" class="form-select">
                    <option value="">{{ __('admin.all_types_plain') }}</option>
                    <option value="agriculture">{{ __('admin.type_agriculture') }}</option>
                    <option value="veterinary">{{ __('admin.type_veterinary') }}</option>
                </select>
            </div>
            <div>
                <label for="subcategory-category" class="form-label">{{ __('admin.parent_category_label') }}</label>
                <select id="subcategory-category" class="form-select">
                    <option value="">{{ __('admin.all_categories_plain') }}</option>
                </select>
            </div>
        </div>
    </div>

    <div id="subcategories-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700 dark:border-t-brand-400"></div>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.loading_subcategories') }}</p>
    </div>

    <div id="subcategories-empty" class="empty-state hidden">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('admin.no_subcategories_found') }}</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.try_another_filter') }}</p>
    </div>

    <div id="subcategories-table-wrap" class="admin-table-wrap table-responsive hidden">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('admin.category_name') }}</th>
                    <th scope="col">{{ __('admin.parent_category_label') }}</th>
                    <th scope="col">{{ __('admin.type_label') }}</th>
                    <th scope="col" class="text-end">{{ __('admin.products') }}</th>
                    <th scope="col" class="text-end">{{ __('admin.th_actions') }}</th>
                </tr>
            </thead>
            <tbody id="subcategories-table-body"></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const i18n = {!! json_encode([
        'allCategories' => __('admin.all_categories_plain'),
        'failedLoadCategories' => __('admin.js_failed_load_categories_plain'),
        'productsCountSuffix' => __('admin.products_count_suffix'),
        'parentCategory' => __('admin.parent_category_label'),
        'view' => __('admin.view'),
        'edit' => __('common.edit'),
        'delete' => __('common.delete'),
        'failedLoadSubcategories' => __('admin.js_failed_load_subcategories'),
        'confirmDeleteSubcategory' => __('admin.js_confirm_delete_subcategory'),
        'failedDeleteSubcategory' => __('admin.js_failed_delete_subcategory'),
    ]) !!};
    const tableWrap = document.getElementById('subcategories-table-wrap');
    const tbody = document.getElementById('subcategories-table-body');
    const loading = document.getElementById('subcategories-loading');
    const emptyState = document.getElementById('subcategories-empty');
    const alert = document.getElementById('subcategories-alert');
    const searchInput = document.getElementById('subcategory-search');
    const typeSelect = document.getElementById('subcategory-type');
    const categorySelect = document.getElementById('subcategory-category');

    await loadCategories();
    await loadSubcategories();

    let timeoutId = null;
    searchInput.addEventListener('input', function () {
        window.clearTimeout(timeoutId);
        timeoutId = window.setTimeout(loadSubcategories, 250);
    });
    typeSelect.addEventListener('change', loadSubcategories);
    categorySelect.addEventListener('change', loadSubcategories);

    async function loadCategories() {
        try {
            const response = await window.axios.get('/api/admin/categories?per_page=100');
            const categories = response.data.data || [];
            categorySelect.innerHTML = `<option value="">${escapeHtml(i18n.allCategories)}</option>` +
                categories.map((category) => `<option value="${category.id}">${escapeHtml(category.name)}</option>`).join('');
        } catch (error) {
            categorySelect.innerHTML = `<option value="">${escapeHtml(i18n.failedLoadCategories)}</option>`;
        }
    }

    async function loadSubcategories() {
        loading.classList.remove('hidden');
        tableWrap.classList.add('hidden');
        emptyState.classList.add('hidden');
        alert.classList.add('hidden');

        try {
            const response = await window.axios.get('/api/admin/subcategories', {
                params: {
                    per_page: 100,
                    search: searchInput.value.trim(),
                    type: typeSelect.value,
                    category_id: categorySelect.value,
                },
            });

            const subcategories = response.data.data || [];
            loading.classList.add('hidden');

            if (subcategories.length === 0) {
                emptyState.classList.remove('hidden');
                return;
            }

            tbody.innerHTML = subcategories.map((subcategory) => `
                <tr>
                    <td>
                        <a href="/admin/subcategories/${subcategory.id}" class="font-semibold text-gray-900 dark:text-white">${escapeHtml(subcategory.name_ar || subcategory.name_en || '-')}</a>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">${escapeHtml(subcategory.name_en || '')}</p>
                    </td>
                    <td class="text-gray-600 dark:text-gray-300">${escapeHtml(subcategory.category?.name || '-')}</td>
                    <td class="text-gray-600 dark:text-gray-300">${escapeHtml(subcategory.category?.type || '-')}</td>
                    <td class="text-end tabular-nums text-gray-600 dark:text-gray-300">${subcategory.products_count || 0}</td>
                    <td class="text-end">
                        <div class="inline-flex items-center gap-1.5">
                            <a href="/admin/subcategories/${subcategory.id}" class="btn-ghost btn-xs" aria-label="${escapeHtml(i18n.view)}">${escapeHtml(i18n.view)}</a>
                            <a href="/admin/subcategories/${subcategory.id}/edit" class="btn-secondary btn-xs" aria-label="${escapeHtml(i18n.edit)}">${escapeHtml(i18n.edit)}</a>
                            <button type="button" data-delete-id="${subcategory.id}" class="btn-danger btn-xs" aria-label="${escapeHtml(i18n.delete)}">${escapeHtml(i18n.delete)}</button>
                        </div>
                    </td>
                </tr>
            `).join('');

            tableWrap.classList.remove('hidden');
        } catch (error) {
            loading.classList.add('hidden');
            alert.classList.remove('hidden');
            document.getElementById('subcategories-alert-message').textContent = error.response?.data?.message || i18n.failedLoadSubcategories;
        }
    }

    window.addEventListener('csv-import:done', function (event) {
        if (event.detail && event.detail.id === 'subcategories') {
            loadSubcategories();
        }
    });

    tbody.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-delete-id]');
        if (!button) {
            return;
        }

        const subcategoryId = button.getAttribute('data-delete-id');
        if (!window.confirm(i18n.confirmDeleteSubcategory)) {
            return;
        }

        try {
            await window.axios.delete(`/api/admin/subcategories/${subcategoryId}`);
            await loadSubcategories();
        } catch (error) {
            alert.classList.remove('hidden');
            document.getElementById('subcategories-alert-message').textContent = error.response?.data?.message || i18n.failedDeleteSubcategory;
        }
    });

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }
});
</script>
@endpush
