@extends('layouts.app')
@section('title', 'All Categories — SyriaZone')

@section('content')
<div class="bg-white dark:bg-gray-950">
    <div class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-2xl px-4 py-3 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">Home</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">All Categories</span>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">All Categories</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Browse all categories and their subcategories</p>

        <div id="loading" class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="skeleton h-48 rounded-2xl"></div><div class="skeleton h-48 rounded-2xl"></div><div class="skeleton h-48 rounded-2xl"></div>
        </div>
        <div id="grid" class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const res = await axios.get('/api/categories');
        const cats = res.data.data || [];
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('grid').innerHTML = cats.map(cat => {
            const logo = cat.logo ? `/storage/${cat.logo}` : '';
            const subs = cat.subcategories || [];
            return `
            <div class="cat-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900">
                <a href="/categories/${cat.id}" class="flex items-center gap-4 p-5">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 ring-1 ring-brand-200/50 dark:from-brand-500/10 dark:to-brand-500/5 dark:ring-brand-500/20">
                        ${logo ? `<img src="${esc(logo)}" class="h-full w-full rounded-2xl object-cover" alt="">` : `<svg class="h-8 w-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581"/></svg>`}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">${esc(cat.name)}</h2>
                        <p class="mt-0.5 text-xs text-gray-400">${subs.length} subcategor${subs.length===1?'y':'ies'} &middot; ${cat.commission}% commission</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
                ${subs.length ? `<div class="border-t border-gray-100 bg-gray-50/50 px-5 py-3 dark:border-gray-800 dark:bg-gray-800/30">
                    <div class="flex flex-wrap gap-2">${subs.map(s => `<a href="/subcategories/${s.id}" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200/80 transition-all hover:ring-brand-300 hover:text-brand-600 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:ring-brand-500 dark:hover:text-brand-400">${s.image?`<img src="/storage/${esc(s.image)}" class="h-4 w-4 rounded object-cover" alt="">`:''} ${esc(s.name)}</a>`).join('')}</div>
                </div>` : ''}
            </div>`;
        }).join('');
    } catch(e) { document.getElementById('loading').innerHTML = '<p class="text-sm text-gray-400">Could not load categories.</p>'; }
    function esc(s){if(!s)return '';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
});
</script>
@endpush
