@extends('layouts.admin')

@section('title', 'User Profile — Vetora Admin')
@section('page-title', 'User Profile')

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.users.index') }}" class="hover:text-gray-700">Users</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Profile</span>
    </nav>

    <div id="show-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading user profile...</p>
    </div>

    <div id="show-content" class="hidden space-y-5">
        <div class="dashboard-page-header">
            <div class="flex min-w-0 flex-1 items-center gap-4">
                <div id="user-avatar" class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full text-lg font-bold text-brand-700 dark:text-brand-300" style="background: var(--color-brand-soft)">
                    <span id="user-avatar-initial">U</span>
                </div>
                <div class="min-w-0">
                    <h2 id="user-name" class="truncate text-xl font-bold text-gray-900 dark:text-white">—</h2>
                    <p id="user-email" class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">—</p>
                    <div class="mt-2" id="user-type-badge"></div>
                </div>
            </div>
            <div class="dashboard-page-header-actions">
                <a id="edit-link" href="#" class="btn-secondary btn-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                    Edit
                </a>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="card lg:col-span-1">
                <div class="card-body border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-900">Personal Information</h3>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Full Name</p>
                        <p id="info-name" class="mt-1 text-sm font-semibold text-gray-900">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Email</p>
                        <p id="info-email" class="mt-1 text-sm text-gray-700">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Phone Number</p>
                        <p id="info-phone" class="mt-1 text-sm text-gray-700">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">National ID</p>
                        <p id="info-national-id" class="mt-1 text-sm text-gray-700">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Member Since</p>
                        <p id="info-created-at" class="mt-1 text-sm text-gray-700">—</p>
                    </div>
                </div>
            </div>

            <div class="card lg:col-span-2">
                <div class="card-body border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900">Favourites</h3>
                        <p id="fav-count" class="text-xs text-gray-500">Loading...</p>
                    </div>
                </div>
                <div class="card-body">
                    <div id="fav-loading" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="skeleton h-48 rounded-xl"></div>
                        <div class="skeleton h-48 rounded-xl"></div>
                        <div class="skeleton h-48 rounded-xl"></div>
                    </div>
                    <div id="fav-grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3"></div>
                    <div id="fav-empty" class="hidden py-10 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                        <p class="mt-2 text-sm font-semibold text-gray-500">No favourites yet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const i18n = {!! json_encode(['avatarAlt' => __('common.avatar_alt')]) !!};
    const userId = '{{ $userId }}';

    try {
        const [userResponse, favouritesResponse] = await Promise.all([
            window.axios.get('/api/admin/users/' + userId),
            window.axios.get('/api/admin/users/' + userId + '/favourites'),
        ]);

        const user = userResponse.data.data;
        const favourites = favouritesResponse.data.data || [];

        renderUser(user);
        renderFavourites(favourites);

        document.getElementById('show-loading').classList.add('hidden');
        document.getElementById('show-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('show-loading').innerHTML = '<p class="text-red-500">Failed to load user profile.</p>';
    }

    function renderUser(user) {
        const roleLabel = { 0: 'User', 1: 'Admin', 2: 'Vendor', 4: 'Employee' };
        const roleBadge = { 0: 'bg-blue-100 text-blue-700', 1: 'bg-purple-100 text-purple-700', 2: 'bg-brand-100 text-brand-700', 4: 'bg-cyan-100 text-cyan-700' };
        const name = user.name || '—';
        const email = user.email || '—';
        const role = roleLabel[user.type] || 'User';

        document.getElementById('user-name').textContent = name;
        document.getElementById('user-email').textContent = email;
        document.getElementById('info-name').textContent = name;
        document.getElementById('info-email').textContent = email;
        document.getElementById('info-phone').textContent = user.phone_number || '—';
        document.getElementById('info-national-id').textContent = user.national_id || '—';
        document.getElementById('info-created-at').textContent = user.created_at ? new Date(user.created_at).toLocaleDateString() : '—';
        document.getElementById('user-avatar-initial').textContent = name.charAt(0).toUpperCase();
        document.getElementById('user-type-badge').innerHTML = `<span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${roleBadge[user.type] || roleBadge[0]}">${role}</span>`;
        document.getElementById('edit-link').href = '/admin/users/' + userId + '/edit';

        if (user.avatar_url) {
            document.getElementById('user-avatar').innerHTML = `<img src="${esc(user.avatar_url)}" alt="${esc(i18n.avatarAlt)}" class="h-full w-full object-cover">`;
        }
    }

    function renderFavourites(favourites) {
        const grid = document.getElementById('fav-grid');
        const count = document.getElementById('fav-count');
        const loading = document.getElementById('fav-loading');
        const empty = document.getElementById('fav-empty');

        loading.classList.add('hidden');
        count.textContent = favourites.length + ' product' + (favourites.length !== 1 ? 's' : '');

        if (!favourites.length) {
            empty.classList.remove('hidden');
            return;
        }

        grid.innerHTML = favourites.map((product) => {
            const photoUrl = product.first_photo_url || '';
            return `<a href="/products/${product.id}" class="group overflow-hidden border border-gray-200/80 bg-white transition-colors hover:border-brand-300 dark:border-gray-800 dark:bg-gray-900" style="border-radius: var(--radius-card)">
                <div class="aspect-square overflow-hidden bg-gray-50 dark:bg-gray-800">
                    ${photoUrl ? `<img src="${esc(photoUrl)}" class="h-full w-full object-contain p-3" loading="lazy" alt="">` : `<div class="flex h-full items-center justify-center"><svg class="h-10 w-10 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg></div>`}
                </div>
                <div class="p-3">
                    ${product.vendor ? `<p class="mb-0.5 truncate text-[10px] text-gray-400 dark:text-gray-500">${esc(product.vendor.store_name)}</p>` : ''}
                    <h4 class="line-clamp-2 text-xs font-bold text-gray-900 group-hover:text-brand-600 dark:text-white">${esc(product.name)}</h4>
                    <div class="mt-1.5 flex items-baseline gap-1">
                        <span class="text-sm font-bold text-gray-900 dark:text-white">${Number.parseFloat(product.price).toLocaleString()}</span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500">SYP</span>
                    </div>
                </div>
            </a>`;
        }).join('');
    }

    function esc(value) {
        if (!value) {
            return '';
        }

        const element = document.createElement('div');
        element.textContent = value;
        return element.innerHTML;
    }
});
</script>
@endpush
