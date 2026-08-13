@php
    $currentRoute = request()->route()?->getName() ?? '';
    $isRtl = app()->getLocale() === 'ar';
    $sidebarEdgeClass = $isRtl ? 'right-0 translate-x-full lg:translate-x-0' : 'left-0 -translate-x-full lg:translate-x-0';
    $closeMarginClass = $isRtl ? 'mr-auto' : 'ml-auto';
    $links = [
        ['group' => __('admin.overview'), 'route' => 'admin.dashboard', 'label' => __('admin.dashboard'), 'icon' => 'fa-solid fa-grip'],
        ['group' => __('admin.management'), 'route' => 'admin.vendors.index', 'label' => __('admin.vendors'), 'icon' => 'fa-solid fa-store'],
        ['group' => __('admin.management'), 'route' => 'admin.syndicates.index', 'label' => __('admin.syndicates'), 'icon' => 'fa-solid fa-user-group'],
        ['group' => __('admin.management'), 'route' => 'admin.products.index', 'label' => __('admin.products'), 'icon' => 'fa-solid fa-box-open'],
        ['group' => __('admin.management'), 'route' => 'admin.employees.index', 'label' => __('admin.employees'), 'icon' => 'fa-solid fa-user-gear'],
        ['group' => __('admin.management'), 'route' => 'admin.coupons.index', 'label' => __('admin.coupons'), 'icon' => 'fa-solid fa-ticket'],
        ['group' => __('admin.management'), 'route' => 'admin.orders.index', 'label' => __('admin.orders'), 'icon' => 'fa-solid fa-bag-shopping'],
        ['group' => __('admin.catalog'), 'route' => 'admin.categories.index', 'label' => __('admin.categories'), 'icon' => 'fa-solid fa-layer-group'],
        ['group' => __('admin.catalog'), 'route' => 'admin.subcategories.index', 'label' => __('admin.subcategories'), 'icon' => 'fa-solid fa-diagram-project'],
        ['group' => __('admin.catalog'), 'route' => 'admin.cities.index', 'label' => __('admin.cities'), 'icon' => 'fa-solid fa-city'],
        ['group' => __('admin.management'), 'route' => 'admin.users.index', 'label' => __('admin.users'), 'icon' => 'fa-solid fa-users'],
        ['group' => __('admin.management'), 'route' => 'admin.notifications.index', 'label' => __('admin.notifications_log'), 'icon' => 'fa-regular fa-bell'],
        ['group' => __('admin.management'), 'route' => 'admin.contact-messages.index', 'label' => __('admin.contact_messages'), 'icon' => 'fa-regular fa-envelope'],
        ['group' => __('admin.management'), 'route' => 'admin.about-us.edit', 'label' => __('admin.about_us'), 'icon' => 'fa-regular fa-circle-info'],
        ['group' => __('admin.management'), 'route' => 'admin.pages.index', 'label' => __('admin.pages'), 'icon' => 'fa-regular fa-file-lines'],
        ['group' => __('admin.management'), 'route' => 'admin.banners.index', 'label' => __('admin.banners'), 'icon' => 'fa-regular fa-image'],
    ];
    $groupedLinks = collect($links)->groupBy('group');
@endphp

<aside id="admin-sidebar" class="dashboard-sidebar fixed inset-y-0 {{ $sidebarEdgeClass }} z-50 flex w-72 flex-col">
    <div class="flex h-[88px] items-center gap-3 border-b border-white/8 px-6">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 text-white">
            <span class="flex h-9 w-9 items-center justify-center border border-brand-300/30 bg-brand-500/15 text-brand-200">
                <i class="fa-solid fa-warehouse text-xs"></i>
            </span>
            <span>
                <span class="block font-display text-xl font-extrabold">Vetora</span>
                <span class="mt-1 block text-[11px] font-extrabold uppercase tracking-[0.28em] text-brand-200">{{ __('admin.badge') }}</span>
            </span>
        </a>
        <button onclick="closeSidebar()" class="{{ $closeMarginClass }} p-2 text-gray-400 hover:bg-white/5 hover:text-white lg:hidden" aria-label="{{ __('common.close') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
        @foreach ($groupedLinks as $group => $items)
            @php
                $isCatalogGroup = $group === __('admin.catalog');
            @endphp
            <div class="mb-6">
                <p class="mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[0.24em] {{ $isCatalogGroup ? 'text-brand-200' : 'text-white/35' }}">{{ $group }}</p>
                @foreach ($items as $item)
                    @php
                        $isActive = str_starts_with($currentRoute, str_replace('.index', '', $item['route']));
                    @endphp
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="dashboard-sidebar-link {{ $isActive ? 'is-active' : '' }}" @if ($isActive) aria-current="page" @endif>
                        <i class="{{ $item['icon'] }} w-4 text-center text-[13px]" aria-hidden="true"></i>
                        <span class="flex-1">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="border-t border-white/8 px-6 py-4 text-[11px] text-white/40">
        <p>{{ __('admin.workspace_footer') }}</p>
        <p class="mt-1">{{ date('Y') }}</p>
    </div>
</aside>
