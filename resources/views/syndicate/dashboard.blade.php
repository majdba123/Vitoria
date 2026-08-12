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
    // Which sections render as a real data table (vs. the overview/reports summary layout).
    $tableSections = ['categories', 'vendors', 'products', 'orders'];

    $syndicateJsStrings = [
        'active' => __('common.active'),
        'inactive' => __('common.inactive'),
        'pending' => __('common.pending'),
        'completed' => __('common.completed'),
        'noData' => __('common.no_data'),
        'thStore' => __('syndicate.th_store'),
        'thOwner' => __('syndicate.th_owner'),
        'thCategory' => __('syndicate.th_category'),
        'thType' => __('syndicate.th_type'),
        'thStatus' => __('syndicate.th_status'),
        'thVendors' => __('syndicate.th_vendors'),
        'thProducts' => __('syndicate.th_products'),
        'thOrder' => __('syndicate.th_order'),
        'thCustomer' => __('syndicate.th_customer'),
        'thTotal' => __('syndicate.th_total'),
        'thDate' => __('syndicate.th_date'),
        'reportsSales' => __('syndicate.reports_sales_title'),
        'reportsOrders' => __('syndicate.reports_orders_title'),
        'reportsProducts' => __('syndicate.reports_products_title'),
        'reportsCategories' => __('syndicate.reports_categories_title'),
        'reportsMerchants' => __('syndicate.reports_merchants_title'),
        'totalSales' => __('syndicate.total_sales'),
        'completedSales' => __('syndicate.completed_sales'),
        'averageOrderValue' => __('syndicate.average_order_value'),
        'totalOrders' => __('syndicate.total_orders'),
        'pendingOrders' => __('syndicate.pending_orders'),
        'completedOrders' => __('syndicate.completed_orders'),
        'cancelledOrders' => __('syndicate.cancelled_orders'),
        'activeProducts' => __('syndicate.active_products'),
        'inactiveProducts' => __('syndicate.inactive_products'),
        'activeMerchants' => __('syndicate.active_merchants'),
        'inactiveMerchants' => __('syndicate.inactive_merchants'),
        'totalCategories' => __('syndicate.categories'),
        'categoriesWithoutProducts' => __('admin.categories_without_products_title'),
        'categoriesWithoutVendors' => __('admin.categories_without_vendors_title'),
    ];
@endphp

@section('title', ($sectionLabels[$section] ?? __('syndicate.dashboard')) . ' - Vetora')
@section('page-title', $sectionLabels[$section] ?? __('syndicate.dashboard'))

@section('content')
<div class="space-y-6">
    <section class="dashboard-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-brand-600 dark:text-brand-300">{{ __('syndicate.workspace') }}</p>
            <h2 id="syndicate-name" class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ __('syndicate.loading_data') }}</h2>
            <p class="dashboard-section-copy">{{ __('syndicate.dashboard_copy') }}</p>
        </div>
        <div class="dashboard-page-header-actions">
            <span id="syndicate-type" class="badge badge-brand">-</span>
            <span id="syndicate-status" class="badge badge-success">-</span>
            <a href="{{ route('syndicate.sales') }}" class="btn-secondary btn-sm">{{ __('syndicate.sales_cta') }}</a>
            <a href="{{ route('syndicate.reports') }}" class="btn-primary btn-sm">{{ __('syndicate.reports_cta') }}</a>
        </div>
    </section>

    {{-- KPI: 3 primary stat-tiles + a metrics-row for secondary counts, not 8 identical tiles. --}}
    <section id="overview-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @for ($i = 0; $i < 3; $i++)
            <div class="stat-tile card-body">
                <div class="skeleton h-4 w-24 rounded"></div>
                <div class="skeleton mt-4 h-8 w-20 rounded"></div>
            </div>
        @endfor
    </section>
    <section id="overview-metrics" class="metrics-row border-t border-gray-100 pt-4 dark:border-gray-800"></section>

    {{-- Table sections: categories / vendors / products / orders --}}
    <section id="table-section" class="card hidden overflow-hidden">
        <div class="card-body flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white" id="table-title">{{ __('syndicate.records_title') }}</h3>
                <p id="table-subtitle" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('syndicate.records_subtitle') }}</p>
            </div>
            <span id="table-count" class="badge badge-brand hidden"></span>
        </div>
        <div class="admin-table-wrap table-responsive">
            <table class="admin-table">
                <thead id="table-head"></thead>
                <tbody id="table-body"></tbody>
            </table>
        </div>
        <div id="table-empty" class="empty-state hidden">{{ __('common.no_data') }}</div>
        <div id="table-pagination" class="hidden items-center justify-between border-t border-gray-100 px-4 py-3 text-sm dark:border-gray-800">
            <p id="table-page-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
            <div class="flex gap-2">
                <button id="table-prev" type="button" class="btn-secondary btn-xs">{{ __('syndicate.prev') }}</button>
                <button id="table-next" type="button" class="btn-secondary btn-xs">{{ __('syndicate.next') }}</button>
            </div>
        </div>
    </section>

    {{-- Podcasts: honest empty state - there is no podcast data source yet. --}}
    <section id="podcasts-section" class="hidden card">
        <div class="empty-state">
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ __('syndicate.podcasts_unavailable') }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('syndicate.podcasts_unavailable_copy') }}</p>
        </div>
    </section>

    {{-- Reports: distinct summary layout per domain, not a generic list. --}}
    <section id="reports-section" class="hidden grid grid-cols-1 gap-5 xl:grid-cols-2"></section>

    {{-- Dashboard / Sales: overview top-performance + quick summary --}}
    <section id="overview-section" class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-body border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('syndicate.top_performance') }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('syndicate.top_performance_copy') }}</p>
            </div>
            <div id="side-list" class="card-body space-y-3"></div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ __('syndicate.quick_summary') }}</h3>
            </div>
            <div id="quick-summary" class="card-body space-y-2"></div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('syndicate-ready', async function () {
    const section = @json($section);
    const tableSections = @json($tableSections);
    const sectionLabels = @json($sectionLabels);
    const i18n = @json($syndicateJsStrings);
    const esc = (value) => { const d = document.createElement('div'); d.textContent = value == null ? '' : String(value); return d.innerHTML; };
    const isTableSection = tableSections.includes(section);
    const isPodcasts = section === 'podcasts';
    const isReports = section === 'reports';
    const isOverview = !isTableSection && !isPodcasts && !isReports;

    document.getElementById('table-section').classList.toggle('hidden', !isTableSection);
    document.getElementById('podcasts-section').classList.toggle('hidden', !isPodcasts);
    document.getElementById('reports-section').classList.toggle('hidden', !isReports);
    document.getElementById('overview-section').classList.toggle('hidden', !isOverview);

    let currentPage = 1;
    let lastPage = 1;
    let overview = {};

    document.getElementById('table-prev')?.addEventListener('click', function () {
        if (currentPage > 1) loadTable(currentPage - 1);
    });
    document.getElementById('table-next')?.addEventListener('click', function () {
        if (currentPage < lastPage) loadTable(currentPage + 1);
    });

    await loadOverview();

    if (isTableSection) {
        await loadTable(1);
    } else if (isReports) {
        await loadReports();
    }

    async function loadOverview() {
        try {
            const res = await window.axios.get('/api/syndicate/overview');
            overview = res.data.data || {};
            renderHeader(overview.syndicate || {});
            renderKpis(overview);
            if (isOverview) {
                renderTopPerformance(overview);
                renderQuickSummary(overview);
            }
        } catch (error) {
            showOverviewLoadError();
        }
    }

    function showOverviewLoadError() {
        const grid = document.getElementById('overview-grid');
        if (!grid) return;
        grid.innerHTML = `
            <div class="stat-tile card-body sm:col-span-3">
                <p class="text-center text-sm text-red-500 dark:text-red-300">${esc(@json(__('syndicate.load_failed')))}</p>
                <button type="button" id="overview-retry-btn" class="mx-auto mt-3 block text-xs font-bold text-brand-600 underline dark:text-brand-300">${esc(@json(__('common.refresh')))}</button>
            </div>
        `;
        document.getElementById('overview-retry-btn')?.addEventListener('click', loadOverview);
    }

    function renderHeader(syndicate) {
        document.getElementById('syndicate-name').textContent = syndicate.name || 'Vetora';
        document.getElementById('syndicate-type').textContent = typeLabel(syndicate.type);
        document.getElementById('syndicate-status').textContent = syndicate.status === 'inactive' ? @json(__('syndicate.inactive')) : @json(__('syndicate.active'));
        document.getElementById('syndicate-status').className = syndicate.status === 'inactive' ? 'badge badge-danger' : 'badge badge-success';
    }

    function renderKpis(data) {
        const orders = data.order_stats || {};
        const merchants = data.merchant_stats || {};
        const primary = [
            { label: @json(__('syndicate.vendors')), value: data.total_merchants || 0 },
            { label: @json(__('syndicate.products')), value: data.total_products || 0 },
            { label: i18n.totalOrders, value: orders.total_orders || 0 },
        ];
        document.getElementById('overview-grid').innerHTML = primary.map((card) => `
            <div class="stat-tile card-body">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">${esc(card.label)}</p>
                <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">${esc(card.value)}</p>
            </div>
        `).join('');

        const secondary = [
            { label: i18n.totalCategories, value: data.total_categories || 0 },
            { label: i18n.activeMerchants, value: merchants.active_merchants || 0, tone: 'success' },
            { label: i18n.pendingOrders, value: orders.pending_orders || 0, tone: 'warning' },
            { label: i18n.completedOrders, value: orders.completed_orders || 0, tone: 'success' },
        ];
        document.getElementById('overview-metrics').innerHTML = secondary.map((item) => `
            <div class="metrics-row-item">
                <span class="metrics-row-label">${esc(item.label)}</span>
                <span class="metrics-row-value"${item.tone ? ` style="color: var(--color-${item.tone}-strong)"` : ''}>${esc(item.value)}</span>
            </div>
        `).join('');
    }

    function renderQuickSummary(data) {
        const container = document.getElementById('quick-summary');
        const rows = [
            [@json(__('syndicate.categories')), data.total_categories || 0],
            [@json(__('syndicate.vendors')), data.total_merchants || 0],
            [@json(__('syndicate.products')), data.total_products || 0],
        ];
        container.innerHTML = rows.map(([label, value]) => `
            <div class="list-panel flex items-center justify-between gap-3">
                <span class="text-sm font-semibold">${esc(label)}</span>
                <span class="badge badge-brand">${esc(value)}</span>
            </div>
        `).join('');
    }

    function renderTopPerformance(data) {
        const container = document.getElementById('side-list');
        const rows = (data.top_merchants_by_sales || data.top_selling_categories || []).slice(0, 6);
        if (!rows.length) {
            container.innerHTML = `<p class="py-6 text-center text-sm text-gray-400">${esc(i18n.noData)}</p>`;
            return;
        }
        container.innerHTML = rows.map((row) => `
            <div class="list-panel flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">${esc(row.store_name || row.name || '—')}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${row.orders_count != null ? esc(row.orders_count) + ' ' + esc(i18n.totalOrders).toLowerCase() : ''}</p>
                </div>
                <span class="badge badge-brand tabular-nums">${row.sales_total != null ? Number(row.sales_total).toLocaleString() : esc(row.products_count || row.count || 0)}</span>
            </div>
        `).join('');
    }

    // ---- Table sections (categories / vendors / products / orders) ----

    const tableSpecs = {
        categories: {
            head: () => `<tr><th scope="col">${esc(i18n.thCategory)}</th><th scope="col">${esc(i18n.thType)}</th><th scope="col" class="text-end">${esc(i18n.thVendors)}</th><th scope="col" class="text-end">${esc(i18n.thProducts)}</th></tr>`,
            row: (c) => `<tr>
                <td class="font-semibold text-gray-900 dark:text-white">${esc(c.name)}</td>
                <td class="text-gray-600 dark:text-gray-300">${typeLabel(c.type)}</td>
                <td class="text-end tabular-nums">${Number(c.vendors_count || 0)}</td>
                <td class="text-end tabular-nums">${Number(c.products_count || 0)}</td>
            </tr>`,
        },
        vendors: {
            head: () => `<tr><th scope="col">${esc(i18n.thStore)}</th><th scope="col">${esc(i18n.thOwner)}</th><th scope="col">${esc(i18n.thCategory)}</th><th scope="col" class="text-end">${esc(i18n.thProducts)}</th><th scope="col">${esc(i18n.thStatus)}</th></tr>`,
            row: (v) => `<tr>
                <td class="font-semibold text-gray-900 dark:text-white">${esc(v.store_name)}</td>
                <td class="text-gray-600 dark:text-gray-300">${esc(v.user?.name || '—')}</td>
                <td class="text-gray-600 dark:text-gray-300">${esc((v.categories || []).map((c) => c.name).join(', ') || '—')}</td>
                <td class="text-end tabular-nums">${Number(v.products_count || 0)}</td>
                <td><span class="badge ${v.is_active ? 'badge-success' : 'badge-danger'}">${v.is_active ? esc(i18n.active) : esc(i18n.inactive)}</span></td>
            </tr>`,
        },
        products: {
            head: () => `<tr><th scope="col">${esc(i18n.thProducts)}</th><th scope="col">${esc(i18n.thStore)}</th><th scope="col">${esc(i18n.thCategory)}</th><th scope="col">${esc(i18n.thStatus)}</th></tr>`,
            row: (p) => `<tr>
                <td class="font-semibold text-gray-900 dark:text-white">${esc(p.name)}</td>
                <td class="text-gray-600 dark:text-gray-300">${esc(p.vendor?.store_name || '—')}</td>
                <td class="text-gray-600 dark:text-gray-300">${esc(p.category?.name || '—')}</td>
                <td><span class="badge ${p.is_active ? 'badge-success' : 'badge-danger'}">${p.is_active ? esc(i18n.active) : esc(i18n.inactive)}</span></td>
            </tr>`,
        },
        orders: {
            head: () => `<tr><th scope="col">${esc(i18n.thOrder)}</th><th scope="col">${esc(i18n.thCustomer)}</th><th scope="col">${esc(i18n.thStore)}</th><th scope="col" class="text-end">${esc(i18n.thTotal)}</th><th scope="col">${esc(i18n.thStatus)}</th></tr>`,
            row: (o) => `<tr>
                <td class="font-semibold text-gray-900 dark:text-white">${esc(o.order_number || ('#' + o.id))}</td>
                <td class="text-gray-600 dark:text-gray-300">${esc(o.user?.name || '—')}</td>
                <td class="text-gray-600 dark:text-gray-300">${esc(o.vendor?.store_name || '—')}</td>
                <td class="text-end tabular-nums">${Number(o.total_amount || 0).toLocaleString()}</td>
                <td><span class="badge ${orderStatusBadge(o.status)}">${esc(orderStatusLabel(o.status))}</span></td>
            </tr>`,
        },
    };

    async function loadTable(page) {
        const spec = tableSpecs[section];
        if (!spec) return;

        currentPage = page;
        const tbody = document.getElementById('table-body');
        const empty = document.getElementById('table-empty');
        const pagination = document.getElementById('table-pagination');
        const countBadge = document.getElementById('table-count');

        document.getElementById('table-title').textContent = sectionLabels[section] || @json(__('syndicate.records_title'));
        document.getElementById('table-head').innerHTML = spec.head();
        tbody.innerHTML = `<tr><td colspan="6" class="py-8 text-center text-gray-400">${esc(@json(__('syndicate.loading_data')))}</td></tr>`;
        empty.classList.add('hidden');
        pagination.classList.add('hidden');
        countBadge.classList.add('hidden');
        setPaginationDisabled(true);

        try {
            const res = await window.axios.get('/api/syndicate/' + section, { params: { page, per_page: 15 } });
            const items = res.data.data || [];
            const meta = res.data.meta || {};
            lastPage = meta.last_page || 1;
            currentPage = meta.current_page || 1;

            if (typeof meta.total === 'number') {
                countBadge.textContent = meta.total;
                countBadge.classList.remove('hidden');
            }

            if (!items.length) {
                tbody.innerHTML = '';
                empty.classList.remove('hidden');
                return;
            }

            tbody.innerHTML = items.map(spec.row).join('');

            if (lastPage > 1) {
                document.getElementById('table-page-info').textContent = `${currentPage} / ${lastPage} (${meta.total || 0})`;
                pagination.classList.remove('hidden');
                setPaginationDisabled(false);
            }
        } catch (error) {
            tbody.innerHTML = '';
            empty.classList.remove('hidden');
        } finally {
            setPaginationDisabled(false);
        }
    }

    function setPaginationDisabled(disabled) {
        const prevBtn = document.getElementById('table-prev');
        const nextBtn = document.getElementById('table-next');
        if (prevBtn) prevBtn.disabled = disabled || currentPage <= 1;
        if (nextBtn) nextBtn.disabled = disabled || currentPage >= lastPage;
    }

    // ---- Reports: one summary card per domain, using the real nested shape. ----

    async function loadReports() {
        const container = document.getElementById('reports-section');
        container.innerHTML = `<p class="col-span-full py-8 text-center text-sm text-gray-400">${esc(@json(__('syndicate.loading_data')))}</p>`;

        try {
            const res = await window.axios.get('/api/syndicate/reports');
            const data = res.data.data || {};
            const sales = data.sales || {};
            const orders = data.orders || {};
            const products = data.products || {};
            const categories = data.categories || {};
            const merchants = data.merchants || {};

            container.innerHTML = [
                reportCard(i18n.reportsSales, [
                    [i18n.totalSales, formatMoney(sales.total_sales)],
                    [i18n.completedSales, formatMoney(sales.completed_sales)],
                    [i18n.averageOrderValue, formatMoney(sales.average_order_value)],
                ]),
                reportCard(i18n.reportsOrders, [
                    [i18n.totalOrders, orders.total_orders || 0],
                    [i18n.pendingOrders, orders.pending_orders || 0],
                    [i18n.completedOrders, orders.completed_orders || 0],
                    [i18n.cancelledOrders, orders.cancelled_orders || 0],
                ]),
                reportCard(i18n.reportsProducts, [
                    [i18n.thProducts, products.total_products || 0],
                    [i18n.activeProducts, products.active_products || 0],
                    [i18n.inactiveProducts, products.inactive_products || 0],
                ]),
                reportCard(i18n.reportsCategories, [
                    [i18n.totalCategories, categories.total_categories || 0],
                    [i18n.categoriesWithoutProducts, categories.categories_without_products || 0],
                    [i18n.categoriesWithoutVendors, categories.categories_without_vendors || 0],
                ]),
                reportCard(i18n.reportsMerchants, [
                    [i18n.thVendors, merchants.total_merchants || 0],
                    [i18n.activeMerchants, merchants.active_merchants || 0],
                    [i18n.inactiveMerchants, merchants.inactive_merchants || 0],
                ]),
            ].join('');
        } catch (error) {
            container.innerHTML = `<p class="col-span-full py-8 text-center text-sm text-red-500 dark:text-red-300">${esc(@json(__('syndicate.load_failed_section')))}</p>`;
        }
    }

    function reportCard(title, rows) {
        return `
            <div class="card">
                <div class="card-body border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">${esc(title)}</h3>
                </div>
                <div class="metrics-row card-body">
                    ${rows.map(([label, value]) => `
                        <div class="metrics-row-item">
                            <span class="metrics-row-label">${esc(label)}</span>
                            <span class="metrics-row-value">${esc(value)}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function formatMoney(value) {
        return Number(value || 0).toLocaleString() + ' SYP';
    }

    function orderStatusBadge(status) {
        if (status === 'completed' || status === 'confirmed') return 'badge-success';
        if (status === 'cancelled') return 'badge-danger';
        return 'badge-warning';
    }

    function orderStatusLabel(status) {
        return status || i18n.pending;
    }

    function typeLabel(type) {
        if (type === 'agriculture') return @json(__('syndicate.type_agriculture'));
        if (type === 'veterinary') return @json(__('syndicate.type_veterinary'));
        return @json(__('syndicate.type_default'));
    }
});
</script>
@endpush
