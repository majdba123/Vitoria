@extends('layouts.admin')

@section('title', 'Categories - Vetora Admin')
@section('page-title', __('admin.categories'))

@section('content')
<div class="content-stack">
    <div class="page-header mb-0">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.manage_categories_copy') }}</p>
        <div class="flex flex-wrap items-center gap-2">
            <x-csv-import
                id="categories"
                label="Categories"
                template-url="/api/admin/categories/import/template"
                import-url="/api/admin/categories/import"
            />
            <a href="{{ route('admin.categories.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">{{ __('admin.add_category') }}</a>
        </div>
    </div>

    <x-alert type="error" id="categories-alert" />
    <x-alert type="success" id="categories-success" />

    <div id="categories-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700 dark:border-t-brand-400"></div>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.loading_categories') }}</p>
    </div>

    <div id="categories-table-wrap" class="admin-table-wrap table-responsive hidden">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('admin.category_name') }}</th>
                    <th scope="col">{{ __('admin.type_label') }}</th>
                    <th scope="col">{{ __('admin.category_commission') }}</th>
                    <th scope="col" class="text-end">{{ __('admin.products') }}</th>
                    <th scope="col" class="text-end">{{ __('admin.th_actions') }}</th>
                </tr>
            </thead>
            <tbody id="categories-table-body"></tbody>
        </table>
    </div>

    <div id="categories-empty" class="empty-state hidden">
        <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ __('admin.categories_empty_title') }}</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.categories_empty_hint') }}</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const i18n = {!! json_encode([
        'loadingCategories' => __('admin.loading_categories'),
        'commissionSuffix' => __('admin.commission_suffix'),
        'productsCountSuffix' => __('admin.products_count_suffix'),
        'viewDetails' => __('common.view_details'),
        'edit' => __('common.edit'),
        'failedLoadCategories' => __('admin.js_failed_load_categories_list'),
    ]) !!};

    async function loadCategories() {
        const loading = document.getElementById('categories-loading');
        const tableWrap = document.getElementById('categories-table-wrap');
        const tbody = document.getElementById('categories-table-body');
        const empty = document.getElementById('categories-empty');

        loading.classList.remove('hidden');
        loading.innerHTML = `<div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700 dark:border-t-brand-400"></div><p class="mt-3 text-sm text-gray-500 dark:text-gray-400">${escapeHtml(i18n.loadingCategories)}</p>`;
        tableWrap.classList.add('hidden');
        empty.classList.add('hidden');

        try {
            const response = await window.axios.get('/api/admin/categories?per_page=100');
            const categories = response.data.data || [];

            if (!categories.length) {
                loading.classList.add('hidden');
                empty.classList.remove('hidden');
                return;
            }

            tbody.innerHTML = categories.map(category => `
                <tr>
                    <td>
                        <a href="/admin/categories/${category.id}" class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden bg-gray-100 dark:bg-gray-800" style="border-radius: var(--radius-control)">
                                ${category.image_url ? `<img src="${category.image_url}" alt="" class="h-full w-full object-cover">` : `<i class="fa-solid fa-layer-group text-xs text-gray-400" aria-hidden="true"></i>`}
                            </span>
                            <span class="truncate font-semibold text-gray-900 dark:text-white">${escapeHtml(category.name)}</span>
                        </a>
                    </td>
                    <td class="text-gray-600 dark:text-gray-300">${escapeHtml(category.type || '-')}</td>
                    <td class="tabular-nums text-gray-600 dark:text-gray-300">${category.commission || 0}%</td>
                    <td class="text-end tabular-nums text-gray-600 dark:text-gray-300">${category.products_count || 0}</td>
                    <td class="text-end">
                        <div class="inline-flex items-center gap-1.5">
                            <a href="/admin/categories/${category.id}" class="btn-ghost btn-xs" aria-label="${escapeHtml(i18n.viewDetails)}: ${escapeHtml(category.name)}">${escapeHtml(i18n.viewDetails)}</a>
                            <a href="/admin/categories/${category.id}/edit" class="btn-secondary btn-xs" aria-label="${escapeHtml(i18n.edit)}: ${escapeHtml(category.name)}">${escapeHtml(i18n.edit)}</a>
                        </div>
                    </td>
                </tr>
            `).join('');
            loading.classList.add('hidden');
            tableWrap.classList.remove('hidden');
        } catch (error) {
            loading.innerHTML = `<p class="text-sm font-medium text-red-500 dark:text-red-400">${escapeHtml(i18n.failedLoadCategories)}</p>`;
        }
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    window.addEventListener('csv-import:done', function (event) {
        if (event.detail && event.detail.id === 'categories') {
            loadCategories();
        }
    });

    loadCategories();
});
</script>
@endpush
