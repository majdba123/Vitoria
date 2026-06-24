@extends('layouts.admin')

@section('title', 'City Details - Vetora Admin')
@section('page-title', 'City Details')

@section('content')
<div class="mx-auto max-w-3xl">
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.cities.index') }}" class="hover:text-gray-700">Cities</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Details</span>
    </nav>

    <div id="show-loading" class="py-20 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading city details...</p>
    </div>

    <div id="show-content" class="hidden space-y-5">
        <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-navy-800 to-navy-900 shadow-xl">
            <div class="px-6 py-8 sm:px-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-white/10 text-2xl text-white ring-1 ring-white/20">
                            <i class="fa-solid fa-city"></i>
                        </span>
                        <div>
                            <h2 id="city-name" class="text-2xl font-bold text-white">-</h2>
                            <p class="mt-1 text-sm text-gray-300">City record used in admin and store vendor management.</p>
                        </div>
                    </div>
                    <a id="edit-link" href="#" class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-4 py-2 text-sm font-medium text-white backdrop-blur-sm transition-colors hover:bg-white/25">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        Edit
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">City Information</h3>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Name</p>
                        <p id="city-name-detail" class="mt-1 text-sm font-semibold text-gray-900">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Created At</p>
                        <p id="city-created-at" class="mt-1 text-sm text-gray-700">-</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Updated At</p>
                        <p id="city-updated-at" class="mt-1 text-sm text-gray-700">-</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">Usage</h3>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Assigned Vendors</p>
                        <p id="city-vendors-count" class="mt-1 text-2xl font-bold text-gray-900">0</p>
                    </div>
                    <div class="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                        Delete is allowed only when the vendor count is zero.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const cityId = {{ $cityId }};

    try {
        const response = await window.axios.get('/api/admin/cities/' + cityId);
        const city = response.data.data;

        document.getElementById('city-name').textContent = city.name || '-';
        document.getElementById('city-name-detail').textContent = city.name || '-';
        document.getElementById('city-created-at').textContent = city.created_at ? new Date(city.created_at).toLocaleString() : '-';
        document.getElementById('city-updated-at').textContent = city.updated_at ? new Date(city.updated_at).toLocaleString() : '-';
        document.getElementById('city-vendors-count').textContent = Number(city.vendors_count || 0);
        document.getElementById('edit-link').href = '/admin/cities/' + cityId + '/edit';

        document.getElementById('show-loading').classList.add('hidden');
        document.getElementById('show-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('show-loading').innerHTML = '<p class="text-sm text-red-600">Failed to load city details.</p>';
    }
});
</script>
@endpush
