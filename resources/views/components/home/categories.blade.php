{{-- ═══ Categories Section — Full grid ═══ --}}
<section id="categories" class="storefront-section scroll-mt-24 bg-transparent">
    <div class="page-shell py-0">
        <div class="mb-8 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <span class="eyebrow">{{ __('home.badge_start_here') }}</span>
                <h2 class="mt-4 text-3xl font-black tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ __('home.choose_category_title') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-gray-500 dark:text-gray-400">{{ __('home.choose_category_subtitle') }}</p>
            </div>
        </div>

        {{-- Skeleton --}}
        <div id="cats-loading" class="grid grid-cols-1 gap-4 min-[520px]:grid-cols-2 xl:grid-cols-4">
            <div class="skeleton h-64 rounded-[28px]"></div><div class="skeleton h-64 rounded-[28px]"></div><div class="skeleton h-64 rounded-[28px]"></div><div class="skeleton h-64 rounded-[28px]"></div>
        </div>

        {{-- Categories Grid --}}
        <div id="cats-grid-gate" class="grid grid-cols-1 gap-4 min-[520px]:grid-cols-2 xl:grid-cols-4"></div>
    </div>
</section>
