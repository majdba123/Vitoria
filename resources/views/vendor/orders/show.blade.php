@extends('layouts.vendor')

@section('title', 'Order Details')
@section('page-title', 'Order Details')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div id="order-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading order details...</p>
    </div>

    <div id="order-content" class="hidden space-y-4"></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    try {
        const response = await window.axios.get('/api/vendor/orders/{{ $orderId }}');
        const order = response.data.data;
        document.getElementById('order-content').innerHTML = (order.items || []).map(item => `
            <article class="card">
                <div class="card-body">
                    <p class="text-sm font-bold text-gray-900">${escapeHtml(item.product_name || 'Product')}</p>
                    <div class="mt-3 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3">
                        ${paramCard('Category', item.product?.category?.name || '—')}
                        ${paramCard('Original Unit Price', money(item.original_unit_price))}
                        ${paramCard('Applied Unit Price', money(item.unit_price))}
                        ${paramCard('Discount %', item.has_discount ? (Number.parseFloat(item.applied_discount_percentage || 0).toLocaleString() + '%') : '—')}
                        ${paramCard('Saved Amount', item.has_discount ? money(item.discount_amount) : '0 SYP')}
                        ${paramCard('Line Total', money(item.line_total))}
                    </div>
                </div>
            </article>`).join('');
        document.getElementById('order-loading').classList.add('hidden');
        document.getElementById('order-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('order-loading').innerHTML = '<p class="text-sm font-medium text-red-500">Failed to load order details.</p>';
    }

    function money(v) { return Number.parseFloat(v || 0).toLocaleString() + ' SYP'; }
    function escapeHtml(value) { const d = document.createElement('div'); d.textContent = value || ''; return d.innerHTML; }
    function paramCard(label, value) { return `<div class="rounded-lg border border-gray-100 bg-gray-50 px-2.5 py-2 dark:border-gray-800 dark:bg-gray-800/60"><p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">${escapeHtml(label)}</p><p class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">${escapeHtml(String(value ?? '—'))}</p></div>`; }
});
</script>
@endpush
