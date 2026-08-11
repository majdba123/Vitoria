@php
    $currentRoute = request()->route()?->getName() ?? '';
    $isRtl = app()->getLocale() === 'ar';
    $sidebarEdgeClass = $isRtl ? 'right-0 translate-x-full lg:translate-x-0' : 'left-0 -translate-x-full lg:translate-x-0';
    $closeMarginClass = $isRtl ? 'mr-auto' : 'ml-auto';
    $currentStatus = (string) request('status', '');
    $links = [
        ['group' => __('nav.dashboard'), 'route' => 'employee.dashboard', 'label' => __('nav.dashboard'), 'icon' => 'fa-solid fa-grip'],
        ['group' => __('employee.workspace'), 'route' => 'employee.products.index', 'label' => __('employee.active_products_tab'), 'icon' => 'fa-solid fa-circle-check', 'params' => ['status' => 'approved']],
        ['group' => __('employee.workspace'), 'route' => 'employee.products.index', 'label' => __('employee.pending_products'), 'icon' => 'fa-solid fa-hourglass-half', 'params' => ['status' => 'pending']],
        ['group' => __('employee.workspace'), 'route' => 'employee.products.index', 'label' => __('employee.rejected_products'), 'icon' => 'fa-solid fa-circle-xmark', 'params' => ['status' => 'rejected']],
    ];
    $groupedLinks = collect($links)->groupBy('group');
@endphp

<aside id="employee-sidebar" class="dashboard-sidebar fixed inset-y-0 {{ $sidebarEdgeClass }} z-50 flex w-72 flex-col">
    <div class="flex h-[88px] items-center gap-3 border-b border-white/8 px-6">
        <a href="{{ route('employee.dashboard') }}" class="flex items-center gap-3 text-white">
            <span class="flex h-9 w-9 items-center justify-center border border-brand-300/30 bg-brand-500/15 text-brand-200">
                <i class="fa-solid fa-clipboard-check text-xs"></i>
            </span>
            <span>
                <span class="block font-display text-xl font-extrabold">Vetora</span>
                <span class="mt-1 block text-[11px] font-extrabold uppercase tracking-[0.28em] text-brand-200">{{ __('employee.workspace') }}</span>
            </span>
        </a>
        <button onclick="closeSidebar()" class="{{ $closeMarginClass }} p-2 text-gray-400 hover:bg-white/5 hover:text-white lg:hidden" aria-label="{{ __('common.close') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
        @foreach ($groupedLinks as $group => $items)
            <div class="mb-6">
                <p class="mb-2 px-3 text-[10px] font-extrabold uppercase tracking-[0.24em] text-white/35">{{ $group }}</p>
                @foreach ($items as $item)
                    @php
                        $itemStatus = $item['params']['status'] ?? null;
                        $isActive = $currentRoute === $item['route']
                            && (
                                ($itemStatus === null && $currentStatus === '')
                                || ($itemStatus !== null && $currentStatus === $itemStatus)
                            );
                    @endphp
                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="dashboard-sidebar-link {{ $isActive ? 'is-active' : '' }}">
                        <span class="dashboard-sidebar-bullet h-2.5 w-2.5 rounded-full bg-white/20"></span>
                        <i class="{{ $item['icon'] }} w-4 text-center text-[13px]"></i>
                        <span class="flex-1">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="border-t border-white/8 px-6 py-4 text-[11px] text-white/40">
        <p>{{ __('Vetora') }} employee workspace</p>
        <p class="mt-1">{{ date('Y') }}</p>
    </div>
</aside>
