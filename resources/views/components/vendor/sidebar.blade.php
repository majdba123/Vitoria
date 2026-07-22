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
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-700 shadow-lg shadow-emerald-500/20">
                <i class="fa-solid fa-store text-sm"></i>
            </span>
            <span>
                <span class="block font-display text-xl font-extrabold">Vetora</span>
                <span class="mt-1 block text-[11px] font-extrabold uppercase tracking-[0.28em] text-emerald-200">{{ __('vendor.badge') }}</span>
            </span>
        </a>
        <button onclick="closeSidebar()" class="{{ $closeMarginClass }} rounded-2xl p-2 text-gray-400 hover:bg-white/5 hover:text-white lg:hidden">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="px-6 pt-5">
        <div class="rounded-[22px] border border-white/8 bg-white/5 p-4 text-white/80">
            <p class="text-[11px] font-extrabold uppercase tracking-[0.24em] text-white/45">{{ __('vendor.workspace') }}</p>
            <p class="mt-2 text-sm leading-6 text-white/75">{{ __('vendor.workspace_summary') }}</p>
        </div>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-4 py-5">
        @foreach ($groupedLinks as $group => $items)
            <div class="mb-6">
                <p class="mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[0.24em] text-white/35">{{ $group }}</p>
                @foreach ($items as $item)
                    @php
                        $isActive = str_starts_with($currentRoute, str_replace('.index', '', $item['route']));
                    @endphp
                    <a href="{{ route($item['route']) }}" class="dashboard-sidebar-link {{ $isActive ? 'is-active' : '' }}">
                        <span class="dashboard-sidebar-bullet h-2.5 w-2.5 rounded-full bg-white/20"></span>
                        <i class="{{ $item['icon'] }} w-4 text-center text-[13px]"></i>
                        <span class="flex-1">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach

        <div class="mb-6">
            <p class="mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[0.24em] text-white/35">{{ __('vendor.categories') }}</p>
            <div id="sidebar-categories" class="space-y-1">
                <div class="rounded-2xl border border-white/8 bg-white/5 px-3 py-3">
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
