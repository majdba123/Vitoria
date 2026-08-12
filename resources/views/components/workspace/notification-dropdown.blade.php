@props([
    'context' => 'admin',
    'route' => null,
    'label' => 'Notifications',
    'strings' => [],
])

<div id="{{ $context }}-notif-wrap" class="relative">
    <button type="button" id="{{ $context }}-notif-btn" class="relative flex h-11 w-11 items-center justify-center text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200" style="border-radius: var(--radius-control)" aria-label="{{ $label }}" title="{{ $label }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
        <span id="{{ $context }}-notif-badge" class="absolute top-1 hidden min-w-[16px] rounded-full bg-warning-500 px-1 text-[10px] font-bold leading-[16px] text-white" style="inset-inline-end: 0.25rem;">0</span>
    </button>

    <div id="{{ $context }}-notif-dropdown" class="dropdown-panel absolute z-50 mt-2 hidden w-[min(420px,95vw)] max-h-[min(32rem,75vh)]" style="inset-inline-end: 0; top: 100%;">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
            <a href="{{ $route ? route($route) : '#' }}" class="text-[13px] font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ $label }}</a>
            <div class="flex items-center gap-3">
                <button type="button" id="{{ $context }}-notif-mark-all" class="text-[11px] font-semibold uppercase tracking-wider text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ $strings['mark_all_read'] ?? __('common.view_all') }}</button>
                <a href="{{ $route ? route($route) : '#' }}" class="text-[11px] font-semibold uppercase tracking-wider text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ $strings['view_all'] ?? __('common.view_all') }}</a>
            </div>
        </div>
        <div id="{{ $context }}-notif-list" class="max-h-[min(24rem,55vh)] overflow-y-auto">
            <p class="px-4 py-10 text-center text-[13px] text-gray-400 dark:text-gray-500">{{ $strings['loading'] ?? __('common.loading') }}</p>
        </div>
        <div id="{{ $context }}-notif-empty" class="hidden px-4 py-12 text-center text-[13px] text-gray-400 dark:text-gray-500">{{ $strings['no_notifications'] ?? __('common.loading') }}</div>
        <div id="{{ $context }}-notif-pagination" class="hidden items-center justify-between gap-2 border-t border-gray-100 bg-gray-50/60 px-3 py-2 dark:border-gray-800 dark:bg-gray-800/30">
            <button type="button" id="{{ $context }}-notif-prev" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 disabled:pointer-events-none disabled:opacity-50" style="border-radius: var(--radius-control)">{{ __('nav.prev') }}</button>
            <span id="{{ $context }}-notif-page-info" class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('nav.page') }} 1 {{ __('nav.of') }} 1</span>
            <button type="button" id="{{ $context }}-notif-next" class="px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 disabled:pointer-events-none disabled:opacity-50" style="border-radius: var(--radius-control)">{{ __('nav.next') }}</button>
        </div>
    </div>
</div>
