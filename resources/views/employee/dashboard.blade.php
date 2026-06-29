@extends('layouts.employee')

@section('title', 'Employee Dashboard - Vetora')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div class="workspace-hero">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <span class="eyebrow bg-white/10 text-white ring-1 ring-inset ring-white/10">{{ __('employee.workspace') }}</span>
                <h2 class="mt-4 text-2xl font-black sm:text-3xl">{{ __('employee.dashboard_title') }}</h2>
                <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">{{ __('employee.dashboard_copy') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button id="reload-dashboard-btn" class="btn-secondary btn-sm">Refresh</button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip bg-cyan-500/10 text-cyan-700 dark:text-cyan-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('employee.total_products') }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" id="stat-total-products">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip bg-sky-500/10 text-sky-700 dark:text-sky-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Active Products</p>
                    <p class="text-lg font-bold text-sky-600" id="stat-active-products">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('employee.approved_products') }}</p>
                    <p class="text-lg font-bold text-emerald-600" id="stat-approved-products">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip bg-amber-500/10 text-amber-700 dark:text-amber-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('employee.pending_products') }}</p>
                    <p class="text-lg font-bold text-amber-600" id="stat-pending-products">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip bg-rose-500/10 text-rose-700 dark:text-rose-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('employee.rejected_products') }}</p>
                    <p class="text-lg font-bold text-rose-600" id="stat-rejected-products">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip bg-slate-500/10 text-slate-700 dark:text-slate-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636A9 9 0 0 1 5.636 18.364M5.636 5.636A9 9 0 0 0 18.364 18.364M5.636 5.636 18.364 18.364"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Inactive Products</p>
                    <p class="text-lg font-bold text-slate-600" id="stat-inactive-products">0</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.4fr_1fr]">
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="dashboard-section-title">Products by Category</h3>
                        <p class="dashboard-section-copy">See which categories hold the most products right now.</p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Live mix</span>
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
                        <h3 class="dashboard-section-title">Products by Type</h3>
                        <p class="dashboard-section-copy">Track whether agriculture or veterinary products need more attention.</p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Type view</span>
                </div>
            </div>
            <div class="card-body">
                <div id="type-stats" class="space-y-3">
                    <div class="py-8 text-center text-sm text-gray-400">{{ __('common.loading') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-b border-gray-100">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="dashboard-section-title">{{ __('employee.all_products') }}</h3>
                    <p class="dashboard-section-copy">Filter products, inspect their content, and open the edit form directly from here.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <select id="status-filter" class="form-select w-full sm:w-52">
                        <option value="">{{ __('employee.all_statuses') }}</option>
                        <option value="pending">{{ __('employee.pending') }}</option>
                        <option value="approved">{{ __('employee.approved') }}</option>
                        <option value="rejected">{{ __('employee.rejected') }}</option>
                    </select>
                    <button id="apply-filter-btn" class="btn-primary btn-sm">{{ __('employee.view_products') }}</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <x-alert type="error" id="products-alert" />
            <div id="products-grid" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>
            <div id="products-empty" class="hidden py-16 text-center text-sm text-gray-400">{{ __('employee.no_products') }}</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('employee-ready', function () {
    const statusFilter = document.getElementById('status-filter');
    const productsGrid = document.getElementById('products-grid');
    const productsEmpty = document.getElementById('products-empty');
    const productsAlert = document.getElementById('products-alert');
    const categoryStats = document.getElementById('category-stats');
    const typeStats = document.getElementById('type-stats');
    const currentStatus = new URLSearchParams(window.location.search).get('status') || '';

    statusFilter.value = currentStatus;

    document.getElementById('reload-dashboard-btn').addEventListener('click', loadDashboard);
    document.getElementById('apply-filter-btn').addEventListener('click', function () {
        syncQueryString();
        loadProducts();
    });
    statusFilter.addEventListener('change', function () {
        syncQueryString();
        loadProducts();
    });

    loadDashboard();

    async function loadDashboard() {
        await Promise.all([loadStats(), loadProducts(), loadDistribution()]);
    }

    async function loadStats() {
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
        } catch (error) {}
    }

    async function loadDistribution() {
        try {
            const allProducts = await fetchAllProducts();
            renderCategoryStats(allProducts);
            renderTypeStats(allProducts);
        } catch (error) {
            categoryStats.innerHTML = '<p class="py-6 text-center text-sm text-red-500">{{ __('common.unexpected_error') }}</p>';
            typeStats.innerHTML = '<p class="py-6 text-center text-sm text-red-500">{{ __('common.unexpected_error') }}</p>';
        }
    }

    async function loadProducts() {
        try {
            productsAlert.classList.add('hidden');
            productsEmpty.classList.add('hidden');
            productsGrid.innerHTML = '<div class="col-span-full py-10 text-center text-sm text-gray-400">{{ __('common.loading') }}</div>';

            const params = new URLSearchParams({ per_page: '100' });
            if (statusFilter.value) {
                params.set('status', statusFilter.value);
            }

            const response = await window.axios.get('/api/employee/products?' + params.toString());
            const products = response.data.data || [];

            if (!products.length) {
                productsGrid.innerHTML = '';
                productsEmpty.classList.remove('hidden');
                return;
            }

            productsGrid.innerHTML = products.map((product) => `
                <div class="group overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-xl dark:border-gray-800 dark:bg-gray-950/70">
                    <div class="aspect-[4/3] overflow-hidden bg-gray-100">
                        <img src="${product.first_photo_url || product.image_url || product.icon_url || '/images/product-placeholder.svg'}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]" alt="">
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-gray-900 dark:text-white">${escapeHtml(product.name || '')}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${escapeHtml(product.category?.name || '')}</p>
                            </div>
                            <span class="badge ${badgeClass(product.status)}">${escapeHtml(product.status || '')}</span>
                        </div>
                        <p class="line-clamp-2 text-sm text-gray-600 dark:text-gray-300">${escapeHtml(product.description || '')}</p>
                        ${product.status === 'rejected' && product.rejection_reason ? `<div class="rounded-2xl bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">${escapeHtml(product.rejection_reason)}</div>` : ''}
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400 dark:text-gray-500">${escapeHtml(product.price || '')}</span>
                            <a href="{{ url('/employee/products') }}/${product.id}/edit" class="btn-primary btn-sm">{{ __('employee.review_product') }}</a>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            productsGrid.innerHTML = '';
            productsAlert.classList.remove('hidden');
            document.getElementById('products-alert-message').textContent = error.response?.data?.message || '{{ __('common.unexpected_error') }}';
        }
    }

    async function fetchAllProducts() {
        const products = [];
        let page = 1;
        let lastPage = 1;

        do {
            const response = await window.axios.get('/api/employee/products?per_page=50&page=' + page);
            const payload = response.data || {};
            products.push(...(payload.data || []));
            lastPage = payload.meta?.last_page ?? 1;
            page++;
        } while (page <= lastPage);

        return products;
    }

    function renderCategoryStats(products) {
        const counts = new Map();

        products.forEach((product) => {
            const categoryName = product.category?.name || 'Unassigned';
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
                    <span class="text-sm font-bold text-cyan-600 dark:text-cyan-300">${value}</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-blue-600" style="width:${Math.max((value / maxValue) * 100, 8)}%"></div>
                </div>
            </div>
        `).join('');
    }

    function renderTypeStats(products) {
        const counts = new Map();

        products.forEach((product) => {
            const typeLabel = product.category?.type_label || product.category?.type || 'Unknown';
            counts.set(typeLabel, (counts.get(typeLabel) || 0) + 1);
        });

        const rows = [...counts.entries()].sort((left, right) => right[1] - left[1]);

        if (!rows.length) {
            typeStats.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">{{ __('employee.no_products') }}</p>';
            return;
        }

        const total = rows.reduce((sum, row) => sum + row[1], 0);
        typeStats.innerHTML = rows.map(([label, value], index) => {
            const colors = [
                'from-emerald-500 to-green-600',
                'from-cyan-500 to-blue-600',
                'from-amber-500 to-orange-500',
                'from-rose-500 to-pink-600',
            ];
            const colorClass = colors[index % colors.length];
            const percent = total > 0 ? Math.round((value / total) * 100) : 0;

            return `
                <div class="rounded-[24px] border border-gray-200/80 p-4 dark:border-gray-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">${escapeHtml(label)}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${percent}% of all products</p>
                        </div>
                        <span class="inline-flex h-12 min-w-12 items-center justify-center rounded-2xl bg-gray-100 px-3 text-sm font-bold text-gray-900 dark:bg-gray-800 dark:text-white">${value}</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-full rounded-full bg-gradient-to-r ${colorClass}" style="width:${Math.max(percent, 8)}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function syncQueryString() {
        const url = new URL(window.location.href);
        if (statusFilter.value) {
            url.searchParams.set('status', statusFilter.value);
        } else {
            url.searchParams.delete('status');
        }
        window.history.replaceState({}, '', url.toString());
    }

    function badgeClass(status) {
        if (status === 'approved') {
            return 'badge-success';
        }
        if (status === 'rejected') {
            return 'badge-danger';
        }
        return 'badge-warning';
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }
});
</script>
@endpush
