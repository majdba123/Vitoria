@extends('layouts.app')
@section('title', __('stores.page_title') . ' ' . __('site.meta_title_separator') . ' Vetora')

@section('content')
<div class="catalog-page-band">
    <div class="page-shell py-3">
        <nav class="page-breadcrumb" aria-label="{{ __('stores.page_title') }}">
            <a href="{{ route('home') }}" class="hover:text-brand-600">{{ __('nav.home') }}</a>
            <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <span class="page-breadcrumb-current">{{ __('stores.page_title') }}</span>
        </nav>
    </div>
</div>

<div class="page-shell">
    <div class="commerce-section-header">
        <div>
            <p class="commerce-kicker">Vetora Marketplace</p>
            <h1 class="commerce-title mt-1">{{ __('stores.page_title') }}</h1>
            <p class="commerce-copy">{{ __('stores.page_copy') }}</p>
        </div>
    </div>

    <div id="loading" class="grid grid-cols-1 gap-4 lg:grid-cols-2" aria-live="polite">
        <div class="skeleton h-32 rounded-xl"></div><div class="skeleton h-32 rounded-xl"></div><div class="skeleton h-32 rounded-xl"></div><div class="skeleton h-32 rounded-xl"></div>
    </div>
    <div id="grid" class="grid grid-cols-1 gap-4 lg:grid-cols-2"></div>
    <div id="empty" class="empty-state hidden">{{ __('stores.empty') }}</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const strings = {
        fallback: @json(__('stores.store_fallback')),
        visit: @json(__('stores.visit_store')),
        error: @json(__('stores.load_error')),
    };

    function esc(value) {
        if (!value) return '';
        const element = document.createElement('div');
        element.textContent = value;
        return element.innerHTML;
    }

    try {
        const response = await axios.get('/api/vendors');
        const vendors = response.data.data || [];
        document.getElementById('loading').classList.add('hidden');
        if (!vendors.length) {
            document.getElementById('empty').classList.remove('hidden');
            return;
        }

        document.getElementById('grid').innerHTML = vendors.map(vendor => {
            const hasLogo = vendor.logo && vendor.logo !== 'null';
            const initial = vendor.store_name ? vendor.store_name.charAt(0).toUpperCase() : 'V';
            const location = vendor.city?.name || vendor.address || '';
            const categories = Array.isArray(vendor.categories) ? vendor.categories.map(category => category.name).filter(Boolean).slice(0, 2).join(' · ') : '';
            return `
                <a href="/vendors/${vendor.id}" class="vendor-directory-row group">
                    <div class="vendor-mark">${hasLogo ? `<img src="${esc(vendor.logo)}" alt="${esc(vendor.store_name)}" loading="lazy">` : `<span>${esc(initial)}</span>`}</div>
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-bold text-gray-900 dark:text-white">${esc(vendor.store_name)}</h2>
                        ${categories ? `<p class="mt-1 truncate text-xs font-medium text-brand-700 dark:text-brand-300">${esc(categories)}</p>` : ''}
                        ${location ? `<p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">${esc(location)}</p>` : ''}
                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-gray-600 dark:text-gray-300">${esc(vendor.description || strings.fallback)}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 self-end text-sm font-semibold text-brand-700 dark:text-brand-300 sm:self-center">
                        ${esc(strings.visit)}
                        <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </span>
                </a>`;
        }).join('');
    } catch (error) {
        document.getElementById('loading').innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">' + esc(strings.error) + '</p>';
    }
});
</script>
@endpush
