@extends('layouts.employee')

@section('title', 'Employee Dashboard - Vetora')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div class="workspace-hero">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="eyebrow bg-white/10 text-white ring-1 ring-inset ring-white/10">{{ __('employee.workspace') }}</span>
                <h2 class="mt-4 text-2xl font-black sm:text-3xl">{{ __('employee.dashboard_title') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-300">{{ __('employee.dashboard_copy') }}</p>
            </div>
            <div class="flex shrink-0 gap-2">
                <a href="{{ route('employee.products.index') }}" class="btn-primary btn-sm">
                    {{ __('employee.view_products') }}
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-tile">
            <div class="card-body flex items-center gap-4">
                <div class="icon-chip bg-cyan-500/10 text-cyan-700 dark:text-cyan-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('employee.total_products') }}</p>
                    <p class="text-lg font-bold text-gray-900" id="stat-total-products">0</p>
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
    </div>

    <div class="card">
        <div class="card-body border-b border-gray-100">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="dashboard-section-title">{{ __('employee.recent_products') }}</h3>
                    <p class="dashboard-section-copy">{{ __('employee.recent_products_copy') }}</p>
                </div>
                <a href="{{ route('employee.products.index') }}" class="btn-secondary btn-xs">{{ __('employee.view_all') }}</a>
            </div>
        </div>
        <div class="card-body">
            <div id="recent-products" class="space-y-3">
                <div class="py-8 text-center">
                    <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-gray-200 border-t-cyan-500"></div>
                    <p class="mt-2 text-sm text-gray-500">{{ __('common.loading') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('employee-ready', async function () {
    try {
        const [overview, pending, approved, rejected] = await Promise.all([
            window.axios.get('/api/employee/products?per_page=5'),
            window.axios.get('/api/employee/products?per_page=1&status=pending'),
            window.axios.get('/api/employee/products?per_page=1&status=approved'),
            window.axios.get('/api/employee/products?per_page=1&status=rejected'),
        ]);

        document.getElementById('stat-total-products').textContent = overview.data.meta?.total ?? 0;
        document.getElementById('stat-pending-products').textContent = pending.data.meta?.total ?? 0;
        document.getElementById('stat-approved-products').textContent = approved.data.meta?.total ?? 0;
        document.getElementById('stat-rejected-products').textContent = rejected.data.meta?.total ?? 0;

        const products = overview.data.data || [];
        const container = document.getElementById('recent-products');

        if (products.length === 0) {
            container.innerHTML = '<p class="py-10 text-center text-sm text-gray-400">{{ __('employee.no_products') }}</p>';
            return;
        }

        container.innerHTML = products.map((product) => `
            <a href="{{ url('/employee/products') }}/${product.id}/edit" class="group flex items-center gap-4 rounded-2xl border border-gray-200 p-4 transition-colors hover:border-cyan-300 hover:bg-cyan-50/40 dark:border-gray-800 dark:hover:border-cyan-500/20 dark:hover:bg-cyan-500/5">
                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl bg-gray-100">
                    <img src="${product.first_photo_url || product.image_url || product.icon_url || '/images/product-placeholder.svg'}" class="h-full w-full object-cover" alt="">
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">${escapeHtml(product.name || '')}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${escapeHtml(product.category?.name || '')}</p>
                </div>
                <span class="badge ${badgeClass(product.status)}">${escapeHtml(product.status || '')}</span>
                <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        `).join('');
    } catch (error) {
        const container = document.getElementById('recent-products');
        container.innerHTML = '<p class="py-8 text-center text-sm text-red-500">{{ __('common.unexpected_error') }}</p>';
    }

    function badgeClass(status) {
        if (status === 'approved') return 'badge-success';
        if (status === 'rejected') return 'badge-danger';
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
