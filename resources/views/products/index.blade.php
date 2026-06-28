@extends('layouts.app')
@section('title', 'جميع المنتجات - Vetora')

@section('content')
<div class="bg-transparent">
    <div class="border-b border-white/40 bg-white/60 backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
        <div class="page-shell py-3">
            <nav class="page-breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
                <svg class="h-3 w-3 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">جميع المنتجات</span>
            </nav>
        </div>
    </div>

    <div class="page-shell">
        <div class="page-header">
            <div>
                <h1 class="text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">جميع المنتجات</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" id="result-count"></p>
            </div>
            <div class="filter-panel grid w-full gap-3 md:grid-cols-2 xl:grid-cols-[repeat(3,minmax(0,1fr))_auto_auto] xl:items-center">
                <select id="f-category-type" class="form-select min-w-0">
                    <option value="">كل الأنواع</option>
                    <option value="agriculture">زراعي</option>
                    <option value="veterinary">بيطري</option>
                </select>
                <select id="f-category" class="form-select min-w-0"><option value="">كل التصنيفات</option></select>
                <select id="f-discount" class="form-select min-w-0">
                    <option value="">كل الخصومات</option>
                    <option value="1">عليه خصم</option>
                    <option value="0">بدون خصم</option>
                </select>
                <button id="btn-apply" class="btn-primary w-full sm:w-auto">تطبيق</button>
                <button id="btn-clear" class="btn-secondary w-full sm:w-auto">مسح</button>
            </div>
        </div>

        <div id="loading" class="responsive-shop-grid">
            <div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton h-80 rounded-2xl"></div><div class="skeleton hidden h-80 rounded-2xl xl:block"></div>
        </div>
        <div id="grid" class="responsive-shop-grid"></div>
        <div id="empty" class="empty-state hidden py-20">
            <svg class="mx-auto h-16 w-16 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5"/></svg>
            <p class="mt-4 font-bold text-gray-600 dark:text-gray-400" id="empty-message">لا توجد منتجات</p>
        </div>
        <div id="pagination" class="mt-10 flex flex-wrap items-center justify-center gap-1.5"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const $ = id => document.getElementById(id);
    const preferredType = @json(auth()->user()?->preferred_product_type ?? session('preferred_product_type', request()->cookie('preferred_product_type', '')));
    let page = 1;
    let ct = '';
    let cId = '';
    let dFilter = '';
    const params = new URLSearchParams(window.location.search);
    ct = params.get('category_type') || params.get('type') || preferredType || '';
    cId = params.get('category_id') || '';
    dFilter = params.get('has_discount') || '';
    let allCats = [];

    async function init() {
        try {
            const cRes = await axios.get('/api/categories?per_page=100');
            allCats = cRes.data.data || [];
            $('f-category-type').value = ct;
            populateCategories();
            if (cId) {
                $('f-category').value = cId;
            }
            if (dFilter) {
                $('f-discount').value = dFilter;
            }
            await load();
        } catch (error) {
            showEmpty('تعذر تحميل المنتجات. يرجى المحاولة مرة أخرى.');
        }
    }

    function populateCategories() {
        const cats = ct ? allCats.filter(c => c.type === ct) : allCats;
        $('f-category').innerHTML = '<option value="">كل التصنيفات</option>' + cats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
    }

    $('f-category-type').addEventListener('change', function() {
        ct = this.value;
        cId = '';
        populateCategories();
        $('f-category').value = '';
        page = 1;
        load();
    });

    $('f-category').addEventListener('change', function() {
        cId = this.value;
        page = 1;
        load();
    });

    $('f-discount').addEventListener('change', function() {
        dFilter = this.value;
        page = 1;
        load();
    });

    async function load() {
        $('loading').classList.remove('hidden');
        $('grid').innerHTML = '';
        $('empty').classList.add('hidden');
        $('pagination').innerHTML = '';
        const query = new URLSearchParams({ page });
        if (ct) query.append('category_type', ct);
        if (cId) query.append('category_id', cId);
        if (dFilter !== '') query.append('has_discount', dFilter);
        const nextUrl = new URL(window.location.href);
        ct ? nextUrl.searchParams.set('category_type', ct) : nextUrl.searchParams.delete('category_type');
        nextUrl.searchParams.delete('type');
        cId ? nextUrl.searchParams.set('category_id', cId) : nextUrl.searchParams.delete('category_id');
        dFilter !== '' ? nextUrl.searchParams.set('has_discount', dFilter) : nextUrl.searchParams.delete('has_discount');
        page > 1 ? nextUrl.searchParams.set('page', String(page)) : nextUrl.searchParams.delete('page');
        window.history.replaceState({}, '', nextUrl.pathname + nextUrl.search);

        try {
            const res = await axios.get('/api/products?' + query.toString());
            const { data, meta } = res.data;
            $('result-count').textContent = `${meta.total} منتج`;
            if (!data.length) {
                showEmpty('لا توجد منتجات مطابقة.');
            } else {
                $('grid').innerHTML = data.map(productCard).join('');
            }
            renderPag(meta);
        } catch (e) {
            showEmpty('تعذر تحميل المنتجات. يرجى المحاولة مرة أخرى.');
        }

        $('loading').classList.add('hidden');
    }

    function showEmpty(message) {
        $('empty-message').textContent = message;
        $('empty').classList.remove('hidden');
        $('loading').classList.add('hidden');
    }

    function starStars(rating) {
        const r = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
        const filled = '<svg class="h-3.5 w-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        const empty = '<svg class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        let html = '';
        for (let i = 0; i < 5; i++) html += i < r ? filled : empty;
        return html;
    }

    function productCard(p) {
        const photo = p.first_photo_url || p.fallback_photo_url || '{{ asset('images/product-placeholder.svg') }}';
        const inStock = Number(p.quantity || 0) > 0;
        const isFav = window._favIds && window._favIds.has(p.id);
        const revCount = parseInt(p.review_count, 10) || 0;
        const typeLabel = p.category?.type === 'agriculture' ? 'زراعي' : (p.category?.type === 'veterinary' ? 'بيطري' : '');
        const displayPrice = parseFloat(p.has_active_discount ? p.discounted_price : p.price || 0).toLocaleString();

        return `<div class="product-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="relative">
                <a href="/products/${p.id}"><div class="shop-card-media">
                    <img src="${esc(photo)}" alt="${esc(p.name)}" class="shop-card-media-img" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}'">
                    ${!inStock ? '<div class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70"><span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400">نفد من المخزون</span></div>' : ''}
                    ${p.has_active_discount ? `<div class="absolute left-2.5 top-2.5 z-10 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">-${parseFloat(p.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                </div></a>
                <button data-fav-btn="${p.id}" onclick="event.stopPropagation();window.toggleFav(${p.id},this)" class="absolute right-2.5 top-2.5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-all hover:scale-110 dark:bg-gray-900/90 ${isFav ? 'text-red-500' : 'text-gray-400 dark:text-gray-500'}"><svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav ? 'currentColor' : 'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button>
            </div>
            <div class="p-3 sm:p-4">
                <a href="/products/${p.id}"><h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">${esc(p.name)}</h3></a>
                ${typeLabel ? `<span class="mt-2 inline-flex rounded-lg bg-brand-50 px-2 py-1 text-[10px] font-black text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">${esc(typeLabel)}</span>` : ''}
                <div class="mt-1.5 flex items-center gap-1.5 text-amber-400">${starStars(p.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${revCount ? revCount + ' تقييم' : ''}</span></div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-lg font-black ${p.has_active_discount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${displayPrice}</span><span class="text-[11px] text-gray-400">SYP</span>
                    ${p.has_active_discount ? `<span class="text-[11px] text-gray-400 line-through">${parseFloat(p.price || 0).toLocaleString()} SYP</span>` : ''}
                </div>
                <button onclick="window.addToCart&&window.addToCart(${p.id},'${esc(p.name)}',${p.has_active_discount ? p.discounted_price : p.price},'${esc(photo)}')" class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold ${inStock ? 'bg-gray-900 text-white hover:bg-brand-600 dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white active:scale-[.97]' : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600'}" ${!inStock ? 'disabled' : ''}>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                    ${inStock ? 'أضف إلى السلة' : 'نفد من المخزون'}
                </button>
            </div>
        </div>`;
    }

    function renderPag(meta) {
        if (!meta || meta.last_page <= 1) return;
        const current = meta.current_page;
        const last = meta.last_page;
        let html = `<button onclick="goP(${current - 1})" class="flex h-10 items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 text-xs font-bold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${current === 1 ? 'pointer-events-none opacity-40' : ''}" ${current === 1 ? 'disabled' : ''}>السابق</button>`;
        getRange(current, last).forEach(item => {
            html += item === '...'
                ? '<span class="px-2 text-gray-400">...</span>'
                : `<button onclick="goP(${item})" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-xs font-bold shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${item === current ? 'page-active' : ''}">${item}</button>`;
        });
        html += `<button onclick="goP(${current + 1})" class="flex h-10 items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 text-xs font-bold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 ${current === last ? 'pointer-events-none opacity-40' : ''}" ${current === last ? 'disabled' : ''}>التالي</button>`;
        $('pagination').innerHTML = html;
    }

    function getRange(current, last) {
        if (last <= 7) return Array.from({ length: last }, (_, index) => index + 1);
        const pages = [1];
        if (current > 3) pages.push('...');
        for (let pageNumber = Math.max(2, current - 1); pageNumber <= Math.min(last - 1, current + 1); pageNumber++) pages.push(pageNumber);
        if (current < last - 2) pages.push('...');
        pages.push(last);
        return pages;
    }

    window.goP = function(targetPage) {
        page = targetPage;
        load();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    $('btn-apply').addEventListener('click', () => {
        page = 1;
        ct = $('f-category-type').value;
        cId = $('f-category').value;
        dFilter = $('f-discount').value;
        load();
    });

    $('btn-clear').addEventListener('click', () => {
        $('f-category-type').value = '';
        $('f-category').value = '';
        $('f-discount').value = '';
        ct = '';
        cId = '';
        dFilter = '';
        page = 1;
        populateCategories();
        load();
    });

    function esc(value) {
        if (!value) return '';
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    init();
});
</script>
@endpush
