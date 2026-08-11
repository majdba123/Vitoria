@extends('layouts.app')

@section('title', __('products.show_title') . ' - Vetora')

@section('content')
    <div class="bg-transparent">
        <div class="border-b border-white/40 bg-white/60 backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
            <div class="page-shell py-3">
                <nav class="page-breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
                    <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    <a href="{{ route('products.index') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.products') }}</a>
                    <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    <span class="page-breadcrumb-current" id="bc-name">{{ __('products.details_breadcrumb') }}</span>
                </nav>
            </div>
        </div>

        <div class="page-shell">
            <div id="show-loading" class="py-16 text-center">
                <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700"></div>
                <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('products.loading_details') }}</p>
            </div>

            <div id="show-content" class="hidden">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(22rem,0.95fr)]">
                    <div class="space-y-4">
                        <div class="storefront-gallery-main">
                            <div id="primary-photo-container" class="absolute inset-0 flex items-center justify-center text-center text-sm font-medium text-gray-400 dark:text-gray-500">
                                {{ __('products.no_primary_photo') }}
                            </div>
                        </div>

                        <div class="storefront-detail-panel">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">{{ __('products.media_badge') }}</p>
                                    <h2 class="mt-2 text-lg font-black text-gray-900 dark:text-white">{{ __('products.gallery_title') }}</h2>
                                </div>
                                <span id="photo-count" class="text-xs font-semibold text-gray-400 dark:text-gray-500"></span>
                            </div>
                            <div id="product-photos" class="storefront-chip-scroll flex gap-3 overflow-x-auto pb-1"></div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="storefront-detail-panel">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] font-black uppercase tracking-[0.24em] text-brand-600 dark:text-brand-300">{{ __('products.overview_badge') }}</p>
                                    <h1 id="product-name" class="mt-3 text-3xl font-black leading-tight tracking-tight text-gray-900 dark:text-white sm:text-4xl"></h1>
                                </div>
                                <button id="fav-detail-btn" onclick="window.toggleFav({{ $productId ?? 0 }},this)" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-white/80 text-gray-400 shadow-sm transition-all hover:scale-105 dark:border-gray-700 dark:bg-gray-900/80 dark:text-gray-500" data-fav-btn="{{ $productId ?? 0 }}">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                                </button>
                            </div>

                            <div id="product-rating-row" class="mt-5 flex flex-wrap items-center gap-2 border-b border-gray-100 pb-5 dark:border-gray-800">
                                <div id="product-stars" class="flex items-center gap-0.5 text-amber-400"></div>
                                <span id="product-rating-text" class="text-sm text-gray-500 dark:text-gray-400"></span>
                            </div>

                            <div class="mt-5 flex flex-wrap items-end gap-3 border-b border-gray-100 pb-5 dark:border-gray-800">
                                <span id="product-price" class="text-4xl font-black text-gray-900 dark:text-white"></span>
                                <span class="pb-1 text-sm font-semibold text-gray-400">SYP</span>
                                <span id="product-price-original" class="hidden pb-1 text-sm text-gray-400 line-through"></span>
                            </div>

                            <div class="storefront-spec-grid mt-5">
                                <div class="storefront-spec-card">
                                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-gray-400">{{ __('products.fields.category') }}</p>
                                    <p id="product-category" class="mt-2 text-sm font-black text-gray-900 dark:text-white">—</p>
                                </div>
                                <div class="storefront-spec-card">
                                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-gray-400">{{ __('products.fields.subcategory') }}</p>
                                    <p id="product-subcategory" class="mt-2 text-sm font-black text-gray-900 dark:text-white">—</p>
                                </div>
                                <div class="storefront-spec-card">
                                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-gray-400">{{ __('products.fields.quantity') }}</p>
                                    <p id="product-quantity" class="mt-2 text-sm font-black text-gray-900 dark:text-white">—</p>
                                </div>
                                <div class="storefront-spec-card">
                                    <p class="text-[11px] font-black uppercase tracking-[0.18em] text-gray-400">{{ __('products.fields.vendor') }}</p>
                                    <p id="product-vendor" class="mt-2 text-sm font-black text-gray-900 dark:text-white">—</p>
                                </div>
                            </div>

                            <div class="mt-5 rounded-[24px] border border-gray-200/80 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/60">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-gray-400">{{ __('products.discount_card_title') }}</p>
                                        <p id="product-discount-status" class="mt-2 text-sm font-black text-gray-900 dark:text-white">—</p>
                                    </div>
                                    <p id="product-availability"></p>
                                </div>
                                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-2xl bg-white px-4 py-3 dark:bg-gray-900/70">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-400">{{ __('products.fields.discount_value') }}</p>
                                        <p id="product-discount-value" class="mt-1 text-sm font-black text-red-600 dark:text-red-400">—</p>
                                    </div>
                                    <div class="rounded-2xl bg-white px-4 py-3 dark:bg-gray-900/70">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-400">{{ __('products.fields.discount_starts') }}</p>
                                        <p id="product-discount-start" class="mt-1 text-sm font-black text-gray-900 dark:text-white">—</p>
                                    </div>
                                    <div class="rounded-2xl bg-white px-4 py-3 dark:bg-gray-900/70">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-400">{{ __('products.fields.discount_ends') }}</p>
                                        <p id="product-discount-end" class="mt-1 text-sm font-black text-gray-900 dark:text-white">—</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                                <button id="add-to-cart-btn" class="flex-1 rounded-2xl bg-gray-900 px-5 py-3.5 text-sm font-black text-white transition-all hover:bg-brand-600 active:scale-[.98] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white" disabled>
                                    <span class="flex items-center justify-center gap-2">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" /></svg>
                                        {{ __('products.add_to_cart_btn') }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="storefront-detail-panel">
                            <p class="text-[11px] font-black uppercase tracking-[0.24em] text-brand-600 dark:text-brand-300">{{ __('products.description_badge') }}</p>
                            <h2 class="mt-2 text-lg font-black text-gray-900 dark:text-white">{{ __('products.description_title') }}</h2>
                            <p id="product-description" class="mt-4 whitespace-pre-wrap text-sm leading-8 text-gray-600 dark:text-gray-300"></p>
                        </div>
                    </div>
                </div>

                <div id="reviews-section" class="mt-10 hidden">
                    <div class="storefront-detail-panel">
                        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-brand-600 dark:text-brand-300">{{ __('products.reviews') }}</p>
                                <h2 class="mt-2 text-xl font-black text-gray-900 dark:text-white">{{ __('products.reviews') }} <span id="reviews-subtitle" class="text-sm font-normal text-gray-500 dark:text-gray-400"></span></h2>
                            </div>
                        </div>

                        <div id="review-form-wrap" class="mb-8 hidden rounded-[24px] border border-gray-200/80 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/60">
                            <form id="review-form" class="space-y-4">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('products.review_rating_label') }}</label>
                                    <div id="review-stars-input" class="flex gap-1 text-2xl text-gray-300 dark:text-gray-600" data-rating="0">
                                        <button type="button" class="star-btn rounded p-0.5 transition-colors hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2" data-value="1" aria-label="{{ __('products.review_star_label', ['count' => 1]) }}"><svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg></button>
                                        <button type="button" class="star-btn rounded p-0.5 transition-colors hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2" data-value="2" aria-label="{{ __('products.review_star_label', ['count' => 2]) }}"><svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg></button>
                                        <button type="button" class="star-btn rounded p-0.5 transition-colors hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2" data-value="3" aria-label="{{ __('products.review_star_label', ['count' => 3]) }}"><svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg></button>
                                        <button type="button" class="star-btn rounded p-0.5 transition-colors hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2" data-value="4" aria-label="{{ __('products.review_star_label', ['count' => 4]) }}"><svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg></button>
                                        <button type="button" class="star-btn rounded p-0.5 transition-colors hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2" data-value="5" aria-label="{{ __('products.review_star_label', ['count' => 5]) }}"><svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg></button>
                                    </div>
                                    <input type="hidden" name="rating" id="review-rating-input" value="0">
                                </div>

                                <div>
                                    <label for="review-body" class="mb-2 block text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('products.review_comment_label') }}</label>
                                    <textarea id="review-body" name="body" rows="4" class="form-textarea" placeholder="{{ __('products.review_placeholder') }}"></textarea>
                                </div>

                                <button type="submit" id="review-submit-btn" class="btn-primary">{{ __('products.review_submit') }}</button>
                            </form>
                        </div>

                        <div id="reviews-list" class="space-y-4"></div>
                        <div id="reviews-empty" class="hidden py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('products.no_reviews') }}</div>
                        <div id="reviews-pagination" class="mt-6 flex flex-wrap items-center justify-center gap-2"></div>
                    </div>
                </div>
            </div>

            <div id="product-error" class="empty-state hidden py-16">
                <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <p class="mt-4 text-base font-bold text-gray-900 dark:text-white">{{ __('products.error_title') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('products.error_copy') }}</p>
                <a href="{{ route('products.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-gray-900 px-6 py-3 text-sm font-bold text-white hover:bg-brand-600 dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">{{ __('products.back_to_products') }}</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        $productShowStrings = [
            'detailsFallback' => __('products.details_breadcrumb'),
            'noRatingsYet' => __('products.no_ratings_yet'),
            'reviewSingle' => __('products.review_single'),
            'reviewPlural' => __('products.review_plural'),
            'units' => __('products.units'),
            'noDescription' => __('products.no_description'),
            'noSubcategory' => __('products.no_subcategory'),
            'noVendor' => __('products.no_vendor'),
            'discountActive' => __('products.discount_status_active'),
            'discountPending' => __('products.discount_status_pending'),
            'discountExpired' => __('products.discount_status_expired'),
            'discountEmpty' => __('products.discount_value_empty'),
            'inStock' => __('products.in_stock'),
            'outOfStock' => __('products.sold_out'),
            'outOfStockButton' => __('products.out_of_stock_button'),
            'photosCount' => __('products.photos'),
            'photoSingle' => __('products.photo_single'),
            'noAdditionalPhotos' => __('products.no_additional_photos'),
            'noPrimaryPhoto' => __('products.no_primary_photo'),
            'reviewsLoadFailed' => __('products.reviews_load_failed'),
            'reviewsNotAvailable' => __('products.reviews_not_available'),
            'reviewDeleteConfirm' => __('products.delete_review_confirm'),
            'reviewDeleteFailed' => __('products.review_delete_failed'),
            'noReviews' => __('products.no_reviews'),
            'deleteReview' => __('products.deleteReview'),
            'reviewRatingHint' => __('products.rating_select_hint'),
            'reviewSubmitFailed' => __('products.review_submit_failed'),
            'reviewLoginRequired' => __('products.review_login_required'),
            'reviewValidationError' => __('products.review_validation_error'),
            'loading' => __('common.loading'),
            'close' => __('common.close'),
            'page' => __('nav.page'),
            'of' => __('nav.of'),
            'prev' => __('nav.prev'),
            'next' => __('nav.next'),
        ];
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const productId = {{ $productId ?? 'null' }};
            const selectedType = @json(auth()->user()?->preferred_product_type ?? session('preferred_product_type', request()->cookie('preferred_product_type', '')));
            const i18n = @json($productShowStrings);
            const $ = (id) => document.getElementById(id);
            let currentReviewsPage = 1;
            let lastReviewsPage = 1;
            const reviewsPerPage = 5;

            function esc(value) {
                if (!value) {
                    return '';
                }

                const element = document.createElement('div');
                element.textContent = value;

                return element.innerHTML;
            }

            function withSelectedType(url) {
                return selectedType ? `${url}${url.includes('?') ? '&' : '?'}type=${encodeURIComponent(selectedType)}` : url;
            }

            if (!productId) {
                $('show-loading').classList.add('hidden');
                $('product-error').classList.remove('hidden');
                return;
            }

            try {
                const response = await window.axios.get(withSelectedType(`/api/products/${productId}`));
                const product = response.data.data;
                const photos = product.photos || [];
                const displayImage = product.first_photo_url || product.fallback_photo_url || '';

                $('product-name').textContent = product.name || '—';
                $('bc-name').textContent = product.name || i18n.detailsFallback || '';
                $('product-description').textContent = product.description || i18n.noDescription || '';
                $('product-category').textContent = product.category?.name || '—';
                $('product-subcategory').textContent = product.subcategory?.name || i18n.noSubcategory || '—';
                $('product-vendor').textContent = product.vendor?.store_name || product.vendor?.name || i18n.noVendor || '—';

                const averageRating = parseFloat(product.average_rating) || 0;
                const reviewCount = parseInt(product.review_count, 10) || 0;
                $('product-stars').innerHTML = renderStars(averageRating, 5, 'h-5 w-5');
                $('product-rating-text').textContent = reviewCount === 0
                    ? i18n.noRatingsYet || ''
                    : `${reviewCount} ${reviewCount === 1 ? (i18n.reviewSingle || '') : (i18n.reviewPlural || '')}`;

                const hasDiscount = !!product.has_active_discount;
                const effectivePrice = parseFloat(hasDiscount ? product.discounted_price : product.price || 0);
                $('product-price').textContent = effectivePrice.toLocaleString();
                $('product-price').className = hasDiscount
                    ? 'text-4xl font-black text-red-600 dark:text-red-400'
                    : 'text-4xl font-black text-gray-900 dark:text-white';

                if (hasDiscount) {
                    $('product-price-original').classList.remove('hidden');
                    $('product-price-original').textContent = `${parseFloat(product.price || 0).toLocaleString()} SYP`;
                } else {
                    $('product-price-original').classList.add('hidden');
                    $('product-price-original').textContent = '';
                }

                $('product-quantity').textContent = `${product.quantity || 0} ${i18n.units || ''}`.trim();
                $('product-discount-status').textContent = formatDiscountStatus(product.discount_status);
                $('product-discount-value').textContent = product.discount_percentage ? `${parseFloat(product.discount_percentage).toFixed(2)}%` : (i18n.discountEmpty || '');
                $('product-discount-start').textContent = formatDateOnly(product.discount_starts_at);
                $('product-discount-end').textContent = formatDateOnly(product.discount_ends_at);

                const inStock = product.quantity > 0;
                $('product-availability').innerHTML = inStock
                    ? `<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>${esc(i18n.inStock || '')}</span>`
                    : `<span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>${esc(i18n.outOfStock || '')}</span>`;

                const addToCartButton = $('add-to-cart-btn');
                if (inStock) {
                    addToCartButton.disabled = false;
                    addToCartButton.onclick = () => {
                        const primaryPhoto = photos.find((photo) => photo.is_primary) || photos[0];
                        const photoUrl = displayImage || (primaryPhoto ? (primaryPhoto.url || `/storage/${primaryPhoto.path}`) : '');
                        window.addToCart(product.id, product.name, hasDiscount ? product.discounted_price : product.price, photoUrl);
                    };
                } else {
                    addToCartButton.innerHTML = `<span class="flex items-center justify-center gap-2"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>${esc(i18n.outOfStockButton || '')}</span>`;
                    addToCartButton.disabled = true;
                    addToCartButton.classList.remove('bg-gray-900', 'dark:bg-white', 'dark:text-gray-900');
                    addToCartButton.classList.add('cursor-not-allowed', 'bg-gray-100', 'text-gray-400', 'dark:bg-gray-800', 'dark:text-gray-500');
                }

                const primaryPhoto = photos.find((photo) => photo.is_primary) || photos[0];
                if (primaryPhoto) {
                    const url = primaryPhoto.url || `/storage/${primaryPhoto.path}`;
                    renderPrimaryPhoto(url, product.name);
                } else if (displayImage) {
                    renderPrimaryPhoto(displayImage, product.name);
                } else {
                    $('primary-photo-container').innerHTML = `<p class="px-6 text-center text-sm font-medium text-gray-400 dark:text-gray-500">${esc(i18n.noPrimaryPhoto || '')}</p>`;
                }

                $('photo-count').textContent = `${photos.length} ${photos.length === 1 ? (i18n.photoSingle || '') : (i18n.photosCount || '')}`.trim();

                const photosContainer = $('product-photos');
                photosContainer.innerHTML = '';

                if (photos.length) {
                    photos.forEach((photo) => {
                        const url = photo.url || `/storage/${photo.path}`;

                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = `storefront-thumb-button ${photo.is_primary ? 'is-active' : ''}`;
                        button.setAttribute('aria-label', product.name || '');

                        const img = document.createElement('img');
                        img.src = url;
                        img.alt = `${product.name || ''} thumbnail`;
                        button.appendChild(img);

                        button.addEventListener('click', function () {
                            renderPrimaryPhoto(url, product.name);
                            document.querySelectorAll('.storefront-thumb-button').forEach((item) => item.classList.remove('is-active'));
                            button.classList.add('is-active');
                        });

                        photosContainer.appendChild(button);
                    });
                } else {
                    const empty = document.createElement('p');
                    empty.className = 'py-4 text-xs text-gray-400 dark:text-gray-500';
                    empty.textContent = i18n.noAdditionalPhotos || '';
                    photosContainer.appendChild(empty);
                }

                window._viewLarge = function (url, name) {
                    const previouslyFocused = document.activeElement;
                    const modal = document.createElement('div');
                    modal.className = 'fixed inset-0 z-[80] flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm';
                    modal.setAttribute('role', 'dialog');
                    modal.setAttribute('aria-modal', 'true');
                    modal.setAttribute('aria-label', name || '');

                    const wrap = document.createElement('div');
                    wrap.className = 'relative max-h-[90vh] max-w-[90vw]';

                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = name || '';
                    img.className = 'max-h-[90vh] max-w-[90vw] rounded-2xl object-contain';

                    const closeBtn = document.createElement('button');
                    closeBtn.type = 'button';
                    closeBtn.setAttribute('aria-label', i18n.close);
                    closeBtn.className = 'absolute -right-2 -top-2 flex h-10 w-10 items-center justify-center rounded-full bg-white text-gray-900 shadow-xl hover:scale-110 transition-transform';
                    closeBtn.innerHTML = '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>';

                    wrap.appendChild(img);
                    wrap.appendChild(closeBtn);
                    modal.appendChild(wrap);

                    function close() {
                        modal.remove();
                        document.removeEventListener('keydown', onKeydown);
                        previouslyFocused?.focus?.();
                    }

                    function onKeydown(event) {
                        if (event.key === 'Escape') {
                            close();
                        }
                    }

                    modal.addEventListener('click', function (event) {
                        if (event.target === modal || event.target.closest('button')) {
                            close();
                        }
                    });
                    document.addEventListener('keydown', onKeydown);
                    document.body.appendChild(modal);
                    closeBtn.focus();
                };

                $('show-loading').classList.add('hidden');
                $('show-content').classList.remove('hidden');
                $('reviews-section').classList.remove('hidden');

                if (window.Auth?.getToken?.() || localStorage.getItem('auth_token')) {
                    $('review-form-wrap').classList.remove('hidden');
                    initReviewForm();
                }

                loadReviews(1);
            } catch (error) {
                $('show-loading').classList.add('hidden');
                $('product-error').classList.remove('hidden');
            }

            function renderPrimaryPhoto(url, name) {
                const container = $('primary-photo-container');
                container.innerHTML = '';

                const img = document.createElement('img');
                img.src = url;
                img.alt = name || '';
                img.loading = 'eager';
                img.className = 'cursor-zoom-in transition-transform duration-300 hover:scale-[1.03]';
                img.addEventListener('click', () => window._viewLarge(url, name));

                container.appendChild(img);
            }

            function formatDateOnly(value) {
                if (!value) {
                    return '—';
                }

                const date = new Date(typeof value === 'string' ? value.replace(' ', 'T') : value);
                if (Number.isNaN(date.getTime())) {
                    return String(value).slice(0, 10);
                }

                return date.toLocaleDateString();
            }

            function formatDiscountStatus(status) {
                if (status === 'active') {
                    return i18n.discountActive || '—';
                }
                if (status === 'pending') {
                    return i18n.discountPending || '—';
                }
                if (status === 'expired') {
                    return i18n.discountExpired || '—';
                }

                return '—';
            }

            function renderStars(rating, max, sizeClass) {
                const resolvedRating = Math.min(max, Math.max(0, Math.round(rating)));
                const filled = `<svg class="${sizeClass}" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg>`;
                const empty = `<svg class="${sizeClass} text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" /></svg>`;
                let html = '';
                for (let index = 0; index < max; index++) {
                    html += index < resolvedRating ? filled : empty;
                }
                return html;
            }

            function loadReviews(page) {
                const listElement = $('reviews-list');
                const emptyElement = $('reviews-empty');
                const paginationElement = $('reviews-pagination');
                const subtitleElement = $('reviews-subtitle');

                listElement.innerHTML = `<p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">${esc(i18n.loading || '')}</p>`;
                emptyElement.classList.add('hidden');
                paginationElement.innerHTML = '';
                subtitleElement.textContent = '';

                window.axios.get(`/api/products/${productId}/reviews`, { params: { page: page || 1, per_page: reviewsPerPage } }).then(function (response) {
                    const reviews = response.data.data || [];
                    const meta = response.data.meta || {};
                    const total = meta.total || 0;
                    currentReviewsPage = meta.current_page || 1;
                    lastReviewsPage = meta.last_page || 1;
                    listElement.innerHTML = '';

                    if (!reviews.length) {
                        emptyElement.classList.remove('hidden');
                        emptyElement.textContent = i18n.noReviews || '';
                        return;
                    }

                    reviews.forEach(function (review) {
                        const userName = review.user?.name ? esc(review.user.name) : 'User';
                        const body = review.body ? `<p class="mt-2 text-sm leading-7 text-gray-600 dark:text-gray-300">${esc(review.body)}</p>` : '';
                        const date = review.created_at ? new Date(review.created_at).toLocaleDateString(undefined, { dateStyle: 'medium' }) : '';
                        const currentUserId = window.Auth?.getUser?.()?.id || null;
                        const canDelete = currentUserId && review.user && review.user.id === currentUserId;
                        const deleteButton = canDelete ? `<button type="button" data-review-id="${review.id}" class="review-delete-btn text-xs font-bold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">${esc(i18n.deleteReview || 'Delete')}</button>` : '';

                        listElement.innerHTML += `
                            <div class="rounded-[24px] border border-gray-200/80 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-950/60" data-review-id="${review.id}">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-gray-900 dark:text-white">${userName}</p>
                                        ${date ? `<p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">${esc(date)}</p>` : ''}
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="flex text-amber-400">${renderStars(review.rating, 5, 'h-4 w-4')}</div>
                                        ${deleteButton}
                                    </div>
                                </div>
                                ${body}
                            </div>
                        `;
                    });

                    listElement.querySelectorAll('.review-delete-btn').forEach(function (button) {
                        button.addEventListener('click', function () {
                            const reviewId = button.getAttribute('data-review-id');
                            if (!reviewId || !confirm(i18n.reviewDeleteConfirm || '')) {
                                return;
                            }

                            window.Auth?.applyToken?.();
                            window.axios.delete(`/api/products/${productId}/reviews/${reviewId}`).then(function () {
                                loadReviews(currentReviewsPage);
                            }).catch(function (error) {
                                alert(error.response?.data?.message || i18n.reviewDeleteFailed || '');
                            });
                        });
                    });

                    subtitleElement.textContent = total ? `(${total} ${total === 1 ? (i18n.reviewSingle || '') : (i18n.reviewPlural || '')})` : '';

                    if (lastReviewsPage > 1) {
                        paginationElement.innerHTML = `
                            <button type="button" onclick="window._reviewsPrev()" class="btn-secondary btn-xs" ${currentReviewsPage <= 1 ? 'disabled' : ''}>${esc(i18n.prev || '')}</button>
                            <span class="text-sm text-gray-500 dark:text-gray-400">${esc(i18n.page || '')} ${currentReviewsPage} ${esc(i18n.of || '')} ${lastReviewsPage}</span>
                            <button type="button" onclick="window._reviewsNext()" class="btn-secondary btn-xs" ${currentReviewsPage >= lastReviewsPage ? 'disabled' : ''}>${esc(i18n.next || '')}</button>
                        `;
                    }
                }).catch(function (error) {
                    listElement.innerHTML = '';
                    emptyElement.classList.remove('hidden');
                    emptyElement.textContent = error.response?.status === 404 ? (i18n.reviewsNotAvailable || '') : (i18n.reviewsLoadFailed || '');
                });
            }

            window._reviewsPrev = function () {
                if (currentReviewsPage > 1) {
                    loadReviews(currentReviewsPage - 1);
                }
            };

            window._reviewsNext = function () {
                if (currentReviewsPage < lastReviewsPage) {
                    loadReviews(currentReviewsPage + 1);
                }
            };

            function initReviewForm() {
                const starsContainer = $('review-stars-input');
                const ratingInput = $('review-rating-input');
                if (!starsContainer || !ratingInput) {
                    return;
                }

                starsContainer.querySelectorAll('.star-btn').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const value = parseInt(button.getAttribute('data-value'), 10);
                        ratingInput.value = value;
                        starsContainer.setAttribute('data-rating', String(value));
                        starsContainer.querySelectorAll('.star-btn').forEach(function (starButton) {
                            const starValue = parseInt(starButton.getAttribute('data-value'), 10);
                            starButton.classList.toggle('text-amber-400', starValue <= value);
                            starButton.classList.toggle('text-gray-300', starValue > value);
                            starButton.classList.toggle('dark:text-gray-600', starValue > value);
                        });
                    });
                });

                $('review-form').addEventListener('submit', function (event) {
                    event.preventDefault();
                    const rating = parseInt(ratingInput.value, 10);
                    if (rating < 1 || rating > 5) {
                        alert(i18n.reviewRatingHint || '');
                        return;
                    }

                    const body = $('review-body').value.trim();
                    const submitButton = $('review-submit-btn');
                    submitButton.disabled = true;
                    window.Auth?.applyToken?.();

                    window.axios.post(`/api/products/${productId}/reviews`, { rating: rating, body: body || null }).then(function () {
                        submitButton.disabled = false;
                        ratingInput.value = '0';
                        starsContainer.setAttribute('data-rating', '0');
                        starsContainer.querySelectorAll('.star-btn').forEach(function (starButton) {
                            starButton.classList.remove('text-amber-400');
                            starButton.classList.add('text-gray-300', 'dark:text-gray-600');
                        });
                        $('review-body').value = '';
                        loadReviews(1);
                    }).catch(function (error) {
                        submitButton.disabled = false;
                        let message = error.response?.data?.message || i18n.reviewSubmitFailed || '';
                        if (error.response?.status === 401) {
                            message = i18n.reviewLoginRequired || message;
                        }
                        if (error.response?.status === 422) {
                            message = i18n.reviewValidationError || message;
                        }
                        alert(message);
                    });
                });
            }
        });
    </script>
@endpush
