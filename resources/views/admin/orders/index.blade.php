@extends('layouts.admin')

@section('title', 'Orders — Vetora Admin')
@section('page-title', 'Orders')

@section('content')
<div class="space-y-4">
    <div class="filter-panel">
        <div>
            <h2 class="dashboard-section-title">Orders Filters</h2>
            <p class="dashboard-section-copy">Refine by product, status, vendor, user, and category.</p>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <input id="f-product" type="text" placeholder="Product name" class="form-input">
            <select id="f-status" class="form-select">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select id="f-user" class="form-select">
                <option value="">All Users</option>
            </select>
            <select id="f-vendor" class="form-select">
                <option value="">All Vendors</option>
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

    <div id="orders-list" class="hidden card admin-table-wrap table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">Order</th>
                    <th scope="col">Vendor / Customer</th>
                    <th scope="col" class="text-end">Items</th>
                    <th scope="col" class="text-end">Total</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody id="orders-list-body"></tbody>
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
document.addEventListener('DOMContentLoaded', function () {
    const state = { page: 1 };
    const $ = id => document.getElementById(id);
    const urlParams = new URLSearchParams(window.location.search);
    const initialVendorId = urlParams.get('vendor_id');
    const initialUserId = urlParams.get('user_id');

    loadFilterData();
    loadOrders();

    ['f-product', 'f-status', 'f-user', 'f-vendor', 'f-category'].forEach((id) => {
        $(id).addEventListener('change', () => { state.page = 1; loadOrders(); });
        if (id === 'f-product') {
            $(id).addEventListener('input', debounce(() => { state.page = 1; loadOrders(); }, 300));
        }
    });

    $('f-reset').addEventListener('click', function () {
        ['f-product', 'f-status', 'f-user', 'f-vendor', 'f-category'].forEach((id) => $(id).value = '');
        state.page = 1;
        loadOrders();
    });

    $('orders-prev').addEventListener('click', () => { if (state.page > 1) { state.page--; loadOrders(); } });
    $('orders-next').addEventListener('click', () => { state.page++; loadOrders(); });

    async function loadFilterData() {
        try {
            const [usersRes, vendorsRes, categoriesRes] = await Promise.all([
                window.axios.get('/api/admin/users'),
                window.axios.get('/api/vendors'),
                window.axios.get('/api/categories?per_page=100'),
            ]);

            const users = usersRes.data.data || [];
            const vendors = vendorsRes.data.data || [];
            const categories = categoriesRes.data.data || [];
            $('f-user').innerHTML = '<option value="">All Users</option>' + users.map(u => `<option value="${u.id}">${esc(u.name || ('User #' + u.id))}</option>`).join('');
            $('f-vendor').innerHTML = '<option value="">All Vendors</option>' + vendors.map(v => `<option value="${v.id}">${esc(v.store_name || ('Vendor #' + v.id))}</option>`).join('');
            $('f-category').innerHTML = '<option value="">All Categories</option>' + categories.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
            if (initialUserId && $('f-user').querySelector(`option[value="${initialUserId}"]`)) {
                $('f-user').value = initialUserId;
            }
            if (initialVendorId && $('f-vendor').querySelector(`option[value="${initialVendorId}"]`)) {
                $('f-vendor').value = initialVendorId;
                state.page = 1;
                loadOrders();
            }
        } catch (e) {}
    }

    async function loadOrders() {
        toggleLoading(true);
        try {
            const params = new URLSearchParams({ page: String(state.page) });
            const product = $('f-product').value.trim();
            const status = $('f-status').value;
            const user = $('f-user').value;
            const vendor = $('f-vendor').value;
            const category = $('f-category').value;
            if (product) params.set('product', product);
            if (status) params.set('status', status);
            if (user) params.set('user_id', user);
            if (vendor) params.set('vendor_id', vendor);
            if (category) params.set('category_id', category);

            const response = await window.axios.get('/api/admin/orders?' + params.toString());
            const orders = response.data.data || [];
            const meta = response.data.meta || {};

            if (!orders.length) {
                $('orders-empty').classList.remove('hidden');
                $('orders-list').classList.add('hidden');
                $('orders-pagination').classList.add('hidden');
                return;
            }

            $('orders-empty').classList.add('hidden');
            $('orders-list').classList.remove('hidden');
            $('orders-list-body').innerHTML = orders.map(orderRow).join('');
            $('orders-pagination').classList.remove('hidden');
            $('orders-pagination').classList.add('flex');
            $('orders-page-info').textContent = `Page ${meta.current_page} of ${meta.last_page} · ${meta.total} orders`;
            $('orders-prev').disabled = meta.current_page <= 1;
            $('orders-next').disabled = meta.current_page >= meta.last_page;
        } catch (e) {
            $('orders-empty').classList.remove('hidden');
            $('orders-list').classList.add('hidden');
            $('orders-pagination').classList.add('hidden');
        } finally {
            toggleLoading(false);
        }
    }

    function orderRow(order) {
        const date = order.created_at ? new Date(order.created_at).toLocaleDateString() : '—';
        const itemCount = order.items_count ?? (order.items || []).length;
        return `<tr>
            <td>
                <a href="/admin/orders/${order.id}" class="font-semibold text-gray-900 dark:text-white">${esc(order.order_number || ('Order #' + order.id))}</a>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">${date}</p>
            </td>
            <td class="text-gray-600 dark:text-gray-300">
                <p>${esc(order.vendor?.store_name || 'Unknown vendor')}</p>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">${esc(order.user?.name || 'Unknown user')}</p>
            </td>
            <td class="text-end tabular-nums text-gray-600 dark:text-gray-300">${itemCount}</td>
            <td class="text-end tabular-nums font-semibold text-gray-900 dark:text-white">${Number.parseFloat(order.total_amount || 0).toLocaleString()} SYP</td>
            <td><div class="flex flex-wrap items-center gap-1.5">${statusBadge(order.status)} ${paymentBadge(order.payment_way)}</div></td>
            <td class="text-end"><a href="/admin/orders/${order.id}" class="btn-secondary btn-xs">View</a></td>
        </tr>`;
    }

    function toggleLoading(show) {
        $('orders-loading').classList.toggle('hidden', !show);
    }

    function esc(value) {
        if (!value) return '';
        const d = document.createElement('div');
        d.textContent = value;
        return d.innerHTML;
    }

    function statusBadge(status) {
        const s = String(status || 'pending').toLowerCase();
        const cls = {
            pending: 'badge-warning',
            confirmed: 'badge-success',
            completed: 'badge-info',
            cancelled: 'badge-danger',
        };
        // Pending orders need action; give them a filled dot + bold weight so
        // they read as "needs attention" rather than just a different hue
        // from resolved statuses (never rely on color alone).
        const dot = s === 'pending' ? `<span class="me-1 inline-block h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>` : '';
        return `<span class="badge ${cls[s] || cls.pending} ${s === 'pending' ? 'font-bold' : ''}">${dot}${esc(s)}</span>`;
    }

    function paymentBadge(paymentWay) {
        return `<span class="badge badge-info">${esc(paymentWay || 'cash')}</span>`;
    }

    function debounce(fn, wait) {
        let timer = null;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), wait);
        };
    }
});
</script>
@endpush
