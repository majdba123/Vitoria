@extends('layouts.admin')

@section('title', 'Edit User — SyriaZone Admin')
@section('page-title', 'Edit User')

@section('content')
<div class="mx-auto max-w-2xl">
    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.users.index') }}" class="hover:text-gray-700">Users</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Edit</span>
    </nav>

    {{-- Loading --}}
    <div id="edit-loading" class="py-20 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading user details...</p>
    </div>

    {{-- Card --}}
    <div id="edit-card" class="card hidden">
        <div class="card-body border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900">Edit User</h2>
            <p class="mt-0.5 text-sm text-gray-500">Update user account information.</p>
        </div>

        <div class="card-body">
            <x-alert type="error" id="edit-alert" />
            <x-alert type="success" id="edit-success" />

            <form id="edit-user-form" class="mt-1 space-y-6" novalidate>
                <fieldset>
                    <legend class="text-sm font-semibold text-gray-900">Account Details</legend>
                    <p class="mb-4 text-xs text-gray-500">The user's login credentials and profile information.</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input name="name" label="Full Name" :required="true" />
                        <x-form.input name="phone_number" label="Phone Number" type="tel" :required="true" />
                        <x-form.input name="national_id" label="National ID" :required="true" />
                        <x-form.input name="email" label="Email" type="email" placeholder="(optional)" />
                        <x-form.input name="password" label="New Password" type="password" placeholder="Leave blank to keep current" />

                        <div>
                            <label for="type" class="form-label">User Type</label>
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
                    <button type="submit" id="edit-btn" class="btn-primary">
                        <span id="edit-btn-text">Save Changes</span>
                        <svg id="edit-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
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
    const userId = {{ $userId }};
    const form = document.getElementById('edit-user-form');

    loadUser();

    async function loadUser() {
        try {
            const response = await window.axios.get('/api/admin/users/' + userId);
            const user = response.data.data;
            form.name.value = user.name || '';
            form.phone_number.value = user.phone_number || '';
            form.national_id.value = user.national_id || '';
            form.email.value = user.email || '';
            form.type.value = user.type ?? 0;
            document.getElementById('edit-loading').classList.add('hidden');
            document.getElementById('edit-card').classList.remove('hidden');
        } catch (error) {
            document.getElementById('edit-loading').innerHTML = '<p class="text-sm text-red-600">Failed to load user details.</p>';
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        toggleLoading(true);

        const payload = {
            name: form.name.value.trim(),
            phone_number: form.phone_number.value.trim(),
            national_id: form.national_id.value.trim(),
            type: parseInt(form.type.value),
        };
        if (form.email.value.trim()) payload.email = form.email.value.trim();
        if (form.password.value) payload.password = form.password.value;

        try {
            await window.axios.put('/api/admin/users/' + userId, payload);
            showAlert('edit-success', 'User updated successfully!');
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
        document.querySelectorAll('[id$="-error"]').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
    }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        document.getElementById(id + '-message').textContent = message;
        box.classList.remove('hidden');
        if (id.includes('success')) setTimeout(() => box.classList.add('hidden'), 3000);
    }

    function handleErrors(error) {
        if (error.response?.status === 422) {
            for (const [field, messages] of Object.entries(error.response.data.errors)) {
                const el = document.getElementById(field + '-error');
                if (el) { el.textContent = messages[0]; el.classList.remove('hidden'); }
            }
        } else {
            showAlert('edit-alert', error.response?.data?.message || 'An unexpected error occurred.');
        }
    }
});
</script>
@endpush
