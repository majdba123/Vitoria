@extends('layouts.admin')

@section('title', 'Subcategory Details - Vetora Admin')
@section('page-title', 'Subcategory Details')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.subcategories.index') }}" class="hover:text-gray-700">Subcategories</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Details</span>
    </nav>

    <div id="subcategory-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading subcategory...</p>
    </div>

    <div id="subcategory-content" class="hidden space-y-5">
        <div class="card">
            <div class="card-body flex items-start justify-between gap-4 border-b border-gray-100">
                <div>
                    <p id="subcategory-type" class="text-xs font-semibold uppercase tracking-wide text-brand-600">-</p>
                    <h2 id="subcategory-name-ar" class="mt-2 text-2xl font-bold text-gray-900">-</h2>
                    <p id="subcategory-name-en" class="mt-1 text-sm text-gray-500">-</p>
                </div>
                <a id="subcategory-edit-link" href="#" class="btn-primary btn-sm">Edit Subcategory</a>
            </div>
            <div class="card-body grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase text-gray-400">Parent Category</p>
                    <p id="subcategory-category" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase text-gray-400">Products</p>
                    <p id="subcategory-products" class="mt-1 text-sm font-semibold text-gray-900">0</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase text-gray-400">Created</p>
                    <p id="subcategory-created" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const subcategoryId = {{ $subcategoryId }};

    try {
        const response = await window.axios.get(`/api/admin/subcategories/${subcategoryId}`);
        const subcategory = response.data.data;
        document.getElementById('subcategory-type').textContent = subcategory.category?.type || '-';
        document.getElementById('subcategory-name-ar').textContent = subcategory.name_ar || '-';
        document.getElementById('subcategory-name-en').textContent = subcategory.name_en || '-';
        document.getElementById('subcategory-category').textContent = subcategory.category?.name || '-';
        document.getElementById('subcategory-products').textContent = subcategory.products_count || 0;
        document.getElementById('subcategory-created').textContent = subcategory.created_at ? new Date(subcategory.created_at).toLocaleDateString() : '-';
        document.getElementById('subcategory-edit-link').href = `/admin/subcategories/${subcategory.id}/edit`;
        document.getElementById('subcategory-loading').classList.add('hidden');
        document.getElementById('subcategory-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('subcategory-loading').innerHTML = '<p class="text-sm font-medium text-red-500">Failed to load subcategory.</p>';
    }
});
</script>
@endpush
