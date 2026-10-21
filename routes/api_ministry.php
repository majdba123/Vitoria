<?php

use App\Http\Controllers\Api\ImportShipmentController;
use App\Http\Controllers\Api\Ministry\DashboardController;
use App\Http\Controllers\Api\Ministry\VendorAnalyticsController;
use App\Http\Controllers\Api\PortController;
use Illuminate\Support\Facades\Route;

/*
| Ministry workspace: the syndicate read API over agriculture, veterinary or
| both (`?sector=`). Read-only by design — no write routes are registered here.
*/
Route::middleware('throttle:api.authenticated')->group(function () {
    Route::get('overview', [DashboardController::class, 'overview'])->middleware('throttle:dashboard.stats')->name('overview');
    Route::get('categories', [DashboardController::class, 'categories'])->name('categories');
    Route::get('vendors', [DashboardController::class, 'vendors'])->name('vendors');
    Route::get('vendors/map', [DashboardController::class, 'vendorsMap'])->name('vendors.map');
    Route::get('vendors/{vendor}/analytics/overview', [VendorAnalyticsController::class, 'overview'])->name('vendors.analytics.overview');
    Route::get('vendors/{vendor}/analytics/products', [VendorAnalyticsController::class, 'products'])->name('vendors.analytics.products');
    Route::get('vendors/{vendor}/analytics/orders', [VendorAnalyticsController::class, 'orders'])->name('vendors.analytics.orders');
    Route::get('vendors/{vendor}/analytics/returns', [VendorAnalyticsController::class, 'returns'])->name('vendors.analytics.returns');
    Route::get('products', [DashboardController::class, 'products'])->name('products');
    Route::get('products/{product}', [DashboardController::class, 'product'])->whereNumber('product')->name('products.show');
    Route::get('orders', [DashboardController::class, 'orders'])->name('orders');
    Route::get('orders/{order}', [DashboardController::class, 'order'])->whereNumber('order')->name('orders.show');
    Route::get('reports', [DashboardController::class, 'reports'])->name('reports');

    Route::get('ports', [PortController::class, 'index'])->name('ports.index');
    Route::get('import-shipments/options', [ImportShipmentController::class, 'options'])->name('import-shipments.options');
    Route::get('import-shipments/stats', [ImportShipmentController::class, 'stats'])->middleware('throttle:dashboard.stats')->name('import-shipments.stats');
    Route::get('import-shipments', [ImportShipmentController::class, 'index'])->name('import-shipments.index');
    Route::get('import-shipments/{importShipment}', [ImportShipmentController::class, 'show'])->whereNumber('importShipment')->name('import-shipments.show');
});
