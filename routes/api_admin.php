<?php

use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\VendorCommissionController;
use App\Http\Controllers\Api\Admin\VendorController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductPhotoController;
use App\Http\Controllers\Api\ProductReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| Routes for admin-only operations. All routes here are prefixed with
| /api/admin and protected by auth:sanctum + admin middleware.
|
*/

Route::apiResource('vendors', VendorController::class);
Route::get('vendors/{vendor}/commission-stats', [VendorCommissionController::class, 'show'])->name('vendors.commission-stats');
Route::post('vendors/{vendor}/commission-paid', [VendorCommissionController::class, 'updatePaidAmount'])->name('vendors.commission-paid');
Route::patch('vendors/{vendor}/toggle-active', [VendorController::class, 'toggleActive'])->name('vendors.toggle-active');
Route::apiResource('users', UserController::class);
Route::get('users/{user}/favourites', [UserController::class, 'favourites'])->name('users.favourites');
Route::get('products/{product}/reviews', [ProductReviewController::class, 'indexForAdmin'])->name('products.reviews.index');
Route::apiResource('products', ProductController::class);
Route::patch('products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle-active');
Route::patch('products/{product}/status', [ProductController::class, 'updateStatus'])->name('products.update-status');
Route::patch('products/{product}/photos/{photo}/set-primary', [ProductController::class, 'setPrimaryPhoto'])->name('products.set-primary-photo');
Route::apiResource('categories', \App\Http\Controllers\Api\Admin\CategoryController::class);
Route::apiResource('subcategories', \App\Http\Controllers\Api\Admin\SubcategoryController::class);
Route::apiResource('coupons', \App\Http\Controllers\Api\Admin\CouponController::class);
Route::get('orders', [\App\Http\Controllers\Api\Admin\OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{orderId}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'show'])->name('orders.show');
Route::patch('orders/{orderId}/complete', [\App\Http\Controllers\Api\Admin\OrderController::class, 'markCompleted'])->name('orders.complete');
Route::post('notifications/send', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'send'])->name('notifications.send');

Route::get('contact-messages', [\App\Http\Controllers\Api\Admin\ContactMessageController::class, 'index'])->name('contact-messages.index');
Route::get('contact-messages/{contactMessage}', [\App\Http\Controllers\Api\Admin\ContactMessageController::class, 'show'])->name('contact-messages.show');
Route::patch('contact-messages/{contactMessage}/reply', [\App\Http\Controllers\Api\Admin\ContactMessageController::class, 'reply'])->name('contact-messages.reply');

Route::get('footer-settings', [\App\Http\Controllers\Api\Admin\FooterSettingController::class, 'show'])->name('footer-settings.show');
Route::put('footer-settings', [\App\Http\Controllers\Api\Admin\FooterSettingController::class, 'update'])->name('footer-settings.update');

// Product Photos (separate API)
Route::get('products/{product}/photos', [ProductPhotoController::class, 'index'])->name('products.photos.index');
Route::post('products/{product}/photos', [ProductPhotoController::class, 'store'])->name('products.photos.store');
Route::post('products/{product}/photos/update', [ProductPhotoController::class, 'updatePhotos'])->name('products.photos.update');
Route::delete('products/{product}/photos/{photo}', [ProductPhotoController::class, 'destroy'])->name('products.photos.destroy');
Route::delete('products/{product}/photos', [ProductPhotoController::class, 'bulkDestroy'])->name('products.photos.bulk-destroy');
