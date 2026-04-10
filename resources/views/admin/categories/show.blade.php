@extends('layouts.admin')

@section('title', 'Category Details — SyriaZone Admin')
@section('page-title', 'Category Details')

@section('content')
<div class="space-y-4">
    {{-- Back Button --}}
    <div>
        <a href="{{ route('admin.categories.index') }}" class="btn-secondary btn-sm inline-flex items-center gap-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
            Back to Categories
        </a>
    </div>

    {{-- Loading --}}
    <div id="category-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading category details...</p>
    </div>

    {{-- Category Details --}}
    <div id="category-details" class="hidden space-y-4">
        {{-- Category Info Card --}}
        <div class="card">
            <div class="card-body">
                <div class="flex flex-col items-center text-center">
                    <div id="category-logo" class="mb-6">
                        <div class="flex h-32 w-32 items-center justify-center rounded-lg bg-gray-100">
                            <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="w-full">
                        <h2 id="category-name" class="text-2xl font-bold text-gray-900"></h2>
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <p class="text-xs font-medium text-gray-500">Commission</p>
                                <p id="category-commission" class="mt-1 text-lg font-bold text-emerald-600"></p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">Created At</p>
                                <p id="category-created" class="mt-1 text-sm font-semibold text-gray-900"></p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">Updated At</p>
                                <p id="category-updated" class="mt-1 text-sm font-semibold text-gray-900"></p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-center gap-2">
                            <a id="edit-link" href="#" class="btn-primary btn-sm">Edit Category</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Subcategories Section --}}
        <div class="card">
            <div class="card-body">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Subcategories</h3>
                        <p id="subcategories-count" class="mt-1 text-sm text-gray-500">0 subcategories</p>
                    </div>
                    <a href="{{ route('admin.subcategories.create') }}" class="btn-primary btn-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Subcategory
                    </a>
                </div>

                {{-- Subcategories Loading --}}
                <div id="subcategories-loading" class="py-8 text-center">
                    <div class="mx-auto h-6 w-6 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
                </div>

                {{-- Subcategories Empty --}}
                <div id="subcategories-empty" class="hidden py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="mt-3 text-sm font-semibold text-gray-900">No subcategories</h3>
                    <p class="mt-1 text-sm text-gray-500">This category doesn't have any subcategories yet.</p>
                </div>

                {{-- Subcategories Grid --}}
                <div id="subcategories-grid" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
            </div>
        </div>
    </div>
</div>

<x-alert type="error" id="category-alert" />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categoryId = {{ $categoryId }};
    const loading = document.getElementById('category-loading');
    const details = document.getElementById('category-details');
    const alert = document.getElementById('category-alert');

    loadCategory();

    async function loadCategory() {
        try {
            const res = await window.axios.get(`/api/admin/categories/${categoryId}`);
            const category = res.data.data;

            // Display category info
            document.getElementById('category-name').textContent = category.name;
            document.getElementById('category-commission').textContent = parseFloat(category.commission || 0).toFixed(2) + '%';
            document.getElementById('category-created').textContent = new Date(category.created_at).toLocaleString();
            document.getElementById('category-updated').textContent = new Date(category.updated_at).toLocaleString();
            document.getElementById('edit-link').href = `/admin/categories/${category.id}/edit`;

            // Display logo
            const logoContainer = document.getElementById('category-logo');
            if (category.logo) {
                logoContainer.innerHTML = `<img src="/storage/${category.logo}" alt="${esc(category.name)}" class="h-24 w-24 rounded-lg object-cover">`;
            }

            // Load subcategories
            loadSubcategories(category.subcategories || []);

            loading.classList.add('hidden');
            details.classList.remove('hidden');
        } catch (e) {
            console.error('Failed to load category:', e);
            alert.textContent = 'Failed to load category details.';
            alert.classList.remove('hidden');
            loading.classList.add('hidden');
        }
    }

    function loadSubcategories(subcategories) {
        const loading = document.getElementById('subcategories-loading');
        const empty = document.getElementById('subcategories-empty');
        const grid = document.getElementById('subcategories-grid');
        const count = document.getElementById('subcategories-count');

        count.textContent = `${subcategories.length} subcategor${subcategories.length !== 1 ? 'ies' : 'y'}`;

        if (subcategories.length === 0) {
            loading.classList.add('hidden');
            empty.classList.remove('hidden');
            grid.classList.add('hidden');
        } else {
            loading.classList.add('hidden');
            empty.classList.add('hidden');
            grid.classList.remove('hidden');
            renderSubcategories(subcategories);
        }
    }

    function renderSubcategories(subcategories) {
        const grid = document.getElementById('subcategories-grid');
        grid.innerHTML = subcategories.map(sub => `
            <div class="card">
                <div class="card-body">
                    <div class="flex items-start gap-4">
                        ${sub.image ? `
                            <img src="/storage/${sub.image}" alt="${esc(sub.name)}" class="h-16 w-16 rounded-lg object-cover flex-shrink-0">
                        ` : `
                            <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-gray-100 flex-shrink-0">
                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        `}
                        <div class="flex-1 min-w-0">
                            <h4 class="text-base font-semibold text-gray-900 truncate">${esc(sub.name)}</h4>
                            <p class="mt-1 text-xs text-gray-500">Created: ${new Date(sub.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4">
                        <a href="/admin/subcategories/${sub.id}" class="btn-primary btn-sm flex-1">View Details</a>
                        <a href="/admin/subcategories/${sub.id}/edit" class="btn-secondary btn-sm">Edit</a>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>
@endpush

