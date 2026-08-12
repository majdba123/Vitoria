@php
    $currentRoute = request()->route()?->getName() ?? '';
    $isRtl = app()->getLocale() === 'ar';
    $sidebarEdgeClass = $isRtl ? 'right-0 translate-x-full lg:translate-x-0' : 'left-0 -translate-x-full lg:translate-x-0';
    $closeMarginClass = $isRtl ? 'mr-auto' : 'ml-auto';
    $links = [
        ['group' => __('vendor.group_overview'), 'route' => 'vendor.dashboard', 'label' => __('vendor.dashboard'), 'icon' => 'fa-solid fa-grid-2'],
        ['group' => __('vendor.group_store'), 'route' => 'vendor.products.index', 'label' => __('vendor.products'), 'icon' => 'fa-solid fa-box-open'],
        ['group' => __('vendor.group_store'), 'route' => 'vendor.discounts.index', 'label' => __('vendor.discounts'), 'icon' => 'fa-solid fa-badge-percent'],
        ['group' => __('vendor.group_store'), 'route' => 'vendor.orders.index', 'label' => __('vendor.orders'), 'icon' => 'fa-solid fa-bag-shopping'],
        ['group' => __('vendor.group_store'), 'route' => 'vendor.commission', 'label' => __('vendor.commission'), 'icon' => 'fa-solid fa-chart-line'],
        ['group' => __('vendor.group_store'), 'route' => 'vendor.notifications.index', 'label' => __('vendor.notifications'), 'icon' => 'fa-regular fa-bell'],
        ['group' => __('vendor.group_account'), 'route' => 'vendor.profile', 'label' => __('common.profile'), 'icon' => 'fa-regular fa-user'],
    ];
    $groupedLinks = collect($links)->groupBy('group');
@endphp

<aside id="vendor-sidebar" class="dashboard-sidebar fixed inset-y-0 {{ $sidebarEdgeClass }} z-50 flex w-72 flex-col">
    <div class="flex h-[88px] items-center gap-3 border-b border-white/8 px-6">
        <a href="{{ route('vendor.dashboard') }}" class="flex items-center gap-3 text-white">
            <span class="flex h-9 w-9 items-center justify-center border border-brand-300/30 bg-brand-500/15 text-brand-200">
                <i class="fa-solid fa-store text-xs"></i>
            </span>
            <span>
                <span class="block font-display text-xl font-extrabold">Vetora</span>
                <span class="mt-1 block text-[11px] font-extrabold uppercase tracking-[0.28em] text-brand-200">{{ __('vendor.badge') }}</span>
            </span>
        </a>
        <button onclick="closeSidebar()" class="{{ $closeMarginClass }} p-2 text-gray-400 hover:bg-white/5 hover:text-white lg:hidden" aria-label="{{ __('common.close') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
        @foreach ($groupedLinks as $group => $items)
            <div class="mb-6">
                <p class="mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[0.24em] text-white/35">{{ $group }}</p>
                @foreach ($items as $item)
                    @php
                        $isActive = str_starts_with($currentRoute, str_replace('.index', '', $item['route']));
                    @endphp
                    <a href="{{ route($item['route']) }}" class="dashboard-sidebar-link {{ $isActive ? 'is-active' : '' }}" @if ($isActive) aria-current="page" @endif>
                        <i class="{{ $item['icon'] }} w-4 text-center text-[13px]" aria-hidden="true"></i>
                        <span class="flex-1">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach

        <div class="mb-6">
            <p class="mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[0.24em] text-white/35">{{ __('vendor.categories') }}</p>
            <div id="sidebar-categories" class="space-y-1" aria-live="polite">
                <div class="border border-white/8 bg-white/5 px-3 py-3">
                    <div class="h-4 w-24 animate-pulse rounded bg-white/10"></div>
                </div>
            </div>
        </div>
    </nav>

    <div class="border-t border-white/8 px-6 py-4 text-[11px] text-white/40">
        <p>{{ __('vendor.workspace_footer') }}</p>
        <p class="mt-1">{{ date('Y') }}</p>
    </div>
</aside>
