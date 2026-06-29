@extends('layouts.employee')

@section('title', 'Review Product - Vetora')
@section('page-title', 'Product Review')

@section('content')
<div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[1fr_1.1fr]">
    <div class="space-y-6">
        <div class="card overflow-hidden">
            <div class="card-body border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('employee.review_product') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500">{{ __('employee.review_product_copy') }}</p>
            </div>
            <div class="card-body">
                <div id="preview-loading" class="py-14 text-center">
                    <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-cyan-500"></div>
                    <p class="mt-3 text-sm text-gray-500">{{ __('common.loading') }}</p>
                </div>
                <div id="preview-content" class="hidden space-y-4">
                    <div class="aspect-[4/3] overflow-hidden rounded-[28px] bg-gray-100">
                        <img id="preview-image" src="" class="h-full w-full object-cover" alt="">
                    </div>
                    <div class="space-y-2">
                        <h3 id="preview-name" class="text-2xl font-black text-gray-900 dark:text-white">-</h3>
                        <p id="preview-desc" class="text-sm leading-7 text-gray-600 dark:text-gray-300"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900/70">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ __('employee.current_status') }}</p>
                            <p id="preview-status" class="mt-2 font-semibold text-gray-900 dark:text-white"></p>
                        </div>
                        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900/70">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ __('employee.vendor_reason') }}</p>
                            <p id="preview-reason" class="mt-2 font-semibold text-gray-900 dark:text-white"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body border-b border-gray-100">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('employee.moderation_form') }}</h2>
            <p class="mt-0.5 text-sm text-gray-500">{{ __('employee.moderation_form_copy') }}</p>
        </div>
        <div class="card-body">
            <x-alert type="error" id="edit-alert" />
            <x-alert type="success" id="edit-success" />

            <form id="edit-form" class="space-y-5" novalidate>
                <x-form.input name="name" :label="__('employee.product_name')" :required="false" />
                <div>
                    <label for="description" class="form-label">{{ __('employee.description') }}</label>
                    <textarea id="description" name="description" rows="4" class="form-textarea"></textarea>
                    <p class="form-error" id="description-error"></p>
                </div>

                <div>
                    <label for="image" class="form-label">{{ __('employee.photo') }}</label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input">
                    <p class="form-error" id="image-error"></p>
                </div>

                <div>
                    <label for="status" class="form-label">{{ __('employee.status') }}</label>
                    <select id="status" name="status" class="form-select">
                        <option value="pending">{{ __('employee.pending') }}</option>
                        <option value="approved">{{ __('employee.approved') }}</option>
                        <option value="rejected">{{ __('employee.rejected') }}</option>
                    </select>
                    <p class="form-error" id="status-error"></p>
                </div>

                <div id="reason-wrap">
                    <label for="rejection_reason" class="form-label">{{ __('employee.rejection_reason') }}</label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="4" class="form-textarea" placeholder="{{ __('employee.rejection_reason_placeholder') }}"></textarea>
                    <p class="form-error" id="rejection_reason-error"></p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('employee.dashboard') }}" class="btn-secondary">{{ __('common.cancel') }}</a>
                    <button type="submit" id="edit-btn" class="btn-primary">
                        <span id="edit-btn-text">{{ __('common.save') }}</span>
                        <svg id="edit-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('employee-ready', async function () {
    const productId = '{{ $productId }}';
    const form = document.getElementById('edit-form');
    const statusSelect = document.getElementById('status');
    const reasonWrap = document.getElementById('reason-wrap');
    const preview = {
        name: document.getElementById('preview-name'),
        desc: document.getElementById('preview-desc'),
        image: document.getElementById('preview-image'),
        status: document.getElementById('preview-status'),
        reason: document.getElementById('preview-reason'),
    };

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    function setStatusVisibility() {
        reasonWrap.classList.toggle('hidden', statusSelect.value !== 'rejected');
    }

    statusSelect.addEventListener('change', setStatusVisibility);

    try {
        const response = await window.axios.get('/api/employee/products/' + productId);
        const product = response.data.data;
        form.name.value = product.name || '';
        form.description.value = product.description || '';
        statusSelect.value = product.status || 'pending';
        document.getElementById('rejection_reason').value = product.rejection_reason || '';
        preview.name.textContent = product.name || '-';
        preview.desc.textContent = product.description || '';
        preview.image.src = product.first_photo_url || product.image_url || product.icon_url || '/images/product-placeholder.svg';
        preview.status.textContent = product.status || '';
        preview.reason.textContent = product.rejection_reason || '{{ __('employee.no_reason') }}';
        setStatusVisibility();
        document.getElementById('preview-loading').classList.add('hidden');
        document.getElementById('preview-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('preview-loading').innerHTML = '<p class="text-sm text-red-500">{{ __('common.unexpected_error') }}</p>';
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearErrors();
        toggleLoading(true);

        const formData = new FormData();
        const name = form.name.value.trim();
        const description = form.description.value.trim();

        if (name) formData.append('name', name);
        if (description) formData.append('description', description);
        if (statusSelect.value) formData.append('status', statusSelect.value);
        if (form.image.files[0]) formData.append('image', form.image.files[0]);
        if (statusSelect.value === 'rejected') {
            const reason = document.getElementById('rejection_reason').value.trim();
            if (reason) formData.append('rejection_reason', reason);
        }

        try {
            formData.append('_method', 'PUT');
            const response = await window.axios.post('/api/employee/products/' + productId, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            const product = response.data.data;
            document.getElementById('edit-success-message').textContent = response.data.message || '{{ __('common.save') }}';
            document.getElementById('edit-success').classList.remove('hidden');
            preview.name.textContent = product.name || '-';
            preview.desc.textContent = product.description || '';
            preview.image.src = product.first_photo_url || product.image_url || product.icon_url || '/images/product-placeholder.svg';
            preview.status.textContent = product.status || '';
            preview.reason.textContent = product.rejection_reason || '{{ __('employee.no_reason') }}';
        } catch (error) {
            if (error.response?.status === 422) {
                Object.entries(error.response.data.errors || {}).forEach(([field, messages]) => {
                    const errorElement = document.getElementById(field + '-error');
                    if (errorElement) {
                        errorElement.textContent = messages[0];
                    }
                });
            }
            document.getElementById('edit-alert-message').textContent = error.response?.data?.message || '{{ __('common.unexpected_error') }}';
            document.getElementById('edit-alert').classList.remove('hidden');
        } finally {
            toggleLoading(false);
        }
    });

    function clearErrors() {
        document.getElementById('edit-alert').classList.add('hidden');
        document.getElementById('edit-success').classList.add('hidden');
        document.querySelectorAll('[id$="-error"]').forEach((el) => {
            el.textContent = '';
        });
    }

    function toggleLoading(loading) {
        document.getElementById('edit-btn').disabled = loading;
        document.getElementById('edit-spinner').classList.toggle('hidden', !loading);
        document.getElementById('edit-btn-text').textContent = loading ? '{{ __('common.saving') }}' : '{{ __('common.save') }}';
    }
});
</script>
@endpush
