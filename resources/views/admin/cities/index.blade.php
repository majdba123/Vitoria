@extends('layouts.admin')

@section('title', 'Cities - Vetora Admin')
@section('page-title', 'Cities')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">Manage cities used across vendors and store records.</p>
        </div>
        <a href="{{ route('admin.cities.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add City
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex flex-col gap-2 lg:flex-row">
                <input type="text" id="search-input" placeholder="Search cities by name..." class="form-input min-w-0 flex-1">
                <button id="search-btn" class="btn-primary btn-sm w-full lg:w-auto">Search</button>
                <button id="clear-search" class="btn-secondary btn-sm hidden w-full lg:w-auto">Clear</button>
            </div>
        </div>
    </div>

    <x-alert type="error" id="cities-alert" />
    <x-alert type="success" id="cities-success" />

    <div id="cities-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading cities...</p>
    </div>

    <div id="cities-empty" class="hidden">
        <div class="card py-16 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21h7.5m-7.5 0V5.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125V21m-7.5 0H5.625A1.125 1.125 0 014.5 19.875V10.5a1.125 1.125 0 011.125-1.125H8.25m7.5 11.625h2.625A1.125 1.125 0 0019.5 19.875V8.25a1.125 1.125 0 00-1.125-1.125H15.75"/></svg>
            <h3 class="mt-3 text-sm font-semibold text-gray-900">No cities found</h3>
            <p class="mt-1 text-sm text-gray-500">Create the first city for vendor assignment.</p>
        </div>
    </div>

    <div id="cities-grid" class="hidden grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>
</div>

<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl">
        <h3 class="text-base font-semibold text-gray-900">Delete City</h3>
        <p class="mt-1 text-sm text-gray-500">This will permanently remove the city if no vendors are assigned to it.</p>
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
    const loading = document.getElementById('cities-loading');
    const empty = document.getElementById('cities-empty');
    const grid = document.getElementById('cities-grid');
    const searchInput = document.getElementById('search-input');
    const searchBtn = document.getElementById('search-btn');
    const clearSearch = document.getElementById('clear-search');
    const deleteModal = document.getElementById('delete-modal');
    const deleteCancel = document.getElementById('delete-cancel');
    const deleteConfirm = document.getElementById('delete-confirm');
    let cityToDelete = null;
    let searchTerm = '';

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
            grid.classList.add('hidden');

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

            grid.classList.remove('hidden');
            grid.innerHTML = cities.map(function (city) {
                return `
                    <div class="card">
                        <div class="card-body">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">${esc(city.name)}</h3>
                                    <p class="mt-1 text-sm text-gray-500">${Number(city.vendors_count || 0)} vendors assigned</p>
                                </div>
                                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                                    <i class="fa-solid fa-city"></i>
                                </span>
                            </div>
                            <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4">
                                <a href="/admin/cities/${city.id}" class="btn-primary btn-sm flex-1">View Details</a>
                                <a href="/admin/cities/${city.id}/edit" class="btn-secondary btn-sm">Edit</a>
                                <button type="button" onclick="confirmDeleteCity(${city.id})" class="btn-danger btn-sm">Delete</button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } catch (error) {
            loading.classList.add('hidden');
            showAlert('cities-alert', error.response?.data?.message || 'Failed to load cities.');
        }
    }

    window.confirmDeleteCity = function (cityId) {
        cityToDelete = cityId;
        deleteModal.classList.remove('hidden');
    };

    deleteCancel.addEventListener('click', function () {
        cityToDelete = null;
        deleteModal.classList.add('hidden');
    });

    deleteConfirm.addEventListener('click', async function () {
        if (!cityToDelete) {
            return;
        }

        try {
            await window.axios.delete('/api/admin/cities/' + cityToDelete);
            showAlert('cities-success', 'City deleted successfully.');
            deleteModal.classList.add('hidden');
            cityToDelete = null;
            loadCities();
        } catch (error) {
            showAlert('cities-alert', error.response?.data?.message || 'Failed to delete city.');
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

    loadCities();
});
</script>
@endpush
