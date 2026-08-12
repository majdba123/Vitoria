@php
    $isRtl = app()->getLocale() === 'ar';
    $mainPaddingClass = $isRtl ? 'lg:pr-72' : 'lg:pl-72';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('employee.workspace') . ' - Vetora')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        ? (new \App\Http\Resources\Auth\UserResource(auth()->user()->loadMissing(['syndicate', 'city'])))->resolve(request())
        : null;
@endphp
<body
    data-session-auth="{{ auth()->check() ? '1' : '0' }}"
    class="dashboard-body min-h-screen font-sans text-gray-900 antialiased transition-colors duration-300 dark:text-gray-100"
>
    <a href="#main-content" class="skip-link">{{ __('common.skip_to_content') }}</a>

    <div id="employee-app" class="hidden">
        <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-gray-950/60 transition-opacity dark:bg-black/70 lg:hidden" onclick="closeSidebar()"></div>

        <x-employee.sidebar />

        <div class="{{ $mainPaddingClass }}">
            <x-workspace.topbar
                context="employee"
                :badge-label="__('employee.workspace_label')"
                :open-sidebar-label="__('employee.open_sidebar')"
                :notifications="false"
                :avatar="false"
                :theme-toggle-label="__('nav.toggle_theme_aria')"
                :sign-out-label="__('nav.sign_out')"
                :login-url="route('login')"
            />

            <main id="main-content" class="workspace-shell py-8">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="employee-loading" class="flex min-h-screen items-center justify-center">
        <div class="text-center">
            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-brand-600 dark:border-gray-700 dark:border-t-brand-300"></div>
            <p class="mt-4 text-sm font-semibold text-gray-500 dark:text-gray-400">{{ __('common.loading') }}</p>
        </div>
    </div>

    <script>
        window.__sessionAuthUser = @json($sessionAuthUser);

        function hydrateEmployeeDashboard(user) {
            if (!user) {
                return;
            }

            window.Auth?.setUser?.(user);
            document.getElementById('employee-loading').classList.add('hidden');
            document.getElementById('employee-app').classList.remove('hidden');
            document.dispatchEvent(new CustomEvent('employee-ready'));
        }

        document.addEventListener('DOMContentLoaded', async function () {
            VetoraWorkspace.initSidebarToggle({ sidebarId: 'employee-sidebar', hiddenClass: @json($isRtl ? 'translate-x-full' : '-translate-x-full') });

            if (window.__sessionAuthUser && window.Auth?.setUser) {
                window.Auth.setUser(window.__sessionAuthUser);
            }

            if (document.body?.dataset?.sessionAuth === '1' && window.__sessionAuthUser?.type === 4) {
                window.Auth?.clearTokenOnly?.();
                hydrateEmployeeDashboard(window.__sessionAuthUser);
                return;
            }

            try {
                if (window.Auth?.applyToken) {
                    window.Auth.applyToken();
                }
                const response = await window.axios.get('/api/user');
                const user = response.data.data || response.data;
                if (user.type !== 4) {
                    window.Auth?.clearAll?.();
                    window.location.href = '{{ route("login") }}';
                    return;
                }

                hydrateEmployeeDashboard(user);
            } catch (error) {
                const sessionUser = window.__sessionAuthUser;
                if (document.body?.dataset?.sessionAuth === '1' && sessionUser?.type === 4) {
                    window.Auth?.clearTokenOnly?.();
                    hydrateEmployeeDashboard(sessionUser);
                    return;
                }

                window.Auth?.clearAll?.();
                window.location.href = '{{ route("login") }}';
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
