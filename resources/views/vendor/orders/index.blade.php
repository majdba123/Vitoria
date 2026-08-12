@extends('layouts.vendor')

@section('title', 'Orders')
@section('page-title', 'Orders')

@section('content')
<div class="space-y-4">
    <div class="filter-panel">
        <div>
            <h2 class="dashboard-section-title">Orders Filters</h2>
            <p class="dashboard-section-copy">Filter your store orders by product, status, and category.</p>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <input id="f-product" type="text" placeholder="Product name" class="form-input">
            <select id="f-status" class="form-select">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select id="f-category" class="form-select">
                <option value="">All Categories</option>
            </select>
            <button id="f-reset" type="button" class="btn-secondary btn-sm">Reset</button>
        </div>
    </div>

    <div id="orders-loading" class="py-14 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700 dark:border-t-brand-400"></div>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Loading orders...</p>
    </div>

    <div id="orders-empty" class="empty-state hidden">
        <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">No orders found.</p>
    </div>

    <div id="orders-table" class="hidden card admin-table-wrap table-responsive">
        <table class="admin-table">
            <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Status</th><th scope="col" class="text-end">Total</th><th scope="col" class="text-end">Action</th></tr></thead>
            <tbody id="orders-list"></tbody>
        </table>
    </div>

    <div id="orders-pagination" class="hidden items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
        <p id="orders-page-info" class="text-xs text-gray-500"></p>
        <div class="flex gap-2">
            <button id="orders-prev" class="btn-secondary btn-xs">Prev</button>
            <button id="orders-next" class="btn-secondary btn-xs">Next</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const state = { page: 1 };
    const $ = id => document.getElementById(id);

    try {
        const categoriesRes = await window.axios.get('/api/vendor/categories');
        const categories = categoriesRes.data.data || [];
        $('f-category').innerHTML = '<option value="">All Categories</option>' + categories.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
    } catch (e) {}

    loadOrders();

    ['f-product', 'f-status', 'f-category'].forEach((id) => {
        $(id).addEventListener('change', () => { state.page = 1; loadOrders(); });
        if (id === 'f-product') $(id).addEventListener('input', debounce(() => { state.page = 1; loadOrders(); }, 300));
    });

    $('f-reset').addEventListener('click', function () {
        ['f-product', 'f-status', 'f-category'].forEach((id) => $(id).value = '');
        state.page = 1;
        loadOrders();
    });

    $('orders-prev').addEventListener('click', () => { if (state.page > 1) { state.page--; loadOrders(); } });
    $('orders-next').addEventListener('click', () => { state.page++; loadOrders(); });

    async function loadOrders() {
        $('orders-loading').classList.remove('hidden');
        try {
            const params = new URLSearchParams({ page: String(state.page) });
            if ($('f-product').value.trim()) params.set('product', $('f-product').value.trim());
            if ($('f-status').value) params.set('status', $('f-status').value);
            if ($('f-category').value) params.set('category_id', $('f-category').value);
            const response = await window.axios.get('/api/vendor/orders?' + params.toString());
            const orders = response.data.data || [];
            const meta = response.data.meta || {};

            if (!orders.length) {
                $('orders-empty').classList.remove('hidden');
                $('orders-table').classList.add('hidden');
                $('orders-pagination').classList.add('hidden');
                return;
            }
            $('orders-empty').classList.add('hidden');
            $('orders-table').classList.remove('hidden');
            $('orders-list').innerHTML = orders.map(order => `<tr><td class="font-semibold text-gray-950 dark:text-white">${esc(order.order_number || ('Order #' + order.id))}</td><td>${esc(order.user?.name || 'Unknown user')}</td><td><span class="badge ${statusClass(order.status)}">${esc(order.status || 'pending')}</span></td><td class="text-end font-semibold" dir="ltr">${esc(order.total || order.total_amount || '—')} SYP</td><td class="text-end"><a href="/vendor/orders/${order.id}" class="btn-secondary btn-xs">View Details</a></td></tr>`).join('');

            if (meta.last_page > 1) {
                $('orders-pagination').classList.remove('hidden');
                $('orders-pagination').classList.add('flex');
                $('orders-page-info').textContent = `Page ${meta.current_page} of ${meta.last_page} · ${meta.total} orders`;
                $('orders-prev').disabled = meta.current_page <= 1;
                $('orders-next').disabled = meta.current_page >= meta.last_page;
            } else {
                $('orders-pagination').classList.add('hidden');
            }
        } catch (e) {
            $('orders-empty').classList.remove('hidden');
            $('orders-table').classList.add('hidden');
            $('orders-pagination').classList.add('hidden');
        } finally {
            $('orders-loading').classList.add('hidden');
        }
    }

    function esc(value) { const d = document.createElement('div'); d.textContent = value || ''; return d.innerHTML; }
    function statusClass(status) {
        if (status === 'completed' || status === 'confirmed') return 'badge-success';
        if (status === 'cancelled') return 'badge-danger';
        return 'badge-warning';
    }
    function debounce(fn, wait) { let timer = null; return function (...args) { clearTimeout(timer); timer = setTimeout(() => fn.apply(this, args), wait); }; }
});
</script>
@endpush
