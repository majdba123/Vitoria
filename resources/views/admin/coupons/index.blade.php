@extends('layouts.admin')

@section('title', 'Coupons — Vetora Admin')
@section('page-title', 'Coupons')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-500">Create and manage coupons with status and date windows.</p>
        <button id="open-create-modal" class="btn-primary btn-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Coupon
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="f-search" class="form-label">Search</label>
                    <input id="f-search" class="form-input" placeholder="Code or title">
                </div>
                <div class="w-full sm:w-64">
                    <label for="f-status" class="form-label">Status</label>
                    <select id="f-status" class="form-input">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <button id="btn-apply" class="btn-primary btn-sm">Apply</button>
            </div>
        </div>
    </div>

    <x-alert type="error" id="coupon-alert" />
    <x-alert type="success" id="coupon-success" />

    <div class="card overflow-hidden">
        <div id="loading" class="py-10 text-center">
            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        </div>
        <div id="table-wrap" class="admin-table-wrap table-responsive hidden">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Code</th>
                        <th scope="col">Title</th>
                        <th scope="col">Discount</th>
                        <th scope="col">Start</th>
                        <th scope="col">End</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div id="empty" class="empty-state hidden">No coupons found.</div>
        <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-800">
            <p id="pagination-info" class="text-xs text-gray-500 dark:text-gray-400"></p>
        </div>
    </div>
</div>

<div id="coupon-modal" class="mobile-dialog">
    <div class="mobile-dialog-card" style="max-width: 40rem;">
        <div class="mb-4 flex items-center justify-between">
            <h3 id="modal-title" class="text-lg font-bold text-gray-900 dark:text-white">Create Coupon</h3>
            <button type="button" onclick="closeCouponModal()" aria-label="Close" class="p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800" style="border-radius: var(--radius-control)">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="coupon-form" class="space-y-4">
            <input type="hidden" id="coupon-id">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="code" class="form-label">Code</label>
                    <input id="code" class="form-input" placeholder="SAVE10">
                    <p class="form-error" id="code-error"></p>
                </div>
                <div>
                    <label for="title" class="form-label">Title</label>
                    <input id="title" class="form-input" placeholder="Welcome Discount">
                    <p class="form-error" id="title-error"></p>
                </div>
                <div>
                    <label for="discount_type" class="form-label">Type</label>
                    <select id="discount_type" class="form-input">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed</option>
                    </select>
                    <p class="form-error" id="discount_type-error"></p>
                </div>
                <div>
                    <label for="discount_value" class="form-label">Value</label>
                    <input id="discount_value" type="number" step="0.01" max="100" class="form-input" placeholder="10">
                    <p class="form-error" id="discount_value-error"></p>
                </div>
                <div>
                    <label for="starts_at" class="form-label">Start Date & Time</label>
                    <input id="starts_at" type="datetime-local" step="60" class="form-input">
                    <p class="form-error" id="starts_at-error"></p>
                </div>
                <div>
                    <label for="ends_at" class="form-label">End Date & Time</label>
                    <input id="ends_at" type="datetime-local" step="60" class="form-input">
                    <p class="form-error" id="ends_at-error"></p>
                </div>
                <div>
                    <label for="usage_limit" class="form-label">Usage Limit</label>
                    <input id="usage_limit" type="number" min="1" class="form-input" placeholder="Optional">
                    <p class="form-error" id="usage_limit-error"></p>
                </div>
                <div class="flex items-end gap-3">
                    <label for="is_active" class="form-label mb-0">Active</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="is_active" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div>
                <label for="description" class="form-label">Description</label>
                <textarea id="description" rows="3" class="form-textarea" placeholder="Optional description"></textarea>
                <p class="form-error" id="description-error"></p>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                <button type="button" class="btn-secondary btn-sm" onclick="closeCouponModal()">Cancel</button>
                <button type="submit" class="btn-primary btn-sm">Save Coupon</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const $ = id => document.getElementById(id);
    let page = 1;
    const couponDialog = window.wireAccessibleDialog($('coupon-modal'), () => window.closeCouponModal(), { labelledBy: 'modal-title' });

    $('open-create-modal').addEventListener('click', () => openCouponModal());
    $('btn-apply').addEventListener('click', () => { page = 1; loadCoupons(); });
    $('coupon-form').addEventListener('submit', submitCoupon);
    $('discount_type').addEventListener('change', () => toggleDiscountValueMax());

    loadCoupons();

    function toggleDiscountValueMax() {
        if ($('discount_type').value === 'percentage') {
            $('discount_value').setAttribute('max', '100');
        } else {
            $('discount_value').removeAttribute('max');
        }
    }

    async function loadCoupons() {
        $('loading').classList.remove('hidden');
        $('table-wrap').classList.add('hidden');
        $('empty').classList.add('hidden');
        try {
            const params = new URLSearchParams({ page: page.toString() });
            const search = $('f-search').value.trim();
            const status = $('f-status').value;
            if (search) {
                params.append('search', search);
            }
            if (status !== '') {
                params.append('status', status);
            }

            const res = await window.axios.get('/api/admin/coupons?' + params.toString());
            renderRows(res.data.data || []);
            const meta = res.data.meta || {};
            $('pagination-info').textContent = `Page ${meta.current_page || 1} of ${meta.last_page || 1} · ${meta.total || 0} total`;
        } catch (e) {
            showAlert('coupon-alert', e.response?.data?.message || 'Failed to load coupons.');
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
        $('rows').innerHTML = rows.map(c => `
            <tr>
                <td class="font-mono text-xs font-bold text-gray-700 dark:text-gray-300">${esc(c.code)}</td>
                <td class="font-semibold text-gray-900 dark:text-white">${esc(c.title)}</td>
                <td class="tabular-nums text-gray-600 dark:text-gray-400">${c.discount_type === 'percentage' ? c.discount_value + '%' : Number(c.discount_value).toLocaleString() + ' SYP'}</td>
                <td class="tabular-nums text-gray-600 dark:text-gray-400">${fmtDate(c.starts_at)}</td>
                <td class="tabular-nums text-gray-600 dark:text-gray-400">${fmtDate(c.ends_at)}</td>
                <td>${statusBadge(c.status)}</td>
                <td class="text-end">
                    <div class="inline-flex justify-end gap-1.5">
                        <button class="btn-secondary btn-xs js-edit-coupon" data-json="${encodeURIComponent(JSON.stringify(c))}" aria-label="Edit ${esc(c.code)}">Edit</button>
                        <button class="btn-danger btn-xs" onclick="deleteCoupon(${c.id})" aria-label="Delete ${esc(c.code)}">Delete</button>
                    </div>
                </td>
            </tr>
        `).join('');

        document.querySelectorAll('.js-edit-coupon[data-json]').forEach(btn => {
            btn.onclick = function () {
                const data = JSON.parse(decodeURIComponent(this.dataset.json));
                openCouponModal(data);
            };
        });
    }

    window.openCouponModal = function(coupon = null) {
        $('coupon-form').reset();
        clearFieldErrors();
        $('coupon-id').value = '';
        $('is_active').checked = true;
        $('modal-title').textContent = coupon ? 'Edit Coupon' : 'Create Coupon';
        if (coupon) {
            $('coupon-id').value = coupon.id || '';
            $('code').value = coupon.code || '';
            $('title').value = coupon.title || '';
            $('discount_type').value = coupon.discount_type || 'percentage';
            $('discount_value').value = coupon.discount_value || '';
            $('description').value = coupon.description || '';
            $('usage_limit').value = coupon.usage_limit || '';
            $('is_active').checked = !!coupon.is_active;
            $('starts_at').value = toDateInput(coupon.starts_at);
            $('ends_at').value = toDateInput(coupon.ends_at);
        }
        toggleDiscountValueMax();
        $('coupon-modal').classList.remove('hidden');
        $('coupon-modal').classList.add('flex');
        couponDialog.open();
    };

    window.closeCouponModal = function() {
        $('coupon-modal').classList.add('hidden');
        $('coupon-modal').classList.remove('flex');
        couponDialog.close();
    };

    async function submitCoupon(e) {
        e.preventDefault();
        clearFieldErrors();
        const id = $('coupon-id').value;
        const payload = {
            code: $('code').value.trim(),
            title: $('title').value.trim(),
            description: $('description').value.trim() || null,
            discount_type: $('discount_type').value,
            discount_value: $('discount_value').value,
            starts_at: normalizeDateTimeValue($('starts_at').value, 'start'),
            ends_at: normalizeDateTimeValue($('ends_at').value, 'end'),
            usage_limit: $('usage_limit').value || null,
            is_active: $('is_active').checked,
        };

        if (payload.discount_type === 'percentage' && Number(payload.discount_value) > 100) {
            showFieldError('discount_value', 'Percentage discount cannot be greater than 100.');
            return;
        }

        const submitButton = e.target.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            if (id) {
                await window.axios.put('/api/admin/coupons/' + id, payload);
                showAlert('coupon-success', 'Coupon updated successfully.');
            } else {
                await window.axios.post('/api/admin/coupons', payload);
                showAlert('coupon-success', 'Coupon created successfully.');
            }
            closeCouponModal();
            loadCoupons();
        } catch (error) {
            const errors = error.response?.data?.errors;
            if (errors) {
                Object.entries(errors).forEach(([field, messages]) => {
                    showFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                });
            } else {
                showAlert('coupon-alert', error.response?.data?.message || 'Failed to save coupon.');
            }
        } finally {
            submitButton.disabled = false;
        }
    }

    function clearFieldErrors() {
        document.querySelectorAll('#coupon-form .form-error').forEach((element) => {
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

        showAlert('coupon-alert', message);
    }

    window.deleteCoupon = async function(id) {
        if (!confirm('Delete this coupon?')) {
            return;
        }
        try {
            await window.axios.delete('/api/admin/coupons/' + id);
            showAlert('coupon-success', 'Coupon deleted successfully.');
            loadCoupons();
        } catch (e) {
            showAlert('coupon-alert', e.response?.data?.message || 'Failed to delete coupon.');
        }
    };

    function showAlert(id, message) {
        const alert = $(id);
        const messageElement = $(id + '-message');
        messageElement.textContent = message;
        alert.classList.remove('hidden');
        setTimeout(() => alert.classList.add('hidden'), 4500);
    }

    function fmtDate(value) {
        if (!value) {
            return '—';
        }
        return new Date(value).toLocaleString();
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

    function normalizeDateTimeValue(value, mode) {
        if (!value) {
            return null;
        }

        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return mode === 'end' ? `${value}T23:59` : `${value}T00:00`;
        }

        return value;
    }

    function statusBadge(status) {
        if (status === 'active') {
            return '<span class="badge badge-success">Active</span>';
        }
        if (status === 'expired') {
            return '<span class="badge badge-danger">Expired</span>';
        }

        return '<span class="badge badge-warning">Pending</span>';
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
