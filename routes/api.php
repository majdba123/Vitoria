<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\StartupPreferenceController;
use App\Services\SelectedProductTypeService;
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
| Contact (public submit)
|--------------------------------------------------------------------------
*/
Route::post('/contact', [\App\Http\Controllers\Api\ContactMessageController::class, 'store'])->middleware('throttle:auth.strict')->name('contact.store');

Route::get('/cities', [\App\Http\Controllers\Api\CityController::class, 'index'])->name('cities.index');

Route::middleware(['web', 'cache.response:120'])->group(function () {
    Route::prefix('vendors')->as('vendors.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VendorController::class, 'index'])->name('public.index');
        Route::get('/{vendor}', [\App\Http\Controllers\Api\VendorController::class, 'show'])->name('public.show');
    });

    Route::prefix('categories')->as('categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'index'])->name('public.index');
        Route::get('/{category}', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'show'])->name('public.show');
        Route::get('/{category}/subcategories', function (Request $request, \App\Models\Category $category, SelectedProductTypeService $selectedProductTypeService) {
            $selectedProductTypeService->abortIfTypeMismatch($request, $category->type);

            $subs = \Illuminate\Support\Facades\Cache::tags(['categories'])->remember(
                "cat:{$category->id}:subs:".($selectedProductTypeService->resolve($request) ?? 'all'),
                1800,
                fn () => $category->subcategories()->select('id', 'name', 'image', 'category_id')->get()
            );

            return response()->json(['data' => $subs]);
        })->name('public.subcategories');
    });

    Route::prefix('subcategories')->as('subcategories.')->group(function () {
        Route::get('/{subcategory}', function (Request $request, \App\Models\Subcategory $subcategory, SelectedProductTypeService $selectedProductTypeService) {
            $subcategory->loadMissing('category:id,name,type');
            $selectedProductTypeService->abortIfTypeMismatch($request, $subcategory->category?->type);

            $data = \Illuminate\Support\Facades\Cache::tags(['categories'])->remember(
                "subcategory:{$subcategory->id}:".($selectedProductTypeService->resolve($request) ?? 'all'),
                1800,
                function () use ($subcategory) {
                    $subcategory->load('category:id,name,type');

                    return $subcategory;
                }
            );

            return response()->json([
                'message' => __('Subcategory retrieved successfully.'),
                'data' => $data,
            ]);
        })->name('public.show');
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('products/{product}/reviews', [\App\Http\Controllers\Api\ProductReviewController::class, 'store'])->name('products.reviews.store');
    Route::delete('products/{product}/reviews/{review}', [\App\Http\Controllers\Api\ProductReviewController::class, 'destroy'])->name('products.reviews.destroy');

    Route::post('broadcasting/auth', [BroadcastController::class, 'authenticate'])->name('broadcasting.auth');

    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $user?->loadMissing('syndicate');

        return new \App\Http\Resources\Auth\UserResource($user);
    })->name('user');

    Route::post('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('favourites')->as('favourites.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\FavouriteController::class, 'index'])->name('index');
        Route::get('/ids', [\App\Http\Controllers\Api\FavouriteController::class, 'ids'])->name('ids');
        Route::post('/{product}', [\App\Http\Controllers\Api\FavouriteController::class, 'toggle'])->name('toggle');
        Route::delete('/{product}', [\App\Http\Controllers\Api\FavouriteController::class, 'destroy'])->name('destroy');
    });

    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderId}', [\App\Http\Controllers\Api\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{orderId}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/checkout', [\App\Http\Controllers\Api\OrderController::class, 'store'])->middleware('throttle:orders.write')->name('orders.checkout');

    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    Route::get('/contact-messages', [\App\Http\Controllers\Api\ContactMessageController::class, 'index'])->name('contact-messages.index');
});
