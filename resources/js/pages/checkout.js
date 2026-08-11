/**
 * Checkout page (spec §7).
 *
 * Renders GET /api/checkout/summary and POSTs {address_id, payment_method} to
 * /api/checkout. It computes nothing: subtotal, discount, shipping, tax and
 * total are all read from the server response, and are re-read after every
 * change so what the shopper confirms is what the backend will charge.
 */

import '../bootstrap';

const el = (id) => document.getElementById(id);
const strings = () => window.__checkoutStrings || {};

let summary = null;
let selectedAddressId = null;
let selectedPaymentMethod = null;

function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

function money(amount, currency) {
    const locale = document.documentElement.lang || 'en';
    const value = Number(amount) || 0;
    return `${value.toLocaleString(locale, { minimumFractionDigits: 0, maximumFractionDigits: 2 })} ${currency || ''}`.trim();
}

function showError(message) {
    const box = el('checkout-error');
    if (!box) return;

    if (!message) {
        box.classList.add('hidden');
        box.textContent = '';
        return;
    }

    box.textContent = message;
    box.classList.remove('hidden');
}

function renderAddresses() {
    const container = el('checkout-addresses');
    const emptyNote = el('checkout-no-addresses');
    const addresses = summary?.addresses || [];

    if (!addresses.length) {
        container.innerHTML = '';
        emptyNote?.classList.remove('hidden');
        selectedAddressId = null;
        return;
    }

    emptyNote?.classList.add('hidden');

    if (!addresses.some((address) => address.id === selectedAddressId)) {
        selectedAddressId = (addresses.find((address) => address.is_default) || addresses[0]).id;
    }

    container.innerHTML = addresses.map((address) => {
        const checked = address.id === selectedAddressId;
        const line = [address.street, address.district, address.city, address.governorate]
            .filter(Boolean).map(escapeHtml).join('، ');

        return `
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors ${
                checked
                    ? 'border-brand-500 bg-brand-50/60 dark:border-brand-400 dark:bg-brand-500/10'
                    : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
            }">
                <input type="radio" name="checkout-address" value="${address.id}" ${checked ? 'checked' : ''}
                    class="mt-1 h-4 w-4 shrink-0 accent-brand-500">
                <span class="min-w-0 flex-1">
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">${escapeHtml(address.recipient_name)}</span>
                        ${address.is_default ? `<span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">${escapeHtml(strings().default || '')}</span>` : ''}
                    </span>
                    <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">${line}</span>
                    <span class="mt-0.5 block text-xs text-gray-400" dir="ltr">${escapeHtml(address.phone)}</span>
                </span>
            </label>`;
    }).join('');
}

function renderItems() {
    const container = el('checkout-items');
    const items = summary?.cart?.items || [];
    const currency = summary?.totals?.currency;

    const vendorIds = new Set(items.map((item) => item.vendor_id));
    el('checkout-multi-vendor')?.classList.toggle('hidden', vendorIds.size < 2);

    container.innerHTML = items.map((item) => `
        <div class="flex items-start justify-between gap-4 py-3">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-gray-900 dark:text-white">${escapeHtml(item.name)}</p>
                ${item.vendor_name ? `<p class="truncate text-xs text-gray-400">${escapeHtml(strings().sold_by || '')} ${escapeHtml(item.vendor_name)}</p>` : ''}
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    ${escapeHtml(strings().qty || '')} ${item.quantity} × ${escapeHtml(money(item.unit_price, currency))}
                </p>
            </div>
            <p class="shrink-0 text-sm font-semibold tabular-nums text-gray-900 dark:text-white">${escapeHtml(money(item.line_total, currency))}</p>
        </div>`).join('');
}

function renderPaymentMethods() {
    const container = el('checkout-payment-methods');
    const methods = summary?.payment_methods || [];

    if (!methods.includes(selectedPaymentMethod)) {
        selectedPaymentMethod = methods[0] || null;
    }

    // Only methods the backend actually reports are rendered. No gateway is
    // shown as "coming soon" or disabled-but-present (spec §11).
    container.innerHTML = methods.map((method) => {
        const checked = method === selectedPaymentMethod;
        return `
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 ${
                checked
                    ? 'border-brand-500 bg-brand-50/60 dark:border-brand-400 dark:bg-brand-500/10'
                    : 'border-gray-200 dark:border-gray-700'
            }">
                <input type="radio" name="checkout-payment" value="${escapeHtml(method)}" ${checked ? 'checked' : ''}
                    class="mt-1 h-4 w-4 shrink-0 accent-brand-500">
                <span>
                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">${escapeHtml(strings().cash || method)}</span>
                    <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">${escapeHtml(strings().cash_hint || '')}</span>
                </span>
            </label>`;
    }).join('');

    if (methods.length === 1) {
        container.insertAdjacentHTML('beforeend',
            `<p class="pt-1 text-xs text-gray-400">${escapeHtml(strings().only_method || '')}</p>`);
    }
}

function renderTotals() {
    const totals = summary?.totals || {};
    const currency = totals.currency;

    el('sum-subtotal').textContent = money(totals.subtotal, currency);
    el('sum-total').textContent = money(totals.grand_total, currency);

    const discount = Number(totals.discount_total) || 0;
    el('sum-discount-row').classList.toggle('hidden', discount <= 0);
    el('sum-discount').textContent = `− ${money(discount, currency)}`;

    const shipping = Number(totals.shipping_total) || 0;
    el('sum-shipping').textContent = shipping > 0 ? money(shipping, currency) : (strings().shipping_free || '');

    const tax = Number(totals.tax_total) || 0;
    el('sum-tax-row').classList.toggle('hidden', tax <= 0);
    el('sum-tax').textContent = money(tax, currency);

    if (summary?.coupon?.code) {
        el('checkout-coupon').value = summary.coupon.code;
    }
}

function render() {
    const hasItems = (summary?.cart?.items || []).length > 0;

    el('checkout-loading').classList.add('hidden');
    el('checkout-empty').classList.toggle('hidden', hasItems);
    el('checkout-body').classList.toggle('hidden', !hasItems);

    if (!hasItems) return;

    renderAddresses();
    renderItems();
    renderPaymentMethods();
    renderTotals();

    (summary?.notices || []).forEach((notice) => window.AppToast?.show(notice, 'warning'));
}

async function loadSummary() {
    try {
        const response = await window.axios.get('/api/checkout/summary', { silent: true });
        summary = response.data?.data;
        render();
    } catch (error) {
        el('checkout-loading').classList.add('hidden');
        showError(error.response?.data?.message || '');
    }
}

async function placeOrder() {
    showError('');

    if (!selectedAddressId) {
        showError(strings().address_required || '');
        return;
    }

    const button = el('checkout-place-order');
    button.disabled = true;
    button.textContent = strings().placing || '';

    try {
        const response = await window.axios.post('/api/checkout', {
            address_id: selectedAddressId,
            payment_method: selectedPaymentMethod,
        }, { silent: true });

        const data = response.data?.data;

        el('checkout-body').classList.add('hidden');
        el('checkout-success').classList.remove('hidden');
        el('checkout-success-message').textContent = response.data?.message || '';
        el('checkout-success-orders').innerHTML = (data?.orders || [])
            .map((order) => `<p class="font-mono text-xs">${escapeHtml(order.order_number)}</p>`)
            .join('');

        window.refreshCart?.();
    } catch (error) {
        showError(error.response?.data?.message || '');
        // The cart may have been reconciled server-side during the attempt, so
        // re-read rather than leaving a stale total on screen.
        await loadSummary();
    } finally {
        button.disabled = false;
        button.textContent = strings().place_order || '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    el('checkout-addresses')?.addEventListener('change', (event) => {
        if (event.target.name === 'checkout-address') {
            selectedAddressId = Number(event.target.value);
            renderAddresses();
        }
    });

    el('checkout-payment-methods')?.addEventListener('change', (event) => {
        if (event.target.name === 'checkout-payment') {
            selectedPaymentMethod = event.target.value;
            renderPaymentMethods();
        }
    });

    el('checkout-coupon-apply')?.addEventListener('click', async () => {
        const code = String(el('checkout-coupon').value || '').trim();
        if (!code) return;

        try {
            await window.axios.post('/api/cart/coupon', { coupon_code: code }, { silent: true });
            showError('');
        } catch (error) {
            showError(error.response?.data?.message || '');
        }

        await loadSummary();
    });

    const form = el('checkout-address-form');

    el('checkout-new-address-btn')?.addEventListener('click', () => form?.classList.toggle('hidden'));
    el('checkout-address-cancel')?.addEventListener('click', () => form?.classList.add('hidden'));

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            const response = await window.axios.post('/api/addresses', payload, { silent: true });
            selectedAddressId = response.data?.data?.id ?? selectedAddressId;
            form.reset();
            form.classList.add('hidden');
            showError('');
            await loadSummary();
        } catch (error) {
            const errors = error.response?.data?.errors;
            const first = errors ? Object.values(errors).flat().find(Boolean) : null;
            showError(first || error.response?.data?.message || '');
        }
    });

    el('checkout-place-order')?.addEventListener('click', placeOrder);

    loadSummary();
});
