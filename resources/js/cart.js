/**
 * Storefront cart — thin client over the authoritative server cart (spec §5).
 *
 * This module holds no pricing logic. It sends intent to /api/cart and renders
 * whatever the server returns. Quantities are echoed optimistically so the badge
 * feels instant, but every response overwrites local state, so the server always
 * wins. Nothing is persisted to localStorage: the cart lives in the database and
 * survives device changes and login.
 */

import { formatNumber } from '@/lib/date-time';

const state = {
    items: [],
    itemsCount: 0,
    subtotal: 0,
    discount: 0,
    total: 0,
    currency: 'SYP',
    coupon: null,
    loaded: false,
    // Guards against a rapid double-click (e.g. tapping "+" again before a
    // "-" that just removed the line has re-rendered) sending a second
    // mutation for a line the server already dropped, which previously
    // surfaced as a confusing "item is not in your cart" error.
    pending: false,
};

function strings() {
    return window.__appStrings || {};
}

function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    const el = document.createElement('div');
    el.textContent = String(value);
    return el.innerHTML;
}

/**
 * Currency comes from the server response, never from a hardcoded literal
 * (spec §17). Formatting follows the page locale so Arabic renders correctly.
 */
function formatMoney(amount) {
    const locale = document.documentElement.lang || 'en';

    return `${formatNumber(amount, locale, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} <span class="text-sm font-normal text-gray-400">${escapeHtml(state.currency)}</span>`;
}

function applyPayload(payload) {
    if (!payload) return;

    state.items = Array.isArray(payload.items) ? payload.items : [];
    state.itemsCount = Number(payload.items_count) || 0;
    state.subtotal = Number(payload.subtotal) || 0;
    state.discount = Number(payload.discount) || 0;
    state.total = payload.total !== undefined ? Number(payload.total) : state.subtotal;
    state.currency = payload.currency || state.currency;
    state.coupon = payload.coupon || null;
    state.loaded = true;

    render();
    updateBadge();
    window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { ...state } }));
}

function toast(message, type = 'success') {
    if (!message) return;
    window.AppToast?.show(message, type);
}

function showMessage(message, type = 'info') {
    const el = document.getElementById('cart-backend-message');
    if (!el) return;

    if (!message) {
        el.textContent = '';
        el.className = 'mb-3 hidden rounded-xl border px-3 py-2 text-xs font-semibold';
        return;
    }

    const palette = {
        success: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
        error: 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
        info: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
    };

    el.className = `mb-3 rounded-xl border px-3 py-2 text-xs font-semibold ${palette[type] || palette.info}`;
    el.textContent = message;
}

function updateBadge(animate = false) {
    const badge = document.getElementById('cart-badge');
    if (!badge) return;

    if (state.itemsCount > 0) {
        badge.textContent = state.itemsCount > 99 ? '99+' : String(state.itemsCount);
        badge.classList.remove('hidden');
        if (animate) {
            badge.classList.add('animate-bounce');
            setTimeout(() => badge.classList.remove('animate-bounce'), 600);
        }
        return;
    }

    badge.textContent = '';
    badge.classList.add('hidden');
}

function renderLine(item) {
    const s = strings();
    const removeLabel = escapeHtml(s.cart_remove);
    const decreaseQuantityLabel = escapeHtml(s.decrease_quantity);
    const increaseQuantityLabel = escapeHtml(s.increase_quantity);
    const atStockCeiling = item.quantity >= item.available_quantity;

    return `
        <div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 dark:border-gray-800 dark:bg-gray-800/50">
            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-gray-50 dark:bg-gray-800">
                ${item.photo
                    ? `<img src="${escapeHtml(item.photo)}" class="h-full w-full object-contain p-1" alt="">`
                    : `<div class="flex h-full items-center justify-center"><svg class="h-5 w-5 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/></svg></div>`}
            </div>
            <div class="min-w-0 flex-1">
                <h4 class="truncate text-sm font-bold text-gray-900 dark:text-white">${escapeHtml(item.name)}</h4>
                ${item.vendor_name ? `<p class="truncate text-[11px] text-gray-400">${escapeHtml(item.vendor_name)}</p>` : ''}
                <p class="text-xs text-gray-500">${formatMoney(item.unit_price)}</p>
                <p class="text-xs font-bold text-brand-600 dark:text-brand-400">${formatMoney(item.line_total)}</p>
            </div>
            <div class="flex flex-col items-end gap-2">
                <div class="flex items-center rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                    <button type="button" data-cart-action="decrement" data-product-id="${item.product_id}" data-quantity="${item.quantity - 1}"
                        class="flex h-7 w-7 items-center justify-center text-gray-500 hover:text-brand-600" aria-label="${decreaseQuantityLabel}">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M19.5 12h-15"/></svg>
                    </button>
                    <span class="w-6 text-center text-xs font-bold tabular-nums dark:text-white">${item.quantity}</span>
                    <button type="button" data-cart-action="increment" data-product-id="${item.product_id}" data-quantity="${item.quantity + 1}"
                        class="flex h-7 w-7 items-center justify-center text-gray-500 hover:text-brand-600 disabled:cursor-not-allowed disabled:opacity-40"
                        ${atStockCeiling ? 'disabled' : ''} aria-label="${increaseQuantityLabel}">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>
                <button type="button" data-cart-action="remove" data-product-id="${item.product_id}"
                    class="text-[10px] font-semibold text-red-500 hover:text-red-700">${removeLabel}</button>
            </div>
        </div>`;
}

function render() {
    const container = document.getElementById('cart-items');
    const empty = document.getElementById('cart-empty');
    const totalEl = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('checkout-btn');
    const count = document.getElementById('cart-item-count');

    if (!container) return;

    if (count) {
        const label = state.itemsCount === 1
            ? count.getAttribute('data-item')
            : count.getAttribute('data-items');
        count.textContent = `${state.itemsCount} ${label || ''}`.trim();
    }

    if (!state.items.length) {
        container.innerHTML = '';
        empty?.classList.remove('hidden');
        checkoutBtn?.classList.add('hidden');
        if (totalEl) totalEl.innerHTML = formatMoney(0);
        return;
    }

    empty?.classList.add('hidden');
    checkoutBtn?.classList.remove('hidden');
    container.innerHTML = state.items.map(renderLine).join('');
    if (totalEl) totalEl.innerHTML = formatMoney(state.total);
}

/**
 * Every mutation funnels through here so error handling and state replacement
 * are identical regardless of which control the shopper used.
 */
async function mutate(request, { successMessage = null, animateBadge = false } = {}) {
    state.pending = true;
    setBusy(true);

    try {
        const response = await request();
        applyPayload(response.data?.data);
        updateBadge(animateBadge);

        (response.data?.notices || []).forEach((notice) => toast(notice, 'warning'));

        const message = successMessage ?? response.data?.message;
        if (message) showMessage(message, 'success');

        return response.data;
    } catch (error) {
        const message = error.response?.data?.message || strings().checkout_failed || '';
        showMessage(message, 'error');
        toast(message, 'error');
        // Resync so the UI never drifts from the server after a rejection.
        await sync();
        throw error;
    } finally {
        state.pending = false;
        setBusy(false);
    }
}

/**
 * Disables pointer interaction on the cart line list while a mutation is
 * in flight, so a rapid double-tap on +/- or remove can't fire a second
 * request against a line the first request is about to change or delete.
 */
function setBusy(busy) {
    const container = document.getElementById('cart-items');
    if (!container) return;
    container.classList.toggle('pointer-events-none', busy);
    container.classList.toggle('opacity-60', busy);
    container.setAttribute('aria-busy', busy ? 'true' : 'false');
}

async function sync() {
    try {
        const response = await window.axios.get('/api/cart', { silent: true });
        applyPayload(response.data?.data);
    } catch {
        // A failed sync leaves the last known state in place rather than
        // blanking a cart the shopper can still see.
    }
}

/*
|--------------------------------------------------------------------------
| Public API — signatures kept compatible with the previous cart
|--------------------------------------------------------------------------
|
| Product cards across the storefront call addToCart(id, name, price, photo).
| Only the id is used now; name, price and photo are ignored because the server
| is the source of truth for all three. Keeping the signature avoids touching
| every call site and removes any chance of a client-supplied price being sent.
|
*/

window.addToCart = function (id) {
    return mutate(
        () => window.axios.post('/api/cart/items', { product_id: Number(id), quantity: 1 }, { silent: true }),
        { animateBadge: true },
    ).then(() => {
        toast(strings().cart_added || '', 'success');
    }).catch(() => {});
};

window.removeFromCart = function (id) {
    return mutate(
        () => window.axios.delete(`/api/cart/items/${Number(id)}`, { silent: true }),
    ).catch(() => {});
};

window.updateQty = function (id, quantity) {
    return mutate(
        () => window.axios.patch('/api/cart/items', {
            product_id: Number(id),
            quantity: Math.max(0, Number(quantity) || 0),
        }, { silent: true }),
    ).catch(() => {});
};

window.applyCartCoupon = function (code) {
    return mutate(
        () => window.axios.post('/api/cart/coupon', { coupon_code: String(code || '').trim() }, { silent: true }),
    ).catch(() => {});
};

window.clearCart = function () {
    return mutate(() => window.axios.delete('/api/cart', { silent: true })).catch(() => {});
};

window.refreshCart = sync;
window.getCartState = () => ({ ...state });

window.showCart = function () {
    showMessage('');
    const modal = document.getElementById('cart-modal');
    if (!modal) return;

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    modal.querySelector('button')?.focus();

    // Reconcile on open: stock and prices may have moved since the last render.
    sync();
};

window.closeCartModal = function () {
    showMessage('');
    document.getElementById('cart-modal')?.classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('nav-cart')?.focus();
};

/**
 * Checkout requires a delivery address, so the modal hands off to the checkout
 * page rather than placing the order inline (spec §7).
 */
window.checkoutCart = function () {
    if (!state.items.length) {
        const message = strings().cart_empty_msg || '';
        showMessage(message, 'error');
        return;
    }

    if (!window.Auth?.isAuthenticated()) {
        window.location.href = '/login?redirect=' + encodeURIComponent('/checkout');
        return;
    }

    window.location.href = '/checkout';
};

/*
|--------------------------------------------------------------------------
| Wiring
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', () => {
    // Delegated so re-rendered lines never need re-binding, and so no inline
    // onclick handler has to be trusted with a product id.
    document.getElementById('cart-items')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-cart-action]');
        if (!button) return;

        // Belt-and-suspenders alongside setBusy()'s pointer-events:none — that
        // CSS guard doesn't stop a keyboard Enter/Space activation on a
        // focused button, so check the flag directly too.
        if (state.pending) return;

        const productId = Number(button.dataset.productId);
        const action = button.dataset.action || button.dataset.cartAction;

        if (action === 'remove') {
            window.removeFromCart(productId);
            return;
        }

        window.updateQty(productId, Number(button.dataset.quantity));
    });

    document.getElementById('checkout-btn')?.addEventListener('click', () => window.checkoutCart());
    document.getElementById('cart-order-success-close')?.addEventListener('click', () => window.closeCartModal());

    const couponInput = document.getElementById('cart-coupon-code');
    couponInput?.addEventListener('change', () => {
        const code = String(couponInput.value || '').trim();
        if (code) window.applyCartCoupon(code);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !document.getElementById('cart-modal')?.classList.contains('hidden')) {
            window.closeCartModal();
        }
    });

    sync();
});
