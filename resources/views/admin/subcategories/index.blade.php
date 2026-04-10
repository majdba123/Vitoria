@extends('layouts.admin')

@section('title', 'Subcategories — SyriaZone Admin')
@section('page-title', 'Subcategories')

@section('content')
<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">Manage product subcategories within categories.</p>
        </div>
        <a href="{{ route('admin.subcategories.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Subcategory
        </a>
    </div>

    {{-- Search --}}
    <div class="card">
        <div class="card-body">
            <div class="flex gap-2">
                <input type="text" id="search-input" placeholder="Search subcategories by name..." class="form-input flex-1">
                <button id="search-btn" class="btn-primary btn-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    Search
                </button>
                <button id="clear-search" class="btn-secondary btn-sm hidden">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    <x-alert type="error" id="subcategories-alert" />
    <x-alert type="success" id="subcategories-success" />

    {{-- Loading --}}
    <div id="subcategories-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading subcategories...</p>
    </div>

    {{-- Empty State --}}
    <div id="subcategories-empty" class="hidden">
        <div class="card py-16 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h69.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
            <h3 class="mt-3 text-sm font-semibold text-gray-900">No subcategories yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new subcategory.</p>
            <div class="mt-5">
                <a href="{{ route('admin.subcategories.create') }}" class="btn-primary btn-sm">Add Subcategory</a>
            </div>
        </div>
    </div>

    {{-- Subcategories Grid --}}
    <div id="subcategories-grid" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
</div>

{{-- Delete Modal --}}
<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900">Delete Subcategory</h3>
                <p class="mt-0.5 text-sm text-gray-500">This will permanently delete the subcategory.</p>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button id="delete-cancel" class="btn-secondary btn-sm">Cancel</button>
            <button id="delete-confirm" class="btn-danger btn-sm">Delete</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const loading = document.getElementById('subcategories-loading');
    const empty = document.getElementById('subcategories-empty');
    const grid = document.getElementById('subcategories-grid');
    const deleteModal = document.getElementById('delete-modal');
    const deleteCancel = document.getElementById('delete-cancel');
    const deleteConfirm = document.getElementById('delete-confirm');
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const clearSearch = document.getElementById('clear-search');
    let subcategoryToDelete = null;
    let searchTerm = '';

    searchBtn.addEventListener('click', () => {
        searchTerm = searchInput.value.trim();
        loadSubcategories();
    });

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchTerm = searchInput.value.trim();
            loadSubcategories();
        }
    });

    clearSearch.addEventListener('click', () => {
        searchInput.value = '';
        searchTerm = '';
        clearSearch.classList.add('hidden');
        loadSubcategories();
    });

    loadSubcategories();

    async function loadSubcategories() {
        try {
            loading.classList.remove('hidden');
            const params = new URLSearchParams();
            if (searchTerm) {
                params.append('search', searchTerm);
                clearSearch.classList.remove('hidden');
            } else {
                clearSearch.classList.add('hidden');
            }

            const url = '/api/admin/subcategories' + (params.toString() ? '?' + params.toString() : '');
            const res = await window.axios.get(url);
            const subcategories = res.data.data || [];

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
        } catch (e) {
            console.error('Failed to load subcategories:', e);
            showAlert('subcategories-alert', 'Failed to load subcategories.');
            loading.classList.add('hidden');
        }
    }

    function renderSubcategories(subcategories) {
        grid.innerHTML = subcategories.map(sub => `
            <div class="card">
                <div class="card-body">
                    <div class="flex items-start gap-4">
                        ${sub.image ? `
                            <img src="/storage/${sub.image}" alt="${esc(sub.name)}" class="h-16 w-16 rounded-lg object-cover">
                        ` : `
                            <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-gray-100">
                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        `}
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900">${esc(sub.name)}</h3>
                            <p class="mt-1 text-sm text-gray-500">${sub.category?.name || 'No category'}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4">
                        <a href="/admin/subcategories/${sub.id}" class="btn-primary btn-sm flex-1">View Details</a>
                        <a href="/admin/subcategories/${sub.id}/edit" class="btn-secondary btn-sm">Edit</a>
                        <button onclick="confirmDelete(${sub.id})" class="btn-danger btn-sm">Delete</button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    window.confirmDelete = function(id) {
        subcategoryToDelete = id;
        deleteModal.classList.remove('hidden');
    };

    deleteCancel.addEventListener('click', () => {
        deleteModal.classList.add('hidden');
        subcategoryToDelete = null;
    });

    deleteConfirm.addEventListener('click', async () => {
        if (!subcategoryToDelete) return;
        try {
            await window.axios.delete(`/api/admin/subcategories/${subcategoryToDelete}`);
            showAlert('subcategories-success', 'Subcategory deleted successfully.');
            deleteModal.classList.add('hidden');
            subcategoryToDelete = null;
            loadSubcategories();
        } catch (e) {
            showAlert('subcategories-alert', e.response?.data?.message || 'Failed to delete subcategory.');
        }
    });

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showAlert(id, message) {
        const alert = document.getElementById(id);
        if (alert) {
            alert.textContent = message;
            alert.classList.remove('hidden');
            setTimeout(() => alert.classList.add('hidden'), 5000);
        }
    }
});
</script>
@endpush

