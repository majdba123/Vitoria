@extends('layouts.admin')

@section('title', 'Create Syndicate - SyriaZone Admin')
@section('page-title', 'Create Syndicate')

@section('content')
<div class="mx-auto max-w-3xl">
    <form id="syndicate-form" class="card card-body space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="form-label">Name</label><input name="name" class="form-input" required><p id="name-error" class="form-error"></p></div>
            <div><label class="form-label">Email</label><input name="email" type="email" class="form-input" required><p id="email-error" class="form-error"></p></div>
            <div><label class="form-label">Phone</label><input name="phone" class="form-input"><p id="phone-error" class="form-error"></p></div>
            <div><label class="form-label">Password</label><input name="password" type="password" class="form-input" required><p id="password-error" class="form-error"></p></div>
            <div><label class="form-label">Type</label><select name="type" class="form-input" required><option value="agriculture">Agriculture</option><option value="veterinary">Veterinary</option></select><p id="type-error" class="form-error"></p></div>
            <div><label class="form-label">Status</label><select name="status" class="form-input"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            <div class="sm:col-span-2"><label class="form-label">Logo</label><input name="logo" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input"><p id="logo-error" class="form-error"></p></div>
        </div>
        <div class="flex justify-end gap-2"><a href="{{ route('admin.syndicates.index') }}" class="btn-secondary">Cancel</a><button class="btn-primary">Create Syndicate</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('syndicate-form').addEventListener('submit', async function (event) {
    event.preventDefault();
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
    const formData = new FormData(event.target);
    try {
        const res = await window.axios.post('/api/admin/syndicates', formData);
        window.location.href = '/admin/syndicates/' + res.data.data.id;
    } catch (error) {
        const errors = error.response?.data?.errors || {};
        Object.entries(errors).forEach(([key, messages]) => { const el = document.getElementById(key + '-error'); if (el) el.textContent = messages[0]; });
    }
});
</script>
@endpush
