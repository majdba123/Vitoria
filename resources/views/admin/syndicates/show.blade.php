@extends('layouts.admin')

@section('title', 'Syndicate Details - Vetora')
@section('page-title', 'Syndicate Details')

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap justify-end gap-2">
        <a id="edit-link" class="btn-primary btn-sm" href="#">Edit</a>
        <a href="{{ route('admin.syndicates.index') }}" class="btn-secondary btn-sm">Back</a>
    </div>

    <div id="details-card" class="card card-body">
        <div class="py-12 text-center text-sm text-gray-400">Loading syndicate details...</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const syndicateId = @json($syndicateId);
    const detailsCard = document.getElementById('details-card');
    const editLink = document.getElementById('edit-link');

    loadSyndicateDetails();

    async function loadSyndicateDetails() {
        try {
            const response = await window.axios.get('/api/admin/syndicates/' + syndicateId, { silent: true });
            const syndicate = response.data.data;
            const userName = syndicate.user?.name || '-';
            const userEmail = syndicate.user?.email || syndicate.email || '-';
            const userPhone = syndicate.user?.phone_number || syndicate.phone || '-';

            editLink.href = '/admin/syndicates/' + syndicate.id + '/edit';

            detailsCard.innerHTML = `
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start">
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-50 text-2xl font-black text-brand-700 ring-1 ring-brand-100">
                        <img src="${escapeHtml(syndicate.logo_url)}" class="h-full w-full object-cover" alt="${escapeHtml(syndicate.name)}" onerror="this.src='{{ asset('images/syndicate-placeholder.svg') }}'">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white">${escapeHtml(syndicate.name)}</h2>
                            <span class="badge ${syndicate.is_active ? 'badge-success' : 'badge-danger'}">${escapeHtml(syndicate.status_label || (syndicate.is_active ? 'Active' : 'Inactive'))}</span>
                            <span class="badge badge-brand">${escapeHtml(syndicate.type_label || syndicate.type)}</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">${escapeHtml(userEmail)} · ${escapeHtml(userPhone)}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    ${metricCard('Categories', Number(syndicate.categories_count || 0))}
                    ${metricCard('Vendors', Number(syndicate.vendors_count || 0))}
                    ${metricCard('Products', Number(syndicate.products_count || 0))}
                    ${metricCard('Completed Orders', Number(syndicate.completed_orders_count || 0))}
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    ${metricCard('Total Sales', formatMoney(syndicate.total_sales))}
                    ${metricCard('All Orders', Number(syndicate.orders_count || 0))}
                    ${metricCard('Created At', formatDate(syndicate.created_at))}
                    ${metricCard('Updated At', formatDate(syndicate.updated_at))}
                </div>

                <div class="mt-6 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h3 class="text-sm font-black text-gray-900 dark:text-white">Linked User Account</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        ${infoBlock('Name', userName)}
                        ${infoBlock('Email', userEmail)}
                        ${infoBlock('Phone', userPhone)}
                    </div>
                </div>
            `;
        } catch (error) {
            const parsed = window.showApiError ? window.showApiError(error) : window.ApiErrors.parse(error);
            detailsCard.innerHTML = `<p class="py-12 text-center text-sm text-red-500">${escapeHtml(parsed.generalMessage || 'Failed to load syndicate details.')}</p>`;
        }
    }

    function formatDate(value) {
        return value
            ? new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
            : '-';
    }

    function formatMoney(value) {
        return `${Number(value || 0).toLocaleString('en-US')} SYP`;
    }

    function metricCard(label, value) {
        return `
            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">${escapeHtml(label)}</p>
                <p class="mt-2 text-lg font-black text-gray-900 dark:text-white">${escapeHtml(value)}</p>
            </div>
        `;
    }

    function infoBlock(label, value) {
        return `
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">${escapeHtml(label)}</p>
                <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">${escapeHtml(value)}</p>
            </div>
        `;
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }
});
</script>
@endpush
