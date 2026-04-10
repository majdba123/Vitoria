@extends('layouts.admin')

@section('title', 'Add User — SyriaZone Admin')
@section('page-title', 'Add User')

@section('content')
<div class="mx-auto max-w-2xl">
    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.users.index') }}" class="hover:text-gray-700">Users</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Create</span>
    </nav>

    <div class="card">
        <div class="card-body border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">Create New User</h2>
            <p class="mt-0.5 text-sm text-gray-500">Create a new user account with credentials.</p>
        </div>

        <div class="card-body">
            <x-alert type="error" id="create-alert" />
            <x-alert type="success" id="create-success" />

            <form id="create-user-form" class="mt-1 space-y-6" novalidate>
                <fieldset>
                    <legend class="text-sm font-semibold text-gray-900">Account Details</legend>
                    <p class="mb-4 text-xs text-gray-500">The user's login credentials and profile information.</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input name="name" label="Full Name" placeholder="User's full name" :required="true" />
                        <x-form.input name="phone_number" label="Phone Number" type="tel" placeholder="09XXXXXXXX" :required="true" />
                        <x-form.input name="national_id" label="National ID" placeholder="National ID number" :required="true" />
                        <x-form.input name="email" label="Email" type="email" placeholder="user@example.com (optional)" />
                        <x-form.input name="password" label="Password" type="password" placeholder="Min 6 characters" :required="true" />

                        {{-- User Type --}}
                        <div>
                            <label for="type" class="form-label">User Type <span class="text-red-500">*</span></label>
                            <select id="type" name="type" class="form-select">
                                <option value="0">User</option>
                                <option value="1">Admin</option>
                                <option value="2">Vendor</option>
                            </select>
                            <p class="form-error" id="type-error"></p>
                        </div>
                    </div>
                </fieldset>

                {{-- Actions --}}
                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" id="create-btn" class="btn-primary">
                        <span id="create-btn-text">Create User</span>
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
    const form = document.getElementById('create-user-form');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        toggleLoading(true);

        const payload = {
            name: form.name.value.trim(),
            phone_number: form.phone_number.value.trim(),
            national_id: form.national_id.value.trim(),
            password: form.password.value,
            type: parseInt(form.type.value),
        };
        if (form.email.value.trim()) payload.email = form.email.value.trim();

        try {
            await window.axios.post('/api/admin/users', payload);
            showAlert('create-success', 'User created successfully! Redirecting...');
            setTimeout(() => { window.location.href = '{{ route("admin.users.index") }}'; }, 800);
        } catch (error) {
            handleErrors(error);
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(loading) {
        document.getElementById('create-btn').disabled = loading;
        document.getElementById('create-spinner').classList.toggle('hidden', !loading);
        document.getElementById('create-btn-text').textContent = loading ? 'Creating...' : 'Create User';
    }

    function clearErrors() {
        document.getElementById('create-alert').classList.add('hidden');
        document.getElementById('create-success').classList.add('hidden');
        document.querySelectorAll('[id$="-error"]').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
    }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        document.getElementById(id + '-message').textContent = message;
        box.classList.remove('hidden');
    }

    function handleErrors(error) {
        if (error.response?.status === 422) {
            for (const [field, messages] of Object.entries(error.response.data.errors)) {
                const el = document.getElementById(field + '-error');
                if (el) { el.textContent = messages[0]; el.classList.remove('hidden'); }
            }
        } else {
            showAlert('create-alert', error.response?.data?.message || 'An unexpected error occurred.');
        }
    }
});
</script>
@endpush
