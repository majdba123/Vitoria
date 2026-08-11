<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\StartupPreferenceController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->as('auth.')->middleware('web')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth.strict')->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth.strict')->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
});

Route::middleware('web')->prefix('startup')->as('startup.')->group(function () {
    Route::get('/preferences', [StartupPreferenceController::class, 'show'])->middleware('throttle:public.browse')->name('preferences.show');
    Route::post('/preferences', [StartupPreferenceController::class, 'store'])->middleware('throttle:auth.strict')->name('preferences.store');
});

/*
|--------------------------------------------------------------------------
| Public Product Routes (for clients/users)
|--------------------------------------------------------------------------
*/
Route::middleware('web')->prefix('products')->as('products.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ProductController::class, 'publicIndex'])->middleware('throttle:search.filters')->name('public.index');
    Route::get('/{product}/reviews', [\App\Http\Controllers\Api\ProductReviewController::class, 'index'])->name('reviews.index');
    Route::get('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'publicShow'])->name('public.show');
});

/*
|--------------------------------------------------------------------------
| Cart (guest + authenticated)
|--------------------------------------------------------------------------
|
| Deliberately outside the auth group: guests must be able to build a cart
| before signing in, and it is merged into their account at login (spec §5).
| Guests are identified by the web session, so the `web` middleware — and the
| session it establishes — is required here.
|
*/
Route::middleware('web')->prefix('cart')->as('cart.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\CartController::class, 'show'])->name('show');
    Route::post('/items', [\App\Http\Controllers\Api\CartController::class, 'store'])->middleware('throttle:api.write')->name('items.store');
    Route::patch('/items', [\App\Http\Controllers\Api\CartController::class, 'update'])->middleware('throttle:api.write')->name('items.update');
    Route::delete('/items/{productId}', [\App\Http\Controllers\Api\CartController::class, 'destroy'])->middleware('throttle:api.write')->name('items.destroy');
    Route::delete('/', [\App\Http\Controllers\Api\CartController::class, 'clear'])->middleware('throttle:api.write')->name('clear');
    Route::post('/coupon', [\App\Http\Controllers\Api\CartController::class, 'applyCoupon'])->middleware('throttle:api.write')->name('coupon.apply');
    Route::delete('/coupon', [\App\Http\Controllers\Api\CartController::class, 'removeCoupon'])->middleware('throttle:api.write')->name('coupon.remove');
});

/*
|--------------------------------------------------------------------------
| Contact (public submit)
|--------------------------------------------------------------------------
*/
Route::post('/contact', [\App\Http\Controllers\Api\ContactMessageController::class, 'store'])->middleware('throttle:auth.strict')->name('contact.store');

Route::get('/cities', [\App\Http\Controllers\Api\CityController::class, 'index'])->middleware('throttle:public.browse')->name('cities.index');

Route::middleware(['web', 'cache.response:120'])->group(function () {
    Route::prefix('vendors')->as('vendors.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VendorController::class, 'index'])->name('public.index');
        Route::get('/{vendor}', [\App\Http\Controllers\Api\VendorController::class, 'show'])->name('public.show');
    });

    Route::prefix('categories')->as('categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'index'])->name('public.index');
        Route::get('/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'show'])->name('public.show');
    });
});

Route::prefix('external/products')->as('external.products.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ProductController::class, 'externalIndex'])->name('index');
    Route::get('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'externalShow'])->name('show');
});

Route::middleware(['auth:sanctum', 'throttle:api.authenticated'])->prefix('ai/products')->as('ai.products.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\AiProductController::class, 'index'])->name('index');
    Route::get('/agriculture', [\App\Http\Controllers\Api\AiProductController::class, 'agricultureIndex'])->name('agriculture');
    Route::get('/veterinary', [\App\Http\Controllers\Api\AiProductController::class, 'veterinaryIndex'])->name('veterinary');
    Route::get('/{product}', [\App\Http\Controllers\Api\AiProductController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:api.authenticated'])->group(function () {
    Route::post('products/{product}/reviews', [\App\Http\Controllers\Api\ProductReviewController::class, 'store'])->middleware('throttle:api.write')->name('products.reviews.store');
    Route::delete('products/{product}/reviews/{review}', [\App\Http\Controllers\Api\ProductReviewController::class, 'destroy'])->middleware('throttle:api.write')->name('products.reviews.destroy');

    Route::post('broadcasting/auth', [BroadcastController::class, 'authenticate'])->middleware('throttle:api.write')->name('broadcasting.auth');

    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $user?->loadMissing('syndicate');

        return new \App\Http\Resources\Auth\UserResource($user);
    })->name('user');

    Route::post('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('profile.update');

    Route::prefix('favourites')->as('favourites.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\FavouriteController::class, 'index'])->name('index');
        Route::get('/ids', [\App\Http\Controllers\Api\FavouriteController::class, 'ids'])->name('ids');
        Route::post('/{product}', [\App\Http\Controllers\Api\FavouriteController::class, 'toggle'])->middleware('throttle:api.write')->name('toggle');
        Route::delete('/{product}', [\App\Http\Controllers\Api\FavouriteController::class, 'destroy'])->middleware('throttle:api.write')->name('destroy');
    });

    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderId}', [\App\Http\Controllers\Api\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{orderId}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel'])->middleware('throttle:api.write')->name('orders.cancel');
    Route::post('/orders/{orderId}/returns', [\App\Http\Controllers\Api\OrderReturnController::class, 'store'])->middleware('throttle:orders.write')->name('orders.returns.store');
    // DEPRECATED: client-supplied items[]. Use POST /api/checkout instead.
    Route::post('/orders/checkout', [\App\Http\Controllers\Api\OrderController::class, 'store'])->middleware('throttle:orders.write')->name('orders.checkout');

    Route::prefix('returns')->as('returns.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\OrderReturnController::class, 'index'])->name('index');
        Route::get('/{returnId}', [\App\Http\Controllers\Api\OrderReturnController::class, 'show'])->name('show');
        Route::patch('/{returnId}/cancel', [\App\Http\Controllers\Api\OrderReturnController::class, 'cancel'])->middleware('throttle:api.write')->name('cancel');
    });

    Route::prefix('addresses')->as('addresses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\UserAddressController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\UserAddressController::class, 'store'])->middleware('throttle:api.write')->name('store');
        Route::patch('/{address}', [\App\Http\Controllers\Api\UserAddressController::class, 'update'])->middleware('throttle:api.write')->name('update');
        Route::delete('/{address}', [\App\Http\Controllers\Api\UserAddressController::class, 'destroy'])->middleware('throttle:api.write')->name('destroy');
        Route::patch('/{address}/default', [\App\Http\Controllers\Api\UserAddressController::class, 'setDefault'])->middleware('throttle:api.write')->name('default');
    });

    Route::get('/checkout/summary', [\App\Http\Controllers\Api\CheckoutController::class, 'summary'])->name('checkout.summary');
    Route::post('/checkout', [\App\Http\Controllers\Api\CheckoutController::class, 'store'])->middleware('throttle:orders.write')->name('checkout.store');

    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead'])->middleware('throttle:notifications.write')->name('notifications.read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead'])->middleware('throttle:notifications.write')->name('notifications.mark-all-read');

    Route::get('/contact-messages', [\App\Http\Controllers\Api\ContactMessageController::class, 'index'])->name('contact-messages.index');
});
