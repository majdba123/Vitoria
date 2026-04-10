@extends('layouts.admin')

@section('title', 'Product Details — SyriaZone Admin')
@section('page-title', 'Product Details')

@section('content')
<div class="mx-auto max-w-5xl">
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.products.index') }}" class="hover:text-gray-700">Products</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-gray-900">Details</span>
    </nav>

    <x-alert type="error" id="edit-alert" />
    <x-alert type="success" id="edit-success" />

    <div id="show-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500"></div>
        <p class="mt-3 text-sm text-gray-500">Loading product...</p>
    </div>

    <div id="show-content" class="hidden space-y-5">
        {{-- Product Info Card --}}
        <div class="card overflow-hidden border border-gray-200/70 shadow-sm">
            <div class="card-body border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900" id="product-name">—</h2>
                        <p class="mt-0.5 text-sm text-gray-500">Product Information</p>
                    </div>
                    <div class="flex gap-2">
                        <a id="reviews-link" href="#" class="btn-secondary btn-sm inline-flex items-center gap-1.5">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            Reviews
                        </a>
                        <a id="edit-link" href="#" class="btn-primary btn-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            Edit
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="mb-6 rounded-lg bg-gray-50 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Vendor</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900" id="product-vendor">—</p>
                        </div>
                        <a id="view-vendor-link" href="#" class="btn-secondary btn-xs">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View Vendor
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Price</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900" id="product-price">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Quantity</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900" id="product-quantity">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Active Status</p>
                        <p class="mt-1" id="product-active-status">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Approval Status</p>
                        <div class="mt-1 flex items-center gap-2">
                            <span id="product-approval-status">—</span>
                            <select id="product-status-select" class="form-input text-xs py-1 px-2 hidden">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <button type="button" id="edit-status-btn" class="btn-secondary btn-xs">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                Change
                            </button>
                            <button type="button" id="save-status-btn" class="btn-primary btn-xs hidden">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                Save
                            </button>
                            <button type="button" id="cancel-status-btn" class="btn-secondary btn-xs hidden">
                                Cancel
                            </button>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Created</p>
                        <p class="mt-1 text-sm font-medium text-gray-900" id="product-created">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Category</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" id="product-category">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Subcategory</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" id="product-subcategory">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Commission</p>
                        <p class="mt-1 text-sm font-bold text-emerald-600" id="product-commission">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Discount Status</p>
                        <p class="mt-1" id="product-discount-status">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Discount Value</p>
                        <p class="mt-1 text-sm font-semibold text-red-600" id="product-discount-value">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Discount Start</p>
                        <p class="mt-1 text-sm font-medium text-gray-900" id="product-discount-start">—</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Discount End</p>
                        <p class="mt-1 text-sm font-medium text-gray-900" id="product-discount-end">—</p>
                    </div>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Description</p>
                    <p class="mt-2 text-sm text-gray-700" id="product-description">—</p>
                </div>
            </div>
        </div>

        {{-- Primary Photo Card --}}
        <div class="card overflow-hidden border border-gray-200/70 shadow-sm">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Primary Photo</h3>
                <p class="mt-0.5 text-sm text-gray-500">Main product image</p>
            </div>
            <div class="card-body">
                <div id="primary-photo-container" class="flex justify-center">
                    <p class="text-sm text-gray-400 py-8">No primary photo available.</p>
                </div>
            </div>
        </div>

        {{-- All Photos Card --}}
        <div class="card overflow-hidden border border-gray-200/70 shadow-sm">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">All Product Photos</h3>
                <p class="mt-0.5 text-sm text-gray-500" id="photo-count">0 photos</p>
            </div>
            <div class="card-body">
                <div id="product-photos" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const productId = '{{ $productId }}';

    try {
        const res = await window.axios.get('/api/admin/products/' + productId);
        const p = res.data.data;

        document.getElementById('product-name').textContent = p.name || '—';
        document.getElementById('product-price').textContent = '$' + parseFloat(p.price || 0).toFixed(2);
        document.getElementById('product-quantity').textContent = p.quantity || 0;
        document.getElementById('product-description').textContent = p.description || 'No description provided.';
        document.getElementById('product-created').textContent = p.created_at ? new Date(p.created_at).toLocaleDateString() : '—';
        document.getElementById('product-category').textContent = p.category?.name || 'Unassigned';
        document.getElementById('product-subcategory').textContent = p.subcategory?.name || 'Unassigned';
        document.getElementById('product-commission').textContent = p.category?.commission ? parseFloat(p.category.commission).toFixed(2) + '%' : '—';
        document.getElementById('product-discount-value').textContent = p.discount_percentage ? parseFloat(p.discount_percentage).toFixed(2) + '%' : 'No discount';
        document.getElementById('product-discount-start').textContent = formatDateOnly(p.discount_starts_at);
        document.getElementById('product-discount-end').textContent = formatDateOnly(p.discount_ends_at);
        updateDiscountStatusDisplay(p.discount_status);

        const vendorName = p.vendor?.store_name || '—';
        const ownerName = p.vendor?.user?.name || '';
        document.getElementById('product-vendor').textContent = vendorName + (ownerName ? ' — ' + ownerName : '');

        // Set vendor link if vendor exists
        if (p.vendor?.id) {
            document.getElementById('view-vendor-link').href = '/admin/vendors/' + p.vendor.id;
        } else {
            document.getElementById('view-vendor-link').classList.add('hidden');
        }

        // Active status
        const activeStatusBadge = p.is_active
            ? '<span class="badge badge-success"><span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Active</span>'
            : '<span class="badge badge-danger"><span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>Inactive</span>';
        document.getElementById('product-active-status').innerHTML = activeStatusBadge;

        // Approval status - initialize display
        const approvalStatus = p.status || 'pending';
        updateStatusDisplay(approvalStatus);
        
        // Status update handlers
        document.getElementById('edit-status-btn').addEventListener('click', function() {
            const statusSelect = document.getElementById('product-status-select');
            const statusBadge = document.getElementById('product-approval-status');
            const editBtn = document.getElementById('edit-status-btn');
            const saveBtn = document.getElementById('save-status-btn');
            const cancelBtn = document.getElementById('cancel-status-btn');
            
            statusSelect.value = approvalStatus;
            statusBadge.classList.add('hidden');
            statusSelect.classList.remove('hidden');
            editBtn.classList.add('hidden');
            saveBtn.classList.remove('hidden');
            cancelBtn.classList.remove('hidden');
        });
        
        document.getElementById('cancel-status-btn').addEventListener('click', function() {
            const statusSelect = document.getElementById('product-status-select');
            const statusBadge = document.getElementById('product-approval-status');
            const editBtn = document.getElementById('edit-status-btn');
            const saveBtn = document.getElementById('save-status-btn');
            const cancelBtn = document.getElementById('cancel-status-btn');
            
            statusBadge.classList.remove('hidden');
            statusSelect.classList.add('hidden');
            editBtn.classList.remove('hidden');
            saveBtn.classList.add('hidden');
            cancelBtn.classList.add('hidden');
        });
        
        document.getElementById('save-status-btn').addEventListener('click', async function() {
            const statusSelect = document.getElementById('product-status-select');
            const newStatus = statusSelect.value;
            const btn = this;
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>';
            
            try {
                const res = await window.axios.patch(`/api/admin/products/${productId}/status`, {
                    status: newStatus
                });
                
                // Update display
                updateStatusDisplay(newStatus);
                document.getElementById('cancel-status-btn').click(); // Hide edit controls
                showAlert('edit-success', res.data.message || 'Status updated successfully.');
            } catch (e) {
                console.error('Failed to update status:', e);
                showAlert('edit-alert', e.response?.data?.message || 'Failed to update status.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
        
        function updateStatusDisplay(status) {
            let approvalStatusBadge = '';
            if (status === 'approved') {
                approvalStatusBadge = '<span class="badge badge-success"><span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Approved</span>';
            } else if (status === 'rejected') {
                approvalStatusBadge = '<span class="badge badge-danger"><span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>Rejected</span>';
            } else {
                approvalStatusBadge = '<span class="badge badge-warning"><span class="mr-1 inline-block h-1.5 w-1.5 rounded-full bg-yellow-500"></span>Pending</span>';
            }
            document.getElementById('product-approval-status').innerHTML = approvalStatusBadge;
        }

        function updateDiscountStatusDisplay(status) {
            const el = document.getElementById('product-discount-status');
            if (status === 'active') {
                el.innerHTML = '<span class="badge badge-success">Active</span>';
                return;
            }
            if (status === 'expired') {
                el.innerHTML = '<span class="badge badge-danger">Expired</span>';
                return;
            }
            el.innerHTML = '<span class="badge badge-warning">Pending</span>';
        }
        
        function showAlert(id, msg) {
            const el = document.getElementById(id);
            if (el) {
                const msgEl = document.getElementById(id + '-message');
                if (msgEl) msgEl.textContent = msg;
                el.classList.remove('hidden');
                setTimeout(() => el.classList.add('hidden'), 5000);
            }
        }

        function formatDateOnly(value) {
            if (!value) {
                return '—';
            }

            const normalized = typeof value === 'string' ? value.replace(' ', 'T') : value;
            const date = new Date(normalized);
            if (Number.isNaN(date.getTime())) {
                return String(value).slice(0, 10);
            }

            return date.toLocaleDateString();
        }

        document.getElementById('edit-link').href = '/admin/products/' + productId + '/edit';
        document.getElementById('reviews-link').href = '/admin/products/' + productId + '/reviews';

        const photos = p.photos || [];
        document.getElementById('photo-count').textContent = photos.length + ' photo' + (photos.length !== 1 ? 's' : '');

        // Display primary photo separately
        const primaryPhoto = photos.find(photo => photo.is_primary === true);
        const primaryPhotoContainer = document.getElementById('primary-photo-container');
        if (primaryPhoto) {
            primaryPhotoContainer.innerHTML = `
                <div class="group relative w-full max-w-2xl overflow-hidden rounded-xl border border-brand-200 bg-gradient-to-br from-gray-50 to-gray-100 p-4 ring-1 ring-brand-100">
                    <div class="aspect-[4/3] overflow-hidden rounded-lg bg-white shadow-inner">
                        <img src="${primaryPhoto.url}" class="h-full w-full object-contain transition-transform group-hover:scale-105" alt="Primary product photo">
                    </div>
                    <div class="absolute left-6 top-6 rounded bg-brand-600 px-2 py-1 text-xs font-semibold text-white">Primary Photo</div>
                    <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/0 transition-all group-hover:bg-black/50 group-hover:opacity-100">
                        <button type="button" onclick="viewPhotoLarge('${primaryPhoto.url}')" title="View Large" class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-white opacity-0 shadow-lg transition-all hover:bg-brand-700 group-hover:opacity-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                        </button>
                    </div>
                </div>
            `;
        } else {
            primaryPhotoContainer.innerHTML = '<p class="text-sm text-gray-400 py-8">No primary photo available.</p>';
        }

        // Display all photos (excluding primary if it exists)
        const otherPhotos = photos.filter(photo => photo.is_primary !== true);
        const photosContainer = document.getElementById('product-photos');
        if (otherPhotos.length === 0) {
            photosContainer.innerHTML = '<p class="col-span-full text-center text-sm text-gray-400 py-8">No additional photos available.</p>';
        } else {
            photosContainer.innerHTML = otherPhotos.map(photo => {
                return `<div class="group relative aspect-[4/3] overflow-hidden rounded-lg border border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100 p-2 transition-colors sm:aspect-square">
                    <img src="${photo.url}" class="h-full w-full rounded-md bg-white object-contain transition-transform group-hover:scale-105" alt="">
                    <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/0 transition-all group-hover:bg-black/50 group-hover:opacity-100">
                        <button type="button" onclick="viewPhotoLarge('${photo.url}')" title="View Large" class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-white opacity-0 shadow-lg transition-all hover:bg-brand-700 group-hover:opacity-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                        </button>
                    </div>
                </div>`;
            }).join('');
        }

        window.viewPhotoLarge = function (url) {
            // Remove existing modal if any
            const existingModal = document.getElementById('photo-modal');
            if (existingModal) existingModal.remove();

            const modal = document.createElement('div');
            modal.id = 'photo-modal';
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4';
            modal.innerHTML = `
                <div class="relative max-h-[90vh] max-w-[90vw]">
                    <img src="${url}" class="max-h-[90vh] max-w-[90vw] rounded-lg object-contain shadow-2xl" alt="Product photo">
                    <button type="button" onclick="document.getElementById('photo-modal')?.remove()" class="absolute right-2 top-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-gray-900 shadow-lg backdrop-blur-sm transition-all hover:bg-white hover:scale-110" title="Close">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            `;
            const closeModal = () => modal.remove();
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            // Close on Escape key
            const escapeHandler = (e) => { if (e.key === 'Escape') { closeModal(); document.removeEventListener('keydown', escapeHandler); } };
            document.addEventListener('keydown', escapeHandler);
            document.body.appendChild(modal);
        };

        document.getElementById('show-loading').classList.add('hidden');
        document.getElementById('show-content').classList.remove('hidden');
    } catch (e) {
        document.getElementById('show-loading').innerHTML = '<p class="text-red-500">Failed to load product.</p>';
    }
});
</script>
@endpush

