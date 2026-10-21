<?php

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (\Illuminate\Http\Request $request) {
    return Inertia::render('Home', [
        'selectedType' => app(\App\Services\SelectedProductTypeService::class)->resolve($request),
    ]);
})->name('home');

Route::get('/contact', fn () => Inertia::render('Contact'))->name('contact');

// robots.txt and sitemap.xml must be served from the domain root — crawlers
// don't look under /api for them — so these live here rather than in
// routes/api.php (which is mounted at the /api prefix).
Route::get('/sitemap.xml', [\App\Http\Controllers\Api\SeoController::class, 'sitemap'])->middleware('throttle:public.browse')->name('sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\Api\SeoController::class, 'robots'])->middleware('throttle:public.browse')->name('robots');

Route::get('/pages/{slug}', function (string $slug) {
    $page = \App\Models\Page::query()->where('slug', $slug)->where('is_published', true)->first();

    return Inertia::render('Pages/Show', [
        'slug' => $slug,
        'page' => $page,
    ]);
})->name('pages.show');

Route::get('/product-type/select', [\App\Http\Controllers\ProductTypePreferenceController::class, 'show'])->name('product-type.select');
Route::post('/product-type/select', [\App\Http\Controllers\ProductTypePreferenceController::class, 'store'])->name('product-type.store');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'], true)) {
        session()->put('locale', $locale);
        Cookie::queue('locale', $locale, 60 * 24 * 365);
        Cookie::queue('sz_locale', $locale, 60 * 24 * 365);

        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
    }

    return redirect()->back();
})->name('locale.switch');

Route::get('/login', function () {
    // If logout parameter is present, don't redirect even if authenticated
    if (request()->has('logout')) {
        return Inertia::render('Auth/Login');
    }

    if (auth()->check()) {
        return auth()->user()->redirectAfterLogin();
    }

    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/register', function () {
    if (auth()->check()) {
        return auth()->user()->redirectAfterLogin();
    }

    return Inertia::render('Auth/Register');
})->name('register');

Route::get('/profile', function () {
    return Inertia::render('Profile/Index');
})->name('profile');

Route::middleware('auth')->group(function () {
    Route::get('/orders/{id}', function (string $id) {
        return Inertia::render('Orders/Show', ['orderId' => $id]);
    })->name('orders.show');

    Route::get('/checkout', function () {
        return Inertia::render('Checkout/Index');
    })->name('checkout');

    Route::get('/invoices/{invoice}/print', function (\App\Models\Invoice $invoice) {
        $invoice->load(['order.items', 'vendor:id,store_name', 'user:id,name']);
        abort_unless(\Illuminate\Support\Facades\Gate::allows('view', $invoice), 403);

        return view('invoices.print', ['invoice' => $invoice]);
    })->name('invoices.print');
});

/*
|--------------------------------------------------------------------------
| Public Listing Routes
|--------------------------------------------------------------------------
*/
Route::get('/products', function () {
    return Inertia::render('Products/Index');
})->middleware('product.type.selected')->name('products.index');

Route::get('/products/{product}', function (\Illuminate\Http\Request $request, \App\Models\Product $product) {
    // Reuses the exact same controller method /api/products/{id} calls (same
    // cache key, same 404/type-mismatch rules) so this SSR-rendered payload
    // can never drift from what the client-side API would have returned.
    $response = app(\App\Http\Controllers\Api\ProductController::class)->publicShow($request, $product);
    $payload = json_decode($response->getContent(), true);

    return Inertia::render('Products/Show', [
        'productId' => (string) $product->id,
        'product' => $payload['data'] ?? null,
    ]);
})->middleware('product.type.selected')->name('products.show');

Route::get('/faq', function () {
    return Inertia::render('Faq');
})->name('faq');

Route::get('/categories', function () {
    return Inertia::render('Categories/Index');
})->middleware('product.type.selected')->name('categories.index');

Route::get('/categories/{category}', function (\Illuminate\Http\Request $request, \App\Models\Category $category) {
    $response = app(\App\Http\Controllers\Api\Admin\CategoryController::class)->show($request, $category);
    $payload = json_decode($response->getContent(), true);

    return Inertia::render('Categories/Show', [
        'categoryId' => $category->id,
        'category' => $payload['data'] ?? null,
    ]);
})->middleware('product.type.selected')->name('categories.show');

Route::redirect('/vendors', '/', 302)->name('vendors.index');

Route::get('/vendors/{vendor}', function (\App\Models\Vendor $vendor) {
    $response = app(\App\Http\Controllers\Api\VendorController::class)->show($vendor);
    $payload = json_decode($response->getContent(), true);

    return Inertia::render('Vendors/Show', [
        'vendorId' => $vendor->id,
        'vendor' => $payload['data'] ?? null,
    ]);
})->whereNumber('vendor')->name('vendors.show');

/*
|--------------------------------------------------------------------------
| Vendor Web Routes
|--------------------------------------------------------------------------
|
| All vendor routes are protected by the 'auth' (session) and 'vendor'
| middleware so that only authenticated vendors can access these pages.
|
*/
Route::prefix('vendor')->as('vendor.')->middleware(['auth', 'vendor'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('vendor.dashboard');
    });

    Route::get('/dashboard', function () {
        return Inertia::render('Vendor/Dashboard');
    })->name('dashboard');

    // Product Management
    Route::get('/products', function () {
        return Inertia::render('Vendor/Products/Index');
    })->name('products.index');

    Route::get('/orders', function () {
        return Inertia::render('Vendor/Orders/Index');
    })->name('orders.index');

    Route::get('/orders/{id}', function (string $id) {
        return Inertia::render('Vendor/Orders/Show', ['orderId' => $id]);
    })->name('orders.show');

    Route::get('/sales', function () {
        return Inertia::render('Vendor/Commission');
    })->name('sales');

    Route::get('/notifications', function () {
        return Inertia::render('Vendor/Notifications/Index');
    })->name('notifications.index');

    Route::get('/products/create', function () {
        return Inertia::render('Vendor/Products/Create');
    })->name('products.create');

    Route::get('/products/{id}/edit', function (string $id) {
        return Inertia::render('Vendor/Products/Edit', ['productId' => $id]);
    })->name('products.edit');

    Route::get('/products/{id}/reviews', \App\Http\Controllers\Vendor\ProductReviewViewController::class)->name('products.reviews');
    Route::delete('/products/{id}/reviews/{review}', [\App\Http\Controllers\Vendor\ProductReviewViewController::class, 'destroy'])->name('products.reviews.destroy');

    Route::get('/products/{id}', function (string $id) {
        return Inertia::render('Vendor/Products/Show', ['productId' => $id]);
    })->name('products.show');

    // Profile
    Route::get('/profile', function () {
        return Inertia::render('Vendor/Profile');
    })->name('profile');
});

Route::prefix('syndicate')->as('syndicate.')->middleware(['auth', 'syndicate'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('syndicate.dashboard');
    });

    foreach (['dashboard', 'categories', 'vendors', 'products', 'podcasts', 'orders', 'sales', 'reports'] as $section) {
        Route::get('/'.$section, function () use ($section) {
            return Inertia::render('Syndicate/Dashboard', ['section' => $section]);
        })->name($section);
    }

    Route::get('/vendors/{id}', function (string $id) {
        return Inertia::render('Syndicate/Vendors/Show', ['vendorId' => (int) $id]);
    })->name('vendors.show');

    Route::get('/products/{id}', function (string $id) {
        return Inertia::render('Syndicate/Products/Show', ['productId' => (int) $id]);
    })->whereNumber('id')->name('products.show');

    Route::get('/orders/{id}', function (string $id) {
        return Inertia::render('Syndicate/Orders/Show', ['orderId' => (int) $id]);
    })->whereNumber('id')->name('orders.show');

    Route::get('/notifications', function () {
        return Inertia::render('Syndicate/Notifications/Index');
    })->name('notifications.index');
});

/*
| Ministry workspace: the syndicate pages, read-only, over agriculture,
| veterinary or both. An unknown `?sector=` falls back to the combined view.
*/
Route::prefix('ministry')->as('ministry.')->middleware(['auth', 'ministry'])->group(function () {
    $workspace = fn (\Illuminate\Http\Request $request): array => [
        'workspace' => 'ministry',
        'sector' => in_array($request->query('sector'), \App\Support\DomainScope::sectors(), true)
            ? $request->query('sector')
            : \App\Support\DomainScope::COMBINED,
    ];

    Route::get('/', fn () => redirect()->route('ministry.dashboard'));

    foreach (['dashboard', 'categories', 'vendors', 'products', 'orders', 'reports'] as $section) {
        Route::get('/'.$section, fn (\Illuminate\Http\Request $request) => Inertia::render('Syndicate/Dashboard', ['section' => $section, ...$workspace($request)]))->name($section);
    }

    Route::get('/vendors/{id}', fn (\Illuminate\Http\Request $request, string $id) => Inertia::render('Syndicate/Vendors/Show', ['vendorId' => (int) $id, ...$workspace($request)]))->whereNumber('id')->name('vendors.show');
    Route::get('/products/{id}', fn (\Illuminate\Http\Request $request, string $id) => Inertia::render('Syndicate/Products/Show', ['productId' => (int) $id, ...$workspace($request)]))->whereNumber('id')->name('products.show');
    Route::get('/orders/{id}', fn (\Illuminate\Http\Request $request, string $id) => Inertia::render('Syndicate/Orders/Show', ['orderId' => (int) $id, ...$workspace($request)]))->whereNumber('id')->name('orders.show');
    Route::get('/notifications', fn (\Illuminate\Http\Request $request) => Inertia::render('Syndicate/Notifications/Index', $workspace($request)))->name('notifications.index');
    Route::get('/shipments', fn (\Illuminate\Http\Request $request) => Inertia::render('Shipments/Index', $workspace($request)))->name('shipments.index');
    Route::get('/shipments/{id}', fn (\Illuminate\Http\Request $request, string $id) => Inertia::render('Shipments/Show', ['shipmentId' => (int) $id, ...$workspace($request)]))->whereNumber('id')->name('shipments.show');
});

Route::prefix('employee')->as('employee.')->middleware(['auth', 'employee'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('employee.dashboard');
    });

    Route::get('/dashboard', function () {
        return Inertia::render('Employee/Dashboard');
    })->name('dashboard');

    Route::get('/notifications', function () {
        return Inertia::render('Employee/Notifications/Index');
    })->name('notifications.index');

    Route::get('/products', function (\Illuminate\Http\Request $request) {
        return Inertia::render('Employee/Products/Index', ['status' => $request->query('status')]);
    })->name('products.index');

    Route::get('/products/{id}', function (string $id) {
        return Inertia::render('Employee/Products/Show', ['productId' => $id]);
    })->name('products.show');

    Route::get('/products/{id}/edit', function (string $id) {
        return Inertia::render('Employee/Products/Edit', ['productId' => $id]);
    })->name('products.edit');
});

/*
|--------------------------------------------------------------------------
| Admin Web Routes
|--------------------------------------------------------------------------
|
| All admin routes are protected by the 'auth' (session) and 'admin'
| middleware so that only authenticated admins can access these pages.
|
*/
Route::prefix('admin')->as('admin.')->middleware(['auth', 'admin', 'admin.permission'])->group(function () {
    // Redirect /admin to the first page this admin may open
    Route::get('/', function (\Illuminate\Http\Request $request) {
        return redirect()->route(\App\Support\AdminRoutePermissions::landingRouteFor($request->user()));
    })->name('home');

    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // Vendor Management
    Route::get('/vendors', function () {
        return Inertia::render('Admin/Vendors/Index');
    })->name('vendors.index');

    Route::get('/syndicates', function () {
        return Inertia::render('Admin/Syndicates/Index');
    })->name('syndicates.index');

    Route::get('/syndicates/create', function () {
        return Inertia::render('Admin/Syndicates/Create');
    })->name('syndicates.create');

    Route::get('/syndicates/{id}', function (string $id) {
        return Inertia::render('Admin/Syndicates/Show', ['syndicateId' => (int) $id]);
    })->name('syndicates.show');

    Route::get('/syndicates/{id}/edit', function (string $id) {
        return Inertia::render('Admin/Syndicates/Edit', ['syndicateId' => (int) $id]);
    })->name('syndicates.edit');

    Route::get('/vendors/create', function () {
        return Inertia::render('Admin/Vendors/Create');
    })->name('vendors.create');

    Route::get('/vendors/{id}', function (string $id) {
        return Inertia::render('Admin/Vendors/Show', ['vendorId' => (int) $id]);
    })->name('vendors.show');

    Route::get('/vendors/{id}/edit', function (string $id) {
        return Inertia::render('Admin/Vendors/Edit', ['vendorId' => (int) $id]);
    })->name('vendors.edit');

    Route::get('/vendors/{id}/commission', function (string $id) {
        return Inertia::render('Admin/Vendors/Commission', ['vendorId' => (int) $id]);
    })->name('vendors.commission');

    Route::get('/financials', function () {
        return Inertia::render('Admin/Financials/Index');
    })->name('financials.index');

    // Product Management
    Route::get('/products', function () {
        return Inertia::render('Admin/Products/Index');
    })->name('products.index');

    Route::get('/discounts', function () {
        return Inertia::render('Admin/Products/Index', ['discountOnly' => true]);
    })->name('discounts.index');

    Route::get('/coupons', function () {
        return Inertia::render('Admin/Coupons/Index');
    })->name('coupons.index');

    Route::get('/pages', function () {
        return Inertia::render('Admin/Pages/Index');
    })->name('pages.index');

    Route::get('/banners', function () {
        return Inertia::render('Admin/Banners/Index');
    })->name('banners.index');

    Route::get('/orders', function () {
        return Inertia::render('Admin/Orders/Index');
    })->name('orders.index');

    Route::get('/orders/{id}', function (string $id) {
        return Inertia::render('Admin/Orders/Show', ['orderId' => (int) $id]);
    })->name('orders.show');

    Route::get('/products/create', function () {
        return Inertia::render('Admin/Products/Create');
    })->name('products.create');

    Route::get('/products/{id}/edit', function (string $id) {
        return Inertia::render('Admin/Products/Edit', ['productId' => (int) $id]);
    })->name('products.edit');

    Route::get('/products/{id}/reviews', \App\Http\Controllers\Admin\ProductReviewViewController::class)->name('products.reviews');
    Route::delete('/products/{id}/reviews/{review}', [\App\Http\Controllers\Admin\ProductReviewViewController::class, 'destroy'])->name('products.reviews.destroy');

    Route::get('/products/{id}', function (string $id) {
        return Inertia::render('Admin/Products/Show', ['productId' => (int) $id]);
    })->name('products.show');

    // User Management
    Route::get('/users', function () {
        return Inertia::render('Admin/Users/Index');
    })->name('users.index');

    Route::get('/employees', function () {
        return redirect()->route('admin.users.index', ['type' => \App\Models\User::TYPE_EMPLOYEE]);
    })->name('employees.index');

    Route::get('/customers', function () {
        return redirect()->route('admin.users.index', ['type' => \App\Models\User::TYPE_USER]);
    })->name('customers.index');

    Route::get('/users/create', function () {
        return Inertia::render('Admin/Users/Create');
    })->name('users.create');

    Route::get('/users/{id}', function (string $id) {
        return Inertia::render('Admin/Users/Show', ['userId' => (int) $id]);
    })->name('users.show');

    Route::get('/users/{id}/edit', function (string $id) {
        return Inertia::render('Admin/Users/Edit', ['userId' => (int) $id]);
    })->name('users.edit');

    // Roles & permissions (native RBAC)
    Route::get('/roles', fn () => Inertia::render('Admin/Roles/Index'))->name('roles.index');
    Route::get('/roles/create', fn () => Inertia::render('Admin/Roles/Form', ['roleId' => null]))->name('roles.create');
    Route::get('/roles/{id}/edit', fn (string $id) => Inertia::render('Admin/Roles/Form', ['roleId' => (int) $id]))->whereNumber('id')->name('roles.edit');

    // Audit trail (read-only)
    Route::get('/audit-logs', fn () => Inertia::render('Admin/AuditLogs/Index'))->name('audit-logs.index');

    Route::get('/notifications', function () {
        return Inertia::render('Admin/Notifications/Index');
    })->name('notifications.index');

    Route::get('/notifications/send', function () {
        return Inertia::render('Admin/Notifications/Send');
    })->name('notifications.send');

    Route::get('/contact-messages', [\App\Http\Controllers\Admin\ContactMessageViewController::class, 'index'])->name('contact-messages.index');

    Route::get('/about-us', [\App\Http\Controllers\Admin\AboutUsController::class, 'edit'])->name('about-us.edit');

    // Category Management
    Route::get('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [\App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('categories.create');
    Route::get('/categories/{id}', [\App\Http\Controllers\Admin\CategoryController::class, 'show'])->name('categories.show');
    Route::get('/categories/{id}/edit', [\App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('categories.edit');
    Route::get('/subcategories', [\App\Http\Controllers\Admin\SubcategoryController::class, 'index'])->name('subcategories.index');
    Route::get('/subcategories/create', [\App\Http\Controllers\Admin\SubcategoryController::class, 'create'])->name('subcategories.create');
    Route::get('/subcategories/{id}', [\App\Http\Controllers\Admin\SubcategoryController::class, 'show'])->name('subcategories.show');
    Route::get('/subcategories/{id}/edit', [\App\Http\Controllers\Admin\SubcategoryController::class, 'edit'])->name('subcategories.edit');

    // City Management
    Route::get('/cities', [\App\Http\Controllers\Admin\CityController::class, 'index'])->name('cities.index');
    Route::get('/cities/create', [\App\Http\Controllers\Admin\CityController::class, 'create'])->name('cities.create');
    Route::get('/cities/{id}', [\App\Http\Controllers\Admin\CityController::class, 'show'])->name('cities.show');
    Route::get('/cities/{id}/edit', [\App\Http\Controllers\Admin\CityController::class, 'edit'])->name('cities.edit');
});

/*
| Import shipments (الشحنات) pages, shared by the admin, employee and
| syndicate workspaces. `workspace` tells the page which layout + API base to use.
*/
$registerShipmentPages = function (string $workspace): void {
    Route::get('/shipments', fn () => Inertia::render('Shipments/Index', ['workspace' => $workspace]))->name('shipments.index');
    Route::get('/shipments/create', fn () => Inertia::render('Shipments/Form', ['workspace' => $workspace, 'shipmentId' => null]))->name('shipments.create');
    Route::get('/shipments/{id}', fn (string $id) => Inertia::render('Shipments/Show', ['workspace' => $workspace, 'shipmentId' => (int) $id]))->whereNumber('id')->name('shipments.show');
    Route::get('/shipments/{id}/edit', fn (string $id) => Inertia::render('Shipments/Form', ['workspace' => $workspace, 'shipmentId' => (int) $id]))->whereNumber('id')->name('shipments.edit');
};

Route::prefix('syndicate')->as('syndicate.')->middleware(['auth', 'syndicate'])->group(fn () => $registerShipmentPages('syndicate'));
Route::prefix('employee')->as('employee.')->middleware(['auth', 'employee'])->group(fn () => $registerShipmentPages('employee'));
Route::prefix('admin')->as('admin.')->middleware(['auth', 'admin', 'admin.permission'])->group(function () use ($registerShipmentPages): void {
    $registerShipmentPages('admin');
    Route::get('/ports', fn () => Inertia::render('Admin/Ports/Index'))->name('ports.index');
});

Route::fallback(function (\Illuminate\Http\Request $request) {
    if ($request->expectsJson() || $request->is('api/*')) {
        abort(404);
    }

    return Inertia::render('Errors/NotFound', [
        'status' => 404,
    ])->toResponse($request)->setStatusCode(404);
})->name('not-found');
