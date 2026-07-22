<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductPhotoController;
use App\Http\Controllers\Api\ProductReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor API Routes
|--------------------------------------------------------------------------
|
| Routes for vendor-only operations. All routes here are prefixed with
| /api/vendor and protected by auth:sanctum + vendor middleware.
|
*/

Route::middleware('throttle:api.authenticated')->group(function () {
    Route::get('products/{product}/reviews', [ProductReviewController::class, 'indexForVendor'])->name('products.reviews.index');
    Route::post('products/store-basic', [ProductController::class, 'storeBasic'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('products.store-basic');
    Route::post('products/store-agriculture', [ProductController::class, 'storeAgriculture'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('products.store-agriculture');
    Route::post('products/store-veterinary', [ProductController::class, 'storeVeterinary'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('products.store-veterinary');
    Route::apiResource('products', ProductController::class)->except(['index', 'show'])->middleware(['throttle:api.write', 'throttle:uploads']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::patch('products/{product}/photos/{photo}/set-primary', [ProductController::class, 'setPrimaryPhoto'])->middleware('throttle:api.write')->name('products.set-primary-photo');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('allowed-categories', [\App\Http\Controllers\Api\Vendor\VendorCategoryController::class, 'index'])->name('allowed-categories.index');

    Route::get('profile', [\App\Http\Controllers\Api\Vendor\VendorProfileController::class, 'show'])->name('profile.show');
    Route::post('profile', [\App\Http\Controllers\Api\Vendor\VendorProfileController::class, 'update'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('profile.update');
    Route::get('orders', [\App\Http\Controllers\Api\Vendor\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{orderId}', [\App\Http\Controllers\Api\Vendor\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{orderId}/cancel', [\App\Http\Controllers\Api\Vendor\OrderController::class, 'cancel'])->middleware('throttle:api.write')->name('orders.cancel');
    Route::get('commission-stats', [\App\Http\Controllers\Api\Vendor\CommissionController::class, 'show'])->name('commission.stats');

    Route::get('products/{product}/photos', [ProductPhotoController::class, 'index'])->name('products.photos.index');
    Route::post('products/{product}/photos', [ProductPhotoController::class, 'store'])->middleware('throttle:uploads')->name('products.photos.store');
    Route::post('products/{product}/photos/update', [ProductPhotoController::class, 'updatePhotos'])->middleware('throttle:uploads')->name('products.photos.update');
    Route::delete('products/{product}/photos/{photo}', [ProductPhotoController::class, 'destroy'])->middleware('throttle:api.write')->name('products.photos.destroy');
    Route::delete('products/{product}/photos', [ProductPhotoController::class, 'bulkDestroy'])->middleware('throttle:api.write')->name('products.photos.bulk-destroy');
});
