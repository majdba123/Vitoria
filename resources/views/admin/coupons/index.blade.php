@extends('layouts.admin')

@section('title', 'Coupons — SyriaZone Admin')
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
        <div id="table-wrap" class="hidden overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Discount</th>
                        <th class="px-4 py-3">Start</th>
                        <th class="px-4 py-3">End</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div id="empty" class="hidden py-10 text-center text-sm text-gray-400">No coupons found.</div>
        <div class="border-t border-gray-100 px-4 py-3">
            <p id="pagination-info" class="text-xs text-gray-500"></p>
        </div>
    </div>
</div>

<div id="coupon-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900">
        <div class="mb-4 flex items-center justify-between">
            <h3 id="modal-title" class="text-lg font-bold text-gray-900 dark:text-white">Create Coupon</h3>
            <button type="button" onclick="closeCouponModal()" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="coupon-form" class="space-y-4">
            <input type="hidden" id="coupon-id">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label">Code</label>
                    <input id="code" class="form-input" placeholder="SAVE10">
                </div>
                <div>
                    <label class="form-label">Title</label>
                    <input id="title" class="form-input" placeholder="Welcome Discount">
                </div>
                <div>
                    <label class="form-label">Type</label>
                    <select id="discount_type" class="form-input">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Value</label>
                    <input id="discount_value" type="number" step="0.01" class="form-input" placeholder="10">
                </div>
                <div>
                    <label class="form-label">Start Date</label>
                    <input id="starts_at" type="datetime-local" class="form-input">
                </div>
                <div>
                    <label class="form-label">End Date</label>
                    <input id="ends_at" type="datetime-local" class="form-input">
                </div>
                <div>
                    <label class="form-label">Usage Limit</label>
                    <input id="usage_limit" type="number" min="1" class="form-input" placeholder="Optional">
                </div>
                <div class="flex items-end gap-3">
                    <label class="form-label mb-0">Active</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="is_active" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div>
                <label class="form-label">Description</label>
                <textarea id="description" rows="3" class="form-textarea" placeholder="Optional description"></textarea>
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

    $('open-create-modal').addEventListener('click', () => openCouponModal());
    $('btn-apply').addEventListener('click', () => { page = 1; loadCoupons(); });
    $('coupon-form').addEventListener('submit', submitCoupon);

    loadCoupons();

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
            <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                <td class="px-4 py-3 font-mono text-xs font-bold text-gray-700 dark:text-gray-300">${esc(c.code)}</td>
                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">${esc(c.title)}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${c.discount_type === 'percentage' ? c.discount_value + '%' : Number(c.discount_value).toLocaleString() + ' SYP'}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${fmtDate(c.starts_at)}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${fmtDate(c.ends_at)}</td>
                <td class="px-4 py-3">${statusBadge(c.status)}</td>
                <td class="px-4 py-3">
                    <div class="flex justify-end gap-2">
                        <button class="btn-secondary btn-xs js-edit-coupon" data-json="${encodeURIComponent(JSON.stringify(c))}">Edit</button>
                        <button class="btn-danger btn-xs" onclick="deleteCoupon(${c.id})">Delete</button>
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
        $('coupon-modal').classList.remove('hidden');
        $('coupon-modal').classList.add('flex');
    };

    window.closeCouponModal = function() {
        $('coupon-modal').classList.add('hidden');
        $('coupon-modal').classList.remove('flex');
    };

    async function submitCoupon(e) {
        e.preventDefault();
        const id = $('coupon-id').value;
        const payload = {
            code: $('code').value.trim(),
            title: $('title').value.trim(),
            description: $('description').value.trim() || null,
            discount_type: $('discount_type').value,
            discount_value: $('discount_value').value,
            starts_at: $('starts_at').value || null,
            ends_at: $('ends_at').value || null,
            usage_limit: $('usage_limit').value || null,
            is_active: $('is_active').checked,
        };

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
                showAlert('coupon-alert', Object.values(errors).flat().join(', '));
            } else {
                showAlert('coupon-alert', error.response?.data?.message || 'Failed to save coupon.');
            }
        }
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
        const date = new Date(value);
        const pad = n => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
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
