@extends('layouts.admin')

@section('title', 'Syndicates - SyriaZone Admin')
@section('page-title', 'Syndicates')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-500">Manage agriculture and veterinary syndicate agents.</p>
        <a href="{{ route('admin.syndicates.create') }}" class="btn-primary btn-sm">Add Syndicate</a>
    </div>
    <div class="card card-body">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1"><label class="form-label">Type</label><select id="filter-type" class="form-input"><option value="">All Types</option><option value="agriculture">Agriculture</option><option value="veterinary">Veterinary</option></select></div>
            <div class="flex-1"><label class="form-label">Status</label><select id="filter-status" class="form-input"><option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            <div class="flex-1"><label class="form-label">Search</label><input id="filter-search" class="form-input" placeholder="Name or email"></div>
            <button id="apply-filters" class="btn-primary btn-sm">Apply</button>
            <button id="clear-filters" class="btn-secondary btn-sm">Clear</button>
        </div>
    </div>
    <div class="card">
        <div id="syndicates-list" class="card-body space-y-3"><p class="py-8 text-center text-sm text-gray-400">Loading syndicates...</p></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('syndicates-list');
    const esc = (v) => { const d = document.createElement('div'); d.textContent = v == null ? '' : String(v); return d.innerHTML; };
    document.getElementById('apply-filters').addEventListener('click', load);
    document.getElementById('clear-filters').addEventListener('click', () => {
        document.getElementById('filter-type').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-search').value = '';
        load();
    });
    load();
    async function load() {
        const params = new URLSearchParams();
        ['type', 'status', 'search'].forEach(key => {
            const value = document.getElementById('filter-' + key).value;
            if (value) params.append(key, value);
        });
        const res = await window.axios.get('/api/admin/syndicates?' + params.toString());
        const rows = res.data.data || [];
        list.innerHTML = rows.length ? rows.map(row => `
            <div class="flex flex-col gap-3 rounded-lg border border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">${esc(row.name)}</p>
                    <p class="mt-0.5 text-xs text-gray-500">${esc(row.email)} · ${esc(row.type_label)}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="badge ${row.is_active ? 'badge-success' : 'badge-danger'}">${esc(row.status_label)}</span>
                    <a href="/admin/syndicates/${row.id}" class="btn-secondary btn-xs">View</a>
                    <a href="/admin/syndicates/${row.id}/edit" class="btn-primary btn-xs">Edit</a>
                    <button onclick="toggleSyndicate(${row.id})" class="btn-secondary btn-xs">Toggle</button>
                    <button onclick="deleteSyndicate(${row.id})" class="btn-danger btn-xs">Delete</button>
                </div>
            </div>
        `).join('') : '<p class="py-8 text-center text-sm text-gray-400">No syndicates found.</p>';
    }
    window.toggleSyndicate = async function (id) { await window.axios.patch('/api/admin/syndicates/' + id + '/toggle-active'); load(); };
    window.deleteSyndicate = async function (id) { if (!confirm('Delete this syndicate?')) return; await window.axios.delete('/api/admin/syndicates/' + id); load(); };
});
</script>
@endpush
