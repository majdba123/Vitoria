@extends('layouts.syndicate')

@php
    $section = $section ?? 'dashboard';
    $sectionLabels = [
        'dashboard' => __('syndicate.dashboard'),
        'categories' => __('syndicate.categories'),
        'vendors' => __('syndicate.vendors'),
        'products' => __('syndicate.products'),
        'podcasts' => __('syndicate.podcasts'),
        'orders' => __('syndicate.orders'),
        'sales' => __('syndicate.sales'),
        'reports' => __('syndicate.reports'),
    ];
@endphp

@section('title', ($sectionLabels[$section] ?? __('syndicate.dashboard')) . ' - Vetora')
@section('page-title', $sectionLabels[$section] ?? __('syndicate.dashboard'))

@section('content')
<div class="space-y-6">
    <section class="dashboard-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-[0.22em] text-brand-600 dark:text-brand-300">{{ __('syndicate.workspace') }}</p>
            <h2 id="syndicate-name" class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ __('syndicate.loading_data') }}</h2>
            <p class="dashboard-section-copy">{{ __('syndicate.dashboard_copy') }}</p>
        </div>
        <div class="dashboard-page-header-actions">
            <span id="syndicate-type" class="badge badge-brand">-</span>
            <span id="syndicate-status" class="badge badge-success">-</span>
            <a href="{{ route('syndicate.sales') }}" class="btn-secondary btn-sm">{{ __('syndicate.sales_cta') }}</a>
            <a href="{{ route('syndicate.reports') }}" class="btn-primary btn-sm">{{ __('syndicate.reports_cta') }}</a>
        </div>
    </section>

    <section id="overview-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @for ($i = 0; $i < 8; $i++)
            <div class="stat-tile card-body">
                <div class="skeleton h-4 w-24 rounded"></div>
                <div class="skeleton mt-4 h-8 w-20 rounded"></div>
            </div>
        @endfor
    </section>

    <section class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-body flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white" id="main-title">{{ __('syndicate.records_title') }}</h3>
                    <p id="main-subtitle" class="mt-1 text-xs text-gray-500">{{ __('syndicate.records_subtitle') }}</p>
                </div>
                <span id="main-count" class="badge badge-brand hidden"></span>
            </div>
            <div id="main-list" class="card-body space-y-3">
                <p class="py-8 text-center text-sm text-gray-400">{{ __('syndicate.loading_data') }}</p>
            </div>
            <div id="main-pagination" class="hidden items-center justify-between border-t border-gray-100 px-4 py-3 text-sm dark:border-gray-800">
                <p id="main-page-info" class="text-xs text-gray-500"></p>
                <div class="flex gap-2">
                    <button id="main-prev" type="button" class="btn-secondary btn-xs">{{ __('syndicate.prev') }}</button>
                    <button id="main-next" type="button" class="btn-secondary btn-xs">{{ __('syndicate.next') }}</button>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card">
                <div class="card-body border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-black text-gray-900 dark:text-white">{{ __('syndicate.top_performance') }}</h3>
                    <p class="mt-1 text-xs text-gray-500">{{ __('syndicate.top_performance_copy') }}</p>
                </div>
                <div id="side-list" class="card-body space-y-3"></div>
            </div>

            <div class="card">
                <div class="card-body border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-black text-gray-900 dark:text-white">{{ __('syndicate.quick_summary') }}</h3>
                </div>
                <div id="quick-summary" class="card-body space-y-2"></div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('syndicate-ready', async function () {
    const section = @json($section);
    const sectionLabels = @json($sectionLabels);
    const endpoint = section === 'dashboard' || section === 'sales' ? 'overview' : (section === 'reports' ? 'reports' : section);
    const list = document.getElementById('main-list');
    const pagination = document.getElementById('main-pagination');
    const esc = (value) => { const d = document.createElement('div'); d.textContent = value == null ? '' : String(value); return d.innerHTML; };
    let currentPage = 1;
    let lastPage = 1;
    let overview = {};

    document.getElementById('main-prev')?.addEventListener('click', function () {
        if (currentPage > 1) loadSection(currentPage - 1);
    });

    document.getElementById('main-next')?.addEventListener('click', function () {
        if (currentPage < lastPage) loadSection(currentPage + 1);
    });

    try {
        const overviewRes = await window.axios.get('/api/syndicate/overview');
        overview = overviewRes.data.data || {};
        renderHeader(overview.syndicate || {});
        renderOverview(overview);
        await loadSection(1);
    } catch (error) {
        showLoadError(error, @json(__('syndicate.load_failed')));
    }

    async function loadSection(page) {
        list.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">' + @json(__('syndicate.loading_data')) + '</p>';
        document.getElementById('main-count').classList.add('hidden');

        try {
            const params = ['categories', 'vendors', 'products', 'orders'].includes(endpoint) ? { page, per_page: 15 } : {};
            const sectionRes = await window.axios.get('/api/syndicate/' + endpoint, { params });
            const payload = sectionRes.data.data || {};
            const meta = sectionRes.data.meta || payload.meta || {};

            currentPage = meta.current_page || 1;
            lastPage = meta.last_page || 1;
            renderSection(section, payload, meta);
            renderPagination(meta);
        } catch (error) {
            showLoadError(error, @json(__('syndicate.load_failed_section')));
        }
    }

    function renderHeader(syndicate) {
        document.getElementById('syndicate-name').textContent = syndicate.name || 'Vetora';
        document.getElementById('syndicate-type').textContent = typeLabel(syndicate.type);
        document.getElementById('syndicate-status').textContent = syndicate.status === 'inactive' ? @json(__('syndicate.inactive')) : @json(__('syndicate.active'));
        document.getElementById('syndicate-status').className = syndicate.status === 'inactive' ? 'badge badge-danger' : 'badge badge-success';
    }

    function renderOverview(data) {
        const sales = data.sales_stats || {};
        const orders = data.order_stats || {};
        const cards = [
            { label: @json(__('syndicate.vendors')), value: data.total_vendors || 0 },
            { label: @json(__('syndicate.products')), value: data.total_products || 0 },
            { label: @json(__('syndicate.orders')), value: orders.total_orders || 0 },
            { label: @json(__('syndicate.sales')), value: sales.total_sales_amount || 0 },
            { label: @json(__('syndicate.categories')), value: data.total_categories || 0 },
            { label: @json(__('common.active')), value: data.active_vendors || 0 },
            { label: @json(__('common.pending')), value: orders.pending_orders || 0 },
            { label: @json(__('common.completed')), value: orders.completed_orders || 0 },
        ];
        document.getElementById('overview-grid').innerHTML = cards.map((card) => `
            <div class="stat-tile card-body">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">${esc(card.label)}</p>
                <p class="mt-3 text-2xl font-black text-gray-900 dark:text-white">${esc(card.value)}</p>
            </div>
        `).join('');

        renderQuickSummary(data);
        renderSideList(data);
    }

    function renderQuickSummary(data) {
        const container = document.getElementById('quick-summary');
        const rows = [
            [@json(__('syndicate.categories')), data.total_categories || 0],
            [@json(__('syndicate.vendors')), data.total_vendors || 0],
            [@json(__('syndicate.products')), data.total_products || 0],
        ];
        container.innerHTML = rows.map(([label, value]) => `
            <div class="list-panel flex items-center justify-between gap-3">
                <span class="text-sm font-semibold">${esc(label)}</span>
                <span class="badge badge-brand">${esc(value)}</span>
            </div>
        `).join('');
    }

    function renderSideList(data) {
        const container = document.getElementById('side-list');
        const rows = (data.top_categories || data.top_vendors || []).slice(0, 5);
        if (!rows.length) {
            container.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">' + @json(__('common.no_data')) + '</p>';
            return;
        }

        container.innerHTML = rows.map((row, index) => `
            <div class="list-panel flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">${esc(row.name || row.title || ('#' + (index + 1)))}</p>
                    <p class="mt-1 text-xs text-gray-500">${esc(sectionLabels[section] || '')}</p>
                </div>
                <span class="badge badge-brand">${esc(row.total_sales || row.products_count || row.orders_count || row.count || 0)}</span>
            </div>
        `).join('');
    }

    function renderSection(name, payload, meta) {
        const title = sectionLabels[name] || @json(__('syndicate.records_title'));
        const subtitle = @json(__('syndicate.records_subtitle'));
        document.getElementById('main-title').textContent = title;
        document.getElementById('main-subtitle').textContent = subtitle;
        const countBadge = document.getElementById('main-count');

        if (typeof meta.total === 'number') {
            countBadge.textContent = meta.total;
            countBadge.classList.remove('hidden');
        }

        const items = Array.isArray(payload.data) ? payload.data : (Array.isArray(payload.items) ? payload.items : []);
        if (!items.length) {
            list.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">' + @json(__('common.no_data')) + '</p>';
            return;
        }

        list.innerHTML = items.map((item) => `
            <div class="list-panel">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-gray-900 dark:text-white">${esc(item.name || item.title || item.id || '—')}</p>
                        <p class="mt-1 text-xs text-gray-500">${esc(item.description || item.email || item.status || '')}</p>
                    </div>
                    <span class="badge badge-brand">${esc(item.products_count || item.orders_count || item.total_sales || item.id || '')}</span>
                </div>
            </div>
        `).join('');
    }

    function renderPagination(meta) {
        if (!meta || !meta.last_page || meta.last_page <= 1) {
            pagination.classList.add('hidden');
            return;
        }

        pagination.classList.remove('hidden');
        document.getElementById('main-page-info').textContent = `${meta.current_page} / ${meta.last_page}`;
        document.getElementById('main-prev').disabled = meta.current_page <= 1;
        document.getElementById('main-next').disabled = meta.current_page >= meta.last_page;
    }

    function typeLabel(type) {
        if (type === 'agriculture') return @json(__('syndicate.type_agriculture'));
        if (type === 'veterinary') return @json(__('syndicate.type_veterinary'));
        return @json(__('syndicate.type_default'));
    }

    function showLoadError(error, message) {
        document.getElementById('main-list').innerHTML = `<p class="py-8 text-center text-sm text-red-500">${esc(message)}</p>`;
    }
});
</script>
@endpush
