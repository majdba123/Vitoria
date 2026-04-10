@extends('layouts.app')

@section('title', 'Order Details — SyriaZone')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-slate-50 to-white dark:from-gray-950 dark:to-gray-950">
    <div class="border-b border-gray-200/80 bg-white/80 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80">
        <div class="mx-auto max-w-screen-2xl px-4 py-3 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">Home</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <a href="{{ route('profile') }}" class="hover:text-brand-600 dark:hover:text-brand-400">Profile</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">Order Details</span>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">
        <div id="order-loading" class="py-16 text-center">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700"></div>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Loading order...</p>
        </div>

        <div id="order-error" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300"></div>

        <div id="order-content" class="hidden space-y-6">
            <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="bg-gradient-to-r from-brand-500/10 via-brand-400/5 to-transparent px-6 py-5 dark:from-brand-500/20 dark:via-brand-400/10">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h1 id="order-number" class="inline-flex rounded-xl bg-gray-900 px-3 py-1 text-lg font-black text-white shadow-sm dark:bg-white dark:text-gray-900">—</h1>
                            <p id="order-number-fallback" class="mt-1 text-sm font-bold text-gray-900 dark:text-white">Order Number: —</p>
                            <p id="order-meta" class="mt-1 text-sm text-gray-600 dark:text-gray-300">—</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span id="order-status" class="rounded-full px-3 py-1 text-xs font-bold">pending</span>
                            <span id="order-payment" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-gray-700 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700">cash</span>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button id="user-cancel-btn" type="button" class="hidden rounded-xl bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-red-700">Cancel Order</button>
                        <p id="user-action-msg" class="hidden text-xs font-semibold"></p>
                    </div>
                    <a href="{{ route('profile') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        Back to Profile
                    </a>
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Order ID</p>
                        <p id="order-id" class="mt-1 text-sm font-bold text-gray-900 dark:text-white">—</p>
                    </div>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/60">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Items Count</p>
                        <p id="order-items-count" class="mt-1 text-sm font-bold text-gray-900 dark:text-white">—</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-12">
                <section class="lg:col-span-8">
                    <div class="rounded-3xl border border-gray-200/80 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="mb-4 flex items-center justify-between gap-2">
                            <h3 class="text-base font-black text-gray-900 dark:text-white">Order Items</h3>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">Detailed View</span>
                        </div>
                        <div id="order-items" class="space-y-3"></div>
                    </div>
                </section>

                <aside class="space-y-4 lg:col-span-4">
                    <div class="rounded-3xl border border-gray-200/80 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="text-sm font-black text-gray-900 dark:text-white">Totals Summary</h3>
                        <div class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400"><span>Subtotal</span><span id="subtotal-val">0 SYP</span></div>
                            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400"><span>Coupon Code</span><span id="coupon-val">—</span></div>
                            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400"><span>Coupon Type</span><span id="coupon-type-val">—</span></div>
                            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400"><span>Coupon Value</span><span id="coupon-value-val">—</span></div>
                            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400"><span>Coupon Discount</span><span id="coupon-discount-val">0 SYP</span></div>
                            <div class="mt-2 flex items-center justify-between rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-base font-black text-brand-700 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-300"><span>Total</span><span id="total-val">0 SYP</span></div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const orderId = '{{ $orderId }}';
    const highContrastNumberIds = ['order-number', 'order-number-fallback', 'order-id', 'order-items-count', 'total-val'];
    const dataValueIds = ['order-meta', 'subtotal-val', 'coupon-val', 'coupon-type-val', 'coupon-value-val', 'coupon-discount-val'];

    function applyNumberContrast() {
        const isDark = document.documentElement.classList.contains('dark');
        const primaryTextColor = isDark ? '#f9fafb' : '#111827';
        const secondaryTextColor = isDark ? '#d1d5db' : '#4b5563';
        highContrastNumberIds.forEach((id) => {
            const el = document.getElementById(id);
            if (!el) {
                return;
            }
            if (id === 'order-number') {
                el.style.backgroundColor = '#0284c7';
                el.style.color = '#ffffff';
                return;
            }
            el.style.color = primaryTextColor;
            el.style.fontWeight = '700';
        });

        dataValueIds.forEach((id) => {
            const el = document.getElementById(id);
            if (!el) {
                return;
            }
            el.style.color = id === 'order-meta' ? secondaryTextColor : primaryTextColor;
            if (id !== 'order-meta') {
                el.style.fontWeight = '600';
            }
        });
    }

    try {
        const response = await window.axios.get('/api/orders/' + orderId);
        const order = response.data.data;

        const visibleOrderNumber = order.order_number || ('Order #' + order.id);
        document.getElementById('order-number').textContent = visibleOrderNumber;
        document.getElementById('order-number-fallback').textContent = 'Order Number: ' + visibleOrderNumber;
        document.getElementById('order-meta').textContent = order.created_at ? new Date(order.created_at).toLocaleDateString() : '—';
        const statusEl = document.getElementById('order-status');
        const status = String(order.status || 'pending').toLowerCase();
        statusEl.textContent = status;
        statusEl.className = 'rounded-full px-3 py-1 text-xs font-semibold ' + statusClass(status);
        renderUserAction(status);
        document.getElementById('order-payment').textContent = order.payment_way || 'cash';
        document.getElementById('order-id').textContent = order.id ?? '—';
        document.getElementById('order-items-count').textContent = order.items_count ?? (order.items || []).length;

        document.getElementById('subtotal-val').textContent = Number.parseFloat(order.subtotal_amount || 0).toLocaleString() + ' SYP';
        document.getElementById('coupon-val').textContent = order.coupon?.code || '—';
        document.getElementById('coupon-type-val').textContent = order.coupon?.type || '—';
        document.getElementById('coupon-value-val').textContent = order.coupon?.value ? Number.parseFloat(order.coupon.value).toLocaleString() : '—';
        document.getElementById('coupon-discount-val').textContent = '- ' + Number.parseFloat(order.coupon_discount_amount || 0).toLocaleString() + ' SYP';
        document.getElementById('total-val').textContent = Number.parseFloat(order.total_amount || 0).toLocaleString() + ' SYP';

        const itemsEl = document.getElementById('order-items');
        itemsEl.innerHTML = (order.items || []).map((item, index) => `
            <article class="rounded-2xl border border-gray-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-black text-gray-900 dark:text-white">${index + 1}. ${esc(item.product_name || 'Product')}</p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Item #${item.id ?? '—'} · Product #${item.product_id ?? '—'} · Qty ${item.quantity ?? 0}</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        ${item.has_discount ? '<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">Discount Applied</span>' : '<span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-300">Standard Price</span>'}
                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">${money(item.line_total)}</span>
                    </div>
                </div>
                <div class="mt-3 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3">
                    ${paramCard('Category', item.category_name || '—')}
                    ${paramCard('Subcategory', item.subcategory_name || '—')}
                    ${paramCard('Original Unit Price', money(item.original_unit_price))}
                    ${paramCard('Applied Unit Price', money(item.unit_price))}
                    ${paramCard('Discount %', item.has_discount ? (Number.parseFloat(item.applied_discount_percentage || 0).toLocaleString() + '%') : '—')}
                    ${paramCard('Saved Amount', item.has_discount ? money(item.discount_amount) : '0 SYP')}
                    ${paramCard('Line Total', money(item.line_total))}
                </div>
            </article>
        `).join('');

        document.getElementById('order-loading').classList.add('hidden');
        document.getElementById('order-content').classList.remove('hidden');
        applyNumberContrast();
    } catch (error) {
        document.getElementById('order-loading').classList.add('hidden');
        const err = document.getElementById('order-error');
        err.textContent = error.response?.data?.message || 'Failed to load order.';
        err.classList.remove('hidden');
    }

    const observer = new MutationObserver(applyNumberContrast);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    function esc(value) {
        if (!value) {
            return '';
        }
        const d = document.createElement('div');
        d.textContent = value;
        return d.innerHTML;
    }

    function money(v) {
        return Number.parseFloat(v || 0).toLocaleString() + ' SYP';
    }

    function paramCard(label, value) {
        return `<div class="rounded-lg border border-gray-100 bg-gray-50 px-2.5 py-2 dark:border-gray-800 dark:bg-gray-800/60">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">${esc(label)}</p>
            <p class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">${esc(String(value ?? '—'))}</p>
        </div>`;
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

    function renderUserAction(status) {
        const btn = document.getElementById('user-cancel-btn');
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
            btn.textContent = 'Cancelling...';
            try {
                const res = await window.axios.patch('/api/orders/' + orderId + '/cancel');
                showActionMessage(res.data?.message || 'Order cancelled successfully.', 'success');
                setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                showActionMessage(error.response?.data?.message || 'Failed to cancel order.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Cancel Order';
            }
        };
    }

    function showActionMessage(message, type) {
        const el = document.getElementById('user-action-msg');
        if (!el) {
            return;
        }
        el.className = 'text-xs font-semibold ' + (type === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400');
        el.textContent = message;
        el.classList.remove('hidden');
    }
});
</script>
@endpush
