/**
 * One canonical commerce product card, shared by every catalog surface
 * (homepage sections, category pages, the products listing, vendor pages).
 *
 * Card hierarchy is fixed on purpose: image -> context -> title -> rating ->
 * price (+ previous price when discounted) -> availability -> action. Callers
 * only ever vary the link target, the context line, and the i18n strings -
 * never the markup - so every catalog surface renders literally the same
 * card grammar instead of four independent near-copies.
 */

import { formatNumber, formatPercent } from '@/lib/date-time';

function escapeHtml(value) {
    if (value === null || value === undefined || value === '') return '';
    const el = document.createElement('div');
    el.textContent = String(value);
    return el.innerHTML;
}

function starRating(rating) {
    const resolved = Math.min(5, Math.max(0, Math.round(parseFloat(rating) || 0)));
    const filled = '<svg class="h-3.5 w-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    const empty = '<svg class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    let html = '';
    for (let i = 0; i < 5; i++) html += i < resolved ? filled : empty;
    return html;
}

function renderProductCard(product, options = {}) {
    const {
        href = '/products/' + product.id,
        context = '',
        placeholder = '/images/product-placeholder.svg',
        soldOutLabel = '',
        inStockLabel = '',
        addToCartLabel = '',
        favoriteLabel = product.name || '',
        reviewsLabel = null,
        rank = null,
    } = options;

    const photo = product.first_photo_url || product.fallback_photo_url || placeholder;
    const inStock = Number(product.quantity || 0) > 0;
    const isFav = window._favIds && window._favIds.has(product.id);
    const reviewCount = parseInt(product.review_count, 10) || 0;
    const locale = document.documentElement.lang || 'en';
    const unitPrice = product.has_active_discount ? product.discounted_price : product.price;
    const displayPrice = formatNumber(unitPrice, locale);
    const reviewText = reviewCount && typeof reviewsLabel === 'function' ? reviewsLabel(reviewCount) : '';

    // Rank and discount both claim the media's top-start corner, so a card
    // never shows both at once - a bestseller position is a structural fact,
    // not a promotion, and the two would otherwise visually compete.
    const cornerBadge = rank !== null
        ? `<span class="product-card-rank">#${rank}</span>`
        : (product.has_active_discount
            ? `<span class="product-card-badge">-${formatPercent(product.discount_percentage, locale, { maximumFractionDigits: 0 })}</span>`
            : '');

    return `<article class="product-card">
        <div class="product-card-media">
            <a href="${href}" class="absolute inset-0 block">
                <img src="${escapeHtml(photo)}" alt="${escapeHtml(product.name)}" loading="lazy" onerror="this.onerror=null;this.src='${placeholder}'">
            </a>
            ${cornerBadge}
            <button type="button" data-fav-btn="${product.id}" onclick="event.stopPropagation();window.toggleFav(${product.id},this)" aria-label="${escapeHtml(favoriteLabel)}" aria-pressed="${isFav ? 'true' : 'false'}" class="product-card-fav ${isFav ? 'is-active' : ''}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav ? 'currentColor' : 'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
            </button>
            ${!inStock ? `<div class="product-card-media-overlay">${escapeHtml(soldOutLabel)}</div>` : ''}
        </div>
        <div class="product-card-body">
            ${context ? `<p class="product-card-context">${escapeHtml(context)}</p>` : ''}
            <a href="${href}"><h3 class="product-card-title">${escapeHtml(product.name)}</h3></a>
            ${reviewCount ? `<div class="product-card-rating">${starRating(product.average_rating)}<span>${escapeHtml(reviewText)}</span></div>` : ''}
            <div class="product-card-footer">
                <div class="product-card-price-group" dir="auto">
                    <span class="product-card-price">${displayPrice} <span class="product-card-price-currency">SYP</span></span>
                    ${product.has_active_discount ? `<span class="product-card-price-was">${formatNumber(product.price, locale)} SYP</span>` : ''}
                    <span class="product-card-stock ${inStock ? '' : 'is-out'}">${inStock ? escapeHtml(inStockLabel) : escapeHtml(soldOutLabel)}</span>
                </div>
                <button type="button" onclick="window.addToCart&&window.addToCart(${product.id},\`${escapeHtml(product.name)}\`,${unitPrice},\`${escapeHtml(photo)}\`)" class="product-card-cta" ${!inStock ? 'disabled' : ''} aria-label="${escapeHtml(addToCartLabel)}: ${escapeHtml(product.name)}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                </button>
            </div>
        </div>
    </article>`;
}

window.renderStarRating = starRating;
window.renderProductCard = renderProductCard;
window.escapeHtmlForCard = escapeHtml;
