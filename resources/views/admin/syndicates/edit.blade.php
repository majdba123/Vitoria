@extends('layouts.admin')

@section('title', 'Edit Syndicate - Vetora')
@section('page-title', 'Edit Syndicate')

@section('content')
<div class="mx-auto max-w-3xl">
    <div id="form-alert" class="mb-4 hidden rounded-xl border px-4 py-3 text-sm font-semibold"></div>
    <form id="syndicate-form" enctype="multipart/form-data" class="card card-body space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">Name</label>
                <input name="name" class="form-input" required>
                <p id="name-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-input" required>
                <p id="email-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">Phone</label>
                <input name="phone" class="form-input" required>
                <p id="phone-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">Type</label>
                <select name="type" class="form-input" required>
                    <option value="agriculture">Agriculture</option>
                    <option value="veterinary">Veterinary</option>
                </select>
                <p id="type-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">New Password</label>
                <input name="password" type="password" class="form-input" autocomplete="new-password">
                <p id="password-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">Confirm New Password</label>
                <input name="password_confirmation" type="password" class="form-input" autocomplete="new-password">
                <p id="password_confirmation-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <p id="status-error" class="form-error"></p>
            </div>
            <div>
                <label class="form-label">Image <span class="text-xs font-normal text-gray-400">(optional)</span></label>
                <input name="logo" type="file" class="form-input">
                <div id="logo-preview" class="mt-2 hidden items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900/60">
                    <img id="logo-preview-img" src="" alt="" class="h-12 w-12 rounded-lg object-cover">
                    <p class="text-xs font-semibold text-gray-500">Current file</p>
                </div>
                <p id="logo-error" class="form-error"></p>
            </div>
        </div>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.syndicates.index') }}" class="btn-secondary">Cancel</a>
            <button id="submit-btn" class="btn-primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const syndicateId = @json($syndicateId);
const form = document.getElementById('syndicate-form');
loadSyndicate();

async function loadSyndicate() {
    try {
        const response = await window.axios.get('/api/admin/syndicates/' + syndicateId, { silent: true });
        const syndicate = response.data.data;
        form.name.value = syndicate.name || '';
        form.email.value = syndicate.email || '';
        form.phone.value = syndicate.phone || '';
        form.type.value = syndicate.type || 'agriculture';
        form.status.value = syndicate.status || 'active';

        if (syndicate.logo_url) {
            document.getElementById('logo-preview-img').src = syndicate.logo_url;
            document.getElementById('logo-preview').classList.remove('hidden');
            document.getElementById('logo-preview').classList.add('flex');
        }
    } catch (error) {
        const parsed = window.showApiError ? window.showApiError(error) : window.ApiErrors.parse(error);
        showAlert(parsed.generalMessage, 'error');
    }
}

form.addEventListener('submit', async function (event) {
    event.preventDefault();
    clearErrors();
    const button = document.getElementById('submit-btn');
    button.disabled = true;
    button.textContent = 'Saving...';
    const formData = syndicateFormData(form);
    formData.append('_method', 'PUT');

    if (!form.password.value) {
        formData.delete('password');
        formData.delete('password_confirmation');
    }

    try {
        await window.axios.post('/api/admin/syndicates/' + syndicateId, formData, { silent: true });
        window.location.href = '/admin/syndicates/' + syndicateId;
    } catch (error) {
        const parsed = window.showApiError ? window.showApiError(error) : window.ApiErrors.parse(error);
        window.ApiErrors.showFieldErrors(parsed.fieldErrors);
        showAlert(parsed.generalMessage, 'error');
    } finally {
        button.disabled = false;
        button.textContent = 'Save Changes';
    }
});

function syndicateFormData(form) {
    const formData = new FormData(form);
    const logo = form.querySelector('input[name="logo"]');
    if (!logo || !logo.files || logo.files.length === 0) {
        formData.delete('logo');
    }

    return formData;
}

function clearErrors() {
    document.querySelectorAll('.form-error').forEach(function (element) {
        element.textContent = '';
        element.classList.add('hidden');
    });
    document.getElementById('form-alert').classList.add('hidden');
}

function showAlert(message, type) {
    const box = document.getElementById('form-alert');
    box.textContent = message;
    box.className = type === 'error'
        ? 'mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700'
        : 'mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700';
}
</script>
@endpush
