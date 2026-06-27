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
        ['group' => __('admin.management'), 'route' => 'admin.coupons.index', 'label' => __('admin.coupons'), 'icon' => 'fa-solid fa-ticket'],
        ['group' => __('admin.management'), 'route' => 'admin.orders.index', 'label' => __('admin.orders'), 'icon' => 'fa-solid fa-bag-shopping'],
        ['group' => 'catalog', 'route' => 'admin.categories.index', 'label' => __('admin.categories'), 'icon' => 'fa-solid fa-layer-group'],
        ['group' => 'catalog', 'route' => 'admin.cities.index', 'label' => 'Cities', 'icon' => 'fa-solid fa-city'],
        ['group' => __('admin.management'), 'route' => 'admin.users.index', 'label' => __('admin.users'), 'icon' => 'fa-solid fa-users'],
        ['group' => __('admin.management'), 'route' => 'admin.notifications.index', 'label' => __('admin.notifications_log'), 'icon' => 'fa-regular fa-bell'],
        ['group' => __('admin.management'), 'route' => 'admin.contact-messages.index', 'label' => __('admin.contact_messages'), 'icon' => 'fa-regular fa-envelope'],
        ['group' => __('admin.management'), 'route' => 'admin.about-us.edit', 'label' => __('admin.about_us'), 'icon' => 'fa-regular fa-circle-info'],
    ];
    $groupedLinks = collect($links)->groupBy('group');
@endphp

<aside id="admin-sidebar" class="dashboard-sidebar fixed inset-y-0 {{ $sidebarEdgeClass }} z-50 flex w-72 flex-col">
    <div class="flex h-[88px] items-center gap-3 border-b border-white/8 px-6">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 text-white">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 shadow-lg shadow-brand-500/20">
                <i class="fa-solid fa-warehouse text-sm"></i>
            </span>
            <span>
                <span class="block font-display text-xl font-extrabold">Vetora</span>
                <span class="mt-1 block text-[11px] font-extrabold uppercase tracking-[0.28em] text-brand-200">{{ __('admin.badge') }}</span>
            </span>
        </a>
        <button onclick="closeSidebar()" class="{{ $closeMarginClass }} rounded-2xl p-2 text-gray-400 hover:bg-white/5 hover:text-white lg:hidden">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="px-6 pt-5">
        <div class="rounded-[24px] border border-white/8 bg-white/5 p-4 text-white/80">
            <p class="text-[11px] font-extrabold uppercase tracking-[0.24em] text-white/45">{{ __('admin.dashboard') }}</p>
            <p class="mt-2 text-sm leading-6 text-white/75">Platform command center for vendors, categories, users, orders, and brand content.</p>
        </div>
    </div>

    <div class="px-6 pt-4">
        <div class="rounded-[24px] border border-brand-400/25 bg-gradient-to-br from-brand-500/20 via-brand-500/10 to-transparent p-4 text-white shadow-lg shadow-brand-900/10">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-500/20 text-brand-100 ring-1 ring-brand-300/20">
                    <i class="fa-solid fa-shapes text-sm"></i>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.24em] text-brand-100/80">Catalog CRUD</p>
                    <p class="mt-2 text-sm leading-6 text-white/80">Manage category records from their own section in the sidebar.</p>
                </div>
            </div>
        </div>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-4 py-5">
        @foreach ($groupedLinks as $group => $items)
            @php
                $isCatalogGroup = $group === 'catalog';
                $groupTitle = $isCatalogGroup ? 'Catalog Management' : $group;
            @endphp
            <div class="mb-6 {{ $isCatalogGroup ? 'rounded-[26px] border border-brand-400/20 bg-white/5 p-3 shadow-inner shadow-brand-950/5' : '' }}">
                <p class="mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[0.24em] {{ $isCatalogGroup ? 'text-brand-200' : 'text-white/35' }}">{{ $groupTitle }}</p>
                @foreach ($items as $item)
                    @php
                        $isActive = str_starts_with($currentRoute, str_replace('.index', '', $item['route']));
                    @endphp
                    <a href="{{ route($item['route']) }}" class="dashboard-sidebar-link {{ $isActive ? 'is-active' : '' }} {{ $isCatalogGroup ? 'border border-transparent hover:border-brand-300/20 hover:bg-brand-400/10' : '' }}">
                        <span class="dashboard-sidebar-bullet h-2.5 w-2.5 rounded-full bg-white/20"></span>
                        <i class="{{ $item['icon'] }} w-4 text-center text-[13px]"></i>
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if ($isActive)
                            <span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-[0.18em] text-brand-200">Live</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="border-t border-white/8 px-6 py-4 text-[11px] text-white/40">
        <p>{{ __('Vetora') }} admin workspace</p>
        <p class="mt-1">{{ date('Y') }}</p>
    </div>
</aside>
