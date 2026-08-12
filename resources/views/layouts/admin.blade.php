<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
@php
    $isRtl = app()->getLocale() === 'ar';
    $adminMainPaddingClass = $isRtl ? 'lg:pr-72' : 'lg:pl-72';
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('admin.dashboard') . ' - ' . __('Vetora'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @stack('styles')
    <script>
        (function () {
            const theme = localStorage.getItem('sz_theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
@php
    $sessionAuthUser = auth()->check()
        ? (new \App\Http\Resources\Auth\UserResource(auth()->user()->loadMissing('syndicate')))->resolve(request())
        : null;
    $adminStrings = [
        'loading' => __('admin.loading'),
        'no_notifications' => __('admin.no_notifications'),
        'mark_all_read' => __('admin.mark_all_read'),
        'view_all' => __('admin.view_all'),
        'failed' => __('common.failed_notifications'),
        'page' => __('nav.page'),
        'of' => __('nav.of'),
        'mark_one_read' => __('admin.mark_one_read'),
    ];
@endphp
<body
    data-session-auth="{{ auth()->check() ? '1' : '0' }}"
    class="dashboard-body min-h-screen font-sans text-gray-900 antialiased transition-colors duration-300 dark:text-gray-100"
>
    <a href="#main-content" class="skip-link">{{ __('common.skip_to_content') }}</a>

    <div id="admin-app" class="hidden">
        <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-gray-950/60 transition-opacity dark:bg-black/70 lg:hidden" onclick="closeSidebar()"></div>

        <x-admin.sidebar />

        <div class="{{ $adminMainPaddingClass }}">
            <x-workspace.topbar
                context="admin"
                :badge-label="__('admin.badge')"
                :open-sidebar-label="__('admin.open_sidebar')"
                :notifications="true"
                notifications-route="admin.notifications.index"
                :notifications-label="__('admin.notifications')"
                :notif-strings="$adminStrings"
                :avatar="true"
                avatar-fallback="A"
                :avatar-role-label="__('admin.badge')"
                :theme-toggle-label="__('admin.toggle_theme')"
                :sign-out-label="__('admin.sign_out')"
                :login-url="route('login')"
            />

            <main id="main-content" class="workspace-shell py-8">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="admin-loading" class="flex min-h-screen items-center justify-center">
        <div class="text-center">
            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-brand-600 dark:border-gray-700 dark:border-t-brand-300"></div>
            <p class="mt-4 text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('admin.verifying_access') }}</p>
        </div>
    </div>

    <script>
        window.__sessionAuthUser = @json($sessionAuthUser);

        function hydrateAdminDashboard(user) {
            if (!user) {
                return;
            }

            window.Auth?.setUser?.(user);
            document.getElementById('admin-name').textContent = user.name;
            document.getElementById('admin-avatar').textContent = (user.name || 'A').charAt(0).toUpperCase();
            document.getElementById('admin-loading').classList.add('hidden');
            document.getElementById('admin-app').classList.remove('hidden');
            if (window.Auth && window.Auth.applyToken) {
                window.Auth.applyToken();
            }
            document.dispatchEvent(new CustomEvent('admin-ready'));
            VetoraWorkspace.initNotifications('admin', @json($adminStrings));
        }

        document.addEventListener('DOMContentLoaded', async function () {
            VetoraWorkspace.initSidebarToggle({ sidebarId: 'admin-sidebar', hiddenClass: @json($isRtl ? 'translate-x-full' : '-translate-x-full') });

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('logout') === '1') {
                return;
            }

            if (window.__sessionAuthUser && window.Auth?.setUser) {
                window.Auth.setUser(window.__sessionAuthUser);
            }

            if (document.body?.dataset?.sessionAuth === '1' && window.__sessionAuthUser?.type === 1) {
                window.Auth?.clearTokenOnly?.();
                hydrateAdminDashboard(window.__sessionAuthUser);
                return;
            }

            try {
                if (window.Auth?.applyToken) {
                    window.Auth.applyToken();
                }
                const response = await window.axios.get('/api/user');
                const user = response.data.data || response.data;

                if (user.type !== 1) {
                    window.Auth.removeToken();
                    window.location.href = '{{ route("login") }}';
                    return;
                }

                hydrateAdminDashboard(user);
            } catch (error) {
                const sessionUser = window.__sessionAuthUser;
                if (document.body?.dataset?.sessionAuth === '1' && sessionUser?.type === 1) {
                    window.Auth?.clearTokenOnly?.();
                    hydrateAdminDashboard(sessionUser);
                    return;
                }

                if (window.Auth?.clearAll) {
                    window.Auth.clearAll();
                }

                window.location.href = '{{ route("login") }}';
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
