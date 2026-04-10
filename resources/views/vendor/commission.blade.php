@extends('layouts.vendor')

@section('title', 'Commission — SyriaZone Vendor')
@section('page-title', 'Commission Statistics')

@section('content')
<div class="space-y-4">
    <x-alert type="error" id="commission-alert" />

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Vendor Financial Analytics</p>
        <h2 id="vendor-title" class="mt-1 text-2xl font-black text-gray-900 dark:text-white">My Commission Dashboard</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Commission is calculated by category commission on completed orders only.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Completed Orders Total</p>
            <p id="stat-completed-total" class="mt-2 text-2xl font-black text-gray-900 dark:text-white">—</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Commission Total</p>
            <p id="stat-commission-total" class="mt-2 text-2xl font-black text-brand-600">—</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paid to You</p>
            <p id="stat-paid-amount" class="mt-2 text-2xl font-black text-emerald-600">—</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Remaining</p>
            <p id="stat-remaining-amount" class="mt-2 text-2xl font-black text-rose-600">—</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Order Status Statistics</h3>
            <div class="mt-4 space-y-3">
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Pending</span>
                        <span id="status-pending-meta" class="font-bold text-amber-600">—</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800">
                        <div id="status-pending-bar" class="h-2.5 rounded-full bg-amber-500 transition-all duration-500" style="width:0%"></div>
                    </div>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Completed</span>
                        <span id="status-completed-meta" class="font-bold text-emerald-600">—</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800">
                        <div id="status-completed-bar" class="h-2.5 rounded-full bg-emerald-500 transition-all duration-500" style="width:0%"></div>
                    </div>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <span class="font-semibold text-gray-700 dark:text-gray-300">Cancelled</span>
                        <span id="status-cancelled-meta" class="font-bold text-rose-600">—</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800">
                        <div id="status-cancelled-bar" class="h-2.5 rounded-full bg-rose-500 transition-all duration-500" style="width:0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Payment Summary</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">These values are managed by admin settlement.</p>
            <div class="mt-4 grid grid-cols-1 gap-3 text-sm">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paid Amount</p>
                    <p id="paid-amount-box" class="mt-1 text-lg font-black text-emerald-600">—</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/50">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Remaining Amount</p>
                    <p id="remaining-amount-box" class="mt-1 text-lg font-black text-rose-600">—</p>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h3 class="text-base font-black text-gray-900 dark:text-white">Commission by Category</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Commission %</th>
                        <th>Sales Total</th>
                        <th>Commission Amount</th>
                    </tr>
                </thead>
                <tbody id="category-breakdown-body">
                    <tr>
                        <td colspan="4" class="py-6 text-center text-sm text-gray-500">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h3 class="text-base font-black text-gray-900 dark:text-white">Last 7 Days Completed Orders</h3>
        <div id="orders-trend" class="mt-4 grid grid-cols-7 gap-2"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const formatter = new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 });
    loadStats();

    async function loadStats() {
        try {
            const response = await window.axios.get('/api/vendor/commission-stats');
            const data = response.data?.data || {};
            const vendor = data.vendor || {};
            const financials = data.financials || {};
            const orders = data.orders || {};
            const statusCounts = orders.status_counts || {};
            const categoryBreakdown = data.category_breakdown || [];
            const trend = data.completed_orders_last_7_days || [];

            document.getElementById('vendor-title').textContent = `${vendor.store_name || 'My Store'} — Commission Dashboard`;
            setMoney('stat-completed-total', financials.completed_order_total || 0);
            setMoney('stat-commission-total', financials.commission_total || 0);
            setMoney('stat-paid-amount', financials.paid_amount || 0);
            setMoney('stat-remaining-amount', financials.remaining_amount || 0);
            setMoney('paid-amount-box', financials.paid_amount || 0);
            setMoney('remaining-amount-box', financials.remaining_amount || 0);

            const total = Number(orders.total || 0);
            setBar('status-pending-bar', 'status-pending-meta', Number(statusCounts.pending || 0), total);
            setBar('status-completed-bar', 'status-completed-meta', Number(statusCounts.completed || 0), total);
            setBar('status-cancelled-bar', 'status-cancelled-meta', Number(statusCounts.cancelled || 0), total);

            renderCategoryBreakdown(categoryBreakdown);
            renderTrend(trend);
        } catch (error) {
            const message = error.response?.data?.message || 'Failed to load commission statistics.';
            showAlert('commission-alert', message);
        }
    }

    function renderCategoryBreakdown(rows) {
        const body = document.getElementById('category-breakdown-body');
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="4" class="py-6 text-center text-sm text-gray-500">No completed orders found.</td></tr>';
            return;
        }

        body.innerHTML = rows.map(row => `
            <tr>
                <td class="text-sm font-semibold text-gray-900 dark:text-white">${escapeHtml(row.category_name || 'Unknown')}</td>
                <td class="text-sm text-gray-600 dark:text-gray-300">${Number(row.commission_rate || 0).toFixed(2)}%</td>
                <td class="text-sm font-semibold text-gray-900 dark:text-white">${formatMoney(row.sales_total || 0)}</td>
                <td class="text-sm font-semibold text-brand-600">${formatMoney(row.commission_amount || 0)}</td>
            </tr>
        `).join('');
    }

    function renderTrend(points) {
        const wrap = document.getElementById('orders-trend');
        if (!points.length) {
            wrap.innerHTML = '<p class="col-span-7 text-sm text-gray-500">No trend data available.</p>';
            return;
        }

        const max = Math.max(...points.map(p => Number(p.count || 0)), 1);
        wrap.innerHTML = points.map(point => {
            const value = Number(point.count || 0);
            const date = new Date(point.date);
            const label = Number.isNaN(date.getTime())
                ? point.date
                : date.toLocaleDateString(undefined, { weekday: 'short' });
            const height = Math.max(Math.round((value / max) * 88), 10);

            return `
                <div class="flex flex-col items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800/50">
                    <div class="flex h-24 items-end">
                        <div class="w-5 rounded-t bg-emerald-500/90" style="height:${height}px"></div>
                    </div>
                    <p class="text-[11px] font-bold text-gray-700 dark:text-gray-300">${escapeHtml(label)}</p>
                    <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">${value}</p>
                </div>
            `;
        }).join('');
    }

    function setMoney(id, amount) {
        const el = document.getElementById(id);
        if (!el) {
            return;
        }
        el.textContent = formatMoney(amount);
    }

    function formatMoney(amount) {
        return `${formatter.format(Number(amount || 0))} SYP`;
    }

    function setBar(barId, labelId, value, total) {
        const p = total > 0 ? Math.round((value / total) * 100) : 0;
        const bar = document.getElementById(barId);
        const label = document.getElementById(labelId);
        if (bar) {
            bar.style.width = p + '%';
        }
        if (label) {
            label.textContent = `${value} (${p}%)`;
        }
    }

    function showAlert(id, message) {
        const box = document.getElementById(id);
        const messageEl = document.getElementById(`${id}-message`);
        if (!box || !messageEl) {
            return;
        }
        messageEl.textContent = message;
        box.classList.remove('hidden');
        setTimeout(() => box.classList.add('hidden'), 4000);
    }

    function escapeHtml(text) {
        if (!text) {
            return '';
        }
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }
});
</script>
@endpush
