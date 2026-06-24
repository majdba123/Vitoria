@extends('layouts.admin')

@section('title', 'Create City - Vetora Admin')
@section('page-title', 'Create City')

@section('content')
<div class="mx-auto max-w-2xl">
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.cities.index') }}" class="hover:text-gray-700">Cities</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Create</span>
    </nav>

    <div class="card">
        <div class="card-body border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">Add New City</h2>
            <p class="mt-0.5 text-sm text-gray-500">Create a city for vendor and store assignment.</p>
        </div>
        <div class="card-body">
            <x-alert type="error" id="create-alert" />
            <x-alert type="success" id="create-success" />

            <form id="create-city-form" class="space-y-5" novalidate>
                <x-form.input name="name" label="City Name" placeholder="Damascus" :required="true" />

                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.cities.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" id="create-btn" class="btn-primary">
                        <span id="create-btn-text">Create City</span>
                        <svg id="create-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('create-city-form');

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearErrors();
        toggleLoading(true);

        try {
            await window.axios.post('/api/admin/cities', {
                name: form.name.value.trim(),
            });

            showAlert('create-success', 'City created successfully. Redirecting...');
            setTimeout(function () {
                window.location.href = '{{ route('admin.cities.index') }}';
            }, 700);
        } catch (error) {
            handleErrors(error);
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(loading) {
        document.getElementById('create-btn').disabled = loading;
        document.getElementById('create-spinner').classList.toggle('hidden', !loading);
        document.getElementById('create-btn-text').textContent = loading ? 'Creating...' : 'Create City';
    }

    function clearErrors() {
        document.getElementById('create-alert').classList.add('hidden');
        document.getElementById('create-success').classList.add('hidden');
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

            showAlert('create-alert', 'Please fix the errors below.');
            return;
        }

        showAlert('create-alert', error.response?.data?.message || 'Failed to create city.');
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
