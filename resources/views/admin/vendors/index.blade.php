@extends('layouts.admin')

@section('title', 'Vendors — SyriaZone Admin')
@section('page-title', 'Vendors')

@section('content')
<div class="space-y-4">
    {{-- Page Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">Manage all vendor accounts and their stores.</p>
        </div>
        <a href="{{ route('admin.vendors.create') }}" class="btn-primary btn-sm w-full shrink-0 sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Vendor
        </a>
    </div>

    {{-- Alerts --}}
    <x-alert type="error" id="vendors-alert" />
    <x-alert type="success" id="vendors-success" />

    {{-- Loading --}}
    <div id="vendors-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading vendors...</p>
    </div>

    {{-- Empty State --}}
    <div id="vendors-empty" class="hidden">
        <div class="card py-16 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35"/></svg>
            <h3 class="mt-3 text-sm font-semibold text-gray-900">No vendors yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new vendor.</p>
            <div class="mt-5">
                <a href="{{ route('admin.vendors.create') }}" class="btn-primary btn-sm">Add Vendor</a>
            </div>
        </div>
    </div>

    {{-- Desktop Table --}}
    <div id="vendors-table-wrapper" class="hidden">
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-12">#</th>
                            <th>Store</th>
                            <th class="hidden md:table-cell">Owner</th>
                            <th class="hidden lg:table-cell">National ID</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="vendors-tbody"></tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="flex flex-col items-center gap-3 border-t border-gray-100 px-4 py-3 sm:flex-row sm:justify-between">
                <p id="vendors-info" class="text-xs text-gray-500"></p>
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
                <h3 class="text-base font-semibold text-gray-900">Delete Vendor</h3>
                <p class="mt-0.5 text-sm text-gray-500">This will permanently delete the vendor and their user account.</p>
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
    let deleteVendorId = null;

    loadVendors();

    document.getElementById('prev-page').addEventListener('click', () => { currentPage--; loadVendors(); });
    document.getElementById('next-page').addEventListener('click', () => { currentPage++; loadVendors(); });
    document.getElementById('delete-cancel').addEventListener('click', closeDeleteModal);
    document.getElementById('delete-confirm').addEventListener('click', confirmDelete);

    async function loadVendors() {
        showLoading(true);
        try {
            const response = await window.axios.get('/api/admin/vendors?page=' + currentPage);
            const { data, meta } = response.data;
            renderVendors(data);
            renderPagination(meta);
        } catch (error) {
            showAlert('vendors-alert', error.response?.data?.message || 'Failed to load vendors.');
        } finally {
            showLoading(false);
        }
    }

    function renderVendors(vendors) {
        const tbody = document.getElementById('vendors-tbody');
        const tableWrapper = document.getElementById('vendors-table-wrapper');
        const emptyState = document.getElementById('vendors-empty');

        if (!vendors || vendors.length === 0) {
            tableWrapper.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        tableWrapper.classList.remove('hidden');

        tbody.innerHTML = vendors.map(vendor => `
            <tr>
                <td class="font-medium text-gray-400">${vendor.id}</td>
                <td>
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand-600">
                            ${escapeHtml((vendor.store_name || '?').charAt(0).toUpperCase())}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">${escapeHtml(vendor.store_name)}</p>
                            <p class="truncate text-xs text-gray-500 md:hidden">${escapeHtml(vendor.user?.name || '—')}</p>
                        </div>
                    </div>
                </td>
                <td class="hidden md:table-cell">
                    <p class="text-sm text-gray-900">${escapeHtml(vendor.user?.name || '—')}</p>
                    <p class="text-xs text-gray-500">${escapeHtml(vendor.user?.phone_number || '')}</p>
                </td>
                <td class="hidden lg:table-cell">
                    <span class="font-mono text-xs text-gray-500">${escapeHtml(vendor.user?.national_id || '—')}</span>
                </td>
                <td>
                    <button onclick="toggleVendorStatus(${vendor.id})" class="badge cursor-pointer transition-opacity hover:opacity-80 ${vendor.is_active ? 'badge-success' : 'badge-danger'}" title="Click to toggle">
                        <span class="mr-1 inline-block h-1.5 w-1.5 rounded-full ${vendor.is_active ? 'bg-emerald-500' : 'bg-red-500'}"></span>
                        ${vendor.is_active ? 'Active' : 'Inactive'}
                    </button>
                </td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="/admin/vendors/${vendor.id}" class="btn-ghost btn-xs text-brand-600" title="View Profile">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </a>
                        <a href="/admin/orders?vendor_id=${vendor.id}" class="btn-ghost btn-xs text-indigo-600" title="View Order History">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h6.75M3.75 3h11.25A2.25 2.25 0 0117.25 5.25V12M3.75 3l13.5 13.5M17.25 21v-4.5A2.25 2.25 0 0119.5 14.25H21m0 0l-3.375-3.375M21 14.25l-3.375 3.375"/></svg>
                        </a>
                        <a href="/admin/vendors/${vendor.id}/commission" class="btn-ghost btn-xs text-emerald-600" title="View Commission Dashboard">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.5 4.5L21.75 7.5M15.75 7.5h6v6"/></svg>
                        </a>
                        <a href="/admin/vendors/${vendor.id}/edit" class="btn-ghost btn-xs text-gray-600" title="Edit">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        </a>
                        <button onclick="openDeleteModal(${vendor.id})" class="btn-ghost btn-xs text-red-500 hover:text-red-700" title="Delete">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(meta) {
        document.getElementById('vendors-info').textContent = `Showing page ${meta.current_page} of ${meta.last_page} · ${meta.total} total`;
        document.getElementById('prev-page').disabled = meta.current_page <= 1;
        document.getElementById('next-page').disabled = meta.current_page >= meta.last_page;
    }

    // Toggle active status
    window.toggleVendorStatus = async function (id) {
        try {
            const response = await window.axios.patch('/api/admin/vendors/' + id + '/toggle-active');
            showAlert('vendors-success', response.data.message);
            loadVendors();
        } catch (error) {
            showAlert('vendors-alert', error.response?.data?.message || 'Failed to toggle status.');
        }
    };

    window.openDeleteModal = function (id) {
        deleteVendorId = id;
        const modal = document.getElementById('delete-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    function closeDeleteModal() {
        deleteVendorId = null;
        const modal = document.getElementById('delete-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function confirmDelete() {
        if (!deleteVendorId) return;
        try {
            await window.axios.delete('/api/admin/vendors/' + deleteVendorId);
            closeDeleteModal();
            showAlert('vendors-success', 'Vendor deleted successfully.');
            loadVendors();
        } catch (error) {
            closeDeleteModal();
            showAlert('vendors-alert', error.response?.data?.message || 'Failed to delete vendor.');
        }
    }

    function showLoading(show) { document.getElementById('vendors-loading').classList.toggle('hidden', !show); }

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
