@extends('layouts.app')

@section('title', __('Vetora') . ' ' . __('site.meta_title_separator') . ' ' . __('home.meta_title'))

@section('content')
    @php
        $homeCategoryId = request()->query('category_id');
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

    <section id="home-type-selector" class="page-shell">
        <div class="surface-card overflow-hidden p-5 sm:p-7 lg:p-8">
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

            <div class="mt-8 grid gap-4 lg:grid-cols-2">
                @foreach ($typeCards as $value => $type)
                    @php
                        $isSelected = $selectedHomeType === $value;
                    @endphp
                    <a
                        href="{{ route('product-type.select', ['preferred_product_type' => $value, 'redirect_to' => 'home']) }}"
                        class="group block h-full rounded-[28px] border p-6 text-start transition duration-300 hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-brand-500/15 {{ $isSelected ? 'border-brand-500 bg-brand-50/70 shadow-[0_20px_45px_-30px_rgba(5,150,105,0.45)] dark:border-brand-400 dark:bg-brand-500/10' : 'border-gray-200 bg-white hover:border-brand-300 dark:border-gray-800 dark:bg-gray-950/80 dark:hover:border-brand-500' }}"
                    >
                        <span class="flex h-full flex-col gap-5">
                            <span class="flex items-center justify-between gap-4">
                                <span class="flex h-16 w-16 items-center justify-center rounded-3xl {{ $isSelected ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/25' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                                    <i class="{{ $type['icon'] }} text-2xl" aria-hidden="true"></i>
                                </span>
                                <span class="rounded-full px-3 py-1 text-[11px] font-black {{ $isSelected ? 'bg-white text-brand-700 ring-1 ring-brand-200 dark:bg-brand-400/15 dark:text-brand-200 dark:ring-brand-400/20' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300' }}">
                                    {{ $isSelected ? __('home.type_selected_now') : __('home.type_choose') }}
                                </span>
                            </span>
                            <span class="block min-w-0 flex-1">
                                <span class="block text-2xl font-black text-gray-950 dark:text-white">{{ $type['label'] }}</span>
                                <span class="mt-3 block text-sm leading-7 text-gray-600 dark:text-gray-300">{{ $type['description'] }}</span>
                            </span>
                            <span class="flex items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                                <span class="text-xs font-bold uppercase tracking-[0.16em] text-gray-400 dark:text-gray-500">Vetora</span>
                                <span class="inline-flex items-center gap-2 rounded-2xl bg-gray-950 px-4 py-2.5 text-xs font-black text-white dark:bg-white dark:text-gray-950">
                                    {{ $type['button'] }}
                                    <svg class="h-3.5 w-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5l6 7.5-6 7.5m6-7.5h-15"/></svg>
                                </span>
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if ($selectedHomeType)
        <div id="sz-category-gate" class="{{ $homeCategoryId ? 'hidden' : '' }}">
            <x-home.categories />
        </div>

        <div id="sz-main-store" class="{{ $homeCategoryId ? '' : 'hidden' }}">
            <section id="sz-category-bar" class="page-shell pt-0">
                <div class="surface-card-muted px-5 py-4 sm:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex min-w-0 items-center gap-4">
                            <div id="sz-category-bar-visual" class="shop-thumb-box hidden h-14 w-14 shrink-0 rounded-2xl ring-1 ring-brand-200/50 sm:flex dark:ring-brand-500/20"></div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-brand-600 dark:text-brand-300">{{ __('home.browsing_prefix') }}</p>
                                <h2 id="sz-category-bar-name" class="mt-2 text-xl font-black text-gray-900 dark:text-white sm:text-2xl">—</h2>
                                <p id="sz-category-bar-meta" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>
                            </div>
                        </div>
                        <a href="{{ route('home', ['type' => $selectedHomeType]) }}" class="btn-secondary btn-xs shrink-0">
                            {{ __('home.change_category') }}
                        </a>
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
            const selectedCategoryId = urlParams.get('category_id');
            const selectedSubcategoryId = urlParams.get('subcategory_id');

            let allCategories = [];
            let activeCategory = null;
            window.__activeHomeSubcategoryId = null;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08 });

            function productsPageUrl(sort) {
                const params = new URLSearchParams();

                if (selectedHomeType) {
                    params.set('type', selectedHomeType);
                }

                if (selectedCategoryId) {
                    params.set('category_id', selectedCategoryId);
                }

                if (window.__activeHomeSubcategoryId) {
                    params.set('subcategory_id', window.__activeHomeSubcategoryId);
                }

                if (sort) {
                    params.set('sort', sort);
                }

                const query = params.toString();

                return query ? '/products?' + query : '/products';
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

            function renderSelectedCategory(category) {
                const barName = $('sz-category-bar-name');
                const barMeta = $('sz-category-bar-meta');
                const barVisual = $('sz-category-bar-visual');

                if (barName) {
                    barName.textContent = category ? category.name : ('#' + selectedCategoryId);
                }

                if (!category) {
                    if (barMeta) {
                        barMeta.textContent = '';
                    }
                    if (barVisual) {
                        barVisual.classList.add('hidden');
                        barVisual.innerHTML = '';
                    }

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
                const validSubcategory = subcategories.find((subcategory) => String(subcategory.id) === String(selectedSubcategoryId));
                window.__activeHomeSubcategoryId = validSubcategory ? String(validSubcategory.id) : null;

                if (!subcategories.length) {
                    shell.classList.add('hidden');
                    return;
                }

                shell.classList.remove('hidden');
                if (helper) {
                    helper.textContent = homeI18n.subcategoryHint || '';
                }

                const allHref = typedPageHref('/', { category_id: category.id });
                const items = [];
                items.push(
                    `<a href="${allHref}" class="inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 py-2 text-sm font-bold transition ${window.__activeHomeSubcategoryId ? 'border-gray-200 bg-white text-gray-700 hover:border-brand-300 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500 dark:hover:text-brand-300' : 'border-brand-500 bg-brand-500 text-white shadow-sm shadow-brand-500/20'}" ${window.__activeHomeSubcategoryId ? '' : 'aria-current="true"'}>${esc(homeI18n.allSubcategories || '')}</a>`
                );

                subcategories.forEach((subcategory) => {
                    const isActive = String(window.__activeHomeSubcategoryId) === String(subcategory.id);
                    const href = typedPageHref('/', { category_id: category.id, subcategory_id: subcategory.id });

                    items.push(
                        `<a href="${href}" class="inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 py-2 text-sm font-bold text-start transition ${isActive ? 'border-brand-500 bg-brand-500 text-white shadow-sm shadow-brand-500/20' : 'border-gray-200 bg-white text-gray-700 hover:border-brand-300 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500 dark:hover:text-brand-300'}" ${isActive ? 'aria-current="true"' : ''}>${esc(subcategoryLabel(subcategory))}</a>`
                    );
                });

                list.innerHTML = items.join('');
            }

            async function loadCategories() {
                try {
                    const response = await window.axios.get(typedUrl('/api/categories?per_page=100'));
                    allCategories = response.data.data || [];
                    $('cats-loading')?.classList.add('hidden');

                    if (!allCategories.length) {
                        return;
                    }

                    if (!selectedCategoryId) {
                        const grid = $('cats-grid-gate');
                        if (!grid) {
                            return;
                        }

                        grid.innerHTML = allCategories.map((category, index) => {
                            const href = typedPageHref('/', { category_id: category.id });
                            const subcategories = Array.isArray(category.subcategories) ? category.subcategories : [];
                            const preview = subcategories.slice(0, 4).map((subcategory) => {
                                const subcategoryHref = typedPageHref('/', { category_id: category.id, subcategory_id: subcategory.id });

                                return `<a href="${subcategoryHref}" class="inline-flex min-h-9 items-center rounded-full bg-gray-100 px-3 py-1.5 text-[11px] font-semibold text-gray-700 transition hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-500/10 dark:hover:text-brand-300">${esc(subcategoryLabel(subcategory))}</a>`;
                            }).join('');
                            const moreCount = Math.max(subcategories.length - 4, 0);
                            const moreLabel = moreCount
                                ? (homeI18n.categoryHasSubcategoriesMore || '').replace(':count', String(moreCount))
                                : '';

                            return `
                                <article class="cat-card group flex h-full flex-col overflow-hidden rounded-[26px] border border-gray-200/80 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-2xl dark:border-gray-800 dark:bg-gray-900" style="opacity:0;transform:translateY(20px);transition:opacity .5s ease ${index * 0.06}s,transform .5s ease ${index * 0.06}s;">
                                    <div class="flex items-start gap-4 p-4 sm:p-5">
                                        <div class="shop-thumb-box h-16 w-16 shrink-0 ring-1 ring-brand-200/50 transition-transform duration-300 group-hover:scale-110 sm:h-20 sm:w-20 dark:ring-brand-500/20">
                                            ${categoryThumbInner(category)}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="${href}" class="text-sm font-bold text-gray-900 transition hover:text-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/20 sm:text-base dark:text-white dark:hover:text-brand-400">${esc(category.name)}</a>
                                                ${categoryTypeLabel(category.type) ? `<span class="inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-black text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">${esc(categoryTypeLabel(category.type))}</span>` : ''}
                                            </div>
                                            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">${esc(categorySubcategorySummary(subcategories))}</p>
                                        </div>
                                        <a href="${href}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-gray-200 text-gray-400 transition hover:border-brand-300 hover:text-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:text-gray-500 dark:hover:border-brand-500 dark:hover:text-brand-300">
                                            <svg class="h-5 w-5 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                        </a>
                                    </div>
                                    ${subcategories.length ? `
                                        <div class="border-t border-gray-100 px-4 py-4 dark:border-gray-800 sm:px-5">
                                            <p class="mb-3 text-[11px] font-black uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">${esc(homeI18n.categoryPreviewTitle || '')}</p>
                                            <div class="flex flex-wrap gap-2">
                                                ${preview}
                                                ${moreCount ? `<span class="inline-flex items-center rounded-full bg-gray-950 px-2.5 py-1 text-[11px] font-bold text-white dark:bg-white dark:text-gray-900">${esc(moreLabel)}</span>` : ''}
                                            </div>
                                        </div>
                                    ` : ''}
                                    <div class="border-t border-gray-100 px-4 py-4 dark:border-gray-800 sm:px-5">
                                        <a href="${href}" class="inline-flex items-center gap-2 text-sm font-bold text-brand-600 transition hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:text-brand-400 dark:hover:text-brand-300">
                                            ${esc(homeI18n.categoryOpen || '')}
                                            <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                        </a>
                                    </div>
                                </article>
                            `;
                        }).join('');

                        grid.querySelectorAll('.cat-card').forEach((element) => observer.observe(element));

                        return;
                    }

                    activeCategory = allCategories.find((category) => String(category.id) === String(selectedCategoryId)) || null;
                    renderSelectedCategory(activeCategory);
                    updateListingLinks();
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
                    const barcode = Array.isArray(product.barcodes) && product.barcodes.length ? product.barcodes[0] : '';
                    const commercialName = product.commercial_name || '';

                    return `
                        <div class="product-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900" style="opacity:0;transform:translateY(16px);transition:opacity .4s ease ${(startOpacity + index) * 0.05}s,transform .4s ease ${(startOpacity + index) * 0.05}s;">
                            <div class="relative">
                                <a href="${typedPageHref('/products/' + product.id)}"><div class="shop-card-media">
                                    ${photo ? `<img src="${esc(photo)}" alt="${esc(product.name)}" class="shop-card-media-img" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}'">` : `<div class="shop-card-media-fallback"><svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg></div>`}
                                    ${!inStock ? '<div class="absolute inset-0 flex items-center justify-center bg-white/70 dark:bg-gray-900/70"><span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400">' + esc(homeI18n.soldOut || '') + '</span></div>' : ''}
                                    ${product.has_active_discount ? `<div class="absolute left-2.5 top-2.5 z-10 rounded-full bg-red-500 px-2.5 py-1 text-[10px] font-bold text-white shadow-sm">-${parseFloat(product.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                                </div></a>
                                <button data-fav-btn="${product.id}" onclick="event.stopPropagation();window.toggleFav(${product.id},this)" class="absolute right-2.5 top-2.5 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-all hover:scale-110 dark:bg-gray-900/90 ${isFav ? 'text-red-500' : 'text-gray-400 dark:text-gray-500'}"><svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav ? 'currentColor' : 'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg></button>
                            </div>
                            <div class="p-3 sm:p-4">
                                <a href="${typedPageHref('/products/' + product.id)}"><h3 class="line-clamp-2 text-sm font-bold leading-snug text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400">${esc(product.name)}</h3></a>
                                ${commercialName ? `<p class="mt-1.5 line-clamp-1 text-xs font-semibold text-gray-500 dark:text-gray-400">${esc(commercialName)}</p>` : ''}
                                ${(subcategoryName || barcode) ? `
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        ${subcategoryName ? `<span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-1 text-[10px] font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">${esc(subcategoryName)}</span>` : ''}
                                        ${barcode ? `<span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">${esc(barcode)}</span>` : ''}
                                    </div>
                                ` : ''}
                                <div class="mt-2.5 flex items-center gap-1.5 text-amber-400">${starStars(product.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${reviewCount ? esc(revLabel(reviewCount)) : ''}</span></div>
                                <div class="mt-2.5 flex items-baseline gap-1">
                                    <span class="text-lg font-black ${product.has_active_discount ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'}">${parseFloat(product.has_active_discount ? product.discounted_price : product.price).toLocaleString()}</span><span class="text-[11px] text-gray-400">SYP</span>
                                    ${product.has_active_discount ? `<span class="text-[11px] text-gray-400 line-through">${parseFloat(product.price).toLocaleString()} SYP</span>` : ''}
                                </div>
                                <button onclick="window.addToCart(${product.id},\`${esc(product.name)}\`,${unitPrice},\`${esc(photo)}\`)" class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-bold transition-all duration-200 ${inStock ? 'bg-gray-900 text-white hover:bg-brand-600 active:scale-[.97] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white' : 'cursor-not-allowed bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-600'}" ${!inStock ? 'disabled' : ''}>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                                    ${inStock ? esc(homeI18n.addCart || '') : esc(homeI18n.soldOut || '')}
                                </button>
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
                    $('products-empty').textContent = window.__activeHomeSubcategoryId
                        ? (homeI18n.noProductsSubcategory || '')
                        : (homeI18n.noProductsCategory || '');
                }

                try {
                    const apiUrl = selectedCategoryId
                        ? '/api/products?per_page=24&category_id=' + encodeURIComponent(selectedCategoryId) + (window.__activeHomeSubcategoryId ? '&subcategory_id=' + encodeURIComponent(window.__activeHomeSubcategoryId) : '')
                        : '/api/products?per_page=5';
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

                if (!gridElement) {
                    return;
                }

                try {
                    const extraFilters = selectedCategoryId
                        ? '&category_id=' + encodeURIComponent(selectedCategoryId) + (window.__activeHomeSubcategoryId ? '&subcategory_id=' + encodeURIComponent(window.__activeHomeSubcategoryId) : '')
                        : '';
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

                if (!gridElement) {
                    return;
                }

                try {
                    const extraFilters = selectedCategoryId
                        ? '&category_id=' + encodeURIComponent(selectedCategoryId) + (window.__activeHomeSubcategoryId ? '&subcategory_id=' + encodeURIComponent(window.__activeHomeSubcategoryId) : '')
                        : '';
                    const response = await window.axios.get(typedUrl('/api/products?per_page=5&sort=most_favorited' + extraFilters, 'category_type'));
                    renderProductCards(response.data.data || [], gridElement, emptyElement, loadingElement);
                } catch (error) {
                    emptyElement?.classList.remove('hidden');
                }

                loadingElement?.classList.add('hidden');
            }

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
                                messageElement.textContent = error.response?.data?.message || homeI18n.genericErr || '';
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

            if (selectedCategoryId) {
                updateListingLinks();
                await Promise.all([loadProducts(), loadBestSelling(), loadMostFavorited()]);
            }
        });
    </script>
@endpush
