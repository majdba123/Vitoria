@extends('layouts.vendor')

@section('title', 'Vendor Dashboard — SyriaZone')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Welcome Banner --}}
    <div class="overflow-hidden rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-white shadow-lg sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold sm:text-2xl" id="vendor-welcome">Welcome back!</h2>
                <p class="mt-1 text-emerald-100">Manage your store, products, and orders from here.</p>
            </div>
            <div class="flex shrink-0 gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5 text-sm font-medium backdrop-blur-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.15c0 .415.336.75.75.75z"/></svg>
                    Vendor Panel
                </span>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        {{-- Store Status --}}
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Store Status</p>
                    <p class="text-lg font-bold text-gray-900" id="store-status">Loading...</p>
                </div>
            </div>
        </div>

        {{-- Products --}}
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Products</p>
                    <p class="text-lg font-bold text-gray-900" id="stat-products">0</p>
                </div>
            </div>
        </div>

        {{-- Active Products --}}
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Active Products</p>
                    <p class="text-lg font-bold text-emerald-600" id="stat-active-products">0</p>
                </div>
            </div>
        </div>

        {{-- Orders --}}
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Orders</p>
                    <p class="text-lg font-bold text-gray-900">0</p>
                </div>
            </div>
        </div>

        {{-- Revenue --}}
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Revenue</p>
                    <p class="text-lg font-bold text-gray-900">$0.00</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Store Info Card --}}
    <div class="card">
        <div class="card-body">
            <h3 class="mb-4 text-base font-semibold text-gray-900">Store Information</h3>
            <div id="store-info" class="space-y-3 text-sm text-gray-500">
                <p>Loading store information...</p>
            </div>
        </div>
    </div>

    {{-- Recent Products --}}
    <div class="card">
        <div class="card-body border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Recent Products</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Your latest products</p>
                </div>
                <a href="{{ route('vendor.products.index') }}" class="btn-secondary btn-xs">View All</a>
            </div>
        </div>
        <div class="card-body">
            <div id="recent-products" class="space-y-3">
                <div class="py-8 text-center">
                    <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-gray-200 border-t-emerald-500"></div>
                    <p class="mt-2 text-sm text-gray-500">Loading products...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="card">
        <div class="card-body">
            <h3 class="mb-4 text-base font-semibold text-gray-900">Quick Actions</h3>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <a href="{{ route('vendor.products.create') }}" class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 text-left text-sm transition-colors hover:bg-emerald-50 hover:border-emerald-300">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Add Product</p>
                        <p class="text-xs text-gray-500">Create a new product</p>
                    </div>
                </a>
                <a href="{{ route('vendor.products.index') }}" class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 text-left text-sm transition-colors hover:bg-blue-50 hover:border-blue-300">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Manage Products</p>
                        <p class="text-xs text-gray-500">View and edit products</p>
                    </div>
                </a>
                <button disabled class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 text-left text-sm transition-colors hover:bg-gray-50 opacity-50 cursor-not-allowed">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Store Settings</p>
                        <p class="text-xs text-gray-500">Coming soon</p>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    // Wait for auth guard to verify first
    const checkReady = setInterval(async function () {
        const app = document.getElementById('vendor-app');
        if (!app || app.classList.contains('hidden')) {
            return;
        }
        clearInterval(checkReady);

        try {
            const [userRes, productsRes] = await Promise.all([
                window.axios.get('/api/user'),
                window.axios.get('/api/vendor/products?page=1&per_page=5'),
            ]);

            const apiUser = userRes.data && (userRes.data.data || userRes.data);
            const authUser = window.Auth && window.Auth.getUser && window.Auth.getUser();
            const user = (apiUser && (apiUser.name != null || apiUser.phone_number != null)) ? apiUser : (authUser || {});

            function safeStr(v) {
                if (v === undefined || v === null) return '—';
                const s = String(v).trim();
                return (s === '' || s === 'undefined') ? '—' : s;
            }

            document.getElementById('vendor-welcome').textContent = 'Welcome back, ' + safeStr(user.name) + '!';
            document.getElementById('store-status').textContent = 'Active';
            document.getElementById('store-status').classList.add('text-emerald-600');

            document.getElementById('store-info').innerHTML = `
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Owner</p>
                        <p class="mt-1 font-medium text-gray-900">${safeStr(user.name)}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Phone</p>
                        <p class="mt-1 font-medium text-gray-900">${safeStr(user.phone_number)}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Email</p>
                        <p class="mt-1 font-medium text-gray-900">${safeStr(user.email)}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">National ID</p>
                        <p class="mt-1 font-medium text-gray-900">${safeStr(user.national_id)}</p>
                    </div>
                </div>
            `;

            // Update product stats
            const totalProducts = productsRes.data.meta?.total ?? 0;
            document.getElementById('stat-products').textContent = totalProducts;

            const products = productsRes.data.data || [];
            let activeProducts = 0;
            products.forEach(p => { if (p.is_active) activeProducts++; });
            document.getElementById('stat-active-products').textContent = activeProducts;

            // Render recent products
            const productsContainer = document.getElementById('recent-products');
            if (products.length === 0) {
                productsContainer.innerHTML = '<p class="py-8 text-center text-sm text-gray-400">No products yet. <a href="/vendor/products/create" class="text-emerald-600 hover:underline">Create your first product</a></p>';
            } else {
                productsContainer.innerHTML = products.map(p => `
                    <a href="/vendor/products/${p.id}" class="group flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:border-emerald-300 hover:bg-emerald-50/50">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">${esc(p.name)}</p>
                            <p class="text-xs text-gray-500">$${parseFloat(p.price || 0).toFixed(2)} · Qty: ${p.quantity}</p>
                        </div>
                        <span class="badge ${p.is_active ? 'badge-success' : 'badge-danger'}">
                            ${p.is_active ? 'Active' : 'Inactive'}
                        </span>
                        <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </a>
                `).join('');
            }
        } catch (e) {
            document.getElementById('store-status').textContent = 'Error';
            document.getElementById('store-status').classList.add('text-red-600');
        }
    }, 200);

    function esc(t) {
        if (!t) return '';
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }
});
</script>
@endpush

