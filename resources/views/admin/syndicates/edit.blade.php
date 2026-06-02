@extends('layouts.admin')
@section('title', 'Edit Syndicate - SyriaZone Admin')
@section('page-title', 'Edit Syndicate')
@section('content')
<div class="mx-auto max-w-3xl">
    <form id="syndicate-form" class="card card-body space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="form-label">Name</label><input name="name" class="form-input" required><p id="name-error" class="form-error"></p></div>
            <div><label class="form-label">Email</label><input name="email" type="email" class="form-input" required><p id="email-error" class="form-error"></p></div>
            <div><label class="form-label">Phone</label><input name="phone" class="form-input"><p id="phone-error" class="form-error"></p></div>
            <div><label class="form-label">New Password</label><input name="password" type="password" class="form-input"><p id="password-error" class="form-error"></p></div>
            <div><label class="form-label">Type</label><select name="type" class="form-input" required><option value="agriculture">Agriculture</option><option value="veterinary">Veterinary</option></select><p id="type-error" class="form-error"></p></div>
            <div><label class="form-label">Status</label><select name="status" class="form-input"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            <div class="sm:col-span-2"><label class="form-label">Logo</label><input name="logo" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input"><p id="logo-error" class="form-error"></p></div>
        </div>
        <div class="flex justify-end gap-2"><a href="{{ route('admin.syndicates.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Save Changes</button></div>
    </form>
</div>
@endsection
@push('scripts')
<script>
const syndicateId = @json($syndicateId);
const form = document.getElementById('syndicate-form');
load();
async function load() {
    const res = await window.axios.get('/api/admin/syndicates/' + syndicateId);
    const s = res.data.data;
    form.name.value = s.name || '';
    form.email.value = s.email || '';
    form.phone.value = s.phone || '';
    form.type.value = s.type || 'agriculture';
    form.status.value = s.status || 'active';
}
form.addEventListener('submit', async function (event) {
    event.preventDefault();
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
    const formData = new FormData(form);
    formData.append('_method', 'PUT');
    if (!form.password.value) formData.delete('password');
    try {
        await window.axios.post('/api/admin/syndicates/' + syndicateId, formData);
        window.location.href = '/admin/syndicates/' + syndicateId;
    } catch (error) {
        const errors = error.response?.data?.errors || {};
        Object.entries(errors).forEach(([key, messages]) => { const el = document.getElementById(key + '-error'); if (el) el.textContent = messages[0]; });
    }
});
</script>
@endpush
