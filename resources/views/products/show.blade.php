@extends('layouts.app')

@section('title', 'Product Details — Vetora')

@section('content')
<div class="bg-white dark:bg-gray-950">
    <div class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto max-w-screen-2xl px-4 py-3 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">Home</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <a href="{{ route('products.index') }}" class="hover:text-brand-600 dark:hover:text-brand-400">Products</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="font-medium text-gray-900 dark:text-white" id="bc-name">Details</span>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
        <div id="show-loading" class="py-16 text-center">
            <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700"></div>
            <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">Loading product details...</p>
        </div>

        <div id="show-content" class="hidden">
            <div class="grid gap-8 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">
                    <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900">
                        <div id="primary-photo-container" class="relative aspect-square bg-gray-50 dark:bg-gray-800">
                            <p class="absolute inset-0 flex items-center justify-center text-gray-400 dark:text-gray-500">No photo available.</p>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-gray-200/80 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Gallery</h3>
                            <span id="photo-count" class="text-xs text-gray-400 dark:text-gray-500"></span>
                        </div>
                        <div id="product-photos" class="flex gap-3 overflow-x-auto pb-2 hide-scrollbar"></div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="sticky top-20 space-y-5">
                        <div class="rounded-2xl border border-gray-200/80 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                            <h1 id="product-name" class="mb-2 text-2xl font-black leading-tight text-gray-900 dark:text-white"></h1>
                            <div id="product-rating-row" class="mb-5 flex items-center gap-2 border-b border-gray-100 pb-5 dark:border-gray-800">
                                <div id="product-stars" class="flex items-center gap-0.5 text-amber-400"></div>
                                <span id="product-rating-text" class="text-sm text-gray-500 dark:text-gray-400"></span>
                            </div>
                            <div class="mb-5 flex items-baseline gap-2 border-b border-gray-100 pb-5 dark:border-gray-800">
                                <span id="product-price" class="text-3xl font-black text-gray-900 dark:text-white"></span>
                                <span id="product-price-original" class="hidden text-sm text-gray-400 line-through"></span>
                                <span class="text-sm text-gray-400">SYP</span>
                            </div>
                            <div class="mb-5 rounded-xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                                <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-400">Discount Details</p>
                                <div class="grid grid-cols-2 gap-2 text-center">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Status</p>
                                        <p id="product-discount-status" class="mt-0.5 text-xs font-bold text-gray-900 dark:text-white">—</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Value</p>
                                        <p id="product-discount-value" class="mt-0.5 text-xs font-bold text-red-600 dark:text-red-400">—</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Starts</p>
                                        <p id="product-discount-start" class="mt-0.5 text-xs font-bold text-gray-900 dark:text-white">—</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Ends</p>
                                        <p id="product-discount-end" class="mt-0.5 text-xs font-bold text-gray-900 dark:text-white">—</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-5 space-y-3">
                                <p id="product-availability" class="mb-2"></p>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Available:</span>
                                    <span id="product-quantity" class="text-sm font-bold text-gray-900 dark:text-white"></span>
                                </div>
                                <div class="rounded-xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50">
                                    <div class="grid grid-cols-2 gap-2 text-center">
                                        <div>
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Category</p>
                                            <p id="product-category" class="mt-0.5 text-xs font-bold text-gray-900 dark:text-white">—</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Subcategory</p>
                                            <p id="product-subcategory" class="mt-0.5 text-xs font-bold text-gray-900 dark:text-white">—</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <button id="add-to-cart-btn" class="flex-1 rounded-xl bg-gray-900 py-3.5 text-sm font-bold text-white transition-all hover:bg-brand-600 active:scale-[.97] dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white" disabled>
                                    <span class="flex items-center justify-center gap-2">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                                        Add to Cart
                                    </span>
                                </button>
                                <button id="fav-detail-btn" onclick="window.toggleFav({{ $productId ?? 0 }},this)" class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-xl border border-gray-200 transition-all hover:scale-105 dark:border-gray-700 text-gray-400 dark:text-gray-500" data-fav-btn="{{ $productId ?? 0 }}">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-gray-200/80 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                            <h3 class="mb-3 text-base font-bold text-gray-900 dark:text-white">Description</h3>
                            <p id="product-description" class="whitespace-pre-wrap text-sm leading-relaxed text-gray-600 dark:text-gray-400"></p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reviews section: last 5 reviews + pagination --}}
            <div id="reviews-section" class="mt-10 hidden">
                <div class="rounded-2xl border border-gray-200/80 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">Reviews <span id="reviews-subtitle" class="text-sm font-normal text-gray-500 dark:text-gray-400"></span></h2>
                    <div id="review-form-wrap" class="mb-8 hidden">
                        <form id="review-form" class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Your rating (1–5 stars)</label>
                                <div id="review-stars-input" class="flex gap-1 text-2xl text-gray-300 dark:text-gray-600" data-rating="0">
                                    <button type="button" class="star-btn transition-colors hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 rounded p-0.5" data-value="1" aria-label="1 star"><svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></button>
                                    <button type="button" class="star-btn transition-colors hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 rounded p-0.5" data-value="2" aria-label="2 stars"><svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></button>
                                    <button type="button" class="star-btn transition-colors hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 rounded p-0.5" data-value="3" aria-label="3 stars"><svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></button>
                                    <button type="button" class="star-btn transition-colors hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 rounded p-0.5" data-value="4" aria-label="4 stars"><svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></button>
                                    <button type="button" class="star-btn transition-colors hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 rounded p-0.5" data-value="5" aria-label="5 stars"><svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></button>
                                </div>
                                <input type="hidden" name="rating" id="review-rating-input" value="0">
                            </div>
                            <div>
                                <label for="review-body" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Comment (optional)</label>
                                <textarea id="review-body" name="body" rows="3" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500" placeholder="Write your review here..."></textarea>
                            </div>
                            <button type="submit" id="review-submit-btn" class="rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-bold text-white transition-all hover:bg-brand-600 dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500">Submit review</button>
                        </form>
                    </div>
                    <div id="reviews-list" class="space-y-4"></div>
                    <div id="reviews-empty" class="hidden py-8 text-center text-sm text-gray-500 dark:text-gray-400">No reviews yet.</div>
                    <div id="reviews-pagination" class="mt-6 flex flex-wrap items-center justify-center gap-2"></div>
                </div>
            </div>
        </div>

        <div id="product-error" class="hidden py-16 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <p class="mt-4 text-base font-bold text-gray-900 dark:text-white">Product not found</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The product you're looking for doesn't exist or has been removed.</p>
            <a href="{{ route('products.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-gray-900 px-6 py-3 text-sm font-bold text-white hover:bg-brand-600 dark:bg-white dark:text-gray-900 dark:hover:bg-brand-500 dark:hover:text-white">Back to Products</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const productId = {{ $productId ?? 'null' }};
    const selectedType = @json(auth()->user()?->preferred_product_type ?? session('preferred_product_type', request()->cookie('preferred_product_type', '')));
    const $ = id => document.getElementById(id);
    function esc(s){if(!s)return '';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
    const withSelectedType = (url) => selectedType ? `${url}${url.includes('?') ? '&' : '?'}type=${encodeURIComponent(selectedType)}` : url;

    if (!productId) { $('show-loading').classList.add('hidden'); $('product-error').classList.remove('hidden'); return; }

    try {
        const res = await window.axios.get(withSelectedType(`/api/products/${productId}`));
        const p = res.data.data;
        const photos = p.photos || [];
        const displayImage = p.image_url || '';

        $('product-name').textContent = p.name || '—';
        $('bc-name').textContent = p.name || 'Details';
        const avgRating = parseFloat(p.average_rating) || 0;
        const reviewCount = parseInt(p.review_count, 10) || 0;
        $('product-stars').innerHTML = renderStars(avgRating, 5, 'w-5 h-5');
        $('product-rating-text').textContent = reviewCount === 0 ? '(No ratings yet)' : '(' + reviewCount + ' ' + (reviewCount === 1 ? 'review' : 'reviews') + ')';
        const hasDiscount = !!p.has_active_discount;
        const effectivePrice = parseFloat(hasDiscount ? p.discounted_price : p.price || 0);
        $('product-price').textContent = effectivePrice.toLocaleString();
        $('product-price').className = hasDiscount
            ? 'text-3xl font-black text-red-600 dark:text-red-400'
            : 'text-3xl font-black text-gray-900 dark:text-white';
        if (hasDiscount) {
            $('product-price-original').classList.remove('hidden');
            $('product-price-original').textContent = parseFloat(p.price || 0).toLocaleString() + ' SYP';
        } else {
            $('product-price-original').classList.add('hidden');
            $('product-price-original').textContent = '';
        }
        $('product-quantity').textContent = (p.quantity || 0) + ' units';
        $('product-description').textContent = p.description || 'No description provided.';
        $('product-category').textContent = p.category?.name || '—';
        $('product-subcategory').textContent = p.subcategory?.name || '—';
        $('product-discount-status').textContent = formatDiscountStatus(p.discount_status);
        $('product-discount-value').textContent = p.discount_percentage ? `${parseFloat(p.discount_percentage).toFixed(2)}%` : 'No discount';
        $('product-discount-start').textContent = formatDateOnly(p.discount_starts_at);
        $('product-discount-end').textContent = formatDateOnly(p.discount_ends_at);

        const inStock = p.quantity > 0;
        $('product-availability').innerHTML = inStock
            ? '<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>In Stock</span>'
            : '<span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600 dark:bg-red-500/10 dark:text-red-400"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Out of Stock</span>';

        const btn = $('add-to-cart-btn');
        if (inStock) {
            btn.disabled = false;
            btn.onclick = () => {
                const primary = photos.find(ph => ph.is_primary) || photos[0];
                const url = displayImage || (primary ? (primary.url || `/storage/${primary.path}`) : '');
                window.addToCart(p.id, p.name, hasDiscount ? p.discounted_price : p.price, url);
            };
        } else {
            btn.innerHTML = '<span class="flex items-center justify-center gap-2"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>Out of Stock</span>';
            btn.disabled = true;
            btn.classList.replace('bg-gray-900', 'bg-gray-200');
            btn.classList.add('cursor-not-allowed');
            btn.classList.replace('dark:bg-white', 'dark:bg-gray-800');
            btn.classList.replace('dark:text-gray-900', 'dark:text-gray-500');
        }

        const primary = photos.find(ph => ph.is_primary) || photos[0];
        if (primary) {
            const url = primary.url || `/storage/${primary.path}`;
            $('primary-photo-container').innerHTML = `<img src="${url}" alt="${esc(p.name)}" class="h-full w-full object-contain p-4 transition-transform duration-500 hover:scale-105 cursor-zoom-in" onclick="window._viewLarge(this.src)" loading="eager">`;
        } else if (displayImage) {
            $('primary-photo-container').innerHTML = `<img src="${displayImage}" alt="${esc(p.name)}" class="h-full w-full object-contain p-4 transition-transform duration-500 hover:scale-105 cursor-zoom-in" onclick="window._viewLarge(this.src)" loading="eager">`;
        }

        $('photo-count').textContent = photos.length + ' photo' + (photos.length !== 1 ? 's' : '');
        $('product-photos').innerHTML = photos.length ? photos.map(ph => {
            const url = ph.url || `/storage/${ph.path}`;
            return `<button onclick="window._setPrimary('${esc(url)}')" class="group relative h-20 w-20 shrink-0 overflow-hidden rounded-xl border-2 ${ph.is_primary ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-gray-200 hover:border-brand-300 dark:border-gray-700 dark:hover:border-brand-500'} transition-all"><img src="${url}" class="h-full w-full object-contain bg-white p-1 dark:bg-gray-800" loading="lazy" alt=""><div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors"></div></button>`;
        }).join('') : '<p class="py-4 text-xs text-gray-400 dark:text-gray-500">No photos available.</p>';

        window._setPrimary = function(url) {
            $('primary-photo-container').innerHTML = `<img src="${url}" alt="${esc(p.name)}" class="h-full w-full object-contain p-4 transition-transform duration-500 hover:scale-105 cursor-zoom-in" onclick="window._viewLarge(this.src)" loading="eager">`;
        };
        window._viewLarge = function(url) {
            const m = document.createElement('div');
            m.className = 'fixed inset-0 z-[80] flex items-center justify-center bg-black/90 backdrop-blur-sm p-4';
            m.innerHTML = `<div class="relative max-h-[90vh] max-w-[90vw]"><img src="${url}" class="max-h-[90vh] max-w-[90vw] rounded-xl object-contain" alt=""><button onclick="this.closest('.fixed').remove()" class="absolute -right-2 -top-2 flex h-10 w-10 items-center justify-center rounded-full bg-white text-gray-900 shadow-xl hover:scale-110 transition-transform"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button></div>`;
            m.addEventListener('click', e => { if (e.target === m) m.remove(); });
            document.addEventListener('keydown', function h(e) { if (e.key === 'Escape') { m.remove(); document.removeEventListener('keydown', h); } });
            document.body.appendChild(m);
        };

        $('show-loading').classList.add('hidden');
        $('show-content').classList.remove('hidden');
        $('reviews-section').classList.remove('hidden');
        if (window.Auth?.getToken?.() || localStorage.getItem('auth_token')) {
            $('review-form-wrap').classList.remove('hidden');
            initReviewForm();
        }
        loadReviews(1);
    } catch (e) {
        console.error('Failed to load product:', e);
        $('show-loading').classList.add('hidden');
        $('product-error').classList.remove('hidden');
    }

    function formatDateOnly(value) {
        if (!value) {
            return '—';
        }

        const normalized = typeof value === 'string' ? value.replace(' ', 'T') : value;
        const date = new Date(normalized);
        if (Number.isNaN(date.getTime())) {
            return String(value).slice(0, 10);
        }

        return date.toLocaleDateString();
    }

    function formatDiscountStatus(status) {
        if (status === 'active') return 'Active';
        if (status === 'pending') return 'Pending';
        if (status === 'expired') return 'Expired';

        return '—';
    }

    function renderStars(rating, max, sizeClass) {
        const r = Math.min(max, Math.max(0, Math.round(rating)));
        const starFilled = '<svg class="' + (sizeClass || 'w-5 h-5') + ' text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        const starEmpty = '<svg class="' + (sizeClass || 'w-5 h-5') + ' text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        let html = '';
        for (let i = 0; i < max; i++) html += i < r ? starFilled : starEmpty;
        return html;
    }

    var REVIEWS_PER_PAGE = 5;
    window._reviewsCurrentPage = 1;
    window._reviewsLastPage = 1;
    function loadReviews(page) {
        var listEl = document.getElementById('reviews-list');
        var emptyEl = document.getElementById('reviews-empty');
        var paginationEl = document.getElementById('reviews-pagination');
        var subtitleEl = document.getElementById('reviews-subtitle');
        if (!listEl) return;
        listEl.innerHTML = '<p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">Loading...</p>';
        if (emptyEl) emptyEl.classList.add('hidden');
        if (paginationEl) paginationEl.innerHTML = '';
        if (subtitleEl) subtitleEl.textContent = '';
        var url = '/api/products/' + String(productId) + '/reviews';
        window.axios.get(url, { params: { page: page || 1, per_page: REVIEWS_PER_PAGE } }).then(function(res) {
            var data = (res && res.data && res.data.data) ? res.data.data : [];
            var meta = (res && res.data && res.data.meta) ? res.data.meta : {};
            var total = meta.total || 0;
            var currentPage = meta.current_page || 1;
            var lastPage = meta.last_page || 1;
            window._reviewsCurrentPage = currentPage;
            window._reviewsLastPage = lastPage;
            listEl.innerHTML = '';
            if (!Array.isArray(data) || data.length === 0) {
                if (page === 1) {
                    if (emptyEl) { emptyEl.classList.remove('hidden'); emptyEl.textContent = 'No reviews yet.'; }
                }
                return;
            }
            for (var i = 0; i < data.length; i++) {
                var r = data[i];
                var userName = (r.user && r.user.name) ? esc(r.user.name) : 'User';
                var body = (r.body) ? '<p class="mt-1 text-sm text-gray-600 dark:text-gray-400">' + esc(r.body) + '</p>' : '';
                var date = r.created_at ? new Date(r.created_at).toLocaleDateString(undefined, { dateStyle: 'medium' }) : '';
                var currentUserId = window.Auth && window.Auth.getUser && window.Auth.getUser() ? (window.Auth.getUser().id || null) : null;
                var canDelete = currentUserId && r.user && r.user.id === currentUserId;
                var deleteBtn = canDelete ? '<button type="button" data-review-id="' + r.id + '" class="review-delete-btn text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Delete</button>' : '';
                listEl.innerHTML += '<div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800" data-review-id="' + r.id + '">' +
                    '<div class="flex items-center justify-between gap-2">' +
                    '<span class="font-medium text-gray-900 dark:text-white">' + userName + '</span>' +
                    '<div class="flex items-center gap-2">' +
                    '<div class="flex text-amber-400">' + renderStars(r.rating, 5, 'w-4 h-4') + '</div>' +
                    (deleteBtn ? '<span class="ml-2">' + deleteBtn + '</span>' : '') +
                    '</div></div>' +
                    (date ? '<p class="text-[11px] text-gray-400 dark:text-gray-500">' + date + '</p>' : '') +
                    body + '</div>';
            }
            listEl.querySelectorAll('.review-delete-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var rid = btn.getAttribute('data-review-id');
                    if (!rid || !confirm('Delete this review?')) return;
                    if (window.Auth && window.Auth.applyToken) window.Auth.applyToken();
                    window.axios.delete('/api/products/' + productId + '/reviews/' + rid).then(function() {
                        loadReviews(window._reviewsCurrentPage);
                        window.axios.get(withSelectedType('/api/products/' + productId)).then(function(res) {
                            var p = res.data.data;
                            if (p) {
                                $('product-stars').innerHTML = renderStars(parseFloat(p.average_rating) || 0, 5, 'w-5 h-5');
                                $('product-rating-text').textContent = (parseInt(p.review_count, 10) || 0) === 0 ? '(No ratings yet)' : '(' + (p.review_count || 0) + ' ' + (p.review_count === 1 ? 'review' : 'reviews') + ')';
                            }
                        });
                    }).catch(function(err) {
                        alert(err.response && err.response.data && err.response.data.message ? err.response.data.message : 'Failed to delete review.');
                    });
                });
            });
            if (subtitleEl) subtitleEl.textContent = total ? '(' + total + ' ' + (total === 1 ? 'review' : 'reviews') + ')' : '';
            if (paginationEl && (lastPage > 1 || total > REVIEWS_PER_PAGE)) {
                paginationEl.innerHTML = '<button type="button" onclick="window._reviewsPrev()" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none" ' + (currentPage <= 1 ? 'disabled' : '') + '>Prev</button>' +
                    '<span class="text-sm text-gray-500 dark:text-gray-400">Page ' + currentPage + ' of ' + lastPage + (total ? ' (' + total + ')' : '') + '</span>' +
                    '<button type="button" onclick="window._reviewsNext()" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none" ' + (currentPage >= lastPage ? 'disabled' : '') + '>Next</button>';
            }
        }).catch(function(err) {
            listEl.innerHTML = '';
            if (emptyEl) {
                emptyEl.classList.remove('hidden');
                emptyEl.textContent = (err.response && err.response.status === 404) ? 'Reviews not available for this product.' : 'Failed to load reviews.';
            }
            console.error('Reviews load error:', err);
        });
    }
    window._reviewsPrev = function() { if (window._reviewsCurrentPage > 1) loadReviews(window._reviewsCurrentPage - 1); };
    window._reviewsNext = function() { if (window._reviewsCurrentPage < window._reviewsLastPage) loadReviews(window._reviewsCurrentPage + 1); };

    function initReviewForm() {
        const container = document.getElementById('review-stars-input');
        const hiddenInput = document.getElementById('review-rating-input');
        if (!container || !hiddenInput) return;
        container.querySelectorAll('.star-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const v = parseInt(btn.getAttribute('data-value'), 10);
                hiddenInput.value = v;
                container.setAttribute('data-rating', v);
                container.querySelectorAll('.star-btn').forEach(function(b) {
                    const bv = parseInt(b.getAttribute('data-value'), 10);
                    b.classList.toggle('text-amber-400', bv <= v);
                    b.classList.toggle('text-gray-300', bv > v);
                    b.classList.toggle('dark:text-gray-600', bv > v);
                });
            });
        });
        document.getElementById('review-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const rating = parseInt(hiddenInput.value, 10);
            if (rating < 1 || rating > 5) { alert('Please select a rating from 1 to 5 stars.'); return; }
            const body = document.getElementById('review-body').value.trim();
            const submitBtn = document.getElementById('review-submit-btn');
            submitBtn.disabled = true;
            if (window.Auth && window.Auth.applyToken) window.Auth.applyToken();
            window.axios.post('/api/products/' + productId + '/reviews', { rating: rating, body: body || null }).then(function(res) {
                submitBtn.disabled = false;
                hiddenInput.value = 0;
                container.setAttribute('data-rating', '0');
                container.querySelectorAll('.star-btn').forEach(function(b) {
                    b.classList.remove('text-amber-400');
                    b.classList.add('text-gray-300', 'dark:text-gray-600');
                });
                document.getElementById('review-body').value = '';
                var prod = res.data.product;
                if (prod) {
                    $('product-stars').innerHTML = renderStars(prod.average_rating, 5, 'w-5 h-5');
                    $('product-rating-text').textContent = (parseInt(prod.review_count, 10) || 0) === 0 ? '(No ratings yet)' : '(' + (prod.review_count || 0) + ' ' + (prod.review_count === 1 ? 'review' : 'reviews') + ')';
                }
                loadReviews(1);
            }).catch(function(err) {
                submitBtn.disabled = false;
                var msg = (err.response && err.response.data && err.response.data.message) ? err.response.data.message : 'Failed to submit review.';
                if (err.response && err.response.status === 401) msg = 'You must be logged in to review this product.';
                if (err.response && err.response.status === 422) msg = msg || 'Invalid data or review not allowed.';
                alert(msg);
            });
        });
    }
});
</script>
@endpush
