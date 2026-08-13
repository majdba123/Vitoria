@extends('layouts.admin')

@section('title', 'Banners — Vetora Admin')
@section('page-title', __('admin.banners'))

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-500">Manage homepage banners and their visibility window.</p>
        <button id="open-create-modal" class="btn-primary btn-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Banner
        </button>
    </div>

    <x-alert type="error" id="banner-alert" />
    <x-alert type="success" id="banner-success" />

    <div class="card overflow-hidden">
        <div id="loading" class="py-10 text-center">
            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        </div>
        <div id="table-wrap" class="admin-table-wrap table-responsive hidden">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Image</th>
                        <th scope="col">Title</th>
                        <th scope="col">Sort</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div id="empty" class="empty-state hidden">No banners found.</div>
    </div>
</div>

<div id="banner-modal" class="mobile-dialog">
    <div class="mobile-dialog-card" style="max-width: 32rem;">
        <div class="mb-4 flex items-center justify-between">
            <h3 id="modal-title" class="text-lg font-bold text-gray-900 dark:text-white">Create Banner</h3>
            <button type="button" onclick="closeBannerModal()" aria-label="Close" class="p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800" style="border-radius: var(--radius-control)">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="banner-form" class="space-y-4">
            <input type="hidden" id="banner-id">
            <div>
                <label for="image" class="form-label">Image <span id="image-required-hint" class="text-gray-400">(required)</span></label>
                <input id="image" type="file" accept="image/*" class="form-input">
                <p class="form-error" id="image-error"></p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="title_en" class="form-label">Title (EN)</label>
                    <input id="title_en" class="form-input">
                    <p class="form-error" id="title_en-error"></p>
                </div>
                <div>
                    <label for="title_ar" class="form-label">Title (AR)</label>
                    <input id="title_ar" class="form-input" dir="rtl">
                    <p class="form-error" id="title_ar-error"></p>
                </div>
            </div>
            <div>
                <label for="link_url" class="form-label">Link URL</label>
                <input id="link_url" type="url" class="form-input" placeholder="https://...">
                <p class="form-error" id="link_url-error"></p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input id="sort_order" type="number" min="0" class="form-input" value="0">
                    <p class="form-error" id="sort_order-error"></p>
                </div>
                <div class="flex items-end gap-3">
                    <label for="is_active" class="form-label mb-0">Active</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="is_active" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div>
                    <label for="starts_at" class="form-label">Starts At</label>
                    <input id="starts_at" type="datetime-local" step="60" class="form-input">
                    <p class="form-error" id="starts_at-error"></p>
                </div>
                <div>
                    <label for="ends_at" class="form-label">Ends At</label>
                    <input id="ends_at" type="datetime-local" step="60" class="form-input">
                    <p class="form-error" id="ends_at-error"></p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                <button type="button" class="btn-secondary btn-sm" onclick="closeBannerModal()">Cancel</button>
                <button type="submit" class="btn-primary btn-sm">Save Banner</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const $ = id => document.getElementById(id);
    const bannerDialog = window.wireAccessibleDialog($('banner-modal'), () => window.closeBannerModal(), { labelledBy: 'modal-title' });

    $('open-create-modal').addEventListener('click', () => openBannerModal());
    $('banner-form').addEventListener('submit', submitBanner);

    loadBanners();

    async function loadBanners() {
        $('loading').classList.remove('hidden');
        $('table-wrap').classList.add('hidden');
        $('empty').classList.add('hidden');
        try {
            const res = await window.axios.get('/api/admin/banners');
            renderRows(res.data.data || []);
        } catch (e) {
            showAlert('banner-alert', e.response?.data?.message || 'Failed to load banners.');
        } finally {
            $('loading').classList.add('hidden');
        }
    }

    function renderRows(rows) {
        if (!rows.length) {
            $('empty').classList.remove('hidden');
            $('rows').innerHTML = '';
            return;
        }

        $('table-wrap').classList.remove('hidden');
        $('rows').innerHTML = rows.map(b => `
            <tr>
                <td><img src="/storage/${b.image_path}" alt="" class="h-10 w-16 object-cover" style="border-radius: var(--radius-control)"></td>
                <td class="font-semibold text-gray-900 dark:text-white">${esc(b.title_en || '—')}</td>
                <td class="tabular-nums text-gray-600 dark:text-gray-400">${b.sort_order ?? 0}</td>
                <td>${b.is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-warning">Inactive</span>'}</td>
                <td class="text-end">
                    <div class="inline-flex justify-end gap-1.5">
                        <button class="btn-secondary btn-xs js-edit-banner" data-json="${encodeURIComponent(JSON.stringify(b))}" aria-label="Edit banner ${b.id}">Edit</button>
                        <button class="btn-danger btn-xs" onclick="deleteBanner(${b.id})" aria-label="Delete banner ${b.id}">Delete</button>
                    </div>
                </td>
            </tr>
        `).join('');

        document.querySelectorAll('.js-edit-banner[data-json]').forEach(btn => {
            btn.onclick = function () {
                openBannerModal(JSON.parse(decodeURIComponent(this.dataset.json)));
            };
        });
    }

    window.openBannerModal = function(banner = null) {
        $('banner-form').reset();
        clearFieldErrors();
        $('banner-id').value = '';
        $('is_active').checked = true;
        $('sort_order').value = 0;
        $('image-required-hint').classList.remove('hidden');
        $('modal-title').textContent = banner ? 'Edit Banner' : 'Create Banner';
        if (banner) {
            $('banner-id').value = banner.id || '';
            $('title_en').value = banner.title_en || '';
            $('title_ar').value = banner.title_ar || '';
            $('link_url').value = banner.link_url || '';
            $('sort_order').value = banner.sort_order ?? 0;
            $('is_active').checked = !!banner.is_active;
            $('starts_at').value = toDateInput(banner.starts_at);
            $('ends_at').value = toDateInput(banner.ends_at);
            $('image-required-hint').classList.add('hidden');
        }
        $('banner-modal').classList.remove('hidden');
        $('banner-modal').classList.add('flex');
        bannerDialog.open();
    };

    window.closeBannerModal = function() {
        $('banner-modal').classList.add('hidden');
        $('banner-modal').classList.remove('flex');
        bannerDialog.close();
    };

    async function submitBanner(e) {
        e.preventDefault();
        clearFieldErrors();
        const id = $('banner-id').value;
        const formData = new FormData();
        if ($('image').files[0]) {
            formData.append('image', $('image').files[0]);
        }
        formData.append('title_en', $('title_en').value.trim());
        formData.append('title_ar', $('title_ar').value.trim());
        if ($('link_url').value.trim()) {
            formData.append('link_url', $('link_url').value.trim());
        }
        formData.append('sort_order', $('sort_order').value || 0);
        formData.append('is_active', $('is_active').checked ? '1' : '0');
        if ($('starts_at').value) {
            formData.append('starts_at', $('starts_at').value);
        }
        if ($('ends_at').value) {
            formData.append('ends_at', $('ends_at').value);
        }

        const submitButton = e.target.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            if (id) {
                formData.append('_method', 'PUT');
                await window.axios.post('/api/admin/banners/' + id, formData);
                showAlert('banner-success', 'Banner updated successfully.');
            } else {
                await window.axios.post('/api/admin/banners', formData);
                showAlert('banner-success', 'Banner created successfully.');
            }
            closeBannerModal();
            loadBanners();
        } catch (error) {
            const errors = error.response?.data?.errors;
            if (errors) {
                Object.entries(errors).forEach(([field, messages]) => {
                    showFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                });
            } else {
                showAlert('banner-alert', error.response?.data?.message || 'Failed to save banner.');
            }
        } finally {
            submitButton.disabled = false;
        }
    }

    function clearFieldErrors() {
        document.querySelectorAll('#banner-form .form-error').forEach((element) => {
            element.classList.add('hidden');
            element.textContent = '';
        });
    }

    function showFieldError(field, message) {
        const errorElement = $(field + '-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
            return;
        }
        showAlert('banner-alert', message);
    }

    window.deleteBanner = async function(id) {
        if (!confirm('Delete this banner?')) {
            return;
        }
        try {
            await window.axios.delete('/api/admin/banners/' + id);
            showAlert('banner-success', 'Banner deleted successfully.');
            loadBanners();
        } catch (e) {
            showAlert('banner-alert', e.response?.data?.message || 'Failed to delete banner.');
        }
    };

    function showAlert(id, message) {
        const alert = $(id);
        const messageElement = $(id + '-message');
        messageElement.textContent = message;
        alert.classList.remove('hidden');
        setTimeout(() => alert.classList.add('hidden'), 4500);
    }

    function toDateInput(value) {
        if (!value) {
            return '';
        }
        const normalized = String(value).replace(' ', 'T');
        if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(normalized)) {
            return normalized.slice(0, 16);
        }
        const date = new Date(value);
        const pad = n => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    function esc(value) {
        if (!value) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }
});
</script>
@endpush
