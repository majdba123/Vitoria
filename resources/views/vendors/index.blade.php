@extends('layouts.app')
@section('title', 'All Stores — SyriaZone')

@section('content')
<div class="bg-white dark:bg-gray-950">
    <div class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-2xl px-4 py-3 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-brand-600">Home</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">All Stores</span>
            </nav>
        </div>
    </div>
    <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">All Stores</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Browse our trusted vendors</p>

        <div id="loading" class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"><div class="skeleton h-52 rounded-2xl"></div><div class="skeleton h-52 rounded-2xl"></div><div class="skeleton h-52 rounded-2xl"></div></div>
        <div id="grid" class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"></div>
        <div id="empty" class="mt-8 hidden py-16 text-center text-sm text-gray-400">No stores available.</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const res = await axios.get('/api/vendors');
        const vendors = res.data.data || [];
        document.getElementById('loading').classList.add('hidden');
        if (!vendors.length) { document.getElementById('empty').classList.remove('hidden'); return; }
        const colors = ['from-brand-400 to-brand-600','from-purple-400 to-purple-600','from-blue-400 to-blue-600','from-emerald-400 to-emerald-600','from-pink-400 to-pink-600'];
        document.getElementById('grid').innerHTML = vendors.map(v => {
            const hasLogo = v.logo && v.logo !== 'null';
            const initial = v.store_name ? v.store_name.charAt(0).toUpperCase() : 'S';
            const grad = colors[v.id % colors.length];
            return `
            <a href="/vendors/${v.id}" class="vendor-card group overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="relative h-40 overflow-hidden">
                    ${hasLogo ? `<img src="${esc(v.logo)}" alt="" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">` : `<div class="flex h-full w-full items-center justify-center bg-gradient-to-br ${grad}"><span class="text-5xl font-black text-white/90">${esc(initial)}</span></div>`}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    <div class="absolute bottom-3 left-4 right-4"><h3 class="truncate text-base font-bold text-white">${esc(v.store_name)}</h3>${v.user?`<p class="text-xs text-white/70">by ${esc(v.user.name)}</p>`:''}</div>
                </div>
                <div class="flex items-center justify-between p-4">
                    <p class="line-clamp-1 text-xs text-gray-500 dark:text-gray-400">${esc(v.description||'Visit our store')}</p>
                    <svg class="h-4 w-4 shrink-0 text-gray-300 group-hover:text-brand-500 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </div>
            </a>`;
        }).join('');
    } catch(e) { document.getElementById('loading').innerHTML = '<p class="text-sm text-gray-400">Could not load stores.</p>'; }
    function esc(s){if(!s)return '';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
});
</script>
@endpush
