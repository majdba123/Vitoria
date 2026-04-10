@extends('layouts.admin')

@section('title', 'Create Category — SyriaZone Admin')
@section('page-title', 'Create Category')

@section('content')
<div class="space-y-4">
    <div class="card">
        <div class="card-body">
            <form id="category-form" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label for="name" class="form-label">Category Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" class="form-input" required>
                    </div>

                    <div>
                        <label for="commission" class="form-label">Commission (%) <span class="text-red-500">*</span></label>
                        <input type="number" id="commission" name="commission" class="form-input" step="0.01" min="0" max="100" placeholder="e.g. 10.00" value="0">
                        <p class="mt-1 text-xs text-gray-500">Percentage commission charged on products in this category (0–100).</p>
                    </div>

                    <div>
                        <label for="logo" class="form-label">Logo</label>
                        <input type="file" id="logo" name="logo" accept="image/*" class="form-input">
                        <p class="mt-1 text-xs text-gray-500">Accepted formats: JPEG, PNG, GIF, WebP. Max size: 2MB</p>
                    </div>

                    <div class="flex gap-2 pt-4">
                        <a href="{{ route('admin.categories.index') }}" class="btn-secondary btn-sm flex-1">Cancel</a>
                        <button type="submit" class="btn-primary btn-sm flex-1">Create Category</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<x-alert type="error" id="category-alert" />
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('category-form');
    const alert = document.getElementById('category-alert');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        try {
            const res = await window.axios.post('/api/admin/categories', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            window.location.href = '/admin/categories';
        } catch (e) {
            alert.textContent = e.response?.data?.message || 'Failed to create category.';
            alert.classList.remove('hidden');
        }
    });
});
</script>
@endpush

