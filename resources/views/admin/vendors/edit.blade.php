@extends('layouts.admin')

@section('title', 'Edit Vendor — SyriaZone Admin')
@section('page-title', 'Edit Vendor')

@section('content')
<div class="mx-auto max-w-2xl">
    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.vendors.index') }}" class="hover:text-gray-700">Vendors</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Edit</span>
    </nav>

    {{-- Loading --}}
    <div id="edit-loading" class="py-20 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading vendor details...</p>
    </div>

    {{-- Card --}}
    <div id="edit-card" class="hidden space-y-5">
        {{-- Header with status toggle --}}
        <div class="card">
            <div class="card-body">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Edit Vendor</h2>
                        <p class="mt-0.5 text-sm text-gray-500">Update vendor account and store information.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span id="status-label" class="text-sm font-medium text-gray-500">Active</span>
                        <button type="button" id="toggle-active-btn"
                                class="toggle-switch bg-emerald-500"
                                role="switch" aria-checked="true">
                            <span class="toggle-switch-dot translate-x-5"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <x-alert type="error" id="edit-alert" />
        <x-alert type="success" id="edit-success" />

        <form id="edit-vendor-form" class="space-y-5" novalidate enctype="multipart/form-data">
            {{-- Images --}}
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Profile & Store Images</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Hover over an image to change it.</p>
                </div>
                <div class="card-body">
                    <div class="flex flex-wrap items-center justify-center gap-10 py-4">
                        {{-- Avatar --}}
                        <div class="flex flex-col items-center gap-3">
                            <div class="group relative">
                                <div id="avatar-display" class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-brand-100 to-brand-200 text-4xl font-bold text-brand-600 shadow-lg ring-4 ring-white">
                                    <span id="avatar-initials">V</span>
                                </div>
                                <label for="avatar-input" class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-full bg-black/50 opacity-0 transition-all duration-200 group-hover:opacity-100">
                                    <div class="text-center">
                                        <svg class="mx-auto h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.04l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                                        <span class="mt-1 block text-[10px] font-medium text-white/80">Change</span>
                                    </div>
                                </label>
                                <input type="file" id="avatar-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                            </div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Profile Photo</span>
                            <p class="form-error" id="avatar-error"></p>
                        </div>

                        {{-- Store Logo --}}
                        <div class="flex flex-col items-center gap-3">
                            <div class="group relative">
                                <div id="logo-display" class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-gray-100 to-gray-200 shadow-lg ring-4 ring-white">
                                    <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z"/></svg>
                                </div>
                                <label for="logo-input" class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-full bg-black/50 opacity-0 transition-all duration-200 group-hover:opacity-100">
                                    <div class="text-center">
                                        <svg class="mx-auto h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.04l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                                        <span class="mt-1 block text-[10px] font-medium text-white/80">Change</span>
                                    </div>
                                </label>
                                <input type="file" id="logo-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                            </div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Store Logo</span>
                            <p class="form-error" id="logo-error"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- User Account --}}
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">User Account</h3>
                    <p class="mt-0.5 text-xs text-gray-500">The vendor's login credentials.</p>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input name="name" label="Full Name" :required="true" />
                        <x-form.input name="phone_number" label="Phone Number" type="tel" :required="true" />
                        <x-form.input name="national_id" label="National ID" :required="true" />
                        <x-form.input name="email" label="Email" type="email" placeholder="(optional)" />
                        <div class="sm:col-span-2">
                            <x-form.input name="password" label="New Password" type="password" placeholder="Leave blank to keep current" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Store Details --}}
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Store Details</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Information about the vendor's store.</p>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input name="store_name" label="Store Name" :required="true" />
                        <x-form.input name="address" label="Address" placeholder="(optional)" />
                    </div>
                    <div class="mt-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" rows="3" class="form-textarea"></textarea>
                        <p class="form-error" id="description-error"></p>
                    </div>
                </div>
            </div>

            {{-- Allowed Categories --}}
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Allowed Categories</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Select which categories this vendor is allowed to sell products in.</p>
                </div>
                <div class="card-body">
                    <div id="categories-loading" class="py-4 text-center text-sm text-gray-400">Loading categories...</div>
                    <div id="categories-checkboxes" class="hidden grid grid-cols-1 gap-3 sm:grid-cols-2"></div>
                    <p class="form-error" id="category_ids-error"></p>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.vendors.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" id="edit-btn" class="btn-primary">
                    <span id="edit-btn-text">Save Changes</span>
                    <svg id="edit-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const vendorId = {{ $vendorId }};
    const form = document.getElementById('edit-vendor-form');
    const toggleBtn = document.getElementById('toggle-active-btn');
    const statusLabel = document.getElementById('status-label');
    let isActive = true;
    let avatarFile = null;
    let logoFile = null;

    toggleBtn.addEventListener('click', async function () {
        try {
            const response = await window.axios.patch('/api/admin/vendors/' + vendorId + '/toggle-active');
            isActive = response.data.data.is_active;
            updateToggleUI();
            showAlert('edit-success', response.data.message);
        } catch (error) {
            showAlert('edit-alert', error.response?.data?.message || 'Failed to toggle status.');
        }
    });

    function updateToggleUI() {
        if (isActive) {
            toggleBtn.classList.remove('bg-gray-300');
            toggleBtn.classList.add('bg-emerald-500');
            toggleBtn.querySelector('span').classList.remove('translate-x-0');
            toggleBtn.querySelector('span').classList.add('translate-x-5');
            statusLabel.textContent = 'Active';
            statusLabel.classList.remove('text-red-500');
            statusLabel.classList.add('text-gray-500');
        } else {
            toggleBtn.classList.remove('bg-emerald-500');
            toggleBtn.classList.add('bg-gray-300');
            toggleBtn.querySelector('span').classList.remove('translate-x-5');
            toggleBtn.querySelector('span').classList.add('translate-x-0');
            statusLabel.textContent = 'Inactive';
            statusLabel.classList.remove('text-gray-500');
            statusLabel.classList.add('text-red-500');
        }
    }

    function setCircleImage(containerId, url) {
        document.getElementById(containerId).innerHTML = `<img src="${url}" alt="" class="h-full w-full object-cover">`;
    }

    document.getElementById('avatar-input').addEventListener('change', function (e) {
        avatarFile = e.target.files[0];
        if (avatarFile) {
            const reader = new FileReader();
            reader.onload = ev => setCircleImage('avatar-display', ev.target.result);
            reader.readAsDataURL(avatarFile);
        }
    });

    document.getElementById('logo-input').addEventListener('change', function (e) {
        logoFile = e.target.files[0];
        if (logoFile) {
            const reader = new FileReader();
            reader.onload = ev => setCircleImage('logo-display', ev.target.result);
            reader.readAsDataURL(logoFile);
        }
    });

    loadVendor();

    async function loadVendor() {
        try {
            const [vendorRes, catsRes] = await Promise.all([
                window.axios.get('/api/admin/vendors/' + vendorId),
                window.axios.get('/api/admin/categories'),
            ]);
            const vendor = vendorRes.data.data;
            const allCategories = catsRes.data.data || [];
            const vendorCategoryIds = vendor.category_ids || [];

            form.name.value = vendor.user?.name || '';
            form.phone_number.value = vendor.user?.phone_number || '';
            form.national_id.value = vendor.user?.national_id || '';
            form.email.value = vendor.user?.email || '';
            form.store_name.value = vendor.store_name || '';
            form.address.value = vendor.address || '';
            form.description.value = vendor.description || '';
            isActive = vendor.is_active;
            updateToggleUI();

            document.getElementById('avatar-initials').textContent = (vendor.user?.name || 'V').charAt(0).toUpperCase();
            if (vendor.user?.avatar_url) {
                setCircleImage('avatar-display', vendor.user.avatar_url);
            }
            if (vendor.logo_url) {
                setCircleImage('logo-display', vendor.logo_url);
            }

            const container = document.getElementById('categories-checkboxes');
            container.innerHTML = allCategories.map(cat => `
                <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 cursor-pointer hover:border-brand-300 hover:bg-brand-50/50 transition-colors">
                    <input type="checkbox" name="category_ids[]" value="${cat.id}" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 category-checkbox" ${vendorCategoryIds.includes(cat.id) ? 'checked' : ''}>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-900">${esc(cat.name)}</span>
                        <span class="ml-2 text-xs text-emerald-600 font-semibold">${parseFloat(cat.commission || 0).toFixed(2)}%</span>
                    </div>
                </label>
            `).join('');
            document.getElementById('categories-loading').classList.add('hidden');
            container.classList.remove('hidden');

            document.getElementById('edit-loading').classList.add('hidden');
            document.getElementById('edit-card').classList.remove('hidden');
        } catch (error) {
            document.getElementById('edit-loading').innerHTML = '<p class="text-sm text-red-600">Failed to load vendor details.</p>';
        }
    }

    function esc(t) { if (!t) return ''; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        toggleLoading(true);

        const selectedCategories = [...document.querySelectorAll('.category-checkbox:checked')].map(cb => parseInt(cb.value));

        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('name', form.name.value.trim());
        formData.append('phone_number', form.phone_number.value.trim());
        formData.append('national_id', form.national_id.value.trim());
        formData.append('store_name', form.store_name.value.trim());
        formData.append('is_active', isActive ? '1' : '0');

        if (form.email.value.trim()) formData.append('email', form.email.value.trim());
        if (form.password.value) formData.append('password', form.password.value);
        if (form.address.value.trim()) formData.append('address', form.address.value.trim());
        if (form.description.value.trim()) formData.append('description', form.description.value.trim());
        if (avatarFile) formData.append('avatar', avatarFile);
        if (logoFile) formData.append('logo', logoFile);

        selectedCategories.forEach(id => formData.append('category_ids[]', id));

        try {
            const res = await window.axios.post('/api/admin/vendors/' + vendorId, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            const vendor = res.data.data;
            if (vendor.user?.avatar_url) {
                setCircleImage('avatar-display', vendor.user.avatar_url);
            }
            if (vendor.logo_url) {
                setCircleImage('logo-display', vendor.logo_url);
            }

            avatarFile = null;
            logoFile = null;
            form.password.value = '';
            showAlert('edit-success', 'Vendor updated successfully!');
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
        document.querySelectorAll('.form-error').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
    }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        document.getElementById(id + '-message').textContent = message;
        box.classList.remove('hidden');
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (id.includes('success')) setTimeout(() => box.classList.add('hidden'), 3000);
    }

    function handleErrors(error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            for (const [field, messages] of Object.entries(errors)) {
                const el = document.getElementById(field + '-error');
                if (el) { el.textContent = Array.isArray(messages) ? messages[0] : messages; el.classList.remove('hidden'); }
            }
            showAlert('edit-alert', 'Please fix the errors below.');
        } else {
            showAlert('edit-alert', error.response?.data?.message || 'An unexpected error occurred.');
        }
    }
});
</script>
@endpush
