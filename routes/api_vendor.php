<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\SubcategoryController;
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

Route::get('products/{product}/reviews', [ProductReviewController::class, 'indexForVendor'])->name('products.reviews.index');
Route::apiResource('products', ProductController::class);
Route::patch('products/{product}/photos/{photo}/set-primary', [ProductController::class, 'setPrimaryPhoto'])->name('products.set-primary-photo');
Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('allowed-categories', [\App\Http\Controllers\Api\Vendor\VendorCategoryController::class, 'index'])->name('allowed-categories.index');

// Vendor profile
Route::get('profile', [\App\Http\Controllers\Api\Vendor\VendorProfileController::class, 'show'])->name('profile.show');
Route::post('profile', [\App\Http\Controllers\Api\Vendor\VendorProfileController::class, 'update'])->name('profile.update');
Route::get('subcategories', [SubcategoryController::class, 'index'])->name('subcategories.index');
Route::get('orders', [\App\Http\Controllers\Api\Vendor\OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{orderId}', [\App\Http\Controllers\Api\Vendor\OrderController::class, 'show'])->name('orders.show');
Route::patch('orders/{orderId}/cancel', [\App\Http\Controllers\Api\Vendor\OrderController::class, 'cancel'])->name('orders.cancel');
Route::get('commission-stats', [\App\Http\Controllers\Api\Vendor\CommissionController::class, 'show'])->name('commission.stats');

// Product Photos (separate API)
Route::get('products/{product}/photos', [ProductPhotoController::class, 'index'])->name('products.photos.index');
Route::post('products/{product}/photos', [ProductPhotoController::class, 'store'])->name('products.photos.store');
Route::post('products/{product}/photos/update', [ProductPhotoController::class, 'updatePhotos'])->name('products.photos.update');
Route::delete('products/{product}/photos/{photo}', [ProductPhotoController::class, 'destroy'])->name('products.photos.destroy');
Route::delete('products/{product}/photos', [ProductPhotoController::class, 'bulkDestroy'])->name('products.photos.bulk-destroy');
