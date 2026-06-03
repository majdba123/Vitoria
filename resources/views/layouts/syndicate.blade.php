@php
    $isRtl = app()->getLocale() === 'ar';
    $sidebarEdgeClass = $isRtl ? 'right-0' : 'left-0';
    $sidebarHiddenClass = $isRtl ? 'translate-x-full' : '-translate-x-full';
    $mainPaddingClass = $isRtl ? 'lg:pr-72' : 'lg:pl-72';
    $closeMarginClass = $isRtl ? 'mr-auto' : 'ml-auto';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() === 'ar' ? 'ar' : 'en' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة النقابة - Vetora')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <div id="syndicate-app" class="hidden">
        <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-gray-900/60 backdrop-blur-sm lg:hidden" onclick="closeSidebar()"></div>
        <aside id="syndicate-sidebar" class="fixed inset-y-0 {{ $sidebarEdgeClass }} z-50 flex w-72 {{ $sidebarHiddenClass }} flex-col bg-gray-900 transition-transform duration-300 lg:translate-x-0">
            <div class="flex h-14 items-center gap-3 border-b border-white/10 px-6">
                <a href="{{ route('syndicate.dashboard') }}" class="text-xl font-bold text-white">Vetora</a>
                <span class="rounded-md bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400">النقابة</span>
                <button onclick="closeSidebar()" class="{{ $closeMarginClass }} rounded-md p-1 text-gray-400 hover:text-white lg:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @php($currentRoute = request()->route()?->getName() ?? '')
            @php($links = [
                ['route' => 'syndicate.dashboard', 'label' => 'نظرة عامة'],
                ['route' => 'syndicate.categories', 'label' => 'التصنيفات'],
                ['route' => 'syndicate.vendors', 'label' => 'التجار'],
                ['route' => 'syndicate.products', 'label' => 'المنتجات'],
                ['route' => 'syndicate.podcasts', 'label' => 'البودكاست'],
                ['route' => 'syndicate.orders', 'label' => 'الطلبات'],
                ['route' => 'syndicate.sales', 'label' => 'المبيعات'],
                ['route' => 'syndicate.reports', 'label' => 'التقارير'],
            ])
            <nav class="flex-1 overflow-y-auto px-4 py-5">
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-500">مساحة العمل</p>
                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}" class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all {{ $currentRoute === $link['route'] ? 'bg-white/10 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' }}">
                        <span class="h-2 w-2 rounded-full {{ $currentRoute === $link['route'] ? 'bg-emerald-400' : 'bg-gray-600' }}"></span>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
            <div class="border-t border-white/10 px-6 py-3">
                <p class="text-[11px] text-gray-500">&copy; {{ date('Y') }} Vetora</p>
            </div>
        </aside>
        <div class="{{ $mainPaddingClass }}">
            <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
                <div class="flex h-14 items-center gap-4 px-4 sm:px-6 lg:px-8">
                    <button type="button" id="sidebar-toggle" class="-m-2.5 p-2.5 text-gray-500 lg:hidden">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                    <h1 class="flex-1 text-base font-semibold text-gray-900 dark:text-white">@yield('page-title', 'النقابة')</h1>
                    <span id="syndicate-type-badge" class="badge badge-brand"></span>
                    <button onclick="syndicateLogout()" class="btn-secondary btn-sm">تسجيل الخروج</button>
                </div>
            </header>
            <main class="px-4 py-6 sm:px-6 lg:px-8">@yield('content')</main>
        </div>
    </div>
    <div id="syndicate-loading" class="flex min-h-screen items-center justify-center">
        <div class="text-center"><div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-emerald-500"></div><p class="mt-4 text-sm text-gray-500">جارٍ التحقق من صلاحية النقابة...</p></div>
    </div>
    <script>
        const syndicateHiddenClass = @json($sidebarHiddenClass);
        function deleteCookie(name) { document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/'; }
        function syndicateTypeLabel(type) {
            if (type === 'agriculture') return 'زراعي';
            if (type === 'veterinary') return 'بيطري';
            return 'نقابة';
        }
        window.syndicateLogout = async function() {
            try { await window.axios.post('/api/auth/logout'); } catch (e) {}
            if (window.Auth?.clearAll) window.Auth.clearAll(); else localStorage.clear();
            deleteCookie('XSRF-TOKEN'); deleteCookie('laravel_session');
            window.location.replace('{{ route("login") }}?logout=1');
        };
        document.addEventListener('DOMContentLoaded', async function () {
            if (!window.Auth || !window.Auth.isAuthenticated()) { window.location.href = '{{ route("login") }}'; return; }
            try {
                const response = await window.axios.get('/api/user');
                const user = response.data.data || response.data;
                if (user.type !== 3) { window.Auth.removeToken(); window.location.href = '{{ route("login") }}'; return; }
                document.getElementById('syndicate-type-badge').textContent = syndicateTypeLabel(user.syndicate?.type);
                document.getElementById('syndicate-loading').classList.add('hidden');
                document.getElementById('syndicate-app').classList.remove('hidden');
                document.dispatchEvent(new CustomEvent('syndicate-ready'));
            } catch (e) { window.Auth.removeToken(); window.location.href = '{{ route("login") }}'; }
        });
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('sidebar-toggle')?.addEventListener('click', function () {
                document.getElementById('syndicate-sidebar').classList.remove(syndicateHiddenClass);
                document.getElementById('sidebar-backdrop').classList.remove('hidden');
            });
        });
        function closeSidebar() {
            document.getElementById('syndicate-sidebar').classList.add(syndicateHiddenClass);
            document.getElementById('sidebar-backdrop').classList.add('hidden');
        }
    </script>
    @stack('scripts')
</body>
</html>
