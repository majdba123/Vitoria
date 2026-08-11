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
    const unitPrice = product.has_active_discount ? product.discounted_price : product.price;
    const displayPrice = parseFloat(unitPrice || 0).toLocaleString();
    const reviewText = reviewCount && typeof reviewsLabel === 'function' ? reviewsLabel(reviewCount) : '';

    const cornerBadge = rank !== null
        ? `<span class="product-card-rank">#${rank}</span>`
        : (product.has_active_discount
            ? `<span class="absolute start-2.5 top-2.5 z-10 rounded-md bg-danger-600 px-2 py-1 text-[10px] font-bold text-white">-${parseFloat(product.discount_percentage || 0).toFixed(0)}%</span>`
            : '');

    return `<article class="commerce-product-card">
        <div class="relative">
            <a href="${href}" class="commerce-product-media block">
                <img src="${escapeHtml(photo)}" alt="${escapeHtml(product.name)}" loading="lazy" onerror="this.onerror=null;this.src='${placeholder}'">
                ${!inStock ? `<div class="absolute inset-x-0 bottom-0 bg-danger-600/95 px-3 py-1.5 text-center text-[11px] font-semibold text-white">${escapeHtml(soldOutLabel)}</div>` : ''}
            </a>
            ${cornerBadge}
            <button type="button" data-fav-btn="${product.id}" onclick="event.stopPropagation();window.toggleFav(${product.id},this)" aria-label="${escapeHtml(favoriteLabel)}" aria-pressed="${isFav ? 'true' : 'false'}" class="absolute end-2.5 top-2.5 z-10 flex h-11 w-11 items-center justify-center rounded-full border bg-white/95 dark:bg-gray-900/95 ${isFav ? 'text-danger-500' : 'text-gray-500 dark:text-gray-400'}" style="border-color:var(--color-border)">
                <svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="${isFav ? 'currentColor' : 'none'}"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
            </button>
        </div>
        <div class="commerce-product-body">
            ${context ? `<p class="commerce-product-context">${escapeHtml(context)}</p>` : ''}
            <a href="${href}"><h3 class="commerce-product-title">${escapeHtml(product.name)}</h3></a>
            <div class="mt-2 flex items-center gap-1.5">${starRating(product.average_rating)}<span class="text-[11px] text-gray-400 dark:text-gray-500">${reviewText ? escapeHtml(reviewText) : ''}</span></div>
            <div class="mt-auto pt-4">
                <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5" dir="auto">
                    <span class="commerce-product-price">${displayPrice}</span><span class="text-xs" style="color: var(--color-text-muted);">SYP</span>
                    ${product.has_active_discount ? `<span class="text-[11px] line-through" style="color: var(--color-text-muted);">${parseFloat(product.price || 0).toLocaleString()} SYP</span>` : ''}
                </div>
                <div class="mt-3 flex items-center justify-between gap-2">
                    <span class="text-xs font-medium ${inStock ? 'text-success-700 dark:text-success-400' : 'text-danger-700 dark:text-danger-400'}">${inStock ? escapeHtml(inStockLabel) : escapeHtml(soldOutLabel)}</span>
                    <button type="button" onclick="window.addToCart&&window.addToCart(${product.id},\`${escapeHtml(product.name)}\`,${unitPrice},\`${escapeHtml(photo)}\`)" class="commerce-product-action" ${!inStock ? 'disabled' : ''} aria-label="${escapeHtml(addToCartLabel)}: ${escapeHtml(product.name)}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </article>`;
}

window.renderStarRating = starRating;
window.renderProductCard = renderProductCard;
window.escapeHtmlForCard = escapeHtml;
