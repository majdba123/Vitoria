@extends('layouts.admin')

@section('title', 'Edit City - Vetora Admin')
@section('page-title', 'Edit City')

@section('content')
<div class="mx-auto max-w-2xl">
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.cities.index') }}" class="hover:text-gray-700">Cities</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Edit</span>
    </nav>

    <div id="edit-loading" class="py-20 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading city details...</p>
    </div>

    <div id="edit-card" class="hidden">
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Edit City</h2>
                <p class="mt-0.5 text-sm text-gray-500">Update the city name used in admin and store records.</p>
            </div>
            <div class="card-body">
                <x-alert type="error" id="edit-alert" />
                <x-alert type="success" id="edit-success" />

                <form id="edit-city-form" class="space-y-5" novalidate>
                    <x-form.input name="name" label="City Name" placeholder="Damascus" :required="true" />

                    <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        Vendors assigned: <span id="vendors-count" class="font-semibold text-gray-900">0</span>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('admin.cities.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" id="edit-btn" class="btn-primary">
                            <span id="edit-btn-text">Save Changes</span>
                            <svg id="edit-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cityId = {{ $cityId }};
    const form = document.getElementById('edit-city-form');

    loadCity();

    async function loadCity() {
        try {
            const response = await window.axios.get('/api/admin/cities/' + cityId);
            const city = response.data.data;

            form.name.value = city.name || '';
            document.getElementById('vendors-count').textContent = Number(city.vendors_count || 0);

            document.getElementById('edit-loading').classList.add('hidden');
            document.getElementById('edit-card').classList.remove('hidden');
        } catch (error) {
            document.getElementById('edit-loading').innerHTML = '<p class="text-sm text-red-600">Failed to load city details.</p>';
        }
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearErrors();
        toggleLoading(true);

        try {
            const response = await window.axios.put('/api/admin/cities/' + cityId, {
                name: form.name.value.trim(),
            });

            document.getElementById('vendors-count').textContent = Number(response.data.data.vendors_count || 0);
            showAlert('edit-success', 'City updated successfully.');
        } catch (error) {
            handleErrors(error);
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(loading) {
        document.getElementById('edit-btn').disabled = loading;
        document.getElementById('edit-spinner').classList.toggle('hidden', !loading);
        document.getElementById('edit-btn-text').textContent = loading ? 'Saving...' : 'Save Changes';
    }

    function clearErrors() {
        document.getElementById('edit-alert').classList.add('hidden');
        document.getElementById('edit-success').classList.add('hidden');
        document.querySelectorAll('.form-error').forEach(function (element) {
            element.classList.add('hidden');
            element.textContent = '';
        });
    }

    function handleErrors(error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            Object.entries(errors).forEach(function ([field, messages]) {
                const element = document.getElementById(field + '-error');
                if (element) {
                    element.textContent = messages[0];
                    element.classList.remove('hidden');
                }
            });

            showAlert('edit-alert', 'Please fix the errors below.');
            return;
        }

        showAlert('edit-alert', error.response?.data?.message || 'Failed to update city.');
    }

    function showAlert(id, message) {
        const alert = document.getElementById(id);
        const messageElement = document.getElementById(id + '-message');

        if (!alert || !messageElement) {
            return;
        }

        messageElement.textContent = message;
        alert.classList.remove('hidden');
    }
});
</script>
@endpush
