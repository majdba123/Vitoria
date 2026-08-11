@extends('layouts.app')

@section('title', __('Vetora') . ' ' . __('site.meta_title_separator') . ' ' . __('home.meta_title'))

@section('content')
    @php
        $selectedHomeType = app(\App\Services\SelectedProductTypeService::class)->resolve(request());
        $typeCards = [
            \App\Models\Category::TYPE_AGRICULTURE => [
                'label' => __('home.type_agriculture_label'),
                'description' => __('home.type_agriculture_description'),
                'icon' => 'fa-solid fa-seedling',
                'button' => __('home.type_agriculture_button'),
            ],
            \App\Models\Category::TYPE_VETERINARY => [
                'label' => __('home.type_veterinary_label'),
                'description' => __('home.type_veterinary_description'),
                'icon' => 'fa-solid fa-stethoscope',
                'button' => __('home.type_veterinary_button'),
            ],
        ];
    @endphp

    <x-home.hero />

    <section id="home-type-selector" class="page-shell pt-2 sm:pt-3">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @error('preferred_product_type')
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                {{ $message }}
            </div>
        @enderror

        <p class="mb-3 text-xs font-semibold uppercase tracking-wide" style="color: var(--color-text-secondary);">{{ __('home.explore_product_types') }}</p>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($typeCards as $value => $type)
                @php
                    $isSelected = $selectedHomeType === $value;
                @endphp
                <a
                    href="{{ route('product-type.select', ['preferred_product_type' => $value, 'redirect_to' => 'home']) }}"
                    class="cat-card group flex items-center gap-4 p-5 text-start focus:outline-none focus:ring-2 focus:ring-brand-500/20 {{ $isSelected ? 'border-brand-500' : '' }}"
                    @if ($isSelected) style="border-color: var(--color-brand); box-shadow: 0 0 0 1px var(--color-brand);" @endif
                >
                    <span class="icon-chip h-14 w-14 shrink-0 text-xl">
                        <i class="{{ $type['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="text-lg font-bold" style="color: var(--color-text);">{{ $type['label'] }}</span>
                            @if ($isSelected)
                                <span class="badge badge-brand">{{ __('home.type_selected_now') }}</span>
                            @endif
                        </span>
                        <span class="mt-1 block text-sm leading-6" style="color: var(--color-text-secondary);">{{ $type['description'] }}</span>
                    </span>
                    <svg class="h-4 w-4 shrink-0 rtl:-scale-x-100" style="color: var(--color-text-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            @endforeach
        </div>
    </section>

    @if ($selectedHomeType)
        <x-home.categories />

        <div id="sz-main-store" class="hidden">
            <section id="sz-category-bar" class="page-shell pt-0">
                <div class="surface-card-muted px-5 py-4 sm:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex min-w-0 items-center gap-4">
                            <div id="sz-category-bar-visual" class="shop-thumb-box hidden h-14 w-14 shrink-0 rounded-2xl ring-1 ring-brand-200/50 sm:flex dark:ring-brand-500/20"></div>
                            <div class="min-w-0">
                                <p class="commerce-kicker">{{ __('home.browsing_prefix') }}</p>
                                <h2 id="sz-category-bar-name" class="mt-2 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">—</h2>
                                <p id="sz-category-bar-meta" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>
                            </div>
                        </div>
                        <button type="button" id="sz-reset-category" class="btn-secondary btn-xs shrink-0">
                            {{ __('home.change_category') }}
                        </button>
                    </div>

                    <div id="sz-subcategory-shell" class="mt-5 hidden border-t border-gray-200/70 pt-4 dark:border-gray-800">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ __('home.subcategory_filter_title') }}</p>
                                <p id="sz-subcategory-helper" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('home.subcategory_filter_hint') }}</p>
                            </div>
                        </div>
                        <div id="sz-subcategory-list" class="flex gap-2 overflow-x-auto pb-1 lg:flex-wrap"></div>
                    </div>
                </div>
            </section>

            <x-home.products />
            <x-home.best-selling-products />
            <x-home.most-favorited-products />
        </div>

        <x-home.contact />
    @else
        <section class="page-shell">
            <div class="mx-auto max-w-3xl text-center">
                <p class="state-panel text-sm font-semibold text-gray-500 dark:text-gray-400">
                    {{ __('home.choose_type_first') }}
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
            'contactErrorDefault' => __('home.contact_error_default'),
            'genericErr' => __('common.generic_error'),
            'allSubcategories' => __('home.all_subcategories'),
            'allProducts' => __('home.all_products'),
            'noSubcategories' => __('home.no_subcategories_available'),
            'noProductsCategory' => __('home.no_products_in_category'),
            'noProductsSubcategory' => __('home.no_products_in_subcategory'),
            'categoryDirectProducts' => __('home.category_direct_products'),
            'subcategoryHint' => __('home.subcategory_filter_hint'),
            'categoryPreviewTitle' => __('home.category_preview_title'),
            'categoryHasSubcategories' => __('home.category_has_subcategories'),
            'categoryHasSubcategoriesMore' => __('home.category_has_subcategories_more'),
            'typeAgriculture' => __('home.type_agriculture_short'),
            'typeVeterinary' => __('home.type_veterinary_short'),
        ];
    @endphp
    <script>
        const homeI18n = @json($homeScriptI18n);
        const selectedHomeType = @json($selectedHomeType);
        const currentLocale = @json(app()->getLocale());

        document.addEventListener('DOMContentLoaded', async function () {
            const $ = (id) => document.getElementById(id);

            function esc(value) {
                if (!value) {
                    return '';
                }

                const element = document.createElement('div');
                element.textContent = value;

                return element.innerHTML;
            }

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

            function categoryThumbInner(category) {
                const imageUrl = categoryImageUrl(category);

                if (imageUrl) {
                    return `<img src="${esc(imageUrl)}" alt="" class="h-full w-full object-cover" loading="lazy">`;
                }

                return `<div class="shop-thumb-fallback"><svg class="h-8 w-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/></svg></div>`;
            }

            function subcategoryLabel(subcategory) {
                if (!subcategory) {
                    return '';
                }

                if (currentLocale === 'ar') {
                    return subcategory.name_ar || subcategory.name_en || '';
                }

                return subcategory.name_en || subcategory.name_ar || '';
            }

            function revLabel(count) {
                const resolvedCount = parseInt(count, 10) || 0;

                if (resolvedCount === 1) {
                    return homeI18n.revOne || '';
                }

                return (homeI18n.revN || '').replace(':count', String(resolvedCount));
            }

            function categoryTypeLabel(type) {
                if (type === 'agriculture') {
                    return homeI18n.typeAgriculture || '';
                }

                if (type === 'veterinary') {
                    return homeI18n.typeVeterinary || '';
                }

                return '';
            }

            function categorySubcategorySummary(subcategories) {
                const count = Array.isArray(subcategories) ? subcategories.length : 0;

                if (count <= 0) {
                    return homeI18n.categoryDirectProducts || '';
                }

                return (homeI18n.categoryHasSubcategories || '').replace(':count', String(count));
            }

            const urlParams = new URLSearchParams(window.location.search);
            let currentSelectedCategoryId = urlParams.get('category_id');
            let currentSelectedSubcategoryId = urlParams.get('subcategory_id');
            let allCategories = [];
            let activeCategory = null;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08 });

            function syncBrowserUrl() {
                const nextUrl = new URL(window.location.href);

                if (selectedHomeType) {
                    nextUrl.searchParams.set('type', selectedHomeType);
                }

                if (currentSelectedCategoryId) {
                    nextUrl.searchParams.set('category_id', currentSelectedCategoryId);
                } else {
                    nextUrl.searchParams.delete('category_id');
                }

                if (currentSelectedSubcategoryId) {
                    nextUrl.searchParams.set('subcategory_id', currentSelectedSubcategoryId);
                } else {
                    nextUrl.searchParams.delete('subcategory_id');
                }

                window.history.replaceState({}, '', nextUrl.pathname + nextUrl.search);
            }

            function productsPageUrl(sort) {
                const params = {};

                if (currentSelectedCategoryId) {
                    params.category_id = currentSelectedCategoryId;
                }

                if (currentSelectedSubcategoryId) {
                    params.subcategory_id = currentSelectedSubcategoryId;
                }

                if (sort) {
                    params.sort = sort;
                }

                return typedPageHref('/products', params);
            }

            function updateListingLinks() {
                const latestDesktop = $('home-products-view-all');
                const latestMobile = $('home-products-view-all-mobile');
                const bestSelling = $('home-best-view-all');
                const mostFavorited = $('home-fav-view-all');

                if (latestDesktop) {
                    latestDesktop.setAttribute('href', productsPageUrl(null));
                }
                if (latestMobile) {
                    latestMobile.setAttribute('href', productsPageUrl(null));
                }
                if (bestSelling) {
                    bestSelling.setAttribute('href', productsPageUrl('best_selling'));
                }
                if (mostFavorited) {
                    mostFavorited.setAttribute('href', productsPageUrl('most_favorited'));
                }
            }

            function updateHomeStateVisibility() {
                const store = $('sz-main-store');
                if (!store) {
                    return;
                }

                if (currentSelectedCategoryId) {
                    store.classList.remove('hidden');
                } else {
                    store.classList.add('hidden');
                }
            }

            function renderSelectedCategory(category) {
                const barName = $('sz-category-bar-name');
                const barMeta = $('sz-category-bar-meta');
                const barVisual = $('sz-category-bar-visual');

                if (barName) {
                    barName.textContent = category ? category.name : '—';
                }

                if (!category) {
                    if (barMeta) {
                        barMeta.textContent = '';
                    }
                    if (barVisual) {
                        barVisual.classList.add('hidden');
                        barVisual.innerHTML = '';
                    }
                    $('sz-subcategory-shell')?.classList.add('hidden');

                    return;
                }

                if (barMeta) {
                    barMeta.textContent = categorySubcategorySummary(category.subcategories);
                }

                if (barVisual) {
                    barVisual.classList.remove('hidden');
                    barVisual.innerHTML = categoryThumbInner(category);
                }

                renderSubcategoryRail(category);
            }

            function renderSubcategoryRail(category) {
                const shell = $('sz-subcategory-shell');
                const list = $('sz-subcategory-list');
                const helper = $('sz-subcategory-helper');

                if (!shell || !list) {
                    return;
                }

                const subcategories = Array.isArray(category.subcategories) ? category.subcategories : [];
                const validSubcategory = subcategories.find((subcategory) => String(subcategory.id) === String(currentSelectedSubcategoryId));
                currentSelectedSubcategoryId = validSubcategory ? String(validSubcategory.id) : null;

                if (!subcategories.length) {
                    shell.classList.add('hidden');
                    list.innerHTML = '';
                    return;
                }

                shell.classList.remove('hidden');
                if (helper) {
                    helper.textContent = homeI18n.subcategoryHint || '';
                }

                const items = [];
                items.push(
                    `<button type="button" data-home-all-subcategories="1" class="shop-nav-chip inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 py-2 text-sm font-bold transition ${currentSelectedSubcategoryId ? 'border-gray-200 bg-white text-gray-700 hover:border-brand-300 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500 dark:hover:text-brand-300' : 'is-active border-brand-500 bg-brand-500 text-white shadow-sm shadow-brand-500/20'}" ${currentSelectedSubcategoryId ? '' : 'aria-current="true"'}>${esc(homeI18n.allSubcategories || '')}</button>`
                );

                subcategories.forEach((subcategory) => {
                    const isActive = String(currentSelectedSubcategoryId) === String(subcategory.id);

                    items.push(
                        `<button type="button" data-home-subcategory="${subcategory.id}" class="shop-nav-chip inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 py-2 text-sm font-bold text-start transition ${isActive ? 'is-active border-brand-500 bg-brand-500 text-white shadow-sm shadow-brand-500/20' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500 dark:hover:text-brand-300'}" ${isActive ? 'aria-current="true"' : ''}>${esc(subcategoryLabel(subcategory))}</button>`
                    );
                });

                list.innerHTML = items.join('');
            }

            function highlightCategoryCard() {
                document.querySelectorAll('[data-home-category-card]').forEach((card) => {
                    const isActive = String(card.getAttribute('data-home-category-card')) === String(currentSelectedCategoryId);
                    card.classList.toggle('ring-2', isActive);
                    card.classList.toggle('ring-brand-500/30', isActive);
                    card.classList.toggle('border-brand-300', isActive);
                    card.classList.toggle('dark:border-brand-500/40', isActive);
                });
            }

            async function setHomeSelection(categoryId = null, subcategoryId = null, options = {}) {
                currentSelectedCategoryId = categoryId ? String(categoryId) : null;
                currentSelectedSubcategoryId = subcategoryId ? String(subcategoryId) : null;
                activeCategory = currentSelectedCategoryId
                    ? allCategories.find((category) => String(category.id) === String(currentSelectedCategoryId)) || null
                    : null;

                if (!activeCategory) {
                    currentSelectedSubcategoryId = null;
                }

                updateHomeStateVisibility();
                renderSelectedCategory(activeCategory);
                highlightCategoryCard();
                updateListingLinks();
                syncBrowserUrl();

                if (!currentSelectedCategoryId) {
                    return;
                }

                if (options.scrollToStore !== false) {
                    $('sz-category-bar')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                await Promise.all([loadProducts(), loadBestSelling(), loadMostFavorited()]);
            }

            async function loadCategories() {
                try {
                    const response = await window.axios.get(typedUrl('/api/categories?per_page=100'));
                    allCategories = response.data.data || [];
                    $('cats-loading')?.classList.add('hidden');

                    if (!allCategories.length) {
                        return;
                    }

                    const grid = $('cats-grid-gate');
                    if (!grid) {
                        return;
                    }

                    grid.innerHTML = allCategories.map((category, index) => {
                        const subcategories = Array.isArray(category.subcategories) ? category.subcategories : [];
                        const preview = subcategories.slice(0, 3).map((subcategory) => {
                            return `<button type="button" data-home-category="${category.id}" data-home-subcategory="${subcategory.id}" class="shop-nav-chip inline-flex min-h-8 items-center rounded-md border px-2.5 py-1 text-[11px] font-medium focus:outline-none focus:ring-2 focus:ring-brand-500/20" style="border-color: var(--color-border); color: var(--color-text-secondary);">${esc(subcategoryLabel(subcategory))}</button>`;
                        }).join('');
                        const moreCount = Math.max(subcategories.length - 3, 0);
                        const moreLabel = moreCount
                            ? (homeI18n.categoryHasSubcategoriesMore || '').replace(':count', String(moreCount))
                            : '';

                        return `
                            <article data-home-category-card="${category.id}" class="storefront-category-card cat-card group flex h-full flex-col" style="opacity:0;transform:translateY(14px);transition:opacity .4s ease ${index * 0.05}s,transform .4s ease ${index * 0.05}s;">
                                <button type="button" data-home-category="${category.id}" class="flex flex-1 flex-col text-start focus:outline-none">
                                    <div class="shop-card-media" style="aspect-ratio: 16/9;">
                                        ${categoryThumbInner(category)}
                                    </div>
                                    <div class="flex flex-1 flex-col gap-1 p-4">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-base font-bold leading-snug" style="color: var(--color-text);">${esc(category.name)}</h3>
                                            ${categoryTypeLabel(category.type) ? `<span class="badge badge-brand shrink-0">${esc(categoryTypeLabel(category.type))}</span>` : ''}
                                        </div>
                                        <p class="text-xs" style="color: var(--color-text-muted);">${esc(categorySubcategorySummary(subcategories))}</p>
                                    </div>
                                </button>
                                ${subcategories.length ? `
                                    <div class="flex flex-wrap gap-1.5 border-t px-4 py-3" style="border-color: var(--color-border);">
                                        ${preview}
                                        ${moreCount ? `<span class="inline-flex items-center rounded-md px-2 py-1 text-[11px] font-semibold" style="background: var(--color-surface-muted); color: var(--color-text-muted);">${esc(moreLabel)}</span>` : ''}
                                    </div>
                                ` : ''}
                            </article>
                        `;
                    }).join('');

                    grid.querySelectorAll('.cat-card').forEach((element) => observer.observe(element));
                    highlightCategoryCard();

                    if (currentSelectedCategoryId) {
                        await setHomeSelection(currentSelectedCategoryId, currentSelectedSubcategoryId, { scrollToStore: false });
                    }
                } catch (error) {
                    if ($('cats-loading')) {
                        $('cats-loading').innerHTML = '<p class="text-sm text-gray-400">' + esc(homeI18n.couldNotLoad || '') + '</p>';
                    }
                }
            }

            function starStars(rating) {
                const resolved = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
                const filled = '<svg class="h-3.5 w-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                const empty = '<svg class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                let html = '';

                for (let index = 0; index < 5; index++) {
                    html += index < resolved ? filled : empty;
                }

                return html;
            }

            function renderProductCards(data, gridElement, emptyElement, loadingElement, startOpacity = 0) {
                if (!gridElement) {
                    return;
                }

                if (loadingElement) {
                    loadingElement.classList.add('hidden');
                }

                if (!Array.isArray(data) || data.length === 0) {
                    if (emptyElement) {
                        emptyElement.classList.remove('hidden');
                    }

                    return;
                }

                if (emptyElement) {
                    emptyElement.classList.add('hidden');
                }

                gridElement.innerHTML = data.map((product, index) => {
                    const photo = product.first_photo_url || product.fallback_photo_url || '{{ asset('images/product-placeholder.svg') }}';
                    const inStock = product.quantity > 0;
                    const unitPrice = product.has_active_discount ? product.discounted_price : product.price;
                    const isFav = window._favIds && window._favIds.has(product.id);
                    const reviewCount = parseInt(product.review_count, 10) || 0;
                    const subcategoryName = subcategoryLabel(product.subcategory);
                    const commercialName = product.commercial_name || '';

                    return `
                        <div class="product-card" style="opacity:0;transform:translateY(12px);transition:opacity .35s ease ${(startOpacity + index) * 0.04}s,transform .35s ease ${(startOpacity + index) * 0.04}s;">
                            <a href="${typedPageHref('/products/' + product.id)}" class="product-card-media block">
                                <img src="${esc(photo)}" alt="${esc(product.name)}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}'">
                                ${!inStock ? '<div class="absolute inset-0 flex items-center justify-center bg-white/75 dark:bg-gray-900/75"><span class="badge badge-danger">' + esc(homeI18n.soldOut || '') + '</span></div>' : ''}
                                ${product.has_active_discount ? `<span class="product-card-badge">-${parseFloat(product.discount_percentage || 0).toFixed(0)}%</span>` : ''}
                            </a>
                            <button type="button" data-fav-btn="${product.id}" onclick="event.stopPropagation();window.toggleFav(${product.id},this)" class="product-card-fav ${isFav ? 'is-active' : ''}" aria-label="${esc(product.name)}" aria-pressed="${isFav ? 'true' : 'false'}"><svg class="h-4 w-4" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav ? 'currentColor' : 'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button>
                            <div class="product-card-body">
                                ${subcategoryName ? `<span class="product-card-context">${esc(subcategoryName)}</span>` : ''}
                                <a href="${typedPageHref('/products/' + product.id)}"><h3 class="product-card-title">${esc(product.name)}</h3></a>
                                ${commercialName ? `<p class="truncate text-xs" style="color: var(--color-text-muted);">${esc(commercialName)}</p>` : ''}
                                <div class="product-card-rating">${starStars(product.average_rating)}<span>${reviewCount ? esc(revLabel(reviewCount)) : ''}</span></div>
                                <div class="product-card-footer">
                                    <div class="product-card-price-group">
                                        <span class="product-card-price">${parseFloat(product.has_active_discount ? product.discounted_price : product.price).toLocaleString()} <span class="text-xs font-medium" style="color: var(--color-text-muted);">SYP</span></span>
                                        ${product.has_active_discount ? `<span class="product-card-price-was">${parseFloat(product.price).toLocaleString()} SYP</span>` : ''}
                                        <span class="product-card-stock ${inStock ? '' : 'is-out'}">${inStock ? '' : esc(homeI18n.soldOut || '')}</span>
                                    </div>
                                    <button type="button" onclick="window.addToCart(${product.id},\`${esc(product.name)}\`,${unitPrice},\`${esc(photo)}\`)" class="product-card-cta" aria-label="${esc(homeI18n.addCart || '')}: ${esc(product.name)}" ${!inStock ? 'disabled' : ''}>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                gridElement.querySelectorAll('.product-card').forEach((element) => observer.observe(element));
            }

            async function loadProducts() {
                $('products-loading')?.classList.remove('hidden');

                if ($('products-empty')) {
                    $('products-empty').classList.add('hidden');
                    $('products-empty').textContent = currentSelectedSubcategoryId
                        ? (homeI18n.noProductsSubcategory || '')
                        : (homeI18n.noProductsCategory || '');
                }

                try {
                    if (!currentSelectedCategoryId) {
                        $('products-grid').innerHTML = '';
                        $('products-loading')?.classList.add('hidden');
                        return;
                    }

                    const apiUrl = '/api/products?per_page=24&category_id=' + encodeURIComponent(currentSelectedCategoryId) + (currentSelectedSubcategoryId ? '&subcategory_id=' + encodeURIComponent(currentSelectedSubcategoryId) : '');
                    const response = await window.axios.get(typedUrl(apiUrl, 'category_type'));
                    const data = response.data.data || [];
                    const grid = $('products-grid');

                    if (!data.length) {
                        $('products-empty')?.classList.remove('hidden');
                    } else if (grid) {
                        renderProductCards(data, grid, $('products-empty'), $('products-loading'));
                    }
                } catch (error) {
                    if ($('products-empty')) {
                        $('products-empty').textContent = homeI18n.genericErr || '';
                        $('products-empty').classList.remove('hidden');
                    }
                }

                $('products-loading')?.classList.add('hidden');
            }

            async function loadBestSelling() {
                const loadingElement = $('best-selling-loading');
                const gridElement = $('best-selling-grid');
                const emptyElement = $('best-selling-empty');

                if (!gridElement || !currentSelectedCategoryId) {
                    return;
                }

                try {
                    const extraFilters = '&category_id=' + encodeURIComponent(currentSelectedCategoryId) + (currentSelectedSubcategoryId ? '&subcategory_id=' + encodeURIComponent(currentSelectedSubcategoryId) : '');
                    const response = await window.axios.get(typedUrl('/api/products?per_page=5&sort=best_selling' + extraFilters, 'category_type'));
                    renderProductCards(response.data.data || [], gridElement, emptyElement, loadingElement);
                } catch (error) {
                    emptyElement?.classList.remove('hidden');
                }

                loadingElement?.classList.add('hidden');
            }

            async function loadMostFavorited() {
                const loadingElement = $('most-favorited-loading');
                const gridElement = $('most-favorited-grid');
                const emptyElement = $('most-favorited-empty');

                if (!gridElement || !currentSelectedCategoryId) {
                    return;
                }

                try {
                    const extraFilters = '&category_id=' + encodeURIComponent(currentSelectedCategoryId) + (currentSelectedSubcategoryId ? '&subcategory_id=' + encodeURIComponent(currentSelectedSubcategoryId) : '');
                    const response = await window.axios.get(typedUrl('/api/products?per_page=5&sort=most_favorited' + extraFilters, 'category_type'));
                    renderProductCards(response.data.data || [], gridElement, emptyElement, loadingElement);
                } catch (error) {
                    emptyElement?.classList.remove('hidden');
                }

                loadingElement?.classList.add('hidden');
            }

            const categoriesGrid = $('cats-grid-gate');
            categoriesGrid?.addEventListener('click', async function (event) {
                const button = event.target.closest('[data-home-category], [data-home-subcategory]');
                if (!button) {
                    return;
                }

                event.preventDefault();
                const categoryId = button.getAttribute('data-home-category');
                const subcategoryId = button.getAttribute('data-home-subcategory');
                await setHomeSelection(categoryId, subcategoryId, { scrollToStore: true });
            });

            $('sz-subcategory-list')?.addEventListener('click', async function (event) {
                const allButton = event.target.closest('[data-home-all-subcategories]');
                const subcategoryButton = event.target.closest('[data-home-subcategory]');

                if (allButton) {
                    event.preventDefault();
                    await setHomeSelection(currentSelectedCategoryId, null, { scrollToStore: false });
                    return;
                }

                if (!subcategoryButton) {
                    return;
                }

                event.preventDefault();
                await setHomeSelection(currentSelectedCategoryId, subcategoryButton.getAttribute('data-home-subcategory'), { scrollToStore: false });
            });

            $('sz-reset-category')?.addEventListener('click', async function () {
                await setHomeSelection(null, null, { scrollToStore: false });
                document.getElementById('categories')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            const contactForm = document.getElementById('contact-form');
            if (contactForm) {
                const user = window.Auth && window.Auth.getUser && window.Auth.getUser();
                if (user) {
                    const nameElement = document.getElementById('contact-name');
                    const emailElement = document.getElementById('contact-email');
                    if (nameElement && !nameElement.value) {
                        nameElement.value = user.name || '';
                    }
                    if (emailElement && !emailElement.value) {
                        emailElement.value = user.email || '';
                    }
                }

                contactForm.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    const submitButton = document.getElementById('contact-submit');
                    const buttonText = submitButton && submitButton.querySelector('.contact-btn-text');
                    const name = document.getElementById('contact-name')?.value || '';
                    const email = document.getElementById('contact-email')?.value || '';
                    const message = document.getElementById('contact-message')?.value || '';

                    document.getElementById('contact-success')?.classList.add('hidden');
                    document.getElementById('contact-error')?.classList.add('hidden');

                    ['name', 'email', 'message'].forEach((key) => {
                        const element = document.getElementById('contact-err-' + key);
                        if (element) {
                            element.classList.add('hidden');
                            element.textContent = '';
                        }
                    });

                    if (!email || !message) {
                        return;
                    }

                    submitButton.disabled = true;
                    if (buttonText) {
                        buttonText.textContent = homeI18n.contactSending || '';
                    }

                    try {
                        await window.axios.post('/api/contact', {
                            name: name.trim() || null,
                            email: email.trim(),
                            message: message.trim(),
                        });

                        document.getElementById('contact-success')?.classList.remove('hidden');
                        document.getElementById('contact-success')?.classList.add('flex');
                        contactForm.reset();

                        if (user) {
                            const nameElement = document.getElementById('contact-name');
                            const emailElement = document.getElementById('contact-email');
                            if (nameElement) {
                                nameElement.value = user.name || '';
                            }
                            if (emailElement) {
                                emailElement.value = user.email || '';
                            }
                        }
                    } catch (error) {
                        if (error.response && error.response.status === 422 && error.response.data.errors) {
                            Object.keys(error.response.data.errors).forEach((key) => {
                                const element = document.getElementById('contact-err-' + key);
                                if (element) {
                                    element.textContent = error.response.data.errors[key][0];
                                    element.classList.remove('hidden');
                                }
                            });
                        } else {
                            document.getElementById('contact-error')?.classList.remove('hidden');
                            document.getElementById('contact-error')?.classList.add('flex');
                            const messageElement = document.getElementById('contact-error-msg');
                            if (messageElement) {
                                messageElement.textContent = error.response?.data?.message || homeI18n.contactErrorDefault || '';
                            }
                        }
                    } finally {
                        submitButton.disabled = false;
                        if (buttonText) {
                            buttonText.textContent = homeI18n.contactSend || '';
                        }
                    }
                });
            }

            if (!selectedHomeType) {
                return;
            }

            await loadCategories();
            updateHomeStateVisibility();
            updateListingLinks();
        });
    </script>
@endpush
