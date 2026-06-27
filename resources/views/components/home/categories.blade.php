{{-- ═══ Categories Section — Full grid ═══ --}}
<section id="categories" class="scroll-mt-20 bg-transparent py-14 sm:py-20">
    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-10 text-center">
            <span class="inline-block rounded-full bg-brand-50 px-4 py-1.5 text-[11px] font-bold uppercase tracking-widest text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ __('home.badge_start_here') }}</span>
            <h2 class="mt-4 text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">{{ __('home.choose_category_title') }}</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('home.choose_category_subtitle') }}</p>
        </div>

        {{-- Skeleton --}}
        <div id="cats-loading" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <div class="skeleton h-56 rounded-2xl"></div><div class="skeleton h-56 rounded-2xl"></div><div class="skeleton h-56 rounded-2xl"></div><div class="skeleton h-56 rounded-2xl"></div>
        </div>

        {{-- Categories Grid --}}
        <div id="cats-grid-gate" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"></div>
    </div>
</section>
