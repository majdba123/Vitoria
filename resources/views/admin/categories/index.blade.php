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

    <div id="categories-grid" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>
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
        const grid = document.getElementById('categories-grid');

        loading.classList.remove('hidden');
        loading.innerHTML = `<div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700 dark:border-t-brand-400"></div><p class="mt-3 text-sm text-gray-500 dark:text-gray-400">${escapeHtml(i18n.loadingCategories)}</p>`;
        grid.classList.add('hidden');

        try {
            const response = await window.axios.get('/api/admin/categories?per_page=100');
            const categories = response.data.data || [];
            grid.innerHTML = categories.map(category => `
                <article class="card overflow-hidden">
                    <div class="card-body">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-gray-100 dark:bg-gray-800">
                                ${category.image_url ? `<img src="${category.image_url}" alt="${escapeHtml(category.name)}" class="h-full w-full object-cover">` : ''}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-base font-bold text-gray-900 dark:text-white">${escapeHtml(category.name)}</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${escapeHtml(category.type || '-')} · ${category.commission || 0}% ${escapeHtml(i18n.commissionSuffix)}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${category.products_count || 0} ${escapeHtml(i18n.productsCountSuffix)}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <a href="/admin/categories/${category.id}" class="btn-secondary btn-sm flex-1">${escapeHtml(i18n.viewDetails)}</a>
                            <a href="/admin/categories/${category.id}/edit" class="btn-primary btn-sm">${escapeHtml(i18n.edit)}</a>
                        </div>
                    </div>
                </article>
            `).join('');
            loading.classList.add('hidden');
            grid.classList.remove('hidden');
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
