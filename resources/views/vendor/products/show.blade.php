@extends('layouts.vendor')

@section('title', 'Product Details')
@section('page-title', 'Product Details')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div id="product-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading product...</p>
    </div>

    <div id="product-content" class="hidden card">
        <div class="card-body space-y-4">
            <h2 id="product-name" class="text-2xl font-bold text-gray-900"></h2>
            <p id="product-category" class="text-sm text-gray-500"></p>
            <p id="product-description" class="text-sm text-gray-600"></p>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs uppercase text-gray-400">Price</p><p id="product-price" class="mt-1 text-sm font-semibold text-gray-900">—</p></div>
                <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs uppercase text-gray-400">Quantity</p><p id="product-quantity" class="mt-1 text-sm font-semibold text-gray-900">—</p></div>
                <div class="rounded-xl bg-gray-50 p-4"><p class="text-xs uppercase text-gray-400">Status</p><p id="product-status" class="mt-1 text-sm font-semibold text-gray-900">—</p></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    try {
        const response = await window.axios.get('/api/vendor/products/{{ $productId }}');
        const p = response.data.data;
        document.getElementById('product-name').textContent = p.name || 'Product';
        document.getElementById('product-category').textContent = p.category?.name || 'Unassigned';
        document.getElementById('product-description').textContent = p.description || 'No description.';
        document.getElementById('product-price').textContent = `$${parseFloat(p.price || 0).toFixed(2)}`;
        document.getElementById('product-quantity').textContent = p.quantity || 0;
        document.getElementById('product-status').textContent = p.is_active ? 'Active' : 'Inactive';
        document.getElementById('product-loading').classList.add('hidden');
        document.getElementById('product-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('product-loading').innerHTML = '<p class="text-sm font-medium text-red-500">Failed to load product.</p>';
    }
});
</script>
@endpush
