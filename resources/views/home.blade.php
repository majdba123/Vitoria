@extends('layouts.app')

@section('title', __('Vetora') . ' ' . __('site.meta_title_separator') . ' ' . __('home.meta_title'))

@section('content')
    @php
        $homeCategoryId = request()->query('category_id');
        $selectedHomeType = app(\App\Services\SelectedProductTypeService::class)->resolve(request());
        $typeCards = [
            \App\Models\Category::TYPE_AGRICULTURE => [
                'label' => 'زراعي',
                'description' => 'تصفح المنتجات والخدمات الزراعية',
                'icon' => 'fa-solid fa-seedling',
                'button' => 'تصفح الزراعي',
            ],
            \App\Models\Category::TYPE_VETERINARY => [
                'label' => 'بيطري',
                'description' => 'تصفح المنتجات والخدمات البيطرية',
                'icon' => 'fa-solid fa-stethoscope',
                'button' => 'تصفح البيطري',
            ],
        ];
    @endphp
    <section id="home-type-selector" class="page-shell">
        <div class="workspace-hero soft-grid">
            <div class="mx-auto max-w-3xl text-center lg:mx-0 lg:max-w-2xl lg:text-start">
                <span class="badge-brand">ابدأ من هنا</span>
                <h1 class="mt-4 max-w-2xl text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl">اختر نوع المنتجات التي ترغب في تصفحها</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">سيتم عرض التصنيفات والمنتجات المناسبة لاختيارك فقط، ويمكنك تغيير النوع من هنا في أي وقت.</p>
            </div>

            @if (session('success'))
                <div class="mx-auto mt-6 max-w-3xl rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @error('preferred_product_type')
                <div class="mx-auto mt-6 max-w-3xl rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                    {{ $message }}
                </div>
            @enderror

            <div class="mx-auto mt-8 grid max-w-5xl gap-4 lg:grid-cols-2">
                @foreach ($typeCards as $value => $type)
                    @php
                        $isSelected = $selectedHomeType === $value;
                    @endphp
                    <a
                        href="{{ route('product-type.select', ['preferred_product_type' => $value, 'redirect_to' => 'home']) }}"
                        class="group surface-card block h-full p-6 text-start transition duration-300 hover:-translate-y-1 hover:border-brand-300 hover:shadow-2xl focus:outline-none focus:ring-4 focus:ring-brand-500/20 {{ $isSelected ? 'border-brand-500 bg-white shadow-[0_28px_70px_-42px_rgba(5,150,105,0.45)] dark:bg-brand-500/12 dark:ring-1 dark:ring-brand-400/20' : 'border-white/20 bg-white/96 dark:border-slate-700/90 dark:bg-slate-950/90 dark:hover:border-brand-500' }}"
                    >
                        <span class="flex items-start gap-4">
                            <span class="icon-chip h-14 w-14 shrink-0 rounded-2xl">
                                <i class="{{ $type['icon'] }} text-2xl" aria-hidden="true"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-xl font-black text-gray-900 dark:text-white">{{ $type['label'] }}</span>
                                    <span class="rounded-full px-3 py-1 text-[11px] font-black {{ $isSelected ? 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $isSelected ? 'محدد الآن' : 'اختيار' }}
                                    </span>
                                </span>
                                <span class="mt-2 block text-sm leading-6 text-gray-600 dark:text-slate-200">{{ $type['description'] }}</span>
                                <span class="btn-primary mt-4 inline-flex px-4 py-2 text-xs">
                                    {{ $type['button'] }}
                                </span>
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if ($selectedHomeType)
    {{-- Step 1: pick a category (same page, links add ?category_id=) --}}
    <div id="sz-category-gate" class="{{ $homeCategoryId ? 'hidden' : '' }}">
        <x-home.categories />
    </div>

    {{-- Step 2: home browse for selected category --}}
    <div id="sz-main-store" class="{{ $homeCategoryId ? '' : 'hidden' }}">
        <section id="sz-category-bar" class="page-shell pt-0">
            <div class="surface-card-muted flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                <p class="text-sm text-gray-700 dark:text-slate-200">
                    {{ __('home.browsing_prefix') }}
                    <span id="sz-category-bar-name" class="font-bold text-gray-900 dark:text-white">—</span>
                </p>
                <a href="{{ route('home', ['type' => $selectedHomeType]) }}" class="btn-secondary btn-xs">
                    {{ __('home.change_category') }}
                </a>
            </div>
        </section>
        <x-home.promo-banner />
        <x-home.products />
        <x-home.best-selling-products />
        <x-home.most-favorited-products />
    </div>

        <x-home.trust-badges />
        <x-home.contact />
    @else
        <section class="page-shell">
            <div class="mx-auto max-w-3xl text-center">
                <p class="state-panel text-sm font-semibold text-gray-500 dark:text-gray-400">
                    اختر النوع أولا حتى تظهر التصنيفات والمنتجات المناسبة.
                </p>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
@php
    $homeScriptI18n = [
        'exploreTypes' => __('home.explore_product_types'),
        'couldNotLoad' => __('home.could_not_load'),
        'soldOut' => __('products.sold_out'),
        'addCart' => __('products.add_to_cart_btn'),
        'revOne' => __('home.review_one'),
        'revN' => __('home.reviews_n'),
        'contactSending' => __('home.contact_sending'),
        'contactSend' => __('home.contact_send'),
        'genericErr' => __('common.generic_error'),
    ];
@endphp
<script>
const homeI18n = @json($homeScriptI18n);
const selectedHomeType = @json($selectedHomeType);
document.addEventListener('DOMContentLoaded', async function () {
    const $ = id => document.getElementById(id);
    function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function categoryImageUrl(category) {
        if (category.image_url) {
            return category.image_url;
        }
        if (category.logo) {
            return '/storage/' + category.logo;
        }
        if (category.icon) {
            return '/storage/' + category.icon;
        }

        return '';
    }
    function typedUrl(url, key = 'type') {
        if (!selectedHomeType) {
            return url;
        }

        const parsed = new URL(url, window.location.origin);
        parsed.searchParams.set(key, selectedHomeType);

        return parsed.pathname + parsed.search;
    }
    function typedPageHref(path, params = {}) {
        const parsed = new URL(path, window.location.origin);
        if (selectedHomeType) {
            parsed.searchParams.set('type', selectedHomeType);
        }
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                parsed.searchParams.set(key, String(value));
            }
        });

        return parsed.pathname + parsed.search;
    }
    function categoryThumbInner(cat) {
        const imageUrl = categoryImageUrl(cat);
        if (imageUrl) {
            return `<img src="${esc(imageUrl)}" alt="" class="h-full w-full rounded-2xl object-cover">`;
        }
        return `<svg class="h-8 w-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/></svg>`;
    }
    function revLabel(n) {
        const c = parseInt(n, 10) || 0;
        if (c === 1) return homeI18n.revOne || '';
        return (homeI18n.revN || '').replace(':count', String(c));
    }
    function categoryTypeLabel(type) {
        if (type === 'agriculture') return 'زراعي';
        if (type === 'veterinary') return 'بيطري';
        return '';
    }

    const urlParams = new URLSearchParams(window.location.search);
    const selectedCategoryId = urlParams.get('category_id');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) { entry.target.style.opacity = '1'; entry.target.style.transform = 'translateY(0)'; observer.unobserve(entry.target); }
        });
    }, { threshold: 0.08 });

    function productsPageUrl(sort) {
        const p = new URLSearchParams();
        if (selectedHomeType) {
            p.set('type', selectedHomeType);
        }
        if (selectedCategoryId) {
            p.set('category_id', selectedCategoryId);
        }
        if (sort) {
            p.set('sort', sort);
        }
        const q = p.toString();

        return q ? ('/products?' + q) : '/products';
    }

    function updateListingLinks() {
        const pq = $('home-products-view-all');
        const pm = $('home-products-view-all-mobile');
        const bq = $('home-best-view-all');
        const fq = $('home-fav-view-all');
        if (pq) {
            pq.setAttribute('href', productsPageUrl(null));
        }
        if (pm) {
            pm.setAttribute('href', productsPageUrl(null));
        }
        if (bq) {
            bq.setAttribute('href', productsPageUrl('best_selling'));
        }
        if (fq) {
            fq.setAttribute('href', productsPageUrl('most_favorited'));
        }
    }

    let allCategories = [];

    async function loadCategories() {
        try {
            const res = await window.axios.get(typedUrl('/api/categories?per_page=100'));
            allCategories = res.data.data || [];
            $('cats-loading')?.classList.add('hidden');
            if (!allCategories.length) {
                return;
            }

            if (!selectedCategoryId) {
                const grid = $('cats-grid-gate');
                if (!grid) {
                    return;
                }
                grid.innerHTML = allCategories.map((cat, i) => {
                    const href = typedPageHref('/', { category_id: cat.id });

                    return `
                <a href="${href}" class="cat-card group overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm hover:shadow-2xl focus:outline-none focus:ring-4 focus:ring-brand-500/15 dark:border-gray-800 dark:bg-gray-900" style="opacity:0;transform:translateY(20px);transition:opacity .5s ease ${i * 0.06}s,transform .5s ease ${i * 0.06}s;">
                    <div class="flex items-center gap-4 p-4 sm:p-5">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 ring-1 ring-brand-200/50 transition-transform duration-300 group-hover:scale-110 sm:h-20 sm:w-20 dark:from-brand-500/10 dark:to-brand-500/5 dark:ring-brand-500/20">
                            ${categoryThumbInner(cat)}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-gray-900 group-hover:text-brand-600 sm:text-base dark:text-white dark:group-hover:text-brand-400">${esc(cat.name)}</h3>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">${esc(categoryTypeLabel(cat.type))}</p>
                        </div>
                        <svg class="h-5 w-5 shrink-0 text-gray-300 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-brand-500 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </div>
                </a>`;
                }).join('');
                grid.querySelectorAll('.cat-card').forEach(el => observer.observe(el));

                return;
            }

            const cat = allCategories.find(c => String(c.id) === String(selectedCategoryId));
            const barName = $('sz-category-bar-name');
            if (barName) {
                barName.textContent = cat ? cat.name : ('#' + selectedCategoryId);
            }
        } catch (e) {
            if ($('cats-loading')) {
                $('cats-loading').innerHTML = '<p class="text-sm text-gray-400">' + esc(homeI18n.couldNotLoad || '') + '</p>';
            }
        }
    }

    async function loadProducts() {
        $('products-loading')?.classList.remove('hidden');
        try {
            const apiUrl = selectedCategoryId
                ? ('/api/products?per_page=24&category_id=' + encodeURIComponent(selectedCategoryId))
                : '/api/products?per_page=5';
            const res = await window.axios.get(typedUrl(apiUrl, 'category_type'));
            const { data } = res.data;
            if (!data.length) { $('products-empty')?.classList.remove('hidden'); }
            else {
                const grid = $('products-grid');
                if (!grid) return;
                function starStars(rating) {
                    const r = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
                    const filled = '<svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                    const empty = '<svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                    let h = ''; for (let i = 0; i < 5; i++) h += i < r ? filled : empty; return h;
                }
                grid.innerHTML = data.map((p, i) => {
                    const photo = p.first_photo_url || p.fallback_photo_url || '{{ asset('images/product-placeholder.svg') }}', inStock = p.quantity > 0;
                    const unitPrice = p.has_active_discount ? p.discounted_price : p.price;
                    const typeLabel = categoryTypeLabel(p.category?.type);
                    const isFav = window._favIds && window._favIds.has(p.id);
                    const revCount = parseInt(p.review_count, 10) || 0;
                    return `
                    <div class="product-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900" style="opacity:0;transform:translateY(16px);transition:opacity .4s ease ${i *0.05}s,transform .4s ease ${i * 0.05}s;">
                        <div class="relative">
                            <a href="${typedPageHref('/products/' + p.id)}"><div class="relative aspect-[4/5] overflow-hidden bg-gray-50 dark:bg-gray-800">
                                ${photo ? `<img src="${esc(photo)}" alt="${esc(p.name)}" class="h-full w-full object-contain p-4 transition-transform duration-500 hover:scale-105" loading="lazy">` : `<div class="flex h-full items-center justify-center"><svg class="h-12 w-12 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg></div>`}
                                ${!inStock ? '<div class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70"><span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400">' + esc(homeI18n.soldOut || '') + '</span></div>' : ''}
                                ${p.has_active_discount ? `<div class="absolute left-2.5 top-2.5 z-10 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">-${parseFloat(p.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                            </div></a>
                            <button data-fav-btn="${p.id}" onclick="event.stopPropagation();window.toggleFav(${p.id},this)" class="absolute right-2.5 top-2.5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-all hover:scale-110 dark:bg-gray-900/90 ${isFav ? 'text-red-500' : 'text-gray-400 dark:text-gray-500'}"><svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav ? 'currentColor' : 'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button>
                        </div>
                        <div class="p-3 sm:p-4">
                            <a href="${typedPageHref('/products/' + p.id)}"><h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">${esc(p.name)}</h3></a>
                            ${typeLabel ? `<span class="mt-2 inline-flex rounded-lg bg-brand-50 px-2 py-1 text-[10px] font-black text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">${esc(typeLabel)}</span>` : ''}
                            <div class="mt-1.5 flex items-center gap-1.5 text-amber-400">${starStars(p.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${revCount ? esc(revLabel(revCount)) : ''}</span></div>
                            <div class="mt-2.5 flex items-baseline gap-1">
                                <span class="text-lg font-black ${p.has_active_discount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${parseFloat(p.has_active_discount ? p.discounted_price : p.price).toLocaleString()}</span><span class="text-[11px] text-gray-400">SYP</span>
                                ${p.has_active_discount ? `<span class="text-[11px] text-gray-400 line-through">${parseFloat(p.price).toLocaleString()} SYP</span>` : ''}
                            </div>
                            <button onclick="window.addToCart(${p.id},\`${esc(p.name)}\`,${unitPrice},\`${esc(photo)}\`)" class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition-all duration-200 ${inStock ? 'bg-gray-900 text-white hover:bg-brand-600 active:scale-[.97] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600'}" ${!inStock ? 'disabled' : ''}>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                                ${inStock ? esc(homeI18n.addCart || '') : esc(homeI18n.soldOut || '')}
                            </button>
                        </div>
                    </div>`;
                }).join('');
                grid.querySelectorAll('.product-card').forEach(el => observer.observe(el));
            }
        } catch (e) {
            if ($('products-empty')) {
                $('products-empty').textContent = homeI18n.genericErr || '';
                $('products-empty').classList.remove('hidden');
            }
        }
        $('products-loading')?.classList.add('hidden');
    }

    function starStars(rating) {
        const r = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
        const filled = '<svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        const empty = '<svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        let h = ''; for (let i = 0; i < 5; i++) h += i < r ? filled : empty; return h;
    }

    function renderProductCards(data, gridEl, emptyEl, loadingEl, startOpacity = 0) {
        if (!gridEl) return;
        if (loadingEl) loadingEl.classList.add('hidden');
        if (!Array.isArray(data) || data.length === 0) {
            if (emptyEl) emptyEl.classList.remove('hidden');
            return;
        }
        if (emptyEl) emptyEl.classList.add('hidden');
        gridEl.innerHTML = data.map((p, i) => {
            const photo = p.first_photo_url || p.fallback_photo_url || '{{ asset('images/product-placeholder.svg') }}', inStock = p.quantity > 0;
            const unitPrice = p.has_active_discount ? p.discounted_price : p.price;
            const isFav = window._favIds && window._favIds.has(p.id);
            const revCount = parseInt(p.review_count, 10) || 0;
            const typeLabel = categoryTypeLabel(p.category?.type);
            return `
            <div class="product-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900" style="opacity:0;transform:translateY(16px);transition:opacity .4s ease ${(startOpacity + i) * 0.05}s,transform .4s ease ${(startOpacity + i) * 0.05}s;">
                <div class="relative">
                    <a href="${typedPageHref('/products/' + p.id)}"><div class="relative aspect-[4/5] overflow-hidden bg-gray-50 dark:bg-gray-800">
                        ${photo ? `<img src="${esc(photo)}" alt="${esc(p.name)}" class="h-full w-full object-contain p-4 transition-transform duration-500 hover:scale-105" loading="lazy">` : `<div class="flex h-full items-center justify-center"><svg class="h-12 w-12 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg></div>`}
                        ${!inStock ? '<div class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70"><span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400">' + esc(homeI18n.soldOut || '') + '</span></div>' : ''}
                        ${p.has_active_discount ? `<div class="absolute left-2.5 top-2.5 z-10 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">-${parseFloat(p.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                    </div></a>
                    <button data-fav-btn="${p.id}" onclick="event.stopPropagation();window.toggleFav(${p.id},this)" class="absolute right-2.5 top-2.5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-all hover:scale-110 dark:bg-gray-900/90 ${isFav ? 'text-red-500' : 'text-gray-400 dark:text-gray-500'}"><svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav ? 'currentColor' : 'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button>
                </div>
                <div class="p-3 sm:p-4">
                    <a href="${typedPageHref('/products/' + p.id)}"><h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">${esc(p.name)}</h3></a>
                    ${typeLabel ? `<span class="mt-2 inline-flex rounded-lg bg-brand-50 px-2 py-1 text-[10px] font-black text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">${esc(typeLabel)}</span>` : ''}
                    <div class="mt-1.5 flex items-center gap-1.5 text-amber-400">${starStars(p.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${revCount ? esc(revLabel(revCount)) : ''}</span></div>
                    <div class="mt-2.5 flex items-baseline gap-1">
                        <span class="text-lg font-black ${p.has_active_discount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${parseFloat(p.has_active_discount ? p.discounted_price : p.price).toLocaleString()}</span><span class="text-[11px] text-gray-400">SYP</span>
                        ${p.has_active_discount ? `<span class="text-[11px] text-gray-400 line-through">${parseFloat(p.price).toLocaleString()} SYP</span>` : ''}
                    </div>
                    <button onclick="window.addToCart(${p.id},\`${esc(p.name)}\`,${unitPrice},\`${esc(photo)}\`)" class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition-all duration-200 ${inStock ? 'bg-gray-900 text-white hover:bg-brand-600 active:scale-[.97] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600'}" ${!inStock ? 'disabled' : ''}>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                        ${inStock ? esc(homeI18n.addCart || '') : esc(homeI18n.soldOut || '')}
                    </button>
                </div>
            </div>`;
        }).join('');
        gridEl.querySelectorAll('.product-card').forEach(el => observer.observe(el));
    }

    async function loadBestSelling() {
        const loadingEl = $('best-selling-loading'), gridEl = $('best-selling-grid'), emptyEl = $('best-selling-empty');
        if (!gridEl) return;
        try {
            const q = selectedCategoryId
                ? ('&category_id=' + encodeURIComponent(selectedCategoryId))
                : '';
            const res = await window.axios.get(typedUrl('/api/products?per_page=5&sort=best_selling' + q, 'category_type'));
            const data = res.data.data || [];
            renderProductCards(data, gridEl, emptyEl, loadingEl);
        } catch (e) { if (emptyEl) emptyEl.classList.remove('hidden'); }
        if (loadingEl) loadingEl.classList.add('hidden');
    }

    async function loadMostFavorited() {
        const loadingEl = $('most-favorited-loading'), gridEl = $('most-favorited-grid'), emptyEl = $('most-favorited-empty');
        if (!gridEl) return;
        try {
            const q = selectedCategoryId
                ? ('&category_id=' + encodeURIComponent(selectedCategoryId))
                : '';
            const res = await window.axios.get(typedUrl('/api/products?per_page=5&sort=most_favorited' + q, 'category_type'));
            const data = res.data.data || [];
            renderProductCards(data, gridEl, emptyEl, loadingEl);
        } catch (e) { if (emptyEl) emptyEl.classList.remove('hidden'); }
        if (loadingEl) loadingEl.classList.add('hidden');
    }

    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
        const user = window.Auth && window.Auth.getUser && window.Auth.getUser();
        if (user) {
            const nameEl = document.getElementById('contact-name');
            const emailEl = document.getElementById('contact-email');
            if (nameEl && !nameEl.value) nameEl.value = user.name || '';
            if (emailEl && !emailEl.value) emailEl.value = user.email || '';
        }
        contactForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submitBtn = document.getElementById('contact-submit');
            const btnText = submitBtn && submitBtn.querySelector('.contact-btn-text');
            const name = (document.getElementById('contact-name') && document.getElementById('contact-name').value) || '';
            const email = document.getElementById('contact-email') && document.getElementById('contact-email').value;
            const message = document.getElementById('contact-message') && document.getElementById('contact-message').value;
            document.getElementById('contact-success').classList.add('hidden');
            document.getElementById('contact-error').classList.add('hidden');
            ['name', 'email', 'message'].forEach(k => { const el = document.getElementById('contact-err-' + k); if (el) { el.classList.add('hidden'); el.textContent = ''; } });
            if (!email || !message) return;
            submitBtn.disabled = true;
            if (btnText) btnText.textContent = homeI18n.contactSending || '';
            try {
                await window.axios.post('/api/contact', { name: name.trim() || null, email: email.trim(), message: message.trim() });
                document.getElementById('contact-success').classList.remove('hidden');
                document.getElementById('contact-success').classList.add('flex');
                contactForm.reset();
                if (user) {
                    const nameEl = document.getElementById('contact-name');
                    const emailEl = document.getElementById('contact-email');
                    if (nameEl) nameEl.value = user.name || '';
                    if (emailEl) emailEl.value = user.email || '';
                }
            } catch (err) {
                if (err.response && err.response.status === 422 && err.response.data.errors) {
                    const errors = err.response.data.errors;
                    Object.keys(errors).forEach(key => {
                        const el = document.getElementById('contact-err-' + key);
                        if (el) { el.textContent = errors[key][0]; el.classList.remove('hidden'); }
                    });
                } else {
                    document.getElementById('contact-error').classList.remove('hidden');
                    document.getElementById('contact-error').classList.add('flex');
                    const msgEl = document.getElementById('contact-error-msg');
                    if (msgEl) msgEl.textContent = err.response?.data?.message || homeI18n.genericErr || '';
                }
            } finally {
                submitBtn.disabled = false;
                if (btnText) btnText.textContent = homeI18n.contactSend || '';
            }
        });
    }

    if (!selectedHomeType) {
        return;
    }

    await loadCategories();
    if (selectedCategoryId) {
        updateListingLinks();
        await Promise.all([loadProducts(), loadBestSelling(), loadMostFavorited()]);
    }
});
</script>
@endpush
