@extends('layouts.vendor')

@section('title', 'Order Details')
@section('page-title', 'Order Details')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <a href="{{ route('vendor.orders.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">&larr; Back to orders</a>

    <div id="order-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading order details...</p>
    </div>

    <div id="order-error" class="hidden py-16 text-center">
        <p class="text-sm font-medium text-red-500">Failed to load order details.</p>
    </div>

    <div id="order-content" class="hidden space-y-6">
        <x-alert type="error" id="order-action-alert" />
        <x-alert type="success" id="order-action-success" />

        <div class="card">
            <div class="card-body flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400">Order</p>
                    <h1 id="order-number" class="mt-1 text-xl font-bold text-gray-900 dark:text-white">—</h1>
                    <p id="order-date" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>
                </div>
                <span id="order-status-badge" class="badge">—</span>
            </div>

            <div id="order-actions" class="card-body flex flex-wrap items-center gap-2 border-t border-gray-100 dark:border-gray-800"></div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card">
                <div class="card-body border-b border-gray-100 dark:border-gray-800">
                    <h2 class="dashboard-section-title">Customer</h2>
                </div>
                <div class="card-body space-y-1 text-sm">
                    <p id="order-customer-name" class="font-semibold text-gray-900 dark:text-white">—</p>
                    <p id="order-customer-email" class="text-gray-500 dark:text-gray-400"></p>
                </div>
            </div>

            <div class="card">
                <div class="card-body border-b border-gray-100 dark:border-gray-800">
                    <h2 class="dashboard-section-title">Shipping Address</h2>
                </div>
                <div id="order-shipping" class="card-body space-y-1 text-sm text-gray-700 dark:text-gray-300"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100 dark:border-gray-800">
                <h2 class="dashboard-section-title">Payment &amp; Totals</h2>
            </div>
            <div class="card-body">
                <div id="order-totals" class="grid gap-2 text-sm sm:grid-cols-2"></div>
            </div>
        </div>

        <div>
            <h2 class="dashboard-section-title mb-3">Items</h2>
            <div id="order-items" class="space-y-4"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const orderId = {{ (int) $orderId }};
    const $ = (id) => document.getElementById(id);

    const statusLabels = @json(__('orders.status'));
    const cancelReasonLabels = @json(__('orders.cancel_reason'));
    const transitions = @json(\App\Models\Order::TRANSITIONS);
    const cancellableStatuses = @json(\App\Models\Order::CANCELLABLE_STATUSES);
    const cancelReasons = @json(\App\Models\Order::CANCEL_REASONS);

    let currentOrder = null;

    await loadOrder();

    async function loadOrder() {
        try {
            const response = await window.axios.get(`/api/vendor/orders/${orderId}`);
            currentOrder = response.data.data;

            renderHeader(currentOrder);
            renderCustomer(currentOrder);
            renderShipping(currentOrder);
            renderTotals(currentOrder);
            renderActions(currentOrder);
            renderItems(currentOrder);

            $('order-loading').classList.add('hidden');
            $('order-content').classList.remove('hidden');
        } catch (error) {
            $('order-loading').classList.add('hidden');
            $('order-error').classList.remove('hidden');
        }
    }

    function renderHeader(order) {
        $('order-number').textContent = order.order_number || `Order #${order.id}`;
        $('order-date').textContent = order.created_at ? new Date(order.created_at).toLocaleString() : '';

        const badge = $('order-status-badge');
        badge.textContent = statusLabels[order.status] || order.status || '—';
        badge.className = `badge ${statusBadgeClass(order.status)}`;
    }

    function statusBadgeClass(status) {
        if (status === 'completed') return 'badge-success';
        if (status === 'cancelled') return 'badge-danger';
        if (status === 'confirmed' || status === 'preparing' || status === 'shipped' || status === 'out_for_delivery') return 'badge-brand';
        return 'badge-warning';
    }

    function renderCustomer(order) {
        $('order-customer-name').textContent = order.user?.name || 'Unknown customer';
        $('order-customer-email').textContent = order.user?.email || '';
    }

    function renderShipping(order) {
        const lines = [
            order.ship_recipient_name,
            [order.ship_street, order.ship_building].filter(Boolean).join(', '),
            [order.ship_district, order.ship_city, order.ship_governorate].filter(Boolean).join(', '),
            order.ship_phone,
            order.ship_notes,
        ].filter((line) => line && String(line).trim() !== '');

        $('order-shipping').innerHTML = lines.length
            ? lines.map((line) => `<p>${escapeHtml(line)}</p>`).join('')
            : '<p class="text-gray-400">No shipping address on file.</p>';
    }

    function renderTotals(order) {
        const rows = [
            ['Subtotal', money(order.subtotal_amount)],
            ['Coupon discount', order.coupon_discount_amount > 0 ? `-${money(order.coupon_discount_amount)}` : null],
            ['Shipping', money(order.shipping_total)],
            ['Tax', order.tax_total > 0 ? money(order.tax_total) : null],
            ['Payment method', humanize(order.payment_way)],
        ].filter(([, value]) => value !== null && value !== undefined && value !== '');

        $('order-totals').innerHTML = rows.map(([label, value]) => `
            <div class="flex items-center justify-between border-b border-gray-100 py-1.5 dark:border-gray-800">
                <span class="text-gray-500 dark:text-gray-400">${escapeHtml(label)}</span>
                <span class="font-semibold text-gray-900 dark:text-white">${escapeHtml(String(value))}</span>
            </div>
        `).join('') + `
            <div class="flex items-center justify-between pt-2 sm:col-span-2">
                <span class="text-sm font-bold text-gray-900 dark:text-white">Grand total</span>
                <span class="text-lg font-bold text-gray-900 dark:text-white">${money(order.grand_total)}</span>
            </div>
        `;
    }

    function renderActions(order) {
        const container = $('order-actions');
        const nextStatuses = (transitions[order.status] || []).filter((status) => status !== 'cancelled');
        const canCancel = cancellableStatuses.includes(order.status);

        if (!nextStatuses.length && !canCancel) {
            container.classList.add('hidden');
            return;
        }

        container.classList.remove('hidden');
        container.innerHTML = '';

        if (nextStatuses.length) {
            const select = document.createElement('select');
            select.id = 'order-next-status';
            select.className = 'form-select w-auto';
            select.innerHTML = nextStatuses.map((status) => `<option value="${status}">${escapeHtml(statusLabels[status] || status)}</option>`).join('');

            const advanceBtn = document.createElement('button');
            advanceBtn.type = 'button';
            advanceBtn.className = 'btn-primary btn-sm';
            advanceBtn.textContent = 'Update status';
            advanceBtn.addEventListener('click', () => updateStatus(select.value, advanceBtn));

            container.appendChild(select);
            container.appendChild(advanceBtn);
        }

        if (canCancel) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'btn-danger btn-sm';
            cancelBtn.textContent = 'Cancel order';
            cancelBtn.addEventListener('click', () => cancelOrder(cancelBtn));
            container.appendChild(cancelBtn);
        }
    }

    async function updateStatus(status, button) {
        button.disabled = true;
        try {
            await window.axios.patch(`/api/vendor/orders/${orderId}/status`, { status });
            showAlert('order-action-success', 'Order status updated.');
            await loadOrder();
        } catch (error) {
            showAlert('order-action-alert', error.response?.data?.message || 'Failed to update order status.');
        } finally {
            button.disabled = false;
        }
    }

    async function cancelOrder(button) {
        const reason = window.prompt(
            `Cancellation reason (${cancelReasons.map((r) => cancelReasonLabels[r] || r).join(', ')}):`,
            cancelReasons[0]
        );
        if (reason === null) {
            return;
        }
        const matchedReason = cancelReasons.includes(reason) ? reason : 'other';

        button.disabled = true;
        try {
            await window.axios.patch(`/api/vendor/orders/${orderId}/cancel`, { reason: matchedReason });
            showAlert('order-action-success', 'Order cancelled.');
            await loadOrder();
        } catch (error) {
            showAlert('order-action-alert', error.response?.data?.message || 'Failed to cancel order.');
        } finally {
            button.disabled = false;
        }
    }

    function renderItems(order) {
        $('order-items').innerHTML = (order.items || []).map(item => `
            <article class="card">
                <div class="card-body">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">${escapeHtml(item.product_name || 'Product')}</p>
                    <div class="mt-3 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3">
                        ${paramCard('Category', item.product?.category?.name || '—')}
                        ${paramCard('Original Unit Price', money(item.original_unit_price))}
                        ${paramCard('Applied Unit Price', money(item.unit_price))}
                        ${paramCard('Discount %', item.has_discount ? (Number.parseFloat(item.applied_discount_percentage || 0).toLocaleString() + '%') : '—')}
                        ${paramCard('Saved Amount', item.has_discount ? money(item.discount_amount) : '0 SYP')}
                        ${paramCard('Line Total', money(item.line_total))}
                    </div>
                </div>
            </article>`).join('');
    }

    function showAlert(id, message) {
        const alert = $(id);
        $(id + '-message').textContent = message;
        alert.classList.remove('hidden');
        setTimeout(() => alert.classList.add('hidden'), 4500);
    }

    function humanize(value) {
        if (!value) return '';
        return String(value).replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function money(v) { return Number.parseFloat(v || 0).toLocaleString() + ' SYP'; }
    function escapeHtml(value) { const d = document.createElement('div'); d.textContent = value || ''; return d.innerHTML; }
    function paramCard(label, value) { return `<div class="rounded-lg border border-gray-100 bg-gray-50 px-2.5 py-2 dark:border-gray-800 dark:bg-gray-800/60"><p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">${escapeHtml(label)}</p><p class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">${escapeHtml(String(value ?? '—'))}</p></div>`; }
});
</script>
@endpush
