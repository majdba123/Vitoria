@extends('layouts.admin')

@section('title', 'Pages — Vetora Admin')
@section('page-title', __('admin.pages'))

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-500">Manage static content pages (About, Terms, Privacy, FAQ...).</p>
        <button id="open-create-modal" class="btn-primary btn-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Page
        </button>
    </div>

    <x-alert type="error" id="page-alert" />
    <x-alert type="success" id="page-success" />

    <div class="card overflow-hidden">
        <div id="loading" class="py-10 text-center">
            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        </div>
        <div id="table-wrap" class="admin-table-wrap table-responsive hidden">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Slug</th>
                        <th scope="col">Title</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div id="empty" class="empty-state hidden">No pages found.</div>
    </div>
</div>

<div id="page-modal" class="mobile-dialog">
    <div class="mobile-dialog-card" style="max-width: 42rem;">
        <div class="mb-4 flex items-center justify-between">
            <h3 id="modal-title" class="text-lg font-bold text-gray-900 dark:text-white">Create Page</h3>
            <button type="button" onclick="closePageModal()" aria-label="Close" class="p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800" style="border-radius: var(--radius-control)">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="page-form" class="space-y-4">
            <input type="hidden" id="page-id">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="slug" class="form-label">Slug</label>
                    <input id="slug" class="form-input" placeholder="about-us">
                    <p class="form-error" id="slug-error"></p>
                </div>
                <div class="flex items-end gap-3">
                    <label for="is_published" class="form-label mb-0">Published</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="is_published" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
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
                <label for="content_en" class="form-label">Content (EN)</label>
                <textarea id="content_en" rows="5" class="form-textarea"></textarea>
                <p class="form-error" id="content_en-error"></p>
            </div>
            <div>
                <label for="content_ar" class="form-label">Content (AR)</label>
                <textarea id="content_ar" rows="5" class="form-textarea" dir="rtl"></textarea>
                <p class="form-error" id="content_ar-error"></p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="meta_title" class="form-label">Meta Title</label>
                    <input id="meta_title" class="form-input">
                    <p class="form-error" id="meta_title-error"></p>
                </div>
                <div>
                    <label for="meta_description" class="form-label">Meta Description</label>
                    <input id="meta_description" class="form-input">
                    <p class="form-error" id="meta_description-error"></p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                <button type="button" class="btn-secondary btn-sm" onclick="closePageModal()">Cancel</button>
                <button type="submit" class="btn-primary btn-sm">Save Page</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const $ = id => document.getElementById(id);
    const pageDialog = window.wireAccessibleDialog($('page-modal'), () => window.closePageModal(), { labelledBy: 'modal-title' });

    $('open-create-modal').addEventListener('click', () => openPageModal());
    $('page-form').addEventListener('submit', submitPage);

    loadPages();

    async function loadPages() {
        $('loading').classList.remove('hidden');
        $('table-wrap').classList.add('hidden');
        $('empty').classList.add('hidden');
        try {
            const res = await window.axios.get('/api/admin/pages');
            renderRows(res.data.data || []);
        } catch (e) {
            showAlert('page-alert', e.response?.data?.message || 'Failed to load pages.');
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
        $('rows').innerHTML = rows.map(p => `
            <tr>
                <td class="font-mono text-xs text-gray-700 dark:text-gray-300">${esc(p.slug)}</td>
                <td class="font-semibold text-gray-900 dark:text-white">${esc(p.title_en)}</td>
                <td>${p.is_published ? '<span class="badge badge-success">Published</span>' : '<span class="badge badge-warning">Draft</span>'}</td>
                <td class="text-end">
                    <div class="inline-flex justify-end gap-1.5">
                        <button class="btn-secondary btn-xs js-edit-page" data-json="${encodeURIComponent(JSON.stringify(p))}" aria-label="Edit ${esc(p.slug)}">Edit</button>
                        <button class="btn-danger btn-xs" onclick="deletePage(${p.id})" aria-label="Delete ${esc(p.slug)}">Delete</button>
                    </div>
                </td>
            </tr>
        `).join('');

        document.querySelectorAll('.js-edit-page[data-json]').forEach(btn => {
            btn.onclick = function () {
                openPageModal(JSON.parse(decodeURIComponent(this.dataset.json)));
            };
        });
    }

    window.openPageModal = function(page = null) {
        $('page-form').reset();
        clearFieldErrors();
        $('page-id').value = '';
        $('is_published').checked = true;
        $('modal-title').textContent = page ? 'Edit Page' : 'Create Page';
        if (page) {
            $('page-id').value = page.id || '';
            $('slug').value = page.slug || '';
            $('title_en').value = page.title_en || '';
            $('title_ar').value = page.title_ar || '';
            $('content_en').value = page.content_en || '';
            $('content_ar').value = page.content_ar || '';
            $('meta_title').value = page.meta_title || '';
            $('meta_description').value = page.meta_description || '';
            $('is_published').checked = !!page.is_published;
        }
        $('page-modal').classList.remove('hidden');
        $('page-modal').classList.add('flex');
        pageDialog.open();
    };

    window.closePageModal = function() {
        $('page-modal').classList.add('hidden');
        $('page-modal').classList.remove('flex');
        pageDialog.close();
    };

    async function submitPage(e) {
        e.preventDefault();
        clearFieldErrors();
        const id = $('page-id').value;
        const payload = {
            slug: $('slug').value.trim(),
            title_en: $('title_en').value.trim(),
            title_ar: $('title_ar').value.trim(),
            content_en: $('content_en').value,
            content_ar: $('content_ar').value,
            meta_title: $('meta_title').value.trim() || null,
            meta_description: $('meta_description').value.trim() || null,
            is_published: $('is_published').checked,
        };

        const submitButton = e.target.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            if (id) {
                await window.axios.put('/api/admin/pages/' + id, payload);
                showAlert('page-success', 'Page updated successfully.');
            } else {
                await window.axios.post('/api/admin/pages', payload);
                showAlert('page-success', 'Page created successfully.');
            }
            closePageModal();
            loadPages();
        } catch (error) {
            const errors = error.response?.data?.errors;
            if (errors) {
                Object.entries(errors).forEach(([field, messages]) => {
                    showFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                });
            } else {
                showAlert('page-alert', error.response?.data?.message || 'Failed to save page.');
            }
        } finally {
            submitButton.disabled = false;
        }
    }

    function clearFieldErrors() {
        document.querySelectorAll('#page-form .form-error').forEach((element) => {
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
        showAlert('page-alert', message);
    }

    window.deletePage = async function(id) {
        if (!confirm('Delete this page?')) {
            return;
        }
        try {
            await window.axios.delete('/api/admin/pages/' + id);
            showAlert('page-success', 'Page deleted successfully.');
            loadPages();
        } catch (e) {
            showAlert('page-alert', e.response?.data?.message || 'Failed to delete page.');
        }
    };

    function showAlert(id, message) {
        const alert = $(id);
        const messageElement = $(id + '-message');
        messageElement.textContent = message;
        alert.classList.remove('hidden');
        setTimeout(() => alert.classList.add('hidden'), 4500);
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
