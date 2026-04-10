@extends('layouts.admin')

@section('title', 'Order Details — SyriaZone Admin')
@section('page-title', 'Order Details')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.orders.index') }}" class="hover:text-gray-700">Orders</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900 dark:text-white">Details</span>
    </nav>

    <div id="order-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading order details...</p>
    </div>

    <div id="order-content" class="hidden space-y-6">
        <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="bg-gradient-to-r from-brand-500/10 via-brand-400/5 to-transparent px-5 py-4 dark:from-brand-500/20 dark:via-brand-400/10">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="o-number" class="inline-flex rounded-xl bg-brand-600 px-3 py-1 text-lg font-black text-white shadow-sm">—</h2>
                        <p id="o-number-fallback" class="mt-1 text-sm font-bold text-gray-900 dark:text-white">Order Number: —</p>
                        <p id="o-meta" class="mt-1 text-sm text-gray-600 dark:text-gray-300">—</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="o-status" class="rounded-full px-3 py-1 text-xs font-bold">pending</span>
                        <span id="o-payment" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700">cash</span>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button id="admin-complete-btn" type="button" class="hidden rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-emerald-700">Mark as Completed</button>
                    <p id="admin-action-msg" class="hidden text-xs font-semibold"></p>
                </div>
            </div>
            <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                <div data-order-surface="true" class="rounded-2xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60"><p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Order ID</p><p id="o-id" class="mt-1 text-sm font-bold text-gray-900 dark:text-white">—</p></div>
                <div data-order-surface="true" class="rounded-2xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60"><p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">User ID</p><p id="o-user-id" class="mt-1 text-sm font-bold text-gray-900 dark:text-white">—</p></div>
                <div data-order-surface="true" class="rounded-2xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60"><p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Vendor ID</p><p id="o-vendor-id" class="mt-1 text-sm font-bold text-gray-900 dark:text-white">—</p></div>
                <div data-order-surface="true" class="rounded-2xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60"><p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Items Count</p><p id="o-items-count" class="mt-1 text-sm font-bold text-gray-900 dark:text-white">—</p></div>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Customer Info</h3>
                <div class="mt-3 space-y-2 text-xs text-gray-600 dark:text-gray-300">
                    <p><span class="text-gray-400">Name:</span> <span id="o-user-name">—</span></p>
                    <p><span class="text-gray-400">Email:</span> <span id="o-user-email">—</span></p>
                </div>
            </div>
            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Vendor Info</h3>
                <div class="mt-3 space-y-2 text-xs text-gray-600 dark:text-gray-300">
                    <p><span class="text-gray-400">Store:</span> <span id="o-vendor-name">—</span></p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-gray-200/80 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-black text-gray-900 dark:text-white">Items Details</h3>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">Full Snapshot</span>
            </div>
            <div id="o-items" class="mt-3 space-y-3"></div>
        </div>

        <div class="rounded-3xl border border-gray-200/80 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Totals & Coupon</h3>
            <div class="mt-3 grid gap-2 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800"><span>Subtotal</span><span id="o-subtotal">0</span></div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800"><span>Coupon Code</span><span id="o-coupon-code">—</span></div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800"><span>Coupon Type</span><span id="o-coupon-type">—</span></div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800"><span>Coupon Value</span><span id="o-coupon-value">—</span></div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800"><span>Coupon Discount</span><span id="o-coupon-discount">0</span></div>
                <div class="flex items-center justify-between rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 font-black text-brand-700 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-300"><span>Total</span><span id="o-total">0</span></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const orderId = '{{ $orderId }}';
    const highContrastNumberIds = ['o-number', 'o-number-fallback', 'o-id', 'o-user-id', 'o-vendor-id', 'o-items-count', 'o-total'];
    const dataValueIds = ['o-meta', 'o-user-name', 'o-user-email', 'o-vendor-name', 'o-subtotal', 'o-coupon-code', 'o-coupon-type', 'o-coupon-value', 'o-coupon-discount'];

    function applyNumberContrast() {
        const isDark = document.documentElement.classList.contains('dark');
        const primaryTextColor = isDark ? '#f9fafb' : '#111827';
        const secondaryTextColor = isDark ? '#d1d5db' : '#4b5563';

        highContrastNumberIds.forEach((id) => {
            const el = document.getElementById(id);
            if (!el) {
                return;
            }

            if (id === 'o-number') {
                el.style.setProperty('background-color', '#0284c7', 'important');
                el.style.setProperty('color', '#ffffff', 'important');
                return;
            }

            el.style.setProperty('color', primaryTextColor, 'important');
            el.style.setProperty('font-weight', '700', 'important');
        });

        dataValueIds.forEach((id) => {
            const el = document.getElementById(id);
            if (!el) {
                return;
            }
            el.style.setProperty('color', id === 'o-meta' ? secondaryTextColor : primaryTextColor, 'important');
            if (id !== 'o-meta') {
                el.style.setProperty('font-weight', '600', 'important');
            }
        });

        // Force all dynamic order values visible in both modes.
        document.querySelectorAll('[data-order-value="true"]').forEach((el) => {
            el.style.setProperty('color', primaryTextColor, 'important');
            el.style.setProperty('font-weight', '600', 'important');
        });

        document.querySelectorAll('[data-order-surface="true"]').forEach((el) => {
            if (isDark) {
                el.style.setProperty('background-color', '#1f2937', 'important');
                el.style.setProperty('border-color', '#374151', 'important');
            } else {
                el.style.setProperty('background-color', '#f9fafb', 'important');
                el.style.setProperty('border-color', '#f3f4f6', 'important');
            }
        });
    }

    try {
        const response = await window.axios.get('/api/admin/orders/' + orderId);
        const order = response.data.data;

        const visibleOrderNumber = order.order_number || ('Order #' + order.id);
        document.getElementById('o-number').textContent = visibleOrderNumber;
        document.getElementById('o-number-fallback').textContent = 'Order Number: ' + visibleOrderNumber;
        document.getElementById('o-meta').textContent = (order.created_at ? new Date(order.created_at).toLocaleDateString() : '—') + ' · Last update: ' + (order.updated_at ? new Date(order.updated_at).toLocaleDateString() : '—');
        const status = String(order.status || 'pending').toLowerCase();
        const statusEl = document.getElementById('o-status');
        statusEl.textContent = status;
        statusEl.className = 'rounded-full px-2.5 py-1 text-[11px] font-semibold ' + statusClass(status);
        renderAdminAction(status);

        document.getElementById('o-payment').textContent = order.payment_way || 'cash';
        document.getElementById('o-id').textContent = order.id ?? '—';
        document.getElementById('o-user-id').textContent = order.user_id ?? '—';
        document.getElementById('o-vendor-id').textContent = order.vendor_id ?? '—';
        document.getElementById('o-items-count').textContent = order.items_count ?? (order.items || []).length;
        document.getElementById('o-user-name').textContent = order.user?.name || '—';
        document.getElementById('o-user-email').textContent = order.user?.email || '—';
        document.getElementById('o-vendor-name').textContent = order.vendor?.store_name || '—';

        document.getElementById('o-subtotal').textContent = money(order.subtotal_amount);
        document.getElementById('o-coupon-code').textContent = order.coupon_code || '—';
        document.getElementById('o-coupon-type').textContent = order.coupon_type || '—';
        document.getElementById('o-coupon-value').textContent = order.coupon_value ? Number.parseFloat(order.coupon_value).toLocaleString() : '—';
        document.getElementById('o-coupon-discount').textContent = '- ' + money(order.coupon_discount_amount);
        document.getElementById('o-total').textContent = money(order.total_amount);

        document.getElementById('o-items').innerHTML = (order.items || []).map((item, index) => {
            return `<article class="rounded-2xl border border-gray-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p data-order-value="true" class="text-sm font-black text-gray-900 dark:text-white">${index + 1}. ${esc(item.product_name || 'Product')}</p>
                        <p data-order-value="true" class="text-[11px] text-gray-500 dark:text-gray-400">Item #${item.id ?? '—'} · Product #${item.product_id ?? '—'} · Qty ${item.quantity ?? 0}</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        ${item.has_discount ? '<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">Discount Applied</span>' : '<span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-300">Standard Price</span>'}
                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">${money(item.line_total)}</span>
                    </div>
                </div>
                <div class="mt-3 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3">
                    ${paramCard('Category', item.product?.subcategory?.category?.name || '—')}
                    ${paramCard('Subcategory', item.product?.subcategory?.name || '—')}
                    ${paramCard('Original Unit Price', money(item.original_unit_price))}
                    ${paramCard('Applied Unit Price', money(item.unit_price))}
                    ${paramCard('Discount %', item.has_discount ? (Number.parseFloat(item.applied_discount_percentage || 0).toLocaleString() + '%') : '—')}
                    ${paramCard('Saved Amount', item.has_discount ? money(item.discount_amount) : '0 SYP')}
                    ${paramCard('Line Total', money(item.line_total))}
                </div>
            </article>`;
        }).join('');

        document.getElementById('order-loading').classList.add('hidden');
        document.getElementById('order-content').classList.remove('hidden');
        applyNumberContrast();
    } catch (error) {
        document.getElementById('order-loading').innerHTML = `<p class="text-sm font-medium text-red-500">${esc(error.response?.data?.message || 'Failed to load order details.')}</p>`;
    }

    const observer = new MutationObserver(applyNumberContrast);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    function money(v) {
        return Number.parseFloat(v || 0).toLocaleString() + ' SYP';
    }

    function statusClass(status) {
        const classes = {
            pending: 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
            confirmed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
            completed: 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
            cancelled: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        };
        return classes[status] || classes.pending;
    }

    function renderAdminAction(status) {
        const btn = document.getElementById('admin-complete-btn');
        if (!btn) {
            return;
        }
        if (status === 'cancelled' || status === 'completed') {
            btn.classList.add('hidden');
            return;
        }
        btn.classList.remove('hidden');
        btn.onclick = async function () {
            btn.disabled = true;
            btn.textContent = 'Updating...';
            try {
                const res = await window.axios.patch('/api/admin/orders/' + orderId + '/complete');
                showActionMessage(res.data?.message || 'Order marked as completed.', 'success');
                setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                showActionMessage(error.response?.data?.message || 'Failed to update order status.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Mark as Completed';
            }
        };
    }

    function showActionMessage(message, type) {
        const el = document.getElementById('admin-action-msg');
        if (!el) {
            return;
        }
        el.className = 'text-xs font-semibold ' + (type === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400');
        el.textContent = message;
        el.classList.remove('hidden');
    }

    function esc(value) {
        if (!value) return '';
        const d = document.createElement('div');
        d.textContent = value;
        return d.innerHTML;
    }

    function paramCard(label, value) {
        return `<div data-order-surface="true" class="rounded-lg border border-gray-100 bg-gray-50 px-2.5 py-2 dark:border-gray-800 dark:bg-gray-800/60">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">${esc(label)}</p>
            <p data-order-value="true" class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">${esc(String(value ?? '—'))}</p>
        </div>`;
    }
});
</script>
@endpush
