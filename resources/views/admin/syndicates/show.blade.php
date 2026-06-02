@extends('layouts.admin')
@section('title', 'Syndicate Details - SyriaZone Admin')
@section('page-title', 'Syndicate Details')
@section('content')
<div class="space-y-4">
    <div class="flex justify-end gap-2"><a id="edit-link" class="btn-primary btn-sm" href="#">Edit</a><a href="{{ route('admin.syndicates.index') }}" class="btn-secondary btn-sm">Back</a></div>
    <div class="card card-body">
        <div class="flex items-center gap-4">
            <div id="logo" class="flex h-16 w-16 items-center justify-center rounded-xl bg-brand-50 text-xl font-black text-brand-600">S</div>
            <div>
                <h2 id="name" class="text-xl font-black text-gray-900"></h2>
                <p id="meta" class="text-sm text-gray-500"></p>
            </div>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 p-4"><p class="text-xs text-gray-500">Type</p><p id="type" class="mt-1 font-bold"></p></div>
            <div class="rounded-lg border border-gray-200 p-4"><p class="text-xs text-gray-500">Status</p><p id="status" class="mt-1 font-bold"></p></div>
            <div class="rounded-lg border border-gray-200 p-4"><p class="text-xs text-gray-500">User ID</p><p id="user-id" class="mt-1 font-bold"></p></div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
const syndicateId = @json($syndicateId);
load();
async function load() {
    const res = await window.axios.get('/api/admin/syndicates/' + syndicateId);
    const s = res.data.data;
    document.getElementById('edit-link').href = '/admin/syndicates/' + s.id + '/edit';
    document.getElementById('name').textContent = s.name;
    document.getElementById('meta').textContent = (s.email || '') + ' · ' + (s.phone || '');
    document.getElementById('type').textContent = s.type_label;
    document.getElementById('status').textContent = s.status_label;
    document.getElementById('user-id').textContent = s.user_id;
    if (s.logo_url) document.getElementById('logo').innerHTML = `<img src="${s.logo_url}" class="h-full w-full rounded-xl object-cover" alt="">`;
}
</script>
@endpush
