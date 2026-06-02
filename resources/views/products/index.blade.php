@extends('layouts.app')
@section('title', 'All Products — SyriaZone')

@section('content')
<div class="bg-white dark:bg-gray-950">
    {{-- Breadcrumb --}}
    <div class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-2xl px-4 py-3 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">Home</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">All Products</span>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Title + Filters --}}
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">All Products</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" id="result-count"></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <select id="f-category-type" class="h-10 rounded-xl border border-gray-200 bg-white px-3 pr-8 text-sm font-medium text-gray-700 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"><option value="">All Types</option><option value="agriculture">Agriculture</option><option value="veterinary">Veterinary</option></select>
                <select id="f-category" class="h-10 rounded-xl border border-gray-200 bg-white px-3 pr-8 text-sm font-medium text-gray-700 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"><option value="">All Categories</option></select>
                <select id="f-subcategory" class="h-10 rounded-xl border border-gray-200 bg-white px-3 pr-8 text-sm font-medium text-gray-700 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"><option value="">All Subcategories</option></select>
                <select id="f-discount" class="h-10 rounded-xl border border-gray-200 bg-white px-3 pr-8 text-sm font-medium text-gray-700 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"><option value="">All Discounts</option><option value="1">Discounted</option><option value="0">No Discount</option></select>
                <button id="btn-apply" class="h-10 rounded-xl bg-brand-500 px-5 text-sm font-bold text-white shadow-sm hover:bg-brand-600 active:scale-[.97]">Apply</button>
                <button id="btn-clear" class="h-10 rounded-xl px-3 text-sm font-medium text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">Clear</button>
            </div>
        </div>

        {{-- Skeleton --}}
        <div id="loading" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            <div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton hidden h-80 rounded-2xl xl:block"></div>
        </div>
        <div id="grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"></div>
        <div id="empty" class="hidden py-20 text-center"><svg class="mx-auto h-16 w-16 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5"/></svg><p class="mt-4 font-bold text-gray-600 dark:text-gray-400">No products found</p></div>
        <div id="pagination" class="mt-10 flex flex-wrap items-center justify-center gap-1.5"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const $ = id => document.getElementById(id);
    let page = 1, ct = '', cId = '', sId = '', dFilter = '';
    const params = new URLSearchParams(window.location.search);
    ct = params.get('category_type') || ''; cId = params.get('category_id') || ''; sId = params.get('subcategory_id') || ''; dFilter = params.get('has_discount') || '';
    let allCats = [];

    async function init() {
        const cRes = await axios.get('/api/categories?per_page=100');
        allCats = cRes.data.data || [];
        $('f-category-type').value = ct;
        populateCategories();
        if (cId) { $('f-category').value = cId; populateSubs(cId); }
        if (dFilter) $('f-discount').value = dFilter;
        if (sId) setTimeout(() => $('f-subcategory').value = sId, 100);
        load();
    }

    function populateCategories() {
        const cats = ct ? allCats.filter(c => c.type === ct) : allCats;
        $('f-category').innerHTML = '<option value="">All Categories</option>' + cats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
    }

    function populateSubs(catId) {
        const cat = allCats.find(c => c.id == catId);
        const subs = cat ? cat.subcategories || [] : [];
        $('f-subcategory').innerHTML = '<option value="">All Subcategories</option>' + subs.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join('');
    }

    $('f-category-type').addEventListener('change', function() { ct = this.value; cId = ''; sId = ''; populateCategories(); populateSubs(''); });
    $('f-category').addEventListener('change', function() { populateSubs(this.value); sId = ''; });

    async function load() {
        $('loading').classList.remove('hidden'); $('grid').innerHTML = ''; $('empty').classList.add('hidden'); $('pagination').innerHTML = '';
        const p = new URLSearchParams({ page }); if (ct) p.append('category_type', ct); if (sId) p.append('subcategory_id', sId); else if (cId) p.append('category_id', cId); if (dFilter !== '') p.append('has_discount', dFilter);
        try {
            const res = await axios.get('/api/products?' + p.toString());
            const { data, meta } = res.data;
            $('result-count').textContent = meta.total + ' product' + (meta.total !== 1 ? 's' : '') + ' found';
            if (!data.length) { $('empty').classList.remove('hidden'); } else {
                $('grid').innerHTML = data.map(p => productCard(p)).join('');
            }
            renderPag(meta);
        } catch(e) { $('empty').classList.remove('hidden'); }
        $('loading').classList.add('hidden');
    }

    function starStars(rating) {
        const r = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
        const filled = '<svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        const empty = '<svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        let h = ''; for (let i = 0; i < 5; i++) h += i < r ? filled : empty; return h;
    }
    function productCard(p) {
        const photo = p.first_photo_url || '', inStock = p.quantity > 0;
        const isFav = window._favIds && window._favIds.has(p.id);
        const revCount = parseInt(p.review_count, 10) || 0;
        return `<div class="product-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="relative">
                <a href="/products/${p.id}"><div class="relative aspect-[4/5] overflow-hidden bg-gray-50 dark:bg-gray-800">
                    ${photo ? `<img src="${esc(photo)}" alt="${esc(p.name)}" class="h-full w-full object-contain p-4 transition-transform duration-500 hover:scale-105" loading="lazy">` : `<div class="flex h-full items-center justify-center"><svg class="h-12 w-12 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg></div>`}
                    ${!inStock ? '<div class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70"><span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400">Sold Out</span></div>' : ''}
                    ${p.has_active_discount ? `<div class="absolute left-2.5 top-2.5 z-10 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">-${parseFloat(p.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                </div></a>
                <button data-fav-btn="${p.id}" onclick="event.stopPropagation();window.toggleFav(${p.id},this)" class="absolute right-2.5 top-2.5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-all hover:scale-110 dark:bg-gray-900/90 ${isFav?'text-red-500':'text-gray-400 dark:text-gray-500'}"><svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav?'currentColor':'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button>
            </div>
            <div class="p-3 sm:p-4">
                <a href="/products/${p.id}"><h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">${esc(p.name)}</h3></a>
                <div class="mt-1.5 flex items-center gap-1.5 text-amber-400">${starStars(p.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${revCount ? revCount + (revCount === 1 ? ' review' : ' reviews') : ''}</span></div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-lg font-black ${p.has_active_discount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${parseFloat(p.has_active_discount ? p.discounted_price : p.price).toLocaleString()}</span><span class="text-[11px] text-gray-400">SYP</span>
                    ${p.has_active_discount ? `<span class="text-[11px] text-gray-400 line-through">${parseFloat(p.price).toLocaleString()} SYP</span>` : ''}
                </div>
                <button onclick="window.addToCart&&window.addToCart(${p.id},'${esc(p.name)}',${p.price},'${esc(photo)}')" class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold ${inStock ? 'bg-gray-900 text-white hover:bg-brand-600 dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white active:scale-[.97]' : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600'}" ${!inStock?'disabled':''}>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                    ${inStock ? 'Add to Cart' : 'Sold Out'}
                </button>
            </div></div>`;
    }

    function renderPag(meta) {
        if (!meta || meta.last_page <= 1) return;
        const c = meta.current_page, l = meta.last_page;
        let h = `<button onclick="goP(${c-1})" class="flex h-10 items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 text-xs font-bold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${c===1?'opacity-40 pointer-events-none':''}" ${c===1?'disabled':''}>Prev</button>`;
        getR(c,l).forEach(p => { h += p==='...'?'<span class="px-2 text-gray-400">...</span>':`<button onclick="goP(${p})" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-xs font-bold shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${p===c?'page-active':''}">${p}</button>`; });
        h += `<button onclick="goP(${c+1})" class="flex h-10 items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 text-xs font-bold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${c===l?'opacity-40 pointer-events-none':''}" ${c===l?'disabled':''}>Next</button>`;
        $('pagination').innerHTML = h;
    }
    function getR(c,l){if(l<=7)return Array.from({length:l},(_,i)=>i+1);const p=[1];if(c>3)p.push('...');for(let i=Math.max(2,c-1);i<=Math.min(l-1,c+1);i++)p.push(i);if(c<l-2)p.push('...');p.push(l);return p;}

    window.goP = function(p) { page=p; load(); window.scrollTo({top:0,behavior:'smooth'}); };
    $('btn-apply').addEventListener('click', () => { page=1; ct=$('f-category-type').value; cId=$('f-category').value; sId=$('f-subcategory').value; dFilter=$('f-discount').value; load(); });
    $('btn-clear').addEventListener('click', () => { $('f-category-type').value=''; $('f-category').value=''; $('f-subcategory').innerHTML='<option value="">All Subcategories</option>'; $('f-discount').value=''; ct=''; cId=''; sId=''; dFilter=''; page=1; populateCategories(); load(); });

    function esc(s){if(!s)return '';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
    init();
});
</script>
@endpush
