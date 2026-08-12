@props([
    'context' => 'admin', // admin | vendor | employee | syndicate
    'badgeLabel' => '',
    'openSidebarLabel' => __('nav.open_sidebar_aria') ?: 'Open sidebar',
    'homeLabel' => __('nav.home'),
    'notifications' => false,
    'notificationsRoute' => null,
    'notificationsLabel' => __('nav.notifications_aria') ?: 'Notifications',
    'notifStrings' => [],
    'avatar' => false,
    'avatarFallback' => 'A',
    'avatarRoleLabel' => '',
    'themeToggleLabel' => __('nav.toggle_theme_aria'),
    'signOutLabel' => __('nav.sign_out'),
    'loginUrl' => route('login'),
])

<header class="dashboard-topbar sticky top-0 z-30">
    <div class="workspace-shell flex h-16 items-center gap-x-4">
        <button type="button" id="sidebar-toggle" class="-m-2.5 flex h-11 w-11 items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200 lg:hidden" style="border-radius: var(--radius-control)" aria-label="{{ $openSidebarLabel }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </button>

        <div class="h-5 w-px bg-gray-200 dark:bg-gray-700 lg:hidden" aria-hidden="true"></div>

        <div class="min-w-0 flex-1">
            <p class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-300">{{ $badgeLabel }}</p>
            <h1 class="mt-0.5 truncate text-lg font-bold text-gray-900 dark:text-white">@yield('page-title', $badgeLabel)</h1>
        </div>

        <div class="flex items-center gap-x-1.5">
            {{ $slot ?? '' }}

            <a href="{{ route('home') }}" class="flex h-11 w-11 items-center justify-center text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200" style="border-radius: var(--radius-control)" aria-label="{{ $homeLabel }}" title="{{ $homeLabel }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            </a>

            <x-language-switcher variant="compact" />

            @if ($notifications)
                <x-workspace.notification-dropdown
                    :context="$context"
                    :route="$notificationsRoute"
                    :label="$notificationsLabel"
                    :strings="$notifStrings"
                />
            @endif

            <button type="button" onclick="VetoraWorkspace.toggleTheme()" class="flex h-11 w-11 items-center justify-center text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200" style="border-radius: var(--radius-control)" aria-label="{{ $themeToggleLabel }}" title="{{ $themeToggleLabel }}">
                <svg class="hidden h-4 w-4 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                <svg class="block h-4 w-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
            </button>

            @if ($avatar)
                <div class="hidden items-center gap-2.5 border border-gray-200 px-2.5 py-1.5 dark:border-gray-700 sm:flex" style="border-radius: var(--radius-control)">
                    <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-brand-600 text-xs font-bold text-white" id="{{ $context }}-avatar">{{ $avatarFallback }}</div>
                    <div class="leading-tight">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-gray-400 dark:text-gray-500">{{ $avatarRoleLabel }}</p>
                        <span id="{{ $context }}-name" class="text-[13px] font-semibold text-gray-700 dark:text-gray-300"></span>
                    </div>
                </div>
            @endif

            <button type="button" onclick="VetoraWorkspace.logout({{ Js::from($loginUrl) }})" class="flex h-11 items-center gap-1.5 px-3 text-sm font-semibold text-gray-500 transition-colors hover:bg-danger-50 hover:text-danger-600 dark:text-gray-400 dark:hover:bg-danger-500/10 dark:hover:text-danger-300" style="border-radius: var(--radius-control)" aria-label="{{ $signOutLabel }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                <span class="hidden sm:inline">{{ $signOutLabel }}</span>
            </button>
        </div>
    </div>
</header>
