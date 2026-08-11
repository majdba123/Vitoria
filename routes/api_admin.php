<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\SyndicateController;
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

Route::middleware('throttle:api.authenticated')->group(function () {
    Route::get('dashboard/vendor-category-stats', [DashboardController::class, 'vendorCategoryStats'])->middleware('throttle:dashboard.stats')->name('dashboard.vendor-category-stats');
    Route::get('dashboard/overview', [DashboardController::class, 'overview'])->middleware('throttle:dashboard.stats')->name('dashboard.overview');
    Route::get('cities/import/template', [\App\Http\Controllers\Api\Admin\CityController::class, 'importTemplate'])->name('cities.import-template');
    Route::post('cities/import', [\App\Http\Controllers\Api\Admin\CityController::class, 'import'])->middleware('throttle:uploads')->name('cities.import');
    Route::get('categories/import/template', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'importTemplate'])->name('categories.import-template');
    Route::post('categories/import', [\App\Http\Controllers\Api\Admin\CategoryController::class, 'import'])->middleware('throttle:uploads')->name('categories.import');
    Route::get('subcategories/import/template', [\App\Http\Controllers\Api\Admin\SubcategoryController::class, 'importTemplate'])->name('subcategories.import-template');
    Route::post('subcategories/import', [\App\Http\Controllers\Api\Admin\SubcategoryController::class, 'import'])->middleware('throttle:uploads')->name('subcategories.import');
    Route::get('products/import/template', [ProductController::class, 'importTemplate'])->name('products.import-template');
    Route::post('products/import', [ProductController::class, 'import'])->middleware('throttle:uploads')->name('products.import');
    Route::patch('syndicates/{syndicate}/toggle-active', [SyndicateController::class, 'toggleActive'])->middleware('throttle:api.write')->name('syndicates.toggle-active');
    Route::apiResource('syndicates', SyndicateController::class)->except(['index', 'show'])->middleware('throttle:api.write');
    Route::apiResource('syndicates', SyndicateController::class)->only(['index', 'show']);
    Route::apiResource('vendors', VendorController::class)->except(['index', 'show'])->middleware('throttle:api.write');
    Route::apiResource('vendors', VendorController::class)->only(['index', 'show']);
    Route::get('vendors/{vendor}/commission-stats', [VendorCommissionController::class, 'show'])->name('vendors.commission-stats');
    Route::post('vendors/{vendor}/commission-paid', [VendorCommissionController::class, 'updatePaidAmount'])->middleware('throttle:api.write')->name('vendors.commission-paid');
    Route::get('vendors/{vendor}/commercial-register', [VendorController::class, 'downloadCommercialRegister'])->name('vendors.commercial-register');
    Route::patch('vendors/{vendor}/approve', [VendorController::class, 'approve'])->middleware('throttle:api.write')->name('vendors.approve');
    Route::patch('vendors/{vendor}/toggle-active', [VendorController::class, 'toggleActive'])->middleware('throttle:api.write')->name('vendors.toggle-active');
    Route::apiResource('users', UserController::class)->except(['index', 'show'])->middleware('throttle:api.write');
    Route::apiResource('users', UserController::class)->only(['index', 'show']);
    Route::get('users/{user}/favourites', [UserController::class, 'favourites'])->name('users.favourites');
    Route::get('products/{product}/reviews', [ProductReviewController::class, 'indexForAdmin'])->name('products.reviews.index');
    Route::post('products/store-basic', [ProductController::class, 'storeBasic'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('products.store-basic');
    Route::post('products/store-agriculture', [ProductController::class, 'storeAgriculture'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('products.store-agriculture');
    Route::post('products/store-veterinary', [ProductController::class, 'storeVeterinary'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('products.store-veterinary');
    Route::apiResource('products', ProductController::class)->except(['index', 'show'])->middleware(['throttle:api.write', 'throttle:uploads']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::patch('products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->middleware('throttle:api.write')->name('products.toggle-active');
    Route::patch('products/{product}/status', [ProductController::class, 'updateStatus'])->middleware('throttle:api.write')->name('products.update-status');
    Route::patch('products/{product}/photos/{photo}/set-primary', [ProductController::class, 'setPrimaryPhoto'])->middleware('throttle:api.write')->name('products.set-primary-photo');
    Route::apiResource('categories', \App\Http\Controllers\Api\Admin\CategoryController::class)->except(['index', 'show'])->middleware(['throttle:api.write', 'throttle:uploads']);
    Route::apiResource('categories', \App\Http\Controllers\Api\Admin\CategoryController::class)->only(['index', 'show']);
    Route::apiResource('subcategories', \App\Http\Controllers\Api\Admin\SubcategoryController::class)->except(['index', 'show'])->middleware('throttle:api.write');
    Route::apiResource('subcategories', \App\Http\Controllers\Api\Admin\SubcategoryController::class)->only(['index', 'show']);
    Route::apiResource('cities', \App\Http\Controllers\Api\Admin\CityController::class)->except(['index', 'show'])->middleware('throttle:api.write');
    Route::apiResource('cities', \App\Http\Controllers\Api\Admin\CityController::class)->only(['index', 'show']);
    Route::apiResource('coupons', \App\Http\Controllers\Api\Admin\CouponController::class)->except(['index', 'show'])->middleware('throttle:api.write');
    Route::apiResource('coupons', \App\Http\Controllers\Api\Admin\CouponController::class)->only(['index', 'show']);
    Route::get('orders', [\App\Http\Controllers\Api\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{orderId}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{orderId}/complete', [\App\Http\Controllers\Api\Admin\OrderController::class, 'markCompleted'])->middleware('throttle:api.write')->name('orders.complete');

    Route::get('returns', [\App\Http\Controllers\Api\Admin\ReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{returnId}', [\App\Http\Controllers\Api\Admin\ReturnController::class, 'show'])->name('returns.show');
    Route::patch('returns/{returnId}/status', [\App\Http\Controllers\Api\Admin\ReturnController::class, 'updateStatus'])->middleware('throttle:api.write')->name('returns.status');
    Route::post('returns/{returnId}/refund', [\App\Http\Controllers\Api\Admin\ReturnController::class, 'refund'])->middleware('throttle:api.write')->name('returns.refund');

    Route::get('refunds', [\App\Http\Controllers\Api\Admin\RefundController::class, 'index'])->name('refunds.index');
    Route::get('refunds/{refundId}', [\App\Http\Controllers\Api\Admin\RefundController::class, 'show'])->name('refunds.show');
    Route::post('refunds', [\App\Http\Controllers\Api\Admin\RefundController::class, 'store'])->middleware('throttle:api.write')->name('refunds.store');
    Route::patch('refunds/{refundId}/complete', [\App\Http\Controllers\Api\Admin\RefundController::class, 'complete'])->middleware('throttle:api.write')->name('refunds.complete');
    Route::patch('refunds/{refundId}/cancel', [\App\Http\Controllers\Api\Admin\RefundController::class, 'cancel'])->middleware('throttle:api.write')->name('refunds.cancel');
    Route::post('notifications/send', [\App\Http\Controllers\Api\Admin\NotificationController::class, 'send'])->middleware('throttle:notifications.write')->name('notifications.send');

    Route::get('contact-messages', [\App\Http\Controllers\Api\Admin\ContactMessageController::class, 'index'])->name('contact-messages.index');
    Route::get('contact-messages/{contactMessage}', [\App\Http\Controllers\Api\Admin\ContactMessageController::class, 'show'])->name('contact-messages.show');
    Route::patch('contact-messages/{contactMessage}/reply', [\App\Http\Controllers\Api\Admin\ContactMessageController::class, 'reply'])->middleware('throttle:api.write')->name('contact-messages.reply');

    Route::get('footer-settings', [\App\Http\Controllers\Api\Admin\FooterSettingController::class, 'show'])->name('footer-settings.show');
    Route::put('footer-settings', [\App\Http\Controllers\Api\Admin\FooterSettingController::class, 'update'])->middleware('throttle:api.write')->name('footer-settings.update');

    Route::get('products/{product}/photos', [ProductPhotoController::class, 'index'])->name('products.photos.index');
    Route::post('products/{product}/photos', [ProductPhotoController::class, 'store'])->middleware('throttle:uploads')->name('products.photos.store');
    Route::post('products/{product}/photos/update', [ProductPhotoController::class, 'updatePhotos'])->middleware('throttle:uploads')->name('products.photos.update');
    Route::delete('products/{product}/photos/{photo}', [ProductPhotoController::class, 'destroy'])->middleware('throttle:api.write')->name('products.photos.destroy');
    Route::delete('products/{product}/photos', [ProductPhotoController::class, 'bulkDestroy'])->middleware('throttle:api.write')->name('products.photos.bulk-destroy');
});
