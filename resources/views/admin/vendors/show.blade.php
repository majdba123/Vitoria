@extends('layouts.admin')

@section('title', 'Vendor Details — SyriaZone Admin')
@section('page-title', 'Vendor Details')

@section('content')
<div class="mx-auto max-w-4xl">
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.vendors.index') }}" class="hover:text-gray-700">Vendors</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Details</span>
    </nav>

    <div id="show-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading vendor...</p>
    </div>

    <div id="show-content" class="hidden space-y-5">
        {{-- Profile Header --}}
        <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-navy-800 to-navy-900 shadow-xl">
            <div class="px-6 py-8 sm:px-8">
                <div class="flex flex-col items-center gap-6 sm:flex-row">
                    {{-- Avatar --}}
                    <div class="flex flex-col items-center gap-1.5">
                        <div id="vendor-avatar-display" class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-white/15 text-3xl font-bold text-white shadow-lg ring-4 ring-white/25">
                            <span id="vendor-avatar-initials">V</span>
                        </div>
                        <span class="text-[10px] font-medium text-gray-400">Profile</span>
                    </div>
                    {{-- Store Logo --}}
                    <div class="flex flex-col items-center gap-1.5">
                        <div id="vendor-logo-display" class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-white/10 shadow-lg ring-4 ring-white/25">
                            <svg class="h-10 w-10 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z"/></svg>
                        </div>
                        <span class="text-[10px] font-medium text-gray-400">Store Logo</span>
                    </div>
                    {{-- Info --}}
                    <div class="flex-1 text-center sm:text-left">
                        <h2 class="text-2xl font-bold text-white" id="vendor-store-name">—</h2>
                        <p class="mt-1 text-sm text-gray-300" id="vendor-owner-line">—</p>
                        <div class="mt-2" id="vendor-status-badge"></div>
                    </div>
                    {{-- Actions --}}
                    <a id="edit-link" href="#" class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-4 py-2 text-sm font-medium text-white backdrop-blur-sm transition-colors hover:bg-white/25">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        Edit
                    </a>
                </div>
            </div>
        </div>

        {{-- Details Grid --}}
        <div class="grid gap-5 lg:grid-cols-2">
            {{-- Personal Info Card --}}
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">Personal Information</h3>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Full Name</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" id="vendor-owner">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Phone Number</p>
                        <p class="mt-1 text-sm font-medium text-gray-900" id="vendor-phone">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Email</p>
                        <p class="mt-1 text-sm font-medium text-gray-900" id="vendor-email">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">National ID</p>
                        <p class="mt-1 text-sm font-medium text-gray-900" id="vendor-national-id">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Joined</p>
                        <p class="mt-1 text-sm font-medium text-gray-900" id="vendor-created">—</p>
                    </div>
                </div>
            </div>

            {{-- Store Info Card --}}
            <div class="card">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">Store Information</h3>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Store Name</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" id="vendor-store">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Address</p>
                        <p class="mt-1 text-sm text-gray-700" id="vendor-address">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Description</p>
                        <p class="mt-1 text-sm text-gray-700" id="vendor-description">—</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Allowed Categories --}}
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-bold text-gray-900">Allowed Categories</h3>
            </div>
            <div class="card-body">
                <div id="vendor-categories" class="flex flex-wrap gap-2">
                    <span class="text-sm text-gray-400">—</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const vendorId = '{{ $vendorId }}';

    try {
        const res = await window.axios.get('/api/admin/vendors/' + vendorId);
        const v = res.data.data;

        document.getElementById('vendor-store-name').textContent = v.store_name || '—';
        document.getElementById('vendor-store').textContent = v.store_name || '—';
        document.getElementById('vendor-address').textContent = v.address || 'No address provided.';
        document.getElementById('vendor-description').textContent = v.description || 'No description provided.';
        document.getElementById('vendor-created').textContent = v.created_at ? new Date(v.created_at).toLocaleDateString() : '—';

        const ownerName = v.user?.name || '—';
        document.getElementById('vendor-owner').textContent = ownerName;
        document.getElementById('vendor-owner-line').textContent = 'Owned by ' + ownerName;
        document.getElementById('vendor-phone').textContent = v.user?.phone_number || '—';
        document.getElementById('vendor-email').textContent = v.user?.email || '—';
        document.getElementById('vendor-national-id').textContent = v.user?.national_id || '—';
        document.getElementById('vendor-avatar-initials').textContent = (ownerName || 'V').charAt(0).toUpperCase();

        // Avatar
        if (v.user?.avatar_url) {
            document.getElementById('vendor-avatar-display').innerHTML = `<img src="${v.user.avatar_url}" alt="Avatar" class="h-full w-full object-cover">`;
        }

        // Store logo
        if (v.logo_url) {
            document.getElementById('vendor-logo-display').innerHTML = `<img src="${v.logo_url}" alt="Logo" class="h-full w-full object-cover">`;
        }

        // Status
        const statusBadge = v.is_active
            ? '<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/20 px-3 py-1 text-xs font-semibold text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Active</span>'
            : '<span class="inline-flex items-center gap-1.5 rounded-full bg-red-400/20 px-3 py-1 text-xs font-semibold text-red-300"><span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>Inactive</span>';
        document.getElementById('vendor-status-badge').innerHTML = statusBadge;

        document.getElementById('edit-link').href = '/admin/vendors/' + vendorId + '/edit';

        // Categories
        const catContainer = document.getElementById('vendor-categories');
        if (v.categories && v.categories.length > 0) {
            catContainer.innerHTML = v.categories.map(c =>
                `<span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-200">${esc(c.name)} <span class="text-emerald-600">(${parseFloat(c.commission || 0).toFixed(2)}%)</span></span>`
            ).join('');
        } else {
            catContainer.innerHTML = '<span class="text-sm text-gray-400 italic">No categories assigned</span>';
        }

        document.getElementById('show-loading').classList.add('hidden');
        document.getElementById('show-content').classList.remove('hidden');
    } catch (e) {
        document.getElementById('show-loading').innerHTML = '<p class="text-red-500">Failed to load vendor.</p>';
    }

    function esc(t) { if (!t) return ''; const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
});
</script>
@endpush
