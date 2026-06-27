@extends('layouts.app')
@section('title', __('categories.page_title') . ' ' . __('site.meta_title_separator') . ' ' . __('site.meta_title_suffix'))

@section('content')
<div class="bg-transparent">
    <div class="border-b border-white/40 bg-white/60 backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
        <div class="page-shell py-3">
            <nav class="page-breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                <span class="font-medium text-gray-900 dark:text-white">{{ __('categories.page_heading') }}</span>
            </nav>
        </div>
    </div>

    <div class="page-shell">
        <h1 class="text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">{{ __('categories.page_heading') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('categories.page_subtitle') }}</p>
        <div class="filter-panel mt-5 flex flex-wrap gap-2">
            <button type="button" data-type-filter="" class="category-type-filter rounded-xl border border-brand-500 bg-brand-500 px-4 py-2 text-sm font-bold text-white shadow-sm">الكل</button>
            <button type="button" data-type-filter="agriculture" class="category-type-filter rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">زراعي</button>
            <button type="button" data-type-filter="veterinary" class="category-type-filter rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">بيطري</button>
        </div>

        <div id="loading" class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="skeleton h-48 rounded-2xl"></div><div class="skeleton h-48 rounded-2xl"></div><div class="skeleton h-48 rounded-2xl"></div>
        </div>
        <div id="grid" class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"></div>
    </div>
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

    function esc(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function categoryImageUrl(cat) {
        if (cat.image_url) return cat.image_url;
        if (cat.logo) return '/storage/' + cat.logo;
        if (cat.icon) return '/storage/' + cat.icon;
        return '';
    }

    function typedPageHref(path) {
        const parsed = new URL(path, window.location.origin);
        if (selectedType !== null) {
            parsed.searchParams.set('type', selectedType);
        }
        return parsed.pathname + parsed.search;
    }

    function setActiveTypeButton() {
        document.querySelectorAll('.category-type-filter').forEach(button => {
            const active = button.dataset.typeFilter === selectedType;
            button.className = active
                ? 'category-type-filter rounded-xl border border-brand-500 bg-brand-500 px-4 py-2 text-sm font-bold text-white shadow-sm'
                : 'category-type-filter rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300';
        });
    }

    function categoryThumbInner(cat) {
        const imageUrl = categoryImageUrl(cat);
        if (imageUrl) {
            return `<img src="${esc(imageUrl)}" class="h-full w-full rounded-2xl object-cover" alt="">`;
        }
        return `<svg class="h-8 w-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581"/></svg>`;
    }

    function commissionLine(pct) {
        return (catPageI18n.commission || '').replace(':count', String(pct));
    }

    async function loadCategories() {
        document.getElementById('loading').classList.remove('hidden');
        document.getElementById('grid').innerHTML = '';
        setActiveTypeButton();
        const params = new URLSearchParams({ per_page: '100' });
        if (selectedType) {
            params.set('type', selectedType);
        }
        const nextUrl = new URL(window.location.href);
        if (selectedType) {
            nextUrl.searchParams.set('type', selectedType);
        } else {
            nextUrl.searchParams.delete('type');
        }
        window.history.replaceState({}, '', nextUrl.pathname + nextUrl.search);
        const res = await axios.get('/api/categories?' + params.toString());
        const cats = res.data.data || [];
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('grid').innerHTML = cats.map(cat => `
            <div class="cat-card overflow-hidden rounded-2xl border border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900">
                <a href="${typedPageHref('/categories/' + cat.id)}" class="flex h-full items-center gap-4 p-5 focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 ring-1 ring-brand-200/50 dark:from-brand-500/10 dark:to-brand-500/5 dark:ring-brand-500/20">
                        ${categoryThumbInner(cat)}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">${esc(cat.name)}</h2>
                        <p class="mt-0.5 text-xs text-gray-400">${esc(commissionLine(cat.commission))}</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            </div>`).join('');
    }

    document.querySelectorAll('.category-type-filter').forEach(button => {
        button.addEventListener('click', async () => {
            selectedType = button.dataset.typeFilter || '';
            await loadCategories();
        });
    });

    try {
        await loadCategories();
    } catch (e) {
        document.getElementById('loading').innerHTML = '<p class="text-sm text-gray-400">' + esc(catPageI18n.loadErr || '') + '</p>';
    }
});
</script>
@endpush
