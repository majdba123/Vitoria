@extends('layouts.app')
@section('title', 'Vetora')

@section('content')
<div class="page-shell py-10">
    <nav class="page-breadcrumb mb-6">
        <a href="{{ route('home') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('nav.home') }}</a>
        <svg class="h-3 w-3 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span id="page-bc-title" class="page-breadcrumb-current"></span>
    </nav>

    <div id="page-loading" class="py-16 text-center">
        <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-brand-500 dark:border-gray-700 dark:border-t-brand-400"></div>
    </div>

    <article id="page-content" class="hidden max-w-3xl">
        <h1 id="page-title" class="text-2xl font-bold sm:text-3xl" style="color: var(--color-text);"></h1>
        <div id="page-body" class="prose mt-6 max-w-none" style="color: var(--color-text-secondary);"></div>
    </article>

    <div id="page-not-found" class="hidden py-16 text-center text-sm" style="color: var(--color-text-muted);">
        {{ __('common.not_found') }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const slug = @json($slug);
    const locale = document.documentElement.lang === 'ar' ? 'ar' : 'en';
    const $ = id => document.getElementById(id);

    try {
        const res = await window.axios.get('/api/pages/' + encodeURIComponent(slug));
        const page = res.data.data;
        const title = locale === 'ar' ? page.title_ar : page.title_en;
        const body = locale === 'ar' ? page.content_ar : page.content_en;

        document.title = (page.meta_title || title) + ' - Vetora';
        $('page-bc-title').textContent = title;
        $('page-title').textContent = title;
        $('page-body').textContent = body;
        $('page-loading').classList.add('hidden');
        $('page-content').classList.remove('hidden');
    } catch (e) {
        $('page-loading').classList.add('hidden');
        $('page-not-found').classList.remove('hidden');
    }
});
</script>
@endpush
