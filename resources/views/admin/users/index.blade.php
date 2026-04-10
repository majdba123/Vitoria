@extends('layouts.admin')

@section('title', 'Users — SyriaZone Admin')
@section('page-title', 'Users')

@section('content')
<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">Manage all user accounts.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Add User
        </a>
    </div>

    {{-- Alerts --}}
    <x-alert type="error" id="users-alert" />
    <x-alert type="success" id="users-success" />

    {{-- Loading --}}
    <div id="users-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading users...</p>
    </div>

    {{-- Empty --}}
    <div id="users-empty" class="hidden">
        <div class="card py-16 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            <h3 class="mt-3 text-sm font-semibold text-gray-900">No users yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new user.</p>
            <div class="mt-5">
                <a href="{{ route('admin.users.create') }}" class="btn-primary btn-sm">Add User</a>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div id="users-table-wrapper" class="hidden">
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>User</th>
                            <th class="hidden md:table-cell">Phone</th>
                            <th class="hidden lg:table-cell">National ID</th>
                            <th class="hidden sm:table-cell">Type</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody"></tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="flex flex-col items-center gap-3 border-t border-gray-100 px-4 py-3 sm:flex-row sm:justify-between">
                <p id="users-info" class="text-xs text-gray-500"></p>
                <div class="flex gap-2">
                    <button id="prev-page" class="btn-secondary btn-xs" disabled>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        Prev
                    </button>
                    <button id="next-page" class="btn-secondary btn-xs" disabled>
                        Next
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
    <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900">Delete User</h3>
                <p class="mt-0.5 text-sm text-gray-500">This will permanently delete this user account and all their tokens.</p>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button id="delete-cancel" class="btn-secondary btn-sm">Cancel</button>
            <button id="delete-confirm" class="btn-danger btn-sm">Delete</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let deleteUserId = null;
    const typeLabels = { 0: 'User', 1: 'Admin', 2: 'Vendor' };
    const typeBadge = { 0: 'badge-info', 1: 'badge-purple', 2: 'badge-brand' };

    loadUsers();

    document.getElementById('prev-page').addEventListener('click', () => { currentPage--; loadUsers(); });
    document.getElementById('next-page').addEventListener('click', () => { currentPage++; loadUsers(); });
    document.getElementById('delete-cancel').addEventListener('click', closeDeleteModal);
    document.getElementById('delete-confirm').addEventListener('click', confirmDelete);

    async function loadUsers() {
        showLoading(true);
        try {
            const response = await window.axios.get('/api/admin/users?page=' + currentPage);
            const { data, meta } = response.data;
            renderUsers(data);
            renderPagination(meta);
        } catch (error) {
            showAlert('users-alert', error.response?.data?.message || 'Failed to load users.');
        } finally {
            showLoading(false);
        }
    }

    function renderUsers(users) {
        const tbody = document.getElementById('users-tbody');
        const tableWrapper = document.getElementById('users-table-wrapper');
        const emptyState = document.getElementById('users-empty');

        if (!users || users.length === 0) {
            tableWrapper.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        tableWrapper.classList.remove('hidden');

        tbody.innerHTML = users.map(user => `
            <tr>
                <td class="font-medium text-gray-400">${user.id}</td>
                <td>
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600">
                            ${escapeHtml((user.name || '?').charAt(0).toUpperCase())}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">${escapeHtml(user.name)}</p>
                            <p class="truncate text-xs text-gray-500">${escapeHtml(user.email || '—')}</p>
                            <p class="truncate text-xs text-gray-400 md:hidden">${escapeHtml(user.phone_number || '')}</p>
                        </div>
                    </div>
                </td>
                <td class="hidden md:table-cell">
                    <span class="font-mono text-sm text-gray-600">${escapeHtml(user.phone_number)}</span>
                </td>
                <td class="hidden lg:table-cell">
                    <span class="font-mono text-xs text-gray-500">${escapeHtml(user.national_id)}</span>
                </td>
                <td class="hidden sm:table-cell">
                    <span class="badge ${typeBadge[user.type] || typeBadge[0]}">
                        ${typeLabels[user.type] || 'User'}
                    </span>
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="/admin/users/${user.id}" class="btn-ghost btn-xs text-brand-600" title="View Profile">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>
                        <a href="/admin/orders?user_id=${user.id}" class="btn-ghost btn-xs text-indigo-600" title="View Order History">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h11.25m-11.25 4.5h11.25m-11.25 4.5h11.25M3.75 6.75h.008v.008H3.75V6.75zm0 4.5h.008v.008H3.75v-.008zm0 4.5h.008v.008H3.75v-.008z"/></svg>
                        </a>
                        <a href="/admin/users/${user.id}/edit" class="btn-ghost btn-xs text-gray-600" title="Edit">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        </a>
                        <button onclick="openDeleteModal(${user.id})" class="btn-ghost btn-xs text-red-500 hover:text-red-700" title="Delete">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(meta) {
        document.getElementById('users-info').textContent = `Showing page ${meta.current_page} of ${meta.last_page} · ${meta.total} total`;
        document.getElementById('prev-page').disabled = meta.current_page <= 1;
        document.getElementById('next-page').disabled = meta.current_page >= meta.last_page;
    }

    window.openDeleteModal = function (id) {
        deleteUserId = id;
        const modal = document.getElementById('delete-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    function closeDeleteModal() {
        deleteUserId = null;
        const modal = document.getElementById('delete-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function confirmDelete() {
        if (!deleteUserId) return;
        try {
            await window.axios.delete('/api/admin/users/' + deleteUserId);
            closeDeleteModal();
            showAlert('users-success', 'User deleted successfully.');
            loadUsers();
        } catch (error) {
            closeDeleteModal();
            showAlert('users-alert', error.response?.data?.message || 'Failed to delete user.');
        }
    }

    function showLoading(show) { document.getElementById('users-loading').classList.toggle('hidden', !show); }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        document.getElementById(id + '-message').textContent = message;
        box.classList.remove('hidden');
        setTimeout(() => box.classList.add('hidden'), 4000);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }
});
</script>
@endpush
