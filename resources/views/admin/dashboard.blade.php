@extends('layouts.admin')

@section('title', 'لوحة التحكم — Vetora')
@section('page-title', 'لوحة التحكم')

@section('content')
<div class="space-y-6">
    {{-- Welcome --}}
    <div class="card card-body">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">مرحباً بعودتك!</h2>
                <p class="mt-0.5 text-sm text-gray-500">هذه أهم مؤشرات المنصة وحالة النقابات اليوم.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.vendors.create') }}" class="btn-primary btn-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    إضافة تاجر
                </a>
                <a href="{{ route('admin.users.create') }}" class="btn-secondary btn-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    إضافة مستخدم
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-7">
        {{-- Total Users --}}
        <div class="card card-body">
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold uppercase tracking-wider text-gray-500">إجمالي المستخدمين</p>
                    <p id="stat-users" class="mt-2 text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                </div>
            </div>
        </div>

        {{-- Total Vendors --}}
        <div class="card card-body">
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold uppercase tracking-wider text-gray-500">إجمالي التجار</p>
                    <p id="stat-vendors" class="mt-2 text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72"/></svg>
                </div>
            </div>
        </div>

        {{-- Total Syndicates --}}
        <div class="card card-body">
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold uppercase tracking-wider text-gray-500">النقابات</p>
                    <p id="stat-syndicates" class="mt-2 text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-cyan-50 text-cyan-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.941 3.479a8.985 8.985 0 01-4.686 0m4.686 0V19.5m-4.686-.5a9.094 9.094 0 01-3.741-.479 3 3 0 014.682-2.72m-.941 3.479V19.5m0 0a3 3 0 11-6 0m6 0a3 3 0 00-6 0m6 0h.008v.008H12v-.008zM12 8.25a3 3 0 100-6 3 3 0 000 6z"/></svg>
                </div>
            </div>
        </div>

        {{-- Active Vendors --}}
        <div class="card card-body">
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold uppercase tracking-wider text-gray-500">تجار نشطون</p>
                    <p id="stat-active-vendors" class="mt-2 text-2xl font-bold text-emerald-600">—</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        {{-- Inactive Vendors --}}
        <div class="card card-body">
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold uppercase tracking-wider text-gray-500">تجار غير نشطين</p>
                    <p id="stat-inactive-vendors" class="mt-2 text-2xl font-bold text-red-600">—</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
            </div>
        </div>

        {{-- Total Products --}}
        <div class="card card-body">
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold uppercase tracking-wider text-gray-500">إجمالي المنتجات</p>
                    <p id="stat-products" class="mt-2 text-2xl font-bold text-gray-900">—</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                </div>
            </div>
        </div>

        {{-- Active Products --}}
        <div class="card card-body">
            <div class="flex items-start justify-between">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold uppercase tracking-wider text-gray-500">منتجات نشطة</p>
                    <p id="stat-active-products" class="mt-2 text-2xl font-bold text-emerald-600">—</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Vendor & Category Type Stats --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">التجار حسب النوع</h3>
                <p class="mt-0.5 text-sm text-gray-500">زراعي، بيطري، أو كلاهما</p>
            </div>
            <div id="vendors-by-type" class="card-body grid gap-3 sm:grid-cols-3"></div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">التصنيفات حسب النوع</h3>
                <p class="mt-0.5 text-sm text-gray-500">توزيع التصنيفات حسب خط العمل</p>
            </div>
            <div id="categories-by-type" class="card-body grid gap-3 sm:grid-cols-2"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">النقابات حسب النوع</h3>
                <p class="mt-0.5 text-sm text-gray-500">تغطية وكلاء النقابات حسب خط العمل</p>
            </div>
            <div id="syndicates-by-type" class="card-body grid gap-3 sm:grid-cols-2"></div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">أحدث وكلاء النقابات</h3>
                <p class="mt-0.5 text-sm text-gray-500">آخر حسابات الزراعة والبيطرة</p>
            </div>
            <div id="recent-syndicate-agents" class="card-body space-y-3"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">المنتجات حسب النوع</h3>
                <p class="mt-0.5 text-sm text-gray-500">مخزون المنتجات حسب نوع التصنيف</p>
            </div>
            <div id="products-by-category-type" class="card-body grid gap-3 sm:grid-cols-2 lg:grid-cols-1"></div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">أفضل التجار</h3>
                <p class="mt-0.5 text-sm text-gray-500">مرتبة حسب عدد المنتجات</p>
            </div>
            <div id="top-vendors-by-product-count" class="card-body space-y-3"></div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">نمو المنتجات الشهري</h3>
                <p class="mt-0.5 text-sm text-gray-500">المنتجات الجديدة خلال آخر 12 شهراً</p>
            </div>
            <div id="monthly-product-growth" class="card-body space-y-2"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">التصنيفات الأكثر اختياراً</h3>
                <p class="mt-0.5 text-sm text-gray-500">أعلى التصنيفات حسب التجار المرتبطين</p>
            </div>
            <div id="most-selected-categories" class="card-body space-y-3"></div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">أحدث تسجيلات التجار</h3>
                <p class="mt-0.5 text-sm text-gray-500">آخر حسابات التجار</p>
            </div>
            <div id="recent-vendors" class="card-body space-y-3"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">تصنيفات بلا منتجات</h3>
                <p class="mt-0.5 text-sm text-gray-500">أقسام تحتاج إلى تغطية منتجات</p>
            </div>
            <div id="categories-with-no-products" class="card-body space-y-3"></div>
        </div>

        <div class="card">
            <div class="card-body border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">تصنيفات بلا تجار</h3>
                <p class="mt-0.5 text-sm text-gray-500">خطوط عمل دون تجار مرتبطين</p>
            </div>
            <div id="categories-with-no-vendors" class="card-body space-y-3"></div>
        </div>
    </div>

    {{-- Vendors by Category --}}
    <div class="card">
        <div class="card-body border-b border-gray-100">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                <h3 class="text-base font-semibold text-gray-900">التجار حسب التصنيف</h3>
                <p class="mt-0.5 text-sm text-gray-500">توزيع التجار حسب التصنيفات المرتبطة</p>
                </div>
                <a href="{{ route('admin.vendors.index') }}" class="btn-secondary btn-xs">عرض التجار</a>
            </div>
        </div>
        <div class="card-body">
            <div id="vendor-category-stats" class="space-y-3">
                <div class="py-8 text-center">
                    <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-gray-200 border-t-brand-500"></div>
            <p class="mt-2 text-sm text-gray-500">جارٍ تحميل إحصاءات التصنيفات...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Products --}}
    <div class="card">
        <div class="card-body border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                <h3 class="text-base font-semibold text-gray-900">أحدث المنتجات</h3>
                <p class="mt-0.5 text-sm text-gray-500">آخر المنتجات المضافة إلى المنصة</p>
                </div>
                <a href="{{ route('admin.products.index') }}" class="btn-secondary btn-xs">عرض الكل</a>
            </div>
        </div>
        <div class="card-body">
            <div id="recent-products" class="space-y-3">
                <div class="py-8 text-center">
                    <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-gray-200 border-t-brand-500"></div>
            <p class="mt-2 text-sm text-gray-500">جارٍ تحميل المنتجات...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('admin.vendors.index') }}" class="card card-body group flex items-center gap-4 transition-shadow hover:shadow-md">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 transition-colors group-hover:bg-brand-100">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900">إدارة التجار</p>
                    <p class="text-xs text-gray-500">عرض وتعديل وتفعيل حسابات التجار</p>
            </div>
            <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </a>

        <a href="{{ route('admin.users.index') }}" class="card card-body group flex items-center gap-4 transition-shadow hover:shadow-md">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition-colors group-hover:bg-blue-100">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900">إدارة المستخدمين</p>
                    <p class="text-xs text-gray-500">عرض وتعديل وإدارة حسابات المستخدمين</p>
            </div>
            <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </a>

        <a href="{{ route('admin.products.index') }}" class="card card-body group flex items-center gap-4 transition-shadow hover:shadow-md">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 transition-colors group-hover:bg-purple-100">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900">إدارة المنتجات</p>
                    <p class="text-xs text-gray-500">عرض وتعديل وإدارة كل المنتجات</p>
            </div>
            <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    try {
        const [usersRes, vendorsRes, productsRes, categoryStatsRes, overviewRes] = await Promise.all([
            window.axios.get('/api/admin/users?page=1'),
            window.axios.get('/api/admin/vendors?page=1'),
            window.axios.get('/api/admin/products?page=1&per_page=5'),
            window.axios.get('/api/admin/dashboard/vendor-category-stats'),
            window.axios.get('/api/admin/dashboard/overview'),
        ]);
        const overview = overviewRes.data.data || {};

        document.getElementById('stat-users').textContent = usersRes.data.meta?.total ?? '0';

        const totalVendors = overview.total_vendors ?? vendorsRes.data.meta?.total ?? 0;
        document.getElementById('stat-vendors').textContent = totalVendors;
        document.getElementById('stat-syndicates').textContent = overview.total_syndicates ?? 0;

        const vendors = vendorsRes.data.data || [];
        let active = 0, inactive = 0;
        vendors.forEach(v => { v.is_active ? active++ : inactive++; });

        document.getElementById('stat-active-vendors').textContent = active;
        document.getElementById('stat-inactive-vendors').textContent = inactive;

        const totalProducts = overview.total_products ?? productsRes.data.meta?.total ?? 0;
        document.getElementById('stat-products').textContent = totalProducts;

        const products = overview.recent_products || productsRes.data.data || [];
        document.getElementById('stat-active-products').textContent = overview.active_products ?? products.filter(p => p.is_active).length;

        renderVendorCategoryStats(categoryStatsRes.data.data || []);
        renderOverview(overview);

        // Render recent products
        const productsContainer = document.getElementById('recent-products');
        if (products.length === 0) {
            productsContainer.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">لا توجد منتجات بعد.</p>';
        } else {
            productsContainer.innerHTML = products.map(p => `
                <a href="/admin/products/${p.id}" class="group flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:border-brand-300 hover:bg-brand-50/50">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">${esc(p.name)}</p>
                        <p class="text-xs text-gray-500">${esc(p.category?.name || p.status || '')}</p>
                    </div>
                    <span class="badge ${p.is_active ? 'badge-success' : 'badge-danger'}">
                        ${p.is_active ? 'نشط' : 'غير نشط'}
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            `).join('');
        }
    } catch (e) {
        document.getElementById('vendor-category-stats').innerHTML = '<p class="py-8 text-center text-sm text-red-500">تعذر تحميل إحصاءات لوحة التحكم.</p>';
    }

    function renderVendorCategoryStats(rows) {
        const container = document.getElementById('vendor-category-stats');

        if (!rows.length) {
            container.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">لا توجد تصنيفات تجار بعد.</p>';
            return;
        }

        container.innerHTML = rows.map(row => {
            const total = Number(row.total_vendors || 0);
            const active = Number(row.active_vendors || 0);
            const pending = Number(row.pending_vendors || 0);
            const inactive = Number(row.inactive_vendors || 0);
            const activeWidth = total > 0 ? Math.round((active / total) * 100) : 0;
            const pendingWidth = total > 0 ? Math.round((pending / total) * 100) : 0;
            const inactiveWidth = Math.max(0, 100 - activeWidth - pendingWidth);
            const url = row.id ? `/admin/vendors?category_id=${encodeURIComponent(row.id)}` : '/admin/vendors';

            return `
                <a href="${url}" class="block rounded-lg border border-gray-200 p-4 transition-colors hover:border-brand-300 hover:bg-brand-50/40">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">${esc(row.name)}</p>
                    <p class="mt-1 text-xs text-gray-500">${total} تاجر إجمالاً</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                        <span class="badge badge-success">${active} نشط</span>
                        <span class="badge badge-warning">${pending} معلق</span>
                        <span class="badge badge-danger">${inactive} غير نشط</span>
                        </div>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="flex h-full w-full">
                            <div class="bg-emerald-500" style="width: ${activeWidth}%"></div>
                            <div class="bg-amber-500" style="width: ${pendingWidth}%"></div>
                            <div class="bg-red-500" style="width: ${inactiveWidth}%"></div>
                        </div>
                    </div>
                </a>
            `;
        }).join('');
    }

    function renderOverview(overview) {
        renderMetricTiles('vendors-by-type', overview.vendors_by_type || [], 'vendor');
        renderMetricTiles('syndicates-by-type', overview.syndicates_by_type || [], 'syndicate');
        renderMetricTiles('categories-by-type', overview.categories_by_type || [], 'category');
        renderMetricTiles('products-by-category-type', overview.products_by_category_type || [], 'product');
        renderMostSelectedCategories(overview.most_selected_categories || []);
        renderRecentVendors(overview.recent_vendor_registrations || []);
        renderRecentSyndicates(overview.recent_syndicate_agents || []);
        renderTopVendors(overview.top_vendors_by_product_count || []);
        renderCategoryGapList('categories-with-no-products', overview.categories_with_no_products || [], 'products_count');
        renderCategoryGapList('categories-with-no-vendors', overview.categories_with_no_vendors || [], 'vendors_count');
        renderMonthlyProductGrowth(overview.monthly_product_growth || []);
    }

    function renderMetricTiles(id, rows, noun) {
        const container = document.getElementById(id);
        if (!container) return;

        container.innerHTML = rows.map(row => `
            <a href="${metricTileUrl(noun, row.type)}" class="rounded-lg border border-gray-200 p-4 transition-colors hover:border-brand-300 hover:bg-brand-50/40">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">${esc(typeLabel(row.type, row.label))}</p>
                <p class="mt-2 text-2xl font-black text-gray-900">${Number(row.total || 0)}</p>
            </a>
        `).join('');
    }

    function metricTileUrl(noun, type) {
        if (noun === 'vendor') return `/admin/vendors?business_type=${encodeURIComponent(type)}`;
        if (noun === 'syndicate') return `/admin/syndicates?type=${encodeURIComponent(type)}`;
        if (noun === 'product') return `/admin/products?category_type=${encodeURIComponent(type)}`;
        return `/admin/categories?type=${encodeURIComponent(type)}`;
    }

    function renderMostSelectedCategories(rows) {
        const container = document.getElementById('most-selected-categories');
        if (!rows.length) {
            container.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">لا توجد اختيارات تصنيفات بعد.</p>';
            return;
        }

        container.innerHTML = rows.map(row => `
            <a href="/admin/vendors?category_id=${encodeURIComponent(row.id)}" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:border-brand-300 hover:bg-brand-50/40">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">${esc(row.name)}</p>
                    <p class="mt-0.5 text-xs text-gray-500">${esc(typeLabel(row.type, row.type_label))}</p>
                </div>
                <span class="badge badge-brand">${Number(row.vendors_count || 0)} تاجر</span>
            </a>
        `).join('');
    }

    function renderRecentVendors(rows) {
        const container = document.getElementById('recent-vendors');
        if (!rows.length) {
            container.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">لا يوجد تجار بعد.</p>';
            return;
        }

        container.innerHTML = rows.map(vendor => `
            <a href="/admin/vendors/${vendor.id}" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:border-brand-300 hover:bg-brand-50/40">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">${esc(vendor.store_name)}</p>
                    <p class="mt-0.5 text-xs text-gray-500">${esc(vendor.user?.name || '')} · ${esc(businessTypeLabel(vendor.business_type, vendor.business_type_label))}</p>
                </div>
                <span class="badge ${vendor.status === 'pending' ? 'badge-warning' : (vendor.is_active ? 'badge-success' : 'badge-danger')}">${esc(statusLabel(vendor.status || (vendor.is_active ? 'active' : 'inactive')))}</span>
            </a>
        `).join('');
    }

    function renderRecentSyndicates(rows) {
        const container = document.getElementById('recent-syndicate-agents');
        if (!rows.length) {
            container.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">لا يوجد وكلاء نقابات بعد.</p>';
            return;
        }

        container.innerHTML = rows.map(syndicate => `
            <a href="/admin/syndicates/${syndicate.id}" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:border-brand-300 hover:bg-brand-50/40">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">${esc(syndicate.name)}</p>
                    <p class="mt-0.5 text-xs text-gray-500">${esc(syndicate.user?.email || '')} · ${esc(typeLabel(syndicate.type, syndicate.type_label))}</p>
                </div>
                <span class="badge ${syndicate.status === 'active' ? 'badge-success' : 'badge-danger'}">${esc(statusLabel(syndicate.status))}</span>
            </a>
        `).join('');
    }

    function renderTopVendors(rows) {
        const container = document.getElementById('top-vendors-by-product-count');
        if (!rows.length) {
            container.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">لا توجد منتجات تجار بعد.</p>';
            return;
        }

        container.innerHTML = rows.map(vendor => `
            <a href="/admin/vendors/${vendor.id}" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:border-brand-300 hover:bg-brand-50/40">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">${esc(vendor.store_name)}</p>
                    <p class="mt-0.5 text-xs text-gray-500">${esc(businessTypeLabel(vendor.business_type, vendor.business_type_label))}</p>
                </div>
                <span class="badge badge-brand">${Number(vendor.products_count || 0)} منتج</span>
            </a>
        `).join('');
    }

    function renderCategoryGapList(id, rows, countKey) {
        const container = document.getElementById(id);
        if (!rows.length) {
            container.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">لا توجد فجوات حالياً.</p>';
            return;
        }

        container.innerHTML = rows.slice(0, 8).map(row => `
            <a href="/admin/categories/${row.id}" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:border-brand-300 hover:bg-brand-50/40">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">${esc(row.name)}</p>
                    <p class="mt-0.5 text-xs text-gray-500">${esc(typeLabel(row.type, row.type_label))}</p>
                </div>
                <span class="badge badge-danger">${Number(row[countKey] || 0)}</span>
            </a>
        `).join('');
    }

    function renderMonthlyProductGrowth(rows) {
        const container = document.getElementById('monthly-product-growth');
        if (!rows.length) {
            container.innerHTML = '<p class="py-6 text-center text-sm text-gray-400">لا يوجد نمو منتجات بعد.</p>';
            return;
        }

        const max = Math.max(...rows.map(row => Number(row.total || 0)), 1);
        container.innerHTML = rows.map(row => {
            const width = Math.max(4, Math.round((Number(row.total || 0) / max) * 100));

            return `
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs">
                        <span class="font-semibold text-gray-700">${esc(row.month)}</span>
                        <span class="text-gray-500">${Number(row.total || 0)}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-brand-500" style="width: ${width}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function esc(t) {
        if (!t) return '';
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    function typeLabel(type, fallback = '') {
        if (type === 'agriculture') return 'زراعي';
        if (type === 'veterinary') return 'بيطري';
        if (type === 'both') return 'زراعي وبيطري';
        return fallback || type || '';
    }

    function businessTypeLabel(type, fallback = '') {
        return typeLabel(type, fallback);
    }

    function statusLabel(status) {
        if (status === 'active') return 'نشط';
        if (status === 'inactive') return 'غير نشط';
        if (status === 'pending') return 'معلق';
        if (status === 'approved') return 'معتمد';
        return status || '';
    }
});
</script>
@endpush
