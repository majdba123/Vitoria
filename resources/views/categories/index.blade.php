@extends('layouts.app')
@section('title', __('categories.page_title') . ' ' . __('site.meta_title_separator') . ' ' . __('site.meta_title_suffix'))

@section('content')
<div class="catalog-page-band">
    <div class="page-shell py-3">
        <nav class="page-breadcrumb" aria-label="{{ __('categories.page_heading') }}">
            <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
            <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            <span class="page-breadcrumb-current">{{ __('categories.page_heading') }}</span>
        </nav>
    </div>
</div>

<div class="page-shell">
    <div class="commerce-section-header">
        <div>
            <p class="commerce-kicker">Vetora Marketplace</p>
            <h1 class="commerce-title mt-1">{{ __('categories.page_heading') }}</h1>
            <p class="commerce-copy">{{ __('categories.page_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap gap-2" aria-label="{{ __('nav.all_types') }}">
            <button type="button" data-type-filter="" class="category-type-filter btn-secondary btn-sm">{{ __('nav.all_types') }}</button>
            <button type="button" data-type-filter="agriculture" class="category-type-filter btn-secondary btn-sm">{{ __('home.type_agriculture_short') }}</button>
            <button type="button" data-type-filter="veterinary" class="category-type-filter btn-secondary btn-sm">{{ __('home.type_veterinary_short') }}</button>
        </div>
    </div>

    <div id="loading" class="grid grid-cols-1 gap-4 lg:grid-cols-2" aria-live="polite">
        <div class="skeleton h-40 rounded-xl"></div><div class="skeleton h-40 rounded-xl"></div><div class="skeleton h-40 rounded-xl"></div><div class="skeleton h-40 rounded-xl"></div>
    </div>
    <div id="grid" class="grid grid-cols-1 gap-4 lg:grid-cols-2"></div>
</div>
@endsection

@push('scripts')
@php
    $categoriesIndexScriptI18n = [
        'commission' => __('categories.commission_line'),
        'loadErr' => __('categories.load_error'),
    ];
@endphp
<script>
const catPageI18n = @json($categoriesIndexScriptI18n);
document.addEventListener('DOMContentLoaded', async function() {
    const initialType = new URLSearchParams(window.location.search).get('type');
    let selectedType = initialType !== null
        ? initialType
        : @json(auth()->user()?->preferred_product_type ?? session('preferred_product_type', request()->cookie('preferred_product_type', '')));

    function esc(value) {
        if (!value) return '';
        const element = document.createElement('div');
        element.textContent = value;
        return element.innerHTML;
    }

    function categoryImageUrl(category) {
        if (category.image_url) return category.image_url;
        if (category.logo) return '/storage/' + category.logo;
        if (category.icon) return '/storage/' + category.icon;
        return '';
    }

    function typedPageHref(path) {
        const parsed = new URL(path, window.location.origin);
        if (selectedType !== null && selectedType !== '') parsed.searchParams.set('type', selectedType);
        return parsed.pathname + parsed.search;
    }

    function setActiveTypeButton() {
        document.querySelectorAll('.category-type-filter').forEach(button => {
            const active = button.dataset.typeFilter === selectedType;
            button.className = active ? 'category-type-filter btn-primary btn-sm' : 'category-type-filter btn-secondary btn-sm';
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function categoryMedia(category) {
        const imageUrl = categoryImageUrl(category);
        if (imageUrl) return `<img src="${esc(imageUrl)}" alt="${esc(category.name)}" loading="lazy">`;
        return `<div class="flex h-full items-center justify-center text-brand-500"><svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581"/></svg></div>`;
    }

    function commissionLine(percentage) {
        return (catPageI18n.commission || '').replace(':count', String(percentage));
    }

    async function loadCategories() {
        document.getElementById('loading').classList.remove('hidden');
        document.getElementById('grid').innerHTML = '';
        setActiveTypeButton();

        const query = new URLSearchParams({ per_page: '100' });
        if (selectedType) query.set('type', selectedType);

        const nextUrl = new URL(window.location.href);
        selectedType ? nextUrl.searchParams.set('type', selectedType) : nextUrl.searchParams.delete('type');
        window.history.replaceState({}, '', nextUrl.pathname + nextUrl.search);

        const response = await axios.get('/api/categories?' + query.toString());
        const categories = response.data.data || [];
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('grid').innerHTML = categories.map(category => `
            <article class="category-directory-card">
                <div class="category-directory-media">${categoryMedia(category)}</div>
                <a href="${typedPageHref('/categories/' + category.id)}" class="flex min-w-0 items-center gap-4 p-5 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">${esc(category.name)}</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${esc(commissionLine(category.commission))}</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-400 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            </article>`).join('');
    }

    document.querySelectorAll('.category-type-filter').forEach(button => {
        button.addEventListener('click', async () => {
            selectedType = button.dataset.typeFilter || '';
            await loadCategories();
        });
    });

    try {
        await loadCategories();
    } catch (error) {
        document.getElementById('loading').innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">' + esc(catPageI18n.loadErr || '') + '</p>';
    }
});
</script>
@endpush
