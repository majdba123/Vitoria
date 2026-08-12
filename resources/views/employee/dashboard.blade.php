@extends('layouts.employee')

@section('title', __('employee.dashboard_overview') . ' - Vetora')
@section('page-title', __('employee.dashboard_overview'))

@section('content')
<div class="space-y-6">
    <section class="dashboard-page-header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-brand-600 dark:text-brand-300">{{ __('employee.workspace') }}</p>
            <h2 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ __('employee.dashboard_title') }}</h2>
            <p class="dashboard-section-copy">{{ __('employee.dashboard_copy') }}</p>
        </div>
        <div class="dashboard-page-header-actions">
            <a href="{{ route('employee.products.index') }}" class="btn-primary btn-sm">{{ __('employee.view_products') }}</a>
            <button id="reload-dashboard-btn" class="btn-secondary btn-sm">{{ __('employee.refresh') }}</button>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('employee.total_products') }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" id="stat-total-products">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('employee.active_products_label') }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" id="stat-active-products">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip" style="background: var(--color-success-soft); color: var(--color-success-strong);">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('employee.approved_products') }}</p>
                    <p class="text-lg font-bold" id="stat-approved-products" style="color: var(--color-success-strong)">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip" style="background: var(--color-warning-soft); color: var(--color-warning-strong);">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('employee.pending_products') }}</p>
                    <p class="text-lg font-bold" id="stat-pending-products" style="color: var(--color-warning-strong)">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip" style="background: var(--color-danger-soft); color: var(--color-danger-strong);">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('employee.rejected_products') }}</p>
                    <p class="text-lg font-bold" id="stat-rejected-products" style="color: var(--color-danger-strong)">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636A9 9 0 0 1 5.636 18.364M5.636 5.636A9 9 0 0 0 18.364 18.364M5.636 5.636 18.364 18.364"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('employee.inactive_products_label') }}</p>
                    <p class="text-lg font-bold text-slate-600" id="stat-inactive-products">0</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
        <a href="{{ route('employee.products.index') }}" class="card border-s-2 border-s-brand-500 transition-colors hover:bg-gray-50 dark:hover:bg-gray-900">
            <div class="card-body flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-gray-400">{{ __('employee.status_summary') }}</p>
                    <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white">{{ __('employee.all_products') }}</h3>
                </div>
                <span class="badge badge-brand">{{ __('employee.open_tab') }}</span>
            </div>
        </a>
        <a href="{{ route('employee.products.index', ['status' => 'approved']) }}" class="card border-s-2 border-s-emerald-500 transition-colors hover:bg-gray-50 dark:hover:bg-gray-900">
            <div class="card-body flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-gray-400">{{ __('employee.status_summary') }}</p>
                    <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white">{{ __('employee.active_products_tab') }}</h3>
                </div>
                <span class="badge badge-success">{{ __('employee.open_tab') }}</span>
            </div>
        </a>
        <a href="{{ route('employee.products.index', ['status' => 'pending']) }}" class="card border-s-2 border-s-amber-500 transition-colors hover:bg-gray-50 dark:hover:bg-gray-900">
            <div class="card-body flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-gray-400">{{ __('employee.status_summary') }}</p>
                    <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white">{{ __('employee.pending_products') }}</h3>
                </div>
                <span class="badge badge-warning">{{ __('employee.open_tab') }}</span>
            </div>
        </a>
        <a href="{{ route('employee.products.index', ['status' => 'rejected']) }}" class="card border-s-2 border-s-red-500 transition-colors hover:bg-gray-50 dark:hover:bg-gray-900">
            <div class="card-body flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-gray-400">{{ __('employee.status_summary') }}</p>
                    <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white">{{ __('employee.rejected_products') }}</h3>
                </div>
                <span class="badge badge-danger">{{ __('employee.open_tab') }}</span>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.4fr_1fr]">
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="dashboard-section-title">{{ __('employee.products_by_category') }}</h3>
                        <p class="dashboard-section-copy">{{ __('employee.products_by_category_copy') }}</p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">{{ __('employee.live_mix') }}</span>
                </div>
            </div>
            <div class="card-body">
                <div id="category-stats" class="space-y-3">
                    <div class="py-8 text-center text-sm text-gray-400">{{ __('common.loading') }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="dashboard-section-title">{{ __('employee.products_by_type') }}</h3>
                        <p class="dashboard-section-copy">{{ __('employee.products_by_type_copy') }}</p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">{{ __('employee.type_view') }}</span>
                </div>
            </div>
            <div class="card-body">
                <div id="type-stats" class="space-y-3">
                    <div class="py-8 text-center text-sm text-gray-400">{{ __('common.loading') }}</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('employee-ready', function () {
    const categoryStats = document.getElementById('category-stats');
    const typeStats = document.getElementById('type-stats');
    const reloadBtn = document.getElementById('reload-dashboard-btn');
    let reloadInFlight = false;

    reloadBtn.addEventListener('click', loadDashboard);

    loadDashboard();

    async function loadDashboard() {
        if (reloadInFlight) {
            return;
        }

        reloadInFlight = true;
        reloadBtn.disabled = true;

        try {
            await Promise.all([loadStats(), loadDistribution()]);
        } finally {
            reloadInFlight = false;
            reloadBtn.disabled = false;
        }
    }

    async function loadStats() {
        const statIds = [
            'stat-total-products',
            'stat-pending-products',
            'stat-approved-products',
            'stat-rejected-products',
            'stat-active-products',
            'stat-inactive-products',
        ];

        try {
            const [overview, pending, approved, rejected, active, inactive] = await Promise.all([
                window.axios.get('/api/employee/products?per_page=1'),
                window.axios.get('/api/employee/products?per_page=1&status=pending'),
                window.axios.get('/api/employee/products?per_page=1&status=approved'),
                window.axios.get('/api/employee/products?per_page=1&status=rejected'),
                window.axios.get('/api/employee/products?per_page=1&is_active=1'),
                window.axios.get('/api/employee/products?per_page=1&is_active=0'),
            ]);

            document.getElementById('stat-total-products').textContent = overview.data.meta?.total ?? 0;
            document.getElementById('stat-pending-products').textContent = pending.data.meta?.total ?? 0;
            document.getElementById('stat-approved-products').textContent = approved.data.meta?.total ?? 0;
            document.getElementById('stat-rejected-products').textContent = rejected.data.meta?.total ?? 0;
            document.getElementById('stat-active-products').textContent = active.data.meta?.total ?? 0;
            document.getElementById('stat-inactive-products').textContent = inactive.data.meta?.total ?? 0;
        } catch (error) {
            statIds.forEach((id) => {
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = '—';
                    el.title = '{{ __('common.generic_error') }}';
                }
            });
        }
    }

    async function loadDistribution() {
        try {
            const allProducts = await fetchAllProducts();
            renderCategoryStats(allProducts);
            renderTypeStats(allProducts);
        } catch (error) {
            categoryStats.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">{{ __('employee.no_products') }}</p>';
            typeStats.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">{{ __('employee.no_products') }}</p>';
        }
    }

    async function fetchAllProducts() {
        const products = [];
        let page = 1;
        let lastPage = 1;

        do {
            const response = await window.axios.get('/api/employee/products?per_page=50&page=' + page);
            const payload = response.data || {};
            const pageItems = Array.isArray(payload.data) ? payload.data : [];
            products.push(...pageItems);
            lastPage = payload.meta?.last_page ?? 1;
            page++;
        } while (page <= lastPage);

        return products;
    }

    function renderCategoryStats(products) {
        const counts = new Map();

        products.forEach((product) => {
            const categoryName = product?.category?.name || '{{ __('common.not_found') }}';
            counts.set(categoryName, (counts.get(categoryName) || 0) + 1);
        });

        const rows = [...counts.entries()]
            .sort((left, right) => right[1] - left[1])
            .slice(0, 6);

        if (!rows.length) {
            categoryStats.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">{{ __('employee.no_products') }}</p>';
            return;
        }

        const maxValue = rows[0][1];
        categoryStats.innerHTML = rows.map(([label, value]) => `
            <div class="rounded-2xl border border-gray-200/80 p-4 dark:border-gray-800">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">${escapeHtml(label)}</p>
                    <span class="text-sm font-bold text-brand-600 dark:text-brand-300">${value}</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-full rounded-full bg-brand-500" style="width:${Math.max((value / maxValue) * 100, 8)}%"></div>
                </div>
            </div>
        `).join('');
    }

    function renderTypeStats(products) {
        const counts = new Map();

        products.forEach((product) => {
            const rawType = product?.category?.type_label || product?.category?.type || '';
            const typeLabel = rawType || '{{ __('common.not_found') }}';
            counts.set(typeLabel, (counts.get(typeLabel) || 0) + 1);
        });

        const rows = [...counts.entries()].sort((left, right) => right[1] - left[1]);

        if (!rows.length) {
            typeStats.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">{{ __('employee.no_products') }}</p>';
            return;
        }

        const total = rows.reduce((sum, row) => sum + row[1], 0);
        typeStats.innerHTML = rows.map(([label, value], index) => {
            const colors = ['bg-brand-500', 'bg-info-500', 'bg-amber-500', 'bg-rose-500'];
            const colorClass = colors[index % colors.length];
            const percent = total > 0 ? Math.round((value / total) * 100) : 0;

            return `
                <div class="border border-gray-200/80 p-4 dark:border-gray-800" style="border-radius: var(--radius-card)">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">${escapeHtml(label)}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${percent}% of all products</p>
                        </div>
                        <span class="inline-flex h-12 min-w-12 items-center justify-center rounded-2xl bg-gray-100 px-3 text-sm font-bold text-gray-900 dark:bg-gray-800 dark:text-white">${value}</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-full rounded-full ${colorClass}" style="width:${Math.max(percent, 8)}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

});
</script>
@endpush
