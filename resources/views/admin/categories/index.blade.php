@extends('layouts.admin')

@section('title', 'Categories â€” Vetora Admin')
@section('page-title', 'Categories')

@section('content')
<div class="content-stack">
    <div class="page-header mb-0">
        <p class="text-sm text-gray-500">Manage product categories.</p>
        <a href="{{ route('admin.categories.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">Add Category</a>
    </div>

    <x-alert type="error" id="categories-alert" />
    <x-alert type="success" id="categories-success" />

    <div id="categories-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading categories...</p>
    </div>

    <div id="categories-grid" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    try {
        const response = await window.axios.get('/api/admin/categories?per_page=100');
        const categories = response.data.data || [];
        const grid = document.getElementById('categories-grid');
        grid.innerHTML = categories.map(category => `
            <article class="card overflow-hidden">
                <div class="card-body">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-gray-100">
                            ${category.image_url ? `<img src="${category.image_url}" alt="${category.name}" class="h-full w-full object-cover">` : ''}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-base font-bold text-gray-900">${escapeHtml(category.name)}</h3>
                            <p class="mt-1 text-sm text-gray-500">${escapeHtml(category.type || 'â€”')} · ${category.commission || 0}% commission</p>
                            <p class="mt-1 text-sm text-gray-500">${category.products_count || 0} products</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="/admin/categories/${category.id}" class="btn-secondary btn-sm flex-1">View Details</a>
                        <a href="/admin/categories/${category.id}/edit" class="btn-primary btn-sm">Edit</a>
                    </div>
                </div>
            </article>
        `).join('');
        document.getElementById('categories-loading').classList.add('hidden');
        grid.classList.remove('hidden');
    } catch (error) {
        document.getElementById('categories-loading').innerHTML = '<p class="text-sm font-medium text-red-500">Failed to load categories.</p>';
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }
});
</script>
@endpush
