@extends('layouts.admin')

@section('title', 'Create Subcategory — SyriaZone Admin')
@section('page-title', 'Create Subcategory')

@section('content')
<div class="space-y-4">
    <div class="card">
        <div class="card-body">
            <form id="subcategory-form" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label for="category_id" class="form-label">Category <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" class="form-input" required>
                            <option value="">Select a category</option>
                        </select>
                    </div>

                    <div>
                        <label for="name" class="form-label">Subcategory Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" class="form-input" required>
                    </div>

                    <div>
                        <label for="image" class="form-label">Image</label>
                        <input type="file" id="image" name="image" accept="image/*" class="form-input">
                        <p class="mt-1 text-xs text-gray-500">Accepted formats: JPEG, PNG, GIF, WebP. Max size: 2MB</p>
                    </div>

                    <div class="flex gap-2 pt-4">
                        <a href="{{ route('admin.subcategories.index') }}" class="btn-secondary btn-sm flex-1">Cancel</a>
                        <button type="submit" class="btn-primary btn-sm flex-1">Create Subcategory</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<x-alert type="error" id="subcategory-alert" />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('subcategory-form');
    const alert = document.getElementById('subcategory-alert');
    const categorySelect = document.getElementById('category_id');

    loadCategories();

    async function loadCategories() {
        try {
            const res = await window.axios.get('/api/admin/categories');
            const categories = res.data.data || [];
            categorySelect.innerHTML = '<option value="">Select a category</option>' +
                categories.map(cat => `<option value="${cat.id}">${esc(cat.name)}</option>`).join('');
        } catch (e) {
            console.error('Failed to load categories:', e);
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        try {
            const res = await window.axios.post('/api/admin/subcategories', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            window.location.href = '/admin/subcategories';
        } catch (e) {
            alert.textContent = e.response?.data?.message || 'Failed to create subcategory.';
            alert.classList.remove('hidden');
        }
    });

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>
@endpush

