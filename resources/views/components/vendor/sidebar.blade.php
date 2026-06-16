@php
    $currentRoute = request()->route()?->getName() ?? '';
    $links = [
        ['group' => 'Overview', 'route' => 'vendor.dashboard', 'label' => 'Dashboard', 'icon' => 'fa-solid fa-grid-2'],
        ['group' => 'My Store', 'route' => 'vendor.products.index', 'label' => 'Products', 'icon' => 'fa-solid fa-box-open'],
        ['group' => 'My Store', 'route' => 'vendor.discounts.index', 'label' => 'Discounts', 'icon' => 'fa-solid fa-badge-percent'],
        ['group' => 'My Store', 'route' => 'vendor.orders.index', 'label' => 'Orders', 'icon' => 'fa-solid fa-bag-shopping'],
        ['group' => 'My Store', 'route' => 'vendor.commission', 'label' => 'Commission', 'icon' => 'fa-solid fa-chart-line'],
        ['group' => 'My Store', 'route' => 'vendor.notifications.index', 'label' => 'Notifications', 'icon' => 'fa-regular fa-bell'],
        ['group' => 'Account', 'route' => 'vendor.profile', 'label' => 'Profile', 'icon' => 'fa-regular fa-user'],
    ];
    $groupedLinks = collect($links)->groupBy('group');
@endphp

<aside id="vendor-sidebar" class="dashboard-sidebar fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col lg:translate-x-0">
    <div class="flex h-[88px] items-center gap-3 border-b border-white/8 px-6">
        <a href="{{ route('vendor.dashboard') }}" class="flex items-center gap-3 text-white">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-700 shadow-lg shadow-emerald-500/20">
                <i class="fa-solid fa-store text-sm"></i>
            </span>
            <span>
                <span class="block font-display text-xl font-extrabold">Vetora</span>
                <span class="mt-1 block text-[11px] font-extrabold uppercase tracking-[0.28em] text-emerald-200">Vendor</span>
            </span>
        </a>
        <button onclick="closeSidebar()" class="ml-auto rounded-2xl p-2 text-gray-400 hover:bg-white/5 hover:text-white lg:hidden">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="px-6 pt-5">
        <div class="rounded-[24px] border border-white/8 bg-white/5 p-4 text-white/80">
            <p class="text-[11px] font-extrabold uppercase tracking-[0.24em] text-white/45">Store cockpit</p>
            <p class="mt-2 text-sm leading-6 text-white/75">Everything you need to manage products, monitor sales, and respond to customers without leaving the workspace.</p>
        </div>
    </div>

    <nav class="hide-scrollbar flex-1 overflow-y-auto px-4 py-5">
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
            <p class="mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[0.24em] text-white/35">Categories</p>
            <div id="sidebar-categories" class="space-y-1">
                <div class="rounded-2xl border border-white/8 bg-white/5 px-3 py-3">
                    <div class="h-4 w-24 animate-pulse rounded bg-white/10"></div>
                </div>
            </div>
        </div>
    </nav>

    <div class="border-t border-white/8 px-6 py-4 text-[11px] text-white/40">
        <p>Seller workspace</p>
        <p class="mt-1">{{ date('Y') }}</p>
    </div>
</aside>
