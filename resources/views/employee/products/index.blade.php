@extends('layouts.employee')

@php
    $employeeStatus = request('status');
    $employeePageTitle = match ($employeeStatus) {
        'approved' => __('employee.active_products_tab'),
        'pending' => __('employee.pending_products'),
        'rejected' => __('employee.rejected_products'),
        default => __('employee.all_products'),
    };
    $employeePageCopy = match ($employeeStatus) {
        'approved' => __('employee.all_products_copy'),
        'pending' => __('employee.all_products_copy'),
        'rejected' => __('employee.all_products_copy'),
        default => __('employee.products_copy'),
    };
@endphp

@section('title', $employeePageTitle . ' - Vetora')
@section('page-title', $employeePageTitle)

@section('content')
<div class="space-y-6">
    <div class="workspace-hero">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-300">{{ __('employee.workspace') }}</p>
                <h2 class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $employeePageTitle }}</h2>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $employeePageCopy }}</p>
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
        <div class="card-body p-0">
            <x-alert type="error" id="products-alert" />
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('employee.all_products') }}</th>
                            <th>{{ __('admin.categories') }}</th>
                            <th>{{ __('employee.status') }}</th>
                            <th class="text-end">{{ __('products.fields.price') }}</th>
                            <th class="text-end">{{ __('admin.th_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="products-grid"></tbody>
                </table>
            </div>
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
    const currentStatus = new URLSearchParams(window.location.search).get('status') || '';

    statusFilter.value = currentStatus;

    loadProducts();

    reloadBtn.addEventListener('click', loadProducts);
    statusFilter.addEventListener('change', function () {
        syncQueryString();
        loadProducts();
    });

    async function loadProducts() {
        try {
            grid.innerHTML = '<tr><td colspan="5" class="py-10 text-center text-sm text-gray-400">{{ __('common.loading') }}</td></tr>';
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
                <tr>
                    <td><div class="flex min-w-56 items-center gap-3"><img src="${product.first_photo_url || '/images/product-placeholder.svg'}" class="h-10 w-10 shrink-0 border border-gray-200 object-cover dark:border-gray-700" alt=""><div><p class="font-semibold text-gray-950 dark:text-white">${escapeHtml(product.name || '')}</p>${product.status === 'rejected' && product.rejection_reason ? `<p class="mt-0.5 max-w-xs truncate text-xs text-red-600 dark:text-red-400">${escapeHtml(product.rejection_reason)}</p>` : ''}</div></div></td>
                    <td class="text-gray-600 dark:text-gray-300">${escapeHtml(product.category?.name || '—')}</td>
                    <td><span class="badge ${badgeClass(product.status)}">${escapeHtml(product.status || '')}</span></td>
                    <td class="text-end font-semibold text-gray-950 dark:text-white" dir="ltr">${escapeHtml(product.price || '')}</td>
                    <td class="text-end"><a href="{{ url('/employee/products') }}/${product.id}/edit" class="btn-secondary btn-xs">{{ __('employee.review_product') }}</a></td>
                </tr>
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

    function syncQueryString() {
        const url = new URL(window.location.href);
        if (statusFilter.value) {
            url.searchParams.set('status', statusFilter.value);
        } else {
            url.searchParams.delete('status');
        }
        window.history.replaceState({}, '', url.toString());
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }
});
</script>
@endpush
