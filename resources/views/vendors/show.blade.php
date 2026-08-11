@extends('layouts.app')

@section('title', __('stores.details_title') . ' — Vetora')

@section('content')
<div class="catalog-page-band">
    <div class="page-shell py-3">
        <nav class="page-breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
            <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <a href="{{ route('vendors.index') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('stores.page_title') }}</a>
            <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <span id="bc-vendor-name" class="page-breadcrumb-current">{{ __('stores.breadcrumb_label') }}</span>
        </nav>
    </div>
</div>

<div class="page-shell">
    <div id="vendor-loading" class="flex justify-center py-16">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4" style="border-color: var(--color-border); border-top-color: var(--color-brand);"></div>
            <p class="mt-4 text-sm font-medium" style="color: var(--color-text-secondary);">{{ __('stores.loading_details') }}</p>
        </div>
    </div>

    <div id="vendor-content" class="hidden">
        <div class="mb-10 border-y py-10" style="border-color: var(--color-border);">
            <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                <div class="shrink-0">
                    <div id="vendor-logo" class="shop-thumb-box h-28 w-28 border" style="border-color: var(--color-border);"></div>
                </div>
                <div class="flex-1 text-center sm:text-start">
                    <p class="commerce-kicker">{{ __('stores.details_title') }}</p>
                    <h1 id="vendor-name" class="mb-3 mt-2 text-3xl font-bold sm:text-4xl" style="color: var(--color-text);"></h1>
                    <p id="vendor-description" class="mt-2 max-w-2xl text-base leading-relaxed" style="color: var(--color-text-secondary);"></p>
                    <div id="vendor-address" class="mt-4 flex items-center justify-center gap-2 text-sm sm:justify-start" style="color: var(--color-text-secondary);">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span id="vendor-address-text" class="font-medium"></span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="commerce-section-header">
                <div>
                    <h2 class="commerce-title text-lg">{{ __('stores.products') }}</h2>
                    <p class="mt-1 text-sm" style="color: var(--color-text-secondary);" id="products-count">{{ __('stores.loading_products') }}</p>
                </div>
            </div>

            <div id="vendor-products-loading" class="flex justify-center py-12">
                <div class="text-center">
                    <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4" style="border-color: var(--color-border); border-top-color: var(--color-brand);"></div>
                    <p class="mt-3 text-sm" style="color: var(--color-text-secondary);">{{ __('stores.loading_products') }}</p>
                </div>
            </div>

            <div id="vendor-products-grid" class="responsive-shop-grid"></div>

            <div id="vendor-products-empty" class="empty-state hidden py-16 text-center">
                <svg class="mx-auto h-16 w-16" style="color: var(--color-text-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="mt-4 text-base font-semibold" id="vendor-products-empty-title" style="color: var(--color-text-secondary);">{{ __('stores.no_products') }}</p>
            </div>

            <div id="vendor-products-pagination" class="mt-8 flex flex-wrap items-center justify-center gap-1.5"></div>
        </div>
    </div>

    <div id="vendor-error" class="hidden py-12 text-center">
        <div class="mx-auto max-w-md">
            <svg class="mx-auto h-16 w-16" style="color: var(--color-danger-300);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="mt-4 text-lg font-semibold" style="color: var(--color-text);">{{ __('stores.not_found') }}</p>
            <p class="mt-2 text-sm" style="color: var(--color-text-secondary);">{{ __('stores.not_found_copy') }}</p>
            <a href="{{ route('home') }}" class="btn-primary mt-6">{{ __('stores.back_home') }}</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $vendorShowI18n = [
        'productsAvailable' => __('stores.products_available'),
        'noDescription' => __('products.no_description'),
        'outOfStock' => __('nav.sold_out'),
        'inStock' => __('nav.in_stock'),
        'addCart' => __('products.add_to_cart_btn'),
        'viewProduct' => __('stores.view_product'),
        'reviewsCount' => __('nav.reviews_count'),
        'prev' => __('nav.prev'),
        'next' => __('nav.next'),
        'page' => __('nav.page'),
        'of' => __('nav.of'),
        'loadProductsError' => __('stores.load_products_error'),
    ];
@endphp
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const vendorId = {{ $vendorId ?? 'null' }};
    const t = @json($vendorShowI18n);
    const vendorLoading = document.getElementById('vendor-loading');
    const vendorContent = document.getElementById('vendor-content');
    const vendorError = document.getElementById('vendor-error');

    if (!vendorId) {
        vendorLoading.classList.add('hidden');
        vendorError.classList.remove('hidden');
        return;
    }

    let currentPage = 1;

    try {
        const response = await window.axios.get(`/api/vendors/${vendorId}`);
        const vendor = response.data.data;

        // Set vendor info
        document.title = vendor.store_name + ' — Vetora';
        document.getElementById('bc-vendor-name').textContent = vendor.store_name;
        document.getElementById('vendor-name').textContent = vendor.store_name;
        document.getElementById('vendor-description').textContent = vendor.description || '';
        if (vendor.address) {
            document.getElementById('vendor-address-text').textContent = vendor.address;
        } else {
            document.getElementById('vendor-address').classList.add('hidden');
        }

        // Set logo
        const logoContainer = document.getElementById('vendor-logo');
        if (vendor.logo) {
            logoContainer.innerHTML = `<img src="${esc(vendor.logo)}" alt="${esc(vendor.store_name)}" class="h-full w-full object-cover" loading="lazy" onerror="this.outerHTML='<span class=&quot;text-4xl font-bold text-brand-600&quot;>${esc(vendor.store_name).charAt(0).toUpperCase()}</span>'">`;
        } else {
            logoContainer.innerHTML = `<span class="text-4xl font-bold text-brand-600">${esc(vendor.store_name).charAt(0).toUpperCase()}</span>`;
        }

        vendorLoading.classList.add('hidden');
        vendorContent.classList.remove('hidden');

        // Load products
        loadVendorProducts();

        async function loadVendorProducts() {
            const productsLoading = document.getElementById('vendor-products-loading');
            const productsGrid = document.getElementById('vendor-products-grid');
            const productsEmpty = document.getElementById('vendor-products-empty');
            const productsPagination = document.getElementById('vendor-products-pagination');
            const productsCount = document.getElementById('products-count');

            productsLoading.classList.remove('hidden');
            productsGrid.innerHTML = '';
            productsEmpty.classList.add('hidden');
            productsPagination.innerHTML = '';

            try {
                const params = new URLSearchParams({
                    page: currentPage,
                    vendor_id: vendorId,
                });

                const response = await window.axios.get(`/api/products?${params.toString()}`);
                const { data, meta } = response.data;

                productsCount.textContent = `${meta.total} product${meta.total !== 1 ? 's' : ''} available`;

                if (data.length === 0) {
                    productsEmpty.classList.remove('hidden');
                } else {
                    productsGrid.innerHTML = data.map(product => `
                        <article class="commerce-product-card">
                            <div class="shop-card-media">
                                <img src="${esc(product.first_photo_url || '/images/product-placeholder.svg')}"
                                     alt="${esc(product.name)}"
                                     class="shop-card-media-img"
                                     loading="lazy"
                                     onerror="this.onerror=null;this.src='/images/product-placeholder.svg'">
                                ${product.quantity <= 0 ? `<div class="absolute end-2 top-2 badge badge-danger">Out of Stock</div>` : ''}
                                ${product.has_active_discount ? `<div class="absolute start-2 top-2 badge badge-danger">-${parseFloat(product.discount_percentage || 0).toFixed(0)}%</div>` : ''}
                            </div>
                            <div class="commerce-product-body">
                                <h3 class="commerce-product-title line-clamp-2">${esc(product.name)}</h3>
                                <p class="mt-1 line-clamp-2 text-sm leading-6 text-gray-600 dark:text-gray-400">${esc(product.description || 'No description available')}</p>
                                <div class="mt-4 border-t border-gray-200 pt-3 dark:border-gray-800">
                                    <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                        <span class="commerce-product-price ${product.has_active_discount ? 'text-red-600 dark:text-red-400' : ''}">${parseFloat(product.has_active_discount ? product.discounted_price : product.price).toFixed(2)} <small>SYP</small></span>
                                        ${product.has_active_discount ? `<span class="text-xs tabular-nums text-gray-400 line-through">${parseFloat(product.price).toFixed(2)} SYP</span>` : ''}
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${product.quantity} available</p>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <a href="/products/${product.id}" class="btn-secondary btn-sm flex-1 justify-center text-center">View</a>
                                    <button data-product-id="${product.id}"
                                            data-product-name="${esc(product.name)}"
                                            data-product-price="${product.has_active_discount ? product.discounted_price : product.price}"
                                            data-product-photo="${esc(product.first_photo_url || '')}"
                                            onclick="handleAddToCartFromCard(this)"
                                            class="btn-primary btn-sm flex-1 justify-center ${product.quantity <= 0 ? 'cursor-not-allowed opacity-50' : ''}"
                                            ${product.quantity <= 0 ? 'disabled' : ''}>
                                        <span class="flex items-center justify-center gap-1.5">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            Add to Cart
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    `).join('');
                }

                // Pagination
                if (meta.last_page > 1) {
                    productsPagination.innerHTML = `
                        <button ${meta.current_page === 1 ? 'disabled' : ''}
                                onclick="currentPage = ${meta.current_page - 1}; loadVendorProducts();"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-all ${meta.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50 hover:border-brand-300'}"
                                ${meta.current_page === 1 ? 'disabled' : ''}>
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                </svg>
                                Previous
                            </span>
                        </button>
                        <span class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700">Page ${meta.current_page} of ${meta.last_page}</span>
                        <button ${meta.current_page === meta.last_page ? 'disabled' : ''}
                                onclick="currentPage = ${meta.current_page + 1}; loadVendorProducts();"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-all ${meta.current_page === meta.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50 hover:border-brand-300'}"
                                ${meta.current_page === meta.last_page ? 'disabled' : ''}>
                            <span class="flex items-center gap-2">
                                Next
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </span>
                        </button>
                    `;
                }
            } catch (error) {
                console.error('Failed to load products:', error);
                productsEmpty.classList.remove('hidden');
                productsEmpty.innerHTML = '<p class="text-lg font-medium text-red-500">Failed to load products. Please try again later.</p>';
            } finally {
                productsLoading.classList.add('hidden');
            }
        }

        window.loadVendorProducts = loadVendorProducts;
        window.currentPage = currentPage;
    } catch (error) {
        console.error('Failed to load vendor:', error);
        vendorLoading.classList.add('hidden');
        vendorError.classList.remove('hidden');
    }

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // addToCart is provided by resources/js/cart.js and always talks to the
    // server cart. The former localStorage fallback here was a second, silent
    // cart implementation with hardcoded English copy — removed (spec §5).

    // Handle add to cart from product cards
    window.handleAddToCartFromCard = function(button) {
        const productId = parseInt(button.dataset.productId);
        const productName = button.dataset.productName;
        const productPrice = parseFloat(button.dataset.productPrice);
        const productPhoto = button.dataset.productPhoto || '/images/placeholder.png';

        if (typeof window.addToCart === 'function') {
            window.addToCart(productId, productName, productPrice, productPhoto);
        } else {
            console.error('addToCart function not available');
        }
    };
});
</script>
@endpush
