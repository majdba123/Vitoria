@extends('layouts.vendor')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')
<div class="mx-auto max-w-2xl">
    <div id="edit-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading product...</p>
    </div>

    <div id="edit-content" class="hidden card">
        <div class="card-body">
            <x-alert type="error" id="edit-alert" />
            <x-alert type="success" id="edit-success" />
            <form id="edit-form" class="space-y-6" novalidate>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-form.input name="name" label="Product Name" placeholder="Enter product name" :required="true" />
                    </div>
                    <x-form.input name="price" label="Price ($)" type="number" placeholder="0.00" :required="true" />
                    <x-form.input name="discount_percentage" label="Discount (%)" type="number" placeholder="Optional" />
                    <x-form.input name="quantity" label="Quantity" type="number" placeholder="0" :required="true" />
                    <div class="sm:col-span-2">
                        <label for="category_id" class="form-label">Category <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" class="form-input">
                            <option value="">Select category...</option>
                        </select>
                        <p class="form-error" id="category_id-error"></p>
                    </div>
                </div>
                <div>
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" rows="3" class="form-textarea"></textarea>
                    <p class="form-error" id="description-error"></p>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('vendor.products.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" id="edit-btn" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const productId = '{{ $productId }}';
    const form = document.getElementById('edit-form');
    const categorySelect = document.getElementById('category_id');
    try {
        const [categoriesResponse, productResponse] = await Promise.all([
            window.axios.get('/api/vendor/categories'),
            window.axios.get('/api/vendor/products/' + productId),
        ]);
        const categories = categoriesResponse.data.data || [];
        const p = productResponse.data.data;
        categorySelect.innerHTML = '<option value="">Select category...</option>' +
            categories.map(category => `<option value="${category.id}">${escapeHtml(category.name)}</option>`).join('');
        form.name.value = p.name || '';
        form.price.value = p.price || '';
        form.discount_percentage.value = p.discount_percentage || '';
        form.quantity.value = p.quantity || 0;
        categorySelect.value = p.category_id || '';
        form.description.value = p.description || '';
        document.getElementById('edit-loading').classList.add('hidden');
        document.getElementById('edit-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('edit-loading').innerHTML = '<p class="text-sm font-medium text-red-500">Failed to load product.</p>';
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('category_id', categorySelect.value);
        formData.append('name', form.name.value.trim());
        formData.append('price', parseFloat(form.price.value) || 0);
        if (form.discount_percentage.value !== '') formData.append('discount_percentage', parseFloat(form.discount_percentage.value) || 0);
        formData.append('quantity', parseInt(form.quantity.value) || 0);
        const desc = form.description.value.trim();
        if (desc) formData.append('description', desc);
        try {
            await window.axios.post('/api/vendor/products/' + productId, formData, {
                params: { _method: 'PUT' },
            });
            document.getElementById('edit-success-message').textContent = 'Product updated successfully.';
            document.getElementById('edit-success').classList.remove('hidden');
        } catch (error) {
            document.getElementById('edit-alert-message').textContent = error.response?.data?.message || 'Failed to update product.';
            document.getElementById('edit-alert').classList.remove('hidden');
        }
    });

    function escapeHtml(value) { const div = document.createElement('div'); div.textContent = value || ''; return div.innerHTML; }
});
</script>
@endpush
