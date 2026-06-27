@extends('layouts.admin')

@section('title', 'Category Details - Vetora Admin')
@section('page-title', 'Category Details')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.categories.index') }}" class="hover:text-gray-700">Categories</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Details</span>
    </nav>

    <div id="category-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading category...</p>
    </div>

    <div id="category-content" class="hidden space-y-5">
        <div class="card">
            <div class="card-body flex items-center justify-between gap-4 border-b border-gray-100">
                <div class="flex items-center gap-4">
                    <div id="category-image" class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl bg-gray-100"></div>
                    <div>
                        <h2 id="category-name" class="text-2xl font-bold text-gray-900"></h2>
                        <p id="category-meta" class="mt-1 text-sm text-gray-500"></p>
                    </div>
                </div>
                <a id="category-edit-link" href="#" class="btn-primary btn-sm">Edit Category</a>
            </div>
            <div class="card-body grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase text-gray-400">Type</p>
                    <p id="category-type" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase text-gray-400">Commission</p>
                    <p id="category-commission" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase text-gray-400">Products</p>
                    <p id="category-products-count" class="mt-1 text-sm font-semibold text-gray-900">0</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const categoryId = {{ $categoryId }};
    try {
        const response = await window.axios.get('/api/admin/categories/' + categoryId);
        const category = response.data.data;
        document.getElementById('category-name').textContent = category.name || 'Category';
        document.getElementById('category-meta').textContent = `Created ${new Date(category.created_at).toLocaleDateString()}`;
        document.getElementById('category-type').textContent = category.type || '-';
        document.getElementById('category-commission').textContent = `${category.commission || 0}%`;
        document.getElementById('category-products-count').textContent = category.products_count || 0;
        document.getElementById('category-edit-link').href = `/admin/categories/${category.id}/edit`;
        if (category.image_url) {
            document.getElementById('category-image').innerHTML = `<img src="${category.image_url}" alt="${category.name}" class="h-full w-full object-cover">`;
        }
        document.getElementById('category-loading').classList.add('hidden');
        document.getElementById('category-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('category-loading').innerHTML = '<p class="text-sm font-medium text-red-500">Failed to load category.</p>';
    }
});
</script>
@endpush
