<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;

if (! function_exists('redirectAuthenticatedUser')) {
    function redirectAuthenticatedUser(\App\Models\User $user)
    {
        return match ($user->type) {
            \App\Models\User::TYPE_ADMIN => redirect()->route('admin.dashboard'),
            \App\Models\User::TYPE_VENDOR => redirect()->route('vendor.dashboard'),
            \App\Models\User::TYPE_SYNDICATE => redirect()->route('syndicate.dashboard'),
            default => $user->preferred_product_type
                ? redirect()->route('home')
                : redirect()->route('product-type.select'),
        };
    }
}

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/product-type/select', [\App\Http\Controllers\ProductTypePreferenceController::class, 'show'])->name('product-type.select');
Route::post('/product-type/select', [\App\Http\Controllers\ProductTypePreferenceController::class, 'store'])->name('product-type.store');

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'], true)) {
        session()->put('locale', $locale);
        Cookie::queue('locale', $locale, 60 * 24 * 365);

        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        session()->put('locale', $locale);
    }

    return redirect()->back();
})->name('locale.switch');

Route::get('/login', function () {
    // If logout parameter is present, don't redirect even if authenticated
    if (request()->has('logout')) {
        return view('auth.login');
    }

    if (auth()->check()) {
        return redirectAuthenticatedUser(auth()->user());
    }

    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    if (auth()->check()) {
        return redirectAuthenticatedUser(auth()->user());
    }

    return view('auth.register');
})->name('register');

Route::get('/profile', function () {
    return view('profile');
})->name('profile');

Route::middleware('auth')->group(function () {
    Route::get('/orders/{id}', function (string $id) {
        return view('orders.show', ['orderId' => $id]);
    })->name('orders.show');
});

/*
|--------------------------------------------------------------------------
| Public Listing Routes
|--------------------------------------------------------------------------
*/
Route::get('/products', function () {
    return view('products.index');
})->middleware('product.type.selected')->name('products.index');

Route::get('/products/{id}', function (string $id) {
    return view('products.show', ['productId' => $id]);
})->middleware('product.type.selected')->name('products.show');

Route::get('/categories', function () {
    return view('categories.index');
})->middleware('product.type.selected')->name('categories.index');

Route::get('/categories/{id}', function (string $id) {
    return view('categories.show', ['categoryId' => $id]);
})->middleware('product.type.selected')->name('categories.show');

Route::redirect('/vendors', '/', 302)->name('vendors.index');

Route::get('/vendors/{id}', function () {
    return redirect('/');
})->whereNumber('id')->name('vendors.show');

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
        return view('vendor.dashboard');
    })->name('dashboard');

    // Product Management
    Route::get('/products', function () {
        return view('vendor.products.index');
    })->name('products.index');

    Route::get('/discounts', function () {
        return view('vendor.products.index', ['discountOnly' => true]);
    })->name('discounts.index');

    Route::get('/orders', function () {
        return view('vendor.orders.index');
    })->name('orders.index');

    Route::get('/orders/{id}', function (string $id) {
        return view('vendor.orders.show', ['orderId' => $id]);
    })->name('orders.show');

    Route::get('/commission', function () {
        return view('vendor.commission');
    })->name('commission');

    Route::get('/notifications', function () {
        return view('vendor.notifications.index');
    })->name('notifications.index');

    Route::get('/products/create', function () {
        return view('vendor.products.create');
    })->name('products.create');

    Route::get('/products/{id}/edit', function (string $id) {
        return view('vendor.products.edit', ['productId' => $id]);
    })->name('products.edit');

    Route::get('/products/{id}/reviews', \App\Http\Controllers\Vendor\ProductReviewViewController::class)->name('products.reviews');
    Route::delete('/products/{id}/reviews/{review}', [\App\Http\Controllers\Vendor\ProductReviewViewController::class, 'destroy'])->name('products.reviews.destroy');

    Route::get('/products/{id}', function (string $id) {
        return view('vendor.products.show', ['productId' => $id]);
    })->name('products.show');

    // Profile
    Route::get('/profile', function () {
        return view('vendor.profile');
    })->name('profile');
});

Route::prefix('syndicate')->as('syndicate.')->middleware(['auth', 'syndicate'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('syndicate.dashboard');
    });

    foreach (['dashboard', 'categories', 'vendors', 'products', 'podcasts', 'orders', 'sales', 'reports'] as $section) {
        Route::get('/'.$section, function () use ($section) {
            return view('syndicate.dashboard', ['section' => $section]);
        })->name($section);
    }
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
Route::prefix('admin')->as('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Redirect /admin to /admin/dashboard
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    // Dashboard
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Vendor Management
    Route::get('/vendors', function () {
        return view('admin.vendors.index');
    })->name('vendors.index');

    Route::get('/syndicates', function () {
        return view('admin.syndicates.index');
    })->name('syndicates.index');

    Route::get('/syndicates/create', function () {
        return view('admin.syndicates.create');
    })->name('syndicates.create');

    Route::get('/syndicates/{id}', function (string $id) {
        return view('admin.syndicates.show', ['syndicateId' => $id]);
    })->name('syndicates.show');

    Route::get('/syndicates/{id}/edit', function (string $id) {
        return view('admin.syndicates.edit', ['syndicateId' => $id]);
    })->name('syndicates.edit');

    Route::get('/vendors/create', function () {
        return view('admin.vendors.create');
    })->name('vendors.create');

    Route::get('/vendors/{id}', function (string $id) {
        return view('admin.vendors.show', ['vendorId' => $id]);
    })->name('vendors.show');

    Route::get('/vendors/{id}/edit', function (string $id) {
        return view('admin.vendors.edit', ['vendorId' => $id]);
    })->name('vendors.edit');

    Route::get('/vendors/{id}/commission', function (string $id) {
        return view('admin.vendors.commission', ['vendorId' => $id]);
    })->name('vendors.commission');

    // Product Management
    Route::get('/products', function () {
        return view('admin.products.index');
    })->name('products.index');

    Route::get('/discounts', function () {
        return view('admin.products.index', ['discountOnly' => true]);
    })->name('discounts.index');

    Route::get('/coupons', function () {
        return view('admin.coupons.index');
    })->name('coupons.index');

    Route::get('/orders', function () {
        return view('admin.orders.index');
    })->name('orders.index');

    Route::get('/orders/{id}', function (string $id) {
        return view('admin.orders.show', ['orderId' => $id]);
    })->name('orders.show');

    Route::get('/products/create', function () {
        return view('admin.products.create');
    })->name('products.create');

    Route::get('/products/{id}/edit', function (string $id) {
        return view('admin.products.edit', ['productId' => $id]);
    })->name('products.edit');

    Route::get('/products/{id}/reviews', \App\Http\Controllers\Admin\ProductReviewViewController::class)->name('products.reviews');
    Route::delete('/products/{id}/reviews/{review}', [\App\Http\Controllers\Admin\ProductReviewViewController::class, 'destroy'])->name('products.reviews.destroy');

    Route::get('/products/{id}', function (string $id) {
        return view('admin.products.show', ['productId' => $id]);
    })->name('products.show');

    // User Management
    Route::get('/users', function () {
        return view('admin.users.index');
    })->name('users.index');

    Route::get('/users/create', function () {
        return view('admin.users.create');
    })->name('users.create');

    Route::get('/users/{id}', function (string $id) {
        return view('admin.users.show', ['userId' => $id]);
    })->name('users.show');

    Route::get('/users/{id}/edit', function (string $id) {
        return view('admin.users.edit', ['userId' => $id]);
    })->name('users.edit');

    Route::get('/notifications', function () {
        return view('admin.notifications.index');
    })->name('notifications.index');

    Route::get('/notifications/send', function () {
        return view('admin.notifications.send');
    })->name('notifications.send');

    Route::get('/contact-messages', [\App\Http\Controllers\Admin\ContactMessageViewController::class, 'index'])->name('contact-messages.index');

    Route::get('/about-us', [\App\Http\Controllers\Admin\AboutUsController::class, 'edit'])->name('about-us.edit');

    // Category Management
    Route::get('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [\App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('categories.create');
    Route::get('/categories/{id}', [\App\Http\Controllers\Admin\CategoryController::class, 'show'])->name('categories.show');
    Route::get('/categories/{id}/edit', [\App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('categories.edit');

    // City Management
    Route::get('/cities', [\App\Http\Controllers\Admin\CityController::class, 'index'])->name('cities.index');
    Route::get('/cities/create', [\App\Http\Controllers\Admin\CityController::class, 'create'])->name('cities.create');
    Route::get('/cities/{id}', [\App\Http\Controllers\Admin\CityController::class, 'show'])->name('cities.show');
    Route::get('/cities/{id}/edit', [\App\Http\Controllers\Admin\CityController::class, 'edit'])->name('cities.edit');
});
