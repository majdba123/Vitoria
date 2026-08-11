{{-- ═══ Categories Section — Full grid ═══ --}}
<section id="categories" class="storefront-section scroll-mt-24 bg-transparent">
    <div class="page-shell py-0">
        <div class="commerce-section-header">
            <div class="min-w-0">
                <p class="commerce-kicker">{{ __('home.badge_start_here') }}</p>
                <h2 class="commerce-title">{{ __('home.choose_category_title') }}</h2>
                <p class="commerce-copy">{{ __('home.choose_category_subtitle') }}</p>
            </div>
        </div>

        {{-- Skeleton --}}
        <div id="cats-loading" class="grid grid-cols-1 gap-4 min-[520px]:grid-cols-2 xl:grid-cols-4">
            <div class="skeleton h-64"></div><div class="skeleton h-64"></div><div class="skeleton h-64"></div><div class="skeleton h-64"></div>
        </div>

        {{-- Categories Grid --}}
        <div id="cats-grid-gate" class="grid grid-cols-1 gap-4 min-[520px]:grid-cols-2 xl:grid-cols-4"></div>
    </div>
</section>
