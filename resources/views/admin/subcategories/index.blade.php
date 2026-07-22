@extends('layouts.admin')

@section('title', 'Subcategories - Vetora Admin')
@section('page-title', 'Subcategories')

@section('content')
<div class="content-stack">
    <div class="page-header mb-0">
        <div>
            <p class="text-sm text-gray-500">Manage subcategories for each category.</p>
        </div>
        <a href="{{ route('admin.subcategories.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">Add Subcategory</a>
    </div>

    <x-alert type="error" id="subcategories-alert" />

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-4 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="subcategory-search" class="form-label">Search</label>
                <input id="subcategory-search" type="text" class="form-input" placeholder="Search by Arabic or English name">
            </div>
            <div>
                <label for="subcategory-type" class="form-label">Category Type</label>
                <select id="subcategory-type" class="form-select">
                    <option value="">All types</option>
                    <option value="agriculture">Agriculture</option>
                    <option value="veterinary">Veterinary</option>
                </select>
            </div>
            <div>
                <label for="subcategory-category" class="form-label">Parent Category</label>
                <select id="subcategory-category" class="form-select">
                    <option value="">All categories</option>
                </select>
            </div>
        </div>
    </div>

    <div id="subcategories-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading subcategories...</p>
    </div>

    <div id="subcategories-empty" class="hidden rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
        <h3 class="text-base font-semibold text-gray-900">No subcategories found</h3>
        <p class="mt-2 text-sm text-gray-500">Try another filter or create a new subcategory.</p>
    </div>

    <div id="subcategories-grid" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const grid = document.getElementById('subcategories-grid');
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
            categorySelect.innerHTML = '<option value="">All categories</option>' +
                categories.map((category) => `<option value="${category.id}">${escapeHtml(category.name)}</option>`).join('');
        } catch (error) {
            categorySelect.innerHTML = '<option value="">Failed to load categories</option>';
        }
    }

    async function loadSubcategories() {
        loading.classList.remove('hidden');
        grid.classList.add('hidden');
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

            grid.innerHTML = subcategories.map((subcategory) => `
                <article class="card overflow-hidden">
                    <div class="card-body">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">${escapeHtml(subcategory.category?.type || '-')}</p>
                                <h3 class="mt-2 truncate text-lg font-bold text-gray-900">${escapeHtml(subcategory.name_ar || '-')}</h3>
                                <p class="mt-1 truncate text-sm text-gray-500">${escapeHtml(subcategory.name_en || '-')}</p>
                            </div>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">${subcategory.products_count || 0} products</span>
                        </div>
                        <div class="mt-4 rounded-2xl bg-gray-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Parent Category</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">${escapeHtml(subcategory.category?.name || '-')}</p>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <a href="/admin/subcategories/${subcategory.id}" class="btn-secondary btn-sm flex-1">View</a>
                            <a href="/admin/subcategories/${subcategory.id}/edit" class="btn-primary btn-sm">Edit</a>
                            <button type="button" data-delete-id="${subcategory.id}" class="btn-danger btn-sm">Delete</button>
                        </div>
                    </div>
                </article>
            `).join('');

            grid.classList.remove('hidden');
        } catch (error) {
            loading.classList.add('hidden');
            alert.classList.remove('hidden');
            document.getElementById('subcategories-alert-message').textContent = error.response?.data?.message || 'Failed to load subcategories.';
        }
    }

    grid.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-delete-id]');
        if (!button) {
            return;
        }

        const subcategoryId = button.getAttribute('data-delete-id');
        if (!window.confirm('Delete this subcategory?')) {
            return;
        }

        try {
            await window.axios.delete(`/api/admin/subcategories/${subcategoryId}`);
            await loadSubcategories();
        } catch (error) {
            alert.classList.remove('hidden');
            document.getElementById('subcategories-alert-message').textContent = error.response?.data?.message || 'Failed to delete subcategory.';
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
