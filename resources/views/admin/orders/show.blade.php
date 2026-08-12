@extends('layouts.admin')

@section('title', 'Order Details — Vetora Admin')
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
        <div class="card overflow-hidden">
            <div class="card-body border-b border-gray-100 dark:border-gray-800">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="o-number" class="badge badge-brand text-sm">—</h2>
                        <p id="o-number-fallback" class="mt-2 text-sm font-bold text-gray-900 dark:text-white">Order Number: —</p>
                        <p id="o-meta" class="mt-1 text-sm text-gray-600 dark:text-gray-300">—</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="o-status" class="badge badge-warning">pending</span>
                        <span id="o-payment" class="badge">cash</span>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button id="admin-complete-btn" type="button" class="hidden btn-sm" style="background: var(--color-success-500); color: #fff; border-radius: var(--radius-control); padding-inline: 0.75rem; padding-block: 0.375rem; font-weight: 700;">Mark as Completed</button>
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
            <div class="card card-body">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Customer Info</h3>
                <div class="mt-3 space-y-2 text-xs text-gray-600 dark:text-gray-300">
                    <p><span class="text-gray-400">Name:</span> <span id="o-user-name">—</span></p>
                    <p><span class="text-gray-400">Email:</span> <span id="o-user-email">—</span></p>
                </div>
            </div>
            <div class="card card-body">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Vendor Info</h3>
                <div class="mt-3 space-y-2 text-xs text-gray-600 dark:text-gray-300">
                    <p><span class="text-gray-400">Store:</span> <span id="o-vendor-name">—</span></p>
                </div>
            </div>
        </div>

        <div class="card card-body">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Items Details</h3>
                <span class="badge">Full Snapshot</span>
            </div>
            <div id="o-items" class="mt-3 space-y-3"></div>
        </div>

        <div class="card card-body">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Totals & Coupon</h3>
            <div class="mt-3 grid gap-2 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800"><span>Subtotal</span><span id="o-subtotal">0</span></div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800"><span>Coupon Code</span><span id="o-coupon-code">—</span></div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800"><span>Coupon Type</span><span id="o-coupon-type">—</span></div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800"><span>Coupon Value</span><span id="o-coupon-value">—</span></div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800"><span>Coupon Discount</span><span id="o-coupon-discount">0</span></div>
                <div class="flex items-center justify-between rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 font-bold text-brand-700 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-300"><span>Total</span><span id="o-total">0</span></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const orderId = '{{ $orderId }}';

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
        statusEl.className = 'badge ' + statusClass(status);
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
            return `<article class="card card-body">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p data-order-value="true" class="text-sm font-bold text-gray-900 dark:text-white">${index + 1}. ${esc(item.product_name || 'Product')}</p>
                        <p data-order-value="true" class="text-[11px] text-gray-500 dark:text-gray-400">Item #${item.id ?? '—'} · Product #${item.product_id ?? '—'} · Qty ${item.quantity ?? 0}</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        ${item.has_discount ? '<span class="badge badge-success">Discount Applied</span>' : '<span class="badge">Standard Price</span>'}
                        <span class="badge badge-brand">${money(item.line_total)}</span>
                    </div>
                </div>
                <div class="mt-3 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3">
                    ${paramCard('Category', item.product?.category?.name || '—')}
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
    } catch (error) {
        document.getElementById('order-loading').innerHTML = `<p class="text-sm font-medium text-red-500">${esc(error.response?.data?.message || 'Failed to load order details.')}</p>`;
    }

    function money(v) {
        return Number.parseFloat(v || 0).toLocaleString() + ' SYP';
    }

    function statusClass(status) {
        const classes = {
            pending: 'badge-warning',
            confirmed: 'badge-success',
            completed: 'badge-info',
            cancelled: 'badge-danger',
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
        el.className = 'text-xs font-semibold';
        el.style.color = type === 'success' ? 'var(--color-success-strong)' : 'var(--color-danger-strong)';
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
