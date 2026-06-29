@extends('layouts.employee')

@section('title', 'Products - Vetora')
@section('page-title', 'Products')

@section('content')
<div class="space-y-6">
    <div class="workspace-hero">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="eyebrow bg-white/10 text-white ring-1 ring-inset ring-white/10">{{ __('employee.workspace') }}</span>
                <h2 class="mt-4 text-2xl font-black sm:text-3xl">{{ __('employee.products_title') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-300">{{ __('employee.products_copy') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('employee.dashboard') }}" class="btn-secondary btn-sm">{{ __('employee.back_dashboard') }}</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-b border-gray-100">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="dashboard-section-title">{{ __('employee.all_products') }}</h3>
                    <p class="dashboard-section-copy">{{ __('employee.all_products_copy') }}</p>
                </div>
                <div class="flex gap-2">
                    <select id="status-filter" class="form-select w-44">
                        <option value="">{{ __('employee.all_statuses') }}</option>
                        <option value="pending">{{ __('employee.pending') }}</option>
                        <option value="approved">{{ __('employee.approved') }}</option>
                        <option value="rejected">{{ __('employee.rejected') }}</option>
                    </select>
                    <button id="reload-btn" class="btn-secondary btn-sm">Refresh</button>
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
    const grid = document.getElementById('products-grid');
    const empty = document.getElementById('products-empty');
    const alertBox = document.getElementById('products-alert');
    const statusFilter = document.getElementById('status-filter');
    const reloadBtn = document.getElementById('reload-btn');

    loadProducts();

    reloadBtn.addEventListener('click', loadProducts);
    statusFilter.addEventListener('change', loadProducts);

    async function loadProducts() {
        try {
            grid.innerHTML = '<div class="col-span-full py-10 text-center text-sm text-gray-400">{{ __('common.loading') }}</div>';
            empty.classList.add('hidden');
            alertBox.classList.add('hidden');

            const params = new URLSearchParams({ per_page: '100' });
            if (statusFilter.value) {
                params.set('status', statusFilter.value);
            }
            const response = await window.axios.get('/api/employee/products?' + params.toString());
            const products = response.data.data || [];

            if (!products.length) {
                grid.innerHTML = '';
                empty.classList.remove('hidden');
                return;
            }

            grid.innerHTML = products.map((product) => `
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
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">${escapeHtml(product.price || '')}</span>
                            <a href="{{ url('/employee/products') }}/${product.id}/edit" class="btn-primary btn-sm">{{ __('employee.review_product') }}</a>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            grid.innerHTML = '';
            alertBox.classList.remove('hidden');
            document.getElementById('products-alert-message').textContent = error.response?.data?.message || '{{ __('common.unexpected_error') }}';
        }
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
