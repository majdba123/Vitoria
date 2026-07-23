@extends('layouts.vendor')

@section('title', __('vendor.dashboard') . ' - Vetora')
@section('page-title', __('vendor.dashboard'))

@section('content')
<div class="space-y-6">
    <section class="dashboard-page-header">
        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-[0.22em] text-brand-600 dark:text-brand-300">{{ __('vendor.workspace') }}</p>
            <h2 class="mt-2 text-2xl font-black text-gray-900 dark:text-white" id="vendor-welcome">{{ __('vendor.dashboard_heading') }}</h2>
            <p class="dashboard-section-copy">{{ __('vendor.dashboard_copy') }}</p>
        </div>
        <div class="dashboard-page-header-actions">
            <a href="{{ route('vendor.products.create') }}" class="btn-primary btn-sm">{{ __('vendor.create_product') }}</a>
            <a href="{{ route('vendor.orders.index') }}" class="btn-secondary btn-sm">{{ __('vendor.orders') }}</a>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('vendor.store_status') }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" id="store-status">{{ __('common.loading') }}</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip bg-blue-500/10 text-blue-700 dark:text-blue-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('vendor.total_products') }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" id="stat-products">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('vendor.active_products') }}</p>
                    <p class="text-lg font-bold text-emerald-600" id="stat-active-products">0</p>
                </div>
            </div>
        </div>

        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip bg-amber-500/10 text-amber-700 dark:text-amber-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('vendor.orders') }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" id="stat-orders">0</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="card">
            <div class="card-body border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="dashboard-section-title">{{ __('vendor.recent_products_title') }}</h3>
                        <p class="dashboard-section-copy">{{ __('vendor.recent_products_copy') }}</p>
                    </div>
                    <a href="{{ route('vendor.products.index') }}" class="btn-secondary btn-xs">{{ __('vendor.view_all') }}</a>
                </div>
            </div>
            <div class="card-body">
                <div id="recent-products" class="space-y-3">
                    <div class="py-8 text-center">
                        <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-gray-200 border-t-emerald-500"></div>
                        <p class="mt-2 text-sm text-gray-500">{{ __('common.loading') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100 dark:border-gray-800">
                <h3 class="dashboard-section-title">{{ __('vendor.quick_actions_title') }}</h3>
                <p class="dashboard-section-copy">{{ __('vendor.quick_actions_copy') }}</p>
            </div>
            <div class="card-body grid gap-3">
                <a href="{{ route('vendor.products.create') }}" class="list-panel text-sm font-semibold">{{ __('vendor.create_product') }}</a>
                <a href="{{ route('vendor.products.index') }}" class="list-panel text-sm font-semibold">{{ __('vendor.products') }}</a>
                <a href="{{ route('vendor.orders.index') }}" class="list-panel text-sm font-semibold">{{ __('vendor.orders') }}</a>
                <a href="{{ route('vendor.commission') }}" class="list-panel text-sm font-semibold">{{ __('vendor.commission') }}</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-b border-gray-100 dark:border-gray-800">
            <h3 class="dashboard-section-title">{{ __('vendor.store_information_title') }}</h3>
            <p class="dashboard-section-copy">{{ __('vendor.store_information_copy') }}</p>
        </div>
        <div class="card-body">
            <div id="store-info" class="grid gap-3 sm:grid-cols-2">
                <p class="text-sm text-gray-500">{{ __('common.loading') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('vendor-ready', async function () {
    try {
        const [productsRes, ordersRes, profileRes] = await Promise.all([
            window.axios.get('/api/vendor/products?per_page=5'),
            window.axios.get('/api/vendor/orders?per_page=1'),
            window.axios.get('/api/vendor/profile'),
        ]);

        const products = productsRes.data.data || [];
        const ordersMeta = ordersRes.data.meta || {};
        const profile = profileRes.data.data || {};

        document.getElementById('vendor-welcome').textContent = profile.store_name || @json(__('vendor.dashboard_heading'));
        document.getElementById('stat-products').textContent = productsRes.data.meta?.total ?? products.length;
        document.getElementById('stat-active-products').textContent = products.filter(product => product.is_active).length;
        document.getElementById('stat-orders').textContent = ordersMeta.total ?? 0;
        document.getElementById('store-status').textContent = profile.is_active ? @json(__('common.active')) : @json(__('common.inactive'));

        renderStoreInfo(profile);
        renderRecentProducts(products);
    } catch (error) {
        document.getElementById('recent-products').innerHTML = '<p class="py-8 text-center text-sm text-red-500">' + @json(__('vendor.failed_notifications')) + '</p>';
    }

    function renderStoreInfo(profile) {
        const items = [
            [@json(__('vendor.store_name_label')), profile.store_name || '—'],
            [@json(__('vendor.owner_name_label')), profile.owner_name || profile.name || '—'],
            [@json(__('vendor.city_label')), profile.city?.name || '—'],
            [@json(__('vendor.email_label')), profile.email || '—'],
        ];

        document.getElementById('store-info').innerHTML = items.map(([label, value]) => `
            <div class="list-panel">
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-gray-400">${label}</p>
                <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">${value}</p>
            </div>
        `).join('');
    }

    function renderRecentProducts(products) {
        const container = document.getElementById('recent-products');
        if (!products.length) {
            container.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">' + @json(__('vendor.assigned_categories_empty')) + '</p>';
            return;
        }

        container.innerHTML = products.map(product => `
            <a href="/vendor/products/${product.id}" class="list-panel block">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">${escapeHtml(product.name || '—')}</p>
                        <p class="mt-1 text-xs text-gray-500">${escapeHtml(product.category?.name || product.status || '')}</p>
                    </div>
                    <span class="badge ${product.is_active ? 'badge-success' : 'badge-warning'}">${product.is_active ? @json(__('common.active')) : @json(__('common.inactive'))}</span>
                </div>
            </a>
        `).join('');
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }
});
</script>
@endpush
