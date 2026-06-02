@extends('layouts.syndicate')
@php($section = $section ?? 'dashboard')
@section('title', ucfirst($section).' - Syndicate')
@section('page-title', ucfirst($section))

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-xl font-black text-gray-900 dark:text-white" id="page-heading">{{ ucfirst($section) }}</h2>
            <p class="mt-1 text-sm text-gray-500">All records are restricted to your assigned category type.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('syndicate.sales') }}" class="btn-secondary btn-sm">Sales</a>
            <a href="{{ route('syndicate.reports') }}" class="btn-primary btn-sm">Reports</a>
        </div>
    </div>

    <div id="overview-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"></div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900" id="main-title">Records</h3>
            </div>
            <div id="main-list" class="card-body space-y-3"></div>
        </div>
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">Analytics</h3>
            </div>
            <div id="side-list" class="card-body space-y-3"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('syndicate-ready', async function () {
    const section = @json($section);
    const endpoint = section === 'dashboard' || section === 'sales' ? 'overview' : (section === 'reports' ? 'reports' : section);
    const esc = (value) => { const d = document.createElement('div'); d.textContent = value == null ? '' : String(value); return d.innerHTML; };

    try {
        const [overviewRes, sectionRes] = await Promise.all([
            window.axios.get('/api/syndicate/overview'),
            window.axios.get('/api/syndicate/' + endpoint),
        ]);
        const overview = overviewRes.data.data || {};
        const payload = sectionRes.data.data || {};
        renderOverview(overview);
        renderSection(section, payload);
    } catch (error) {
        document.getElementById('main-list').innerHTML = '<p class="py-8 text-center text-sm text-red-500">Failed to load syndicate data.</p>';
    }

    function renderOverview(data) {
        const sales = data.sales_stats || {};
        const orders = data.order_stats || {};
        const cards = [
            ['Categories', data.total_categories || 0],
            ['Merchants', data.total_merchants || 0],
            ['Products', data.total_products || 0],
            ['Completed Sales', Number(sales.completed_sales || 0).toLocaleString()],
            ['Completed Orders', orders.completed_orders || 0],
            ['Pending Orders', orders.pending_orders || 0],
            ['Avg Order Value', Number(sales.average_order_value || 0).toLocaleString()],
            ['Podcasts', data.total_podcasts || 0],
        ];
        document.getElementById('overview-grid').innerHTML = cards.map(([label, value]) => `
            <div class="card card-body">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">${esc(label)}</p>
                <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">${esc(value)}</p>
            </div>
        `).join('');

        const side = [
            ...(data.top_selling_categories || []).map(row => ['Category', row.name, row.sales_total]),
            ...(data.top_selling_products || []).map(row => ['Product', row.name, row.sales_total]),
            ...(data.top_merchants_by_sales || []).map(row => ['Merchant', row.store_name, row.sales_total]),
        ].slice(0, 10);
        document.getElementById('side-list').innerHTML = side.length ? side.map(row => `
            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3">
                <div><p class="text-sm font-semibold text-gray-900">${esc(row[1])}</p><p class="text-xs text-gray-500">${esc(row[0])}</p></div>
                <span class="badge badge-brand">${Number(row[2] || 0).toLocaleString()}</span>
            </div>
        `).join('') : '<p class="py-6 text-center text-sm text-gray-400">No analytics yet.</p>';
    }

    function renderSection(name, payload) {
        const list = document.getElementById('main-list');
        const rows = Array.isArray(payload.data) ? payload.data : (Array.isArray(payload) ? payload : []);
        document.getElementById('main-title').textContent = name.charAt(0).toUpperCase() + name.slice(1);
        if (name === 'dashboard' || name === 'sales' || name === 'reports') {
            const data = name === 'reports' ? payload : (payload || {});
            list.innerHTML = renderReportBlocks(data);
            return;
        }
        if (!rows.length) {
            list.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">No records found.</p>';
            return;
        }
        list.innerHTML = rows.map(row => renderRow(name, row)).join('');
    }

    function renderReportBlocks(data) {
        const blocks = [
            ['Sales', data.sales_stats || data.sales || {}],
            ['Orders', data.order_stats || data.orders || {}],
            ['Products', data.product_stats || data.products || {}],
            ['Categories', data.category_stats || data.categories || {}],
            ['Merchants', data.merchant_stats || data.merchants || {}],
            ['Podcasts', data.podcast_stats || data.podcasts || {}],
        ];
        return blocks.map(([title, stats]) => `
            <div class="rounded-lg border border-gray-200 p-4">
                <h4 class="text-sm font-bold text-gray-900">${esc(title)}</h4>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    ${Object.entries(stats).map(([key, value]) => `<div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm"><span class="text-gray-500">${esc(key.replaceAll('_', ' '))}</span><span class="font-bold text-gray-900">${esc(value)}</span></div>`).join('')}
                </div>
            </div>
        `).join('');
    }

    function renderRow(type, row) {
        const title = row.name || row.store_name || row.order_number || row.product_name || ('Record #' + row.id);
        const subtitle = row.type_label || row.business_type || row.status || row.category?.name || row.vendor?.store_name || '';
        const metric = row.products_count ?? row.vendors_count ?? row.total_amount ?? row.quantity ?? '';
        return `
            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-4">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">${esc(title)}</p>
                    <p class="mt-0.5 text-xs text-gray-500">${esc(subtitle)}</p>
                </div>
                <span class="badge badge-brand">${esc(metric)}</span>
            </div>
        `;
    }
});
</script>
@endpush
