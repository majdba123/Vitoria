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
    Route::get('products/import/template', [ProductController::class, 'importTemplate'])->name('products.import-template');
    Route::post('products/import', [ProductController::class, 'import'])->middleware('throttle:uploads')->name('products.import');
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
    Route::patch('orders/{orderId}/status', [\App\Http\Controllers\Api\Vendor\OrderController::class, 'updateStatus'])->middleware('throttle:api.write')->name('orders.status');
    Route::patch('orders/{orderId}/cancel', [\App\Http\Controllers\Api\Vendor\OrderController::class, 'cancel'])->middleware('throttle:api.write')->name('orders.cancel');
    Route::get('commission-stats', [\App\Http\Controllers\Api\Vendor\CommissionController::class, 'show'])->name('commission.stats');

    Route::get('returns', [\App\Http\Controllers\Api\Vendor\ReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{returnId}', [\App\Http\Controllers\Api\Vendor\ReturnController::class, 'show'])->name('returns.show');
    Route::patch('returns/{returnId}/status', [\App\Http\Controllers\Api\Vendor\ReturnController::class, 'updateStatus'])->middleware('throttle:api.write')->name('returns.status');
    Route::post('returns/{returnId}/refund', [\App\Http\Controllers\Api\Vendor\ReturnController::class, 'refund'])->middleware('throttle:api.write')->name('returns.refund');

    Route::get('invoices', [\App\Http\Controllers\Api\Vendor\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoiceId}', [\App\Http\Controllers\Api\Vendor\InvoiceController::class, 'show'])->name('invoices.show');

    Route::get('shipments', [\App\Http\Controllers\Api\Vendor\ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('shipments/{shipmentId}', [\App\Http\Controllers\Api\Vendor\ShipmentController::class, 'show'])->name('shipments.show');
    Route::patch('shipments/{shipmentId}/tracking', [\App\Http\Controllers\Api\Vendor\ShipmentController::class, 'updateTracking'])->middleware('throttle:api.write')->name('shipments.tracking');
    Route::patch('shipments/{shipmentId}/failed', [\App\Http\Controllers\Api\Vendor\ShipmentController::class, 'markFailed'])->middleware('throttle:api.write')->name('shipments.failed');
    Route::patch('shipments/{shipmentId}/returned', [\App\Http\Controllers\Api\Vendor\ShipmentController::class, 'markReturned'])->middleware('throttle:api.write')->name('shipments.returned');

    Route::get('ledger', [\App\Http\Controllers\Api\Vendor\LedgerController::class, 'index'])->name('ledger.index');
    Route::get('ledger/summary', [\App\Http\Controllers\Api\Vendor\LedgerController::class, 'summary'])->name('ledger.summary');

    Route::get('products/{product}/documents', [\App\Http\Controllers\Api\Vendor\ProductDocumentController::class, 'index'])->name('products.documents.index');
    Route::post('products/{product}/documents', [\App\Http\Controllers\Api\Vendor\ProductDocumentController::class, 'store'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('products.documents.store');
    Route::get('products/{product}/documents/{documentId}/download', [\App\Http\Controllers\Api\Vendor\ProductDocumentController::class, 'download'])->name('products.documents.download');
    Route::patch('products/{product}/documents/{documentId}/disable', [\App\Http\Controllers\Api\Vendor\ProductDocumentController::class, 'disable'])->middleware('throttle:api.write')->name('products.documents.disable');

    Route::get('documents', [\App\Http\Controllers\Api\Vendor\DocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [\App\Http\Controllers\Api\Vendor\DocumentController::class, 'store'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('documents.store');
    Route::get('documents/{documentId}', [\App\Http\Controllers\Api\Vendor\DocumentController::class, 'show'])->name('documents.show');
    Route::get('documents/{documentId}/download', [\App\Http\Controllers\Api\Vendor\DocumentController::class, 'download'])->name('documents.download');

    Route::get('staff', [\App\Http\Controllers\Api\Vendor\StaffController::class, 'index'])->name('staff.index');
    Route::post('staff', [\App\Http\Controllers\Api\Vendor\StaffController::class, 'store'])->middleware('throttle:api.write')->name('staff.store');
    Route::patch('staff/{memberId}', [\App\Http\Controllers\Api\Vendor\StaffController::class, 'update'])->middleware('throttle:api.write')->name('staff.update');
    Route::delete('staff/{memberId}', [\App\Http\Controllers\Api\Vendor\StaffController::class, 'destroy'])->middleware('throttle:api.write')->name('staff.destroy');

    Route::get('products/{product}/photos', [ProductPhotoController::class, 'index'])->name('products.photos.index');
    Route::post('products/{product}/photos', [ProductPhotoController::class, 'store'])->middleware('throttle:uploads')->name('products.photos.store');
    Route::post('products/{product}/photos/update', [ProductPhotoController::class, 'updatePhotos'])->middleware('throttle:uploads')->name('products.photos.update');
    Route::delete('products/{product}/photos/{photo}', [ProductPhotoController::class, 'destroy'])->middleware('throttle:api.write')->name('products.photos.destroy');
    Route::delete('products/{product}/photos', [ProductPhotoController::class, 'bulkDestroy'])->middleware('throttle:api.write')->name('products.photos.bulk-destroy');
});
