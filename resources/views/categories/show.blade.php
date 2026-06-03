@extends('layouts.app')
@section('title', 'Category — Vetora')

@section('content')
<div class="bg-white dark:bg-gray-950">
    <div class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-2xl px-4 py-3 sm:px-6 lg:px-8">
            <nav id="breadcrumb" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-brand-600">Home</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <a href="{{ route('categories.index') }}" class="hover:text-brand-600">Categories</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span id="bc-name" class="font-medium text-gray-900 dark:text-white"></span>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Category Header --}}
        <div id="cat-header" class="mb-8 flex items-center gap-4">
            <div id="cat-logo" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-brand-50 dark:bg-brand-500/10"></div>
            <div><h1 id="cat-name" class="text-2xl font-black text-gray-900 sm:text-3xl dark:text-white"></h1><p id="cat-meta" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"></p></div>
        </div>

        {{-- Subcategories --}}
        <div class="mb-10">
            <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">Subcategories</h2>
            <div id="subs-grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"></div>
        </div>

        {{-- Products --}}
        <div>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Products in this category</h2>
                <a href="/products?category_id={{ $categoryId }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">View All &rarr;</a>
            </div>
            <div id="p-loading" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div></div>
            <div id="p-grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"></div>
            <div id="p-empty" class="hidden py-12 text-center text-sm text-gray-400 dark:text-gray-500">No products in this category yet.</div>
            <div id="p-pagination" class="mt-8 flex flex-wrap items-center justify-center gap-1.5"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const catId = {{ $categoryId }};
    const selectedType = @json(auth()->user()?->preferred_product_type ?? session('preferred_product_type', request()->cookie('preferred_product_type', '')));
    const $ = id => document.getElementById(id);
    let page = 1;
    const withSelectedType = (url) => selectedType ? `${url}${url.includes('?') ? '&' : '?'}type=${encodeURIComponent(selectedType)}` : url;
    function esc(s){if(!s)return '';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
    function categoryHeroInner(cat) {
        if (cat.icon_class) {
            return `<i class="${esc(cat.icon_class)} text-3xl leading-none text-brand-500 dark:text-brand-400" aria-hidden="true"></i>`;
        }
        const catHero = cat.logo || cat.icon;
        if (catHero) {
            return `<img src="/storage/${esc(catHero)}" class="h-full w-full rounded-2xl object-cover" alt="">`;
        }
        return `<svg class="h-7 w-7 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581"/></svg>`;
    }
    function subGridThumbInner(s) {
        if (s.icon_class) {
            return `<i class="${esc(s.icon_class)} text-xl leading-none text-brand-500 dark:text-gray-400" aria-hidden="true"></i>`;
        }
        if (s.image) {
            return `<img src="/storage/${esc(s.image)}" class="h-full w-full object-cover" alt="">`;
        }
        return `<svg class="h-6 w-6 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg>`;
    }

    try {
        const res = await axios.get(withSelectedType('/api/categories/' + catId));
        const cat = res.data.data;
        document.title = cat.name + ' — Vetora';
        $('bc-name').textContent = cat.name;
        $('cat-name').textContent = cat.name;
        $('cat-meta').textContent = (cat.subcategories||[]).length + ' subcategories · ' + cat.commission + '% commission';
        $('cat-logo').innerHTML = categoryHeroInner(cat);

        const subs = cat.subcategories || [];
        $('subs-grid').innerHTML = subs.map(s => `
            <a href="/subcategories/${s.id}" class="group flex items-center gap-3 rounded-2xl border border-gray-200/80 bg-white p-4 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                    ${subGridThumbInner(s)}
                </div>
                <span class="text-sm font-bold text-gray-700 group-hover:text-brand-600 dark:text-gray-300 dark:group-hover:text-brand-400">${esc(s.name)}</span>
            </a>
        `).join('') || '<p class="col-span-full text-sm text-gray-400">No subcategories</p>';
    } catch(e) { $('cat-name').textContent = 'Category not found'; }

    loadProducts();

    async function loadProducts() {
        $('p-loading').classList.remove('hidden'); $('p-grid').innerHTML = ''; $('p-empty').classList.add('hidden'); $('p-pagination').innerHTML = '';
        try {
            const res = await axios.get(withSelectedType('/api/products?category_id='+catId+'&page='+page));
            const { data, meta } = res.data;
            if (!data.length) { $('p-empty').classList.remove('hidden'); } else { $('p-grid').innerHTML = data.map(p => pCard(p)).join(''); }
            renderPag(meta);
        } catch(e) { $('p-empty').classList.remove('hidden'); }
        $('p-loading').classList.add('hidden');
    }

    function starStars(rating) {
        const r = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
        const filled = '<svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        const empty = '<svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        let h = ''; for (let i = 0; i < 5; i++) h += i < r ? filled : empty; return h;
    }
    function pCard(p) {
        const photo = p.first_photo_url||'', inStock = p.quantity>0;
        const isFav = window._favIds && window._favIds.has(p.id);
        const revCount = parseInt(p.review_count, 10) || 0;
        return `<div class="product-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900"><div class="relative"><a href="/products/${p.id}"><div class="relative aspect-[4/5] overflow-hidden bg-gray-50 dark:bg-gray-800">${photo?`<img src="${esc(photo)}" class="h-full w-full object-contain p-4" loading="lazy">`:`<div class="flex h-full items-center justify-center"><svg class="h-12 w-12 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg></div>`}${!inStock?'<div class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70"><span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-bold text-red-600">Sold Out</span></div>':''}${p.has_active_discount?`<div class="absolute left-2.5 top-2.5 z-10 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">-${parseFloat(p.discount_percentage || 0).toFixed(0)}%</div>`:''}</div></a><button data-fav-btn="${p.id}" onclick="event.stopPropagation();window.toggleFav(${p.id},this)" class="absolute right-2.5 top-2.5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-all hover:scale-110 dark:bg-gray-900/90 ${isFav?'text-red-500':'text-gray-400 dark:text-gray-500'}"><svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav?'currentColor':'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button></div><div class="p-3 sm:p-4"><a href="/products/${p.id}"><h3 class="line-clamp-2 text-sm font-bold text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">${esc(p.name)}</h3></a><div class="mt-1.5 flex items-center gap-1.5 text-amber-400">${starStars(p.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${revCount ? revCount + (revCount === 1 ? ' review' : ' reviews') : ''}</span></div><div class="mt-2 flex items-baseline gap-1"><span class="text-lg font-black ${p.has_active_discount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${parseFloat(p.has_active_discount ? p.discounted_price : p.price).toLocaleString()}</span><span class="text-[11px] text-gray-400">SYP</span>${p.has_active_discount?`<span class="text-[11px] text-gray-400 line-through">${parseFloat(p.price).toLocaleString()} SYP</span>`:''}</div><button onclick="window.addToCart&&window.addToCart(${p.id},\`${esc(p.name)}\`,${p.has_active_discount ? p.discounted_price : p.price},\`${esc(photo)}\`)" class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition-all ${inStock?'bg-gray-900 text-white hover:bg-brand-600 active:scale-[.97] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white':'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600'}" ${!inStock?'disabled':''}><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>${inStock?'Add to Cart':'Sold Out'}</button></div></div>`;
    }

    function renderPag(meta) {
        if(!meta||meta.last_page<=1)return;const c=meta.current_page,l=meta.last_page;
        let h=`<button onclick="window._goP(${c-1})" class="flex h-10 items-center rounded-xl border border-gray-200 bg-white px-4 text-xs font-bold dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${c===1?'opacity-40 pointer-events-none':''}" ${c===1?'disabled':''}>Prev</button>`;
        getR(c,l).forEach(p=>{h+=p==='...'?'<span class="px-2 text-gray-400">...</span>':`<button onclick="window._goP(${p})" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-xs font-bold dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${p===c?'page-active':''}">${p}</button>`;});
        h+=`<button onclick="window._goP(${c+1})" class="flex h-10 items-center rounded-xl border border-gray-200 bg-white px-4 text-xs font-bold dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${c===l?'opacity-40 pointer-events-none':''}" ${c===l?'disabled':''}>Next</button>`;
        $('p-pagination').innerHTML=h;
    }
    function getR(c,l){if(l<=7)return Array.from({length:l},(_,i)=>i+1);const p=[1];if(c>3)p.push('...');for(let i=Math.max(2,c-1);i<=Math.min(l-1,c+1);i++)p.push(i);if(c<l-2)p.push('...');p.push(l);return p;}
    window._goP = function(p) { page=p; loadProducts(); window.scrollTo({top:0,behavior:'smooth'}); };
});
</script>
@endpush
