@extends('layouts.vendor')

@section('title', 'My Profile — SyriaZone Vendor')
@section('page-title', 'My Profile')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    {{-- Loading --}}
    <div id="profile-loading" class="py-20 text-center">
        <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-emerald-500"></div>
        <p class="mt-4 text-sm font-medium text-gray-500">Loading your profile...</p>
    </div>

    <div id="profile-content" class="hidden space-y-6">
        {{-- Header Banner --}}
        <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 shadow-xl">
            <div class="px-6 py-8 sm:px-8">
                <div class="flex flex-col items-center gap-5 sm:flex-row">
                    <div class="text-center sm:text-left">
                        <h2 id="profile-name" class="text-2xl font-bold text-white"></h2>
                        <p id="profile-store" class="mt-1 text-emerald-100"></p>
                        <div id="profile-status-badge" class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur-sm"></div>
                    </div>
                </div>
            </div>
        </div>

        <x-alert type="error" id="profile-alert" />
        <x-alert type="success" id="profile-success" />

        <form id="profile-form" class="space-y-6" novalidate enctype="multipart/form-data">
            {{-- Avatar & Store Logo Side by Side --}}
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Profile & Store Images</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Hover over an image to change it.</p>
                </div>
                <div class="card-body">
                    <div class="flex flex-wrap items-center justify-center gap-10 py-4">
                        {{-- Profile Avatar --}}
                        <div class="flex flex-col items-center gap-3">
                            <div class="group relative">
                                <div id="avatar-display" class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 text-4xl font-bold text-emerald-600 shadow-lg ring-4 ring-white">
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
                                <div id="store-logo-display" class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-gray-100 to-gray-200 shadow-lg ring-4 ring-white">
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

            {{-- Personal Information --}}
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Personal Information</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Your account details and login credentials.</p>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input name="name" label="Full Name" placeholder="Your full name" :required="true" />
                        <x-form.input name="phone_number" label="Phone Number" type="tel" placeholder="09XXXXXXXX" :required="true" />
                        <x-form.input name="national_id" label="National ID" placeholder="National ID number" :required="true" />
                        <x-form.input name="email" label="Email" type="email" placeholder="you@example.com (optional)" />
                        <div class="sm:col-span-2">
                            <x-form.input name="password" label="New Password" type="password" placeholder="Leave blank to keep current" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Store Information --}}
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Store Information</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Your store profile visible to customers.</p>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input name="store_name" label="Store Name" placeholder="Your store name" :required="true" />
                        <x-form.input name="address" label="Address" placeholder="Store address (optional)" />
                    </div>
                    <div class="mt-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" rows="3" placeholder="Tell customers about your store..." class="form-textarea"></textarea>
                        <p class="form-error" id="description-error"></p>
                    </div>
                </div>
            </div>

            {{-- Allowed Categories (read-only) --}}
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Allowed Categories</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Categories assigned by admin. Contact admin to change.</p>
                </div>
                <div class="card-body">
                    <div id="profile-categories" class="flex flex-wrap gap-2">
                        <span class="text-sm text-gray-400 italic">Loading...</span>
                    </div>
                </div>
            </div>

            {{-- Save --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('vendor.dashboard') }}" class="btn-secondary">Cancel</a>
                <button type="submit" id="save-btn" class="btn-primary">
                    <span id="save-btn-text">Save Changes</span>
                    <svg id="save-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('profile-form');
    let avatarFile = null;
    let logoFile = null;

    const checkReady = setInterval(async function () {
        const app = document.getElementById('vendor-app');
        if (!app || app.classList.contains('hidden')) return;
        clearInterval(checkReady);
        loadProfile();
    }, 200);

    async function loadProfile() {
        try {
            const res = await window.axios.get('/api/vendor/profile');
            const { user, vendor } = res.data.data;

            form.name.value = user.name || '';
            form.phone_number.value = user.phone_number || '';
            form.national_id.value = user.national_id || '';
            form.email.value = user.email || '';
            form.store_name.value = vendor?.store_name || '';
            form.address.value = vendor?.address || '';
            form.description.value = vendor?.description || '';

            document.getElementById('profile-name').textContent = user.name || 'Vendor';
            document.getElementById('profile-store').textContent = vendor?.store_name || 'My Store';
            document.getElementById('avatar-initials').textContent = (user.name || 'V').charAt(0).toUpperCase();

            if (user.avatar_url) {
                setCircleImage('avatar-display', user.avatar_url);
            }

            if (vendor?.logo_url) {
                setCircleImage('store-logo-display', vendor.logo_url);
            }

            const statusBadge = document.getElementById('profile-status-badge');
            statusBadge.innerHTML = vendor?.is_active
                ? '<span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span> Active Store'
                : '<span class="h-1.5 w-1.5 rounded-full bg-red-300"></span> Inactive Store';

            const catsContainer = document.getElementById('profile-categories');
            if (vendor?.categories && vendor.categories.length > 0) {
                catsContainer.innerHTML = vendor.categories.map(c =>
                    `<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">${esc(c.name)} <span class="text-emerald-500">${parseFloat(c.commission || 0).toFixed(0)}%</span></span>`
                ).join('');
            } else {
                catsContainer.innerHTML = '<span class="text-sm text-gray-400 italic">No categories assigned</span>';
            }

            document.getElementById('profile-loading').classList.add('hidden');
            document.getElementById('profile-content').classList.remove('hidden');
        } catch (e) {
            document.getElementById('profile-loading').innerHTML = '<p class="text-sm text-red-600">Failed to load profile.</p>';
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
            reader.onload = ev => setCircleImage('store-logo-display', ev.target.result);
            reader.readAsDataURL(logoFile);
        }
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        toggleLoading(true);

        const formData = new FormData();
        formData.append('name', form.name.value.trim());
        formData.append('phone_number', form.phone_number.value.trim());
        formData.append('national_id', form.national_id.value.trim());
        formData.append('store_name', form.store_name.value.trim());

        if (form.email.value.trim()) formData.append('email', form.email.value.trim());
        if (form.password.value) formData.append('password', form.password.value);
        if (form.address.value.trim()) formData.append('address', form.address.value.trim());
        if (form.description.value.trim()) formData.append('description', form.description.value.trim());
        if (avatarFile) formData.append('avatar', avatarFile);
        if (logoFile) formData.append('logo', logoFile);

        try {
            const res = await window.axios.post('/api/vendor/profile', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            const { user, vendor } = res.data.data;
            document.getElementById('profile-name').textContent = user.name || 'Vendor';
            document.getElementById('profile-store').textContent = vendor?.store_name || 'My Store';

            if (user.avatar_url) {
                setCircleImage('avatar-display', user.avatar_url);
            }
            if (vendor?.logo_url) {
                setCircleImage('store-logo-display', vendor.logo_url);
            }

            const vendorName = document.getElementById('vendor-name');
            const vendorAvatar = document.getElementById('vendor-avatar');
            if (vendorName) vendorName.textContent = user.name;
            if (vendorAvatar) {
                if (user.avatar_url) {
                    vendorAvatar.innerHTML = `<img src="${user.avatar_url}" alt="" class="h-full w-full rounded-full object-cover">`;
                } else {
                    vendorAvatar.textContent = (user.name || 'V').charAt(0).toUpperCase();
                }
            }

            avatarFile = null;
            logoFile = null;
            form.password.value = '';
            showAlert('profile-success', 'Profile updated successfully!');
        } catch (error) {
            handleErrors(error);
        } finally {
            toggleLoading(false);
        }
    });

    function toggleLoading(l) {
        document.getElementById('save-btn').disabled = l;
        document.getElementById('save-spinner').classList.toggle('hidden', !l);
        document.getElementById('save-btn-text').textContent = l ? 'Saving...' : 'Save Changes';
    }
    function clearErrors() {
        document.getElementById('profile-alert').classList.add('hidden');
        document.getElementById('profile-success').classList.add('hidden');
        document.querySelectorAll('.form-error').forEach(el => { el.classList.add('hidden'); el.textContent = ''; });
    }
    function showAlert(id, msg) {
        const el = document.getElementById(id);
        document.getElementById(id + '-message').textContent = msg;
        el.classList.remove('hidden');
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (id.includes('success')) setTimeout(() => el.classList.add('hidden'), 4000);
    }
    function handleErrors(error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            for (const [f, m] of Object.entries(errors)) {
                const el = document.getElementById(f + '-error');
                if (el) { el.textContent = Array.isArray(m) ? m[0] : m; el.classList.remove('hidden'); }
            }
            showAlert('profile-alert', 'Please fix the errors below.');
        } else {
            showAlert('profile-alert', error.response?.data?.message || 'An unexpected error occurred.');
        }
    }
    function esc(t) { if (!t) return ''; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
});
</script>
@endpush
