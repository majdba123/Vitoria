@extends('layouts.admin')

@section('title', 'Orders — SyriaZone Admin')
@section('page-title', 'Orders')

@section('content')
<div class="space-y-4">
    <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="bg-gradient-to-r from-brand-500/10 via-brand-400/5 to-transparent px-5 py-4 dark:from-brand-500/20 dark:via-brand-400/10">
            <h2 class="text-base font-black text-gray-900 dark:text-white">Orders Filters</h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Refine by product, status, vendor, category and subcategory.</p>
        </div>
        <div class="p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
            <input id="f-product" type="text" placeholder="Product name" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm transition-all focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            <select id="f-status" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm transition-all focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select id="f-user" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm transition-all focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">All Users</option>
            </select>
            <select id="f-vendor" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm transition-all focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">All Vendors</option>
            </select>
            <select id="f-category" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm transition-all focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">All Categories</option>
            </select>
            <select id="f-subcategory" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 shadow-sm transition-all focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">All Subcategories</option>
            </select>
            <button id="f-reset" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold text-gray-700 transition-all hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-gray-700 dark:text-gray-300 dark:hover:border-brand-500/40 dark:hover:bg-brand-500/10 dark:hover:text-brand-300">Reset</button>
        </div>
        </div>
    </div>

    <div id="orders-loading" class="py-14 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading orders...</p>
    </div>

    <div id="orders-empty" class="hidden card py-14 text-center">
        <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">No orders found.</p>
    </div>

    <div id="orders-list" class="hidden space-y-3"></div>

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
    const state = { page: 1, categories: [] };
    const $ = id => document.getElementById(id);
    const urlParams = new URLSearchParams(window.location.search);
    const initialVendorId = urlParams.get('vendor_id');
    const initialUserId = urlParams.get('user_id');

    loadFilterData();
    loadOrders();

    ['f-product', 'f-status', 'f-user', 'f-vendor', 'f-category', 'f-subcategory'].forEach((id) => {
        $(id).addEventListener('change', () => { state.page = 1; loadOrders(); });
        if (id === 'f-product') {
            $(id).addEventListener('input', debounce(() => { state.page = 1; loadOrders(); }, 300));
        }
    });

    $('f-category').addEventListener('change', function () {
        fillSubcategories(this.value);
        state.page = 1;
        loadOrders();
    });

    $('f-reset').addEventListener('click', function () {
        ['f-product', 'f-status', 'f-user', 'f-vendor', 'f-category', 'f-subcategory'].forEach((id) => $(id).value = '');
        fillSubcategories('');
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
                window.axios.get('/api/categories'),
            ]);

            const users = usersRes.data.data || [];
            const vendors = vendorsRes.data.data || [];
            const categories = categoriesRes.data.data || [];
            state.categories = categories;

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

    function fillSubcategories(categoryId) {
        if (!categoryId) {
            $('f-subcategory').innerHTML = '<option value="">All Subcategories</option>';
            return;
        }
        const category = state.categories.find(c => String(c.id) === String(categoryId));
        const subs = category?.subcategories || [];
        $('f-subcategory').innerHTML = '<option value="">All Subcategories</option>' + subs.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join('');
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
            const subcategory = $('f-subcategory').value;
            if (product) params.set('product', product);
            if (status) params.set('status', status);
            if (user) params.set('user_id', user);
            if (vendor) params.set('vendor_id', vendor);
            if (category) params.set('category_id', category);
            if (subcategory) params.set('subcategory_id', subcategory);

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
            $('orders-list').innerHTML = orders.map(orderCard).join('');
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

    function orderCard(order) {
        const date = order.created_at ? new Date(order.created_at).toLocaleDateString() : '—';
        const items = (order.items || []).slice(0, 3).map(i => `<li class="text-xs text-gray-500 dark:text-gray-400">${esc(i.product_name)} · Qty ${i.quantity}</li>`).join('');
        const extraItems = (order.items || []).length > 3 ? `<li class="text-xs font-semibold text-gray-400">+ ${(order.items || []).length - 3} more items</li>` : '';
        return `<article class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-start justify-between gap-2 border-b border-gray-100 bg-gray-50/80 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/40">
                <div>
                    <p><span class="inline-flex rounded-lg bg-gray-900 px-2.5 py-1 text-xs font-black text-white shadow-sm dark:bg-white dark:text-gray-900">${esc(order.order_number || ('Order #' + order.id))}</span></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">${date} · ${esc(order.vendor?.store_name || 'Unknown vendor')} · ${esc(order.user?.name || 'Unknown user')}</p>
                </div>
                <div class="flex items-center gap-2">${statusBadge(order.status)} ${paymentBadge(order.payment_way)}</div>
            </div>
            <div class="p-4">
                <ul class="space-y-1.5">${items || '<li class="text-xs text-gray-400">No items.</li>'}${extraItems}</ul>
                <div class="mt-3 grid gap-2 text-xs sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-2.5 py-2 dark:border-gray-800 dark:bg-gray-800/60"><p class="text-gray-400">Order ID</p><p class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">${order.id ?? '—'}</p></div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-2.5 py-2 dark:border-gray-800 dark:bg-gray-800/60"><p class="text-gray-400">Items</p><p class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">${order.items_count ?? (order.items || []).length}</p></div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-2.5 py-2 dark:border-gray-800 dark:bg-gray-800/60"><p class="text-gray-400">Total</p><p class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">${Number.parseFloat(order.total_amount || 0).toLocaleString()} SYP</p></div>
                </div>
                <div class="mt-3 flex justify-end">
                    <a href="/admin/orders/${order.id}" class="inline-flex items-center gap-1 rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 transition-all hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-gray-700 dark:text-gray-300 dark:hover:border-brand-500/40 dark:hover:bg-brand-500/10 dark:hover:text-brand-300">
                        View Details
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </article>`;
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
            pending: 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
            confirmed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
            completed: 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
            cancelled: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400'
        };
        return `<span class="rounded-full px-2.5 py-1 text-[11px] font-semibold ${cls[s] || cls.pending}">${esc(s)}</span>`;
    }

    function paymentBadge(paymentWay) {
        return `<span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">${esc(paymentWay || 'cash')}</span>`;
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
