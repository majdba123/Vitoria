@extends('layouts.employee')

@section('title', 'Product Review - Vetora')
@section('page-title', 'Product Review')

@section('content')
<div class="mx-auto max-w-5xl">
    <div class="card overflow-hidden">
        <div class="card-body border-b border-gray-100">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white" id="product-name">{{ __('common.loading') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500">{{ __('employee.review_product_copy') }}</p>
                </div>
                <a href="{{ route('employee.products.index') }}" class="btn-secondary btn-sm">{{ __('employee.back_products') }}</a>
            </div>
        </div>
        <div class="card-body">
            <div id="show-loading" class="py-16 text-center">
                <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-cyan-500"></div>
            </div>
            <div id="show-content" class="hidden grid gap-6 lg:grid-cols-[1fr_1fr]">
                <div class="overflow-hidden rounded-[28px] bg-gray-100">
                    <img id="product-image" class="h-full w-full object-cover" alt="">
                </div>
                <div class="space-y-4">
                    <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900/70">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ __('employee.description') }}</p>
                        <p id="product-description" class="mt-2 text-sm leading-7 text-gray-700 dark:text-gray-300"></p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900/70">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ __('employee.current_status') }}</p>
                        <p id="product-status" class="mt-2 text-sm font-semibold text-gray-900 dark:text-white"></p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900/70">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ __('employee.vendor_reason') }}</p>
                        <p id="product-reason" class="mt-2 text-sm font-semibold text-gray-900 dark:text-white"></p>
                    </div>
                    <a href="#" id="edit-link" class="btn-primary btn-sm">{{ __('employee.review_product') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('employee-ready', async function () {
    const productId = '{{ $productId }}';
    try {
        const response = await window.axios.get('/api/employee/products/' + productId);
        const product = response.data.data;
        document.getElementById('product-name').textContent = product.name || '';
        document.getElementById('product-image').src = product.first_photo_url || product.image_url || product.icon_url || '/images/product-placeholder.svg';
        document.getElementById('product-description').textContent = product.description || '';
        document.getElementById('product-status').textContent = product.status || '';
        document.getElementById('product-reason').textContent = product.rejection_reason || '{{ __('employee.no_reason') }}';
        document.getElementById('edit-link').href = '{{ url('/employee/products') }}/' + productId + '/edit';
        document.getElementById('show-loading').classList.add('hidden');
        document.getElementById('show-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('show-loading').innerHTML = '<p class="text-sm text-red-500">{{ __('common.unexpected_error') }}</p>';
    }
});
</script>
@endpush
