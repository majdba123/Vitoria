<?php

use App\Http\Controllers\Api\Syndicate\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api.authenticated')->group(function () {
    Route::get('overview', [DashboardController::class, 'overview'])->middleware('throttle:dashboard.stats')->name('overview');
    Route::get('categories', [DashboardController::class, 'categories'])->name('categories');
    Route::get('vendors', [DashboardController::class, 'vendors'])->name('vendors');
    Route::get('vendors/map', [DashboardController::class, 'vendorsMap'])->name('vendors.map');
    Route::get('vendors/{vendor}/analytics/overview', [\App\Http\Controllers\Api\Syndicate\VendorAnalyticsController::class, 'overview'])->name('vendors.analytics.overview');
    Route::get('vendors/{vendor}/analytics/products', [\App\Http\Controllers\Api\Syndicate\VendorAnalyticsController::class, 'products'])->name('vendors.analytics.products');
    Route::get('vendors/{vendor}/analytics/orders', [\App\Http\Controllers\Api\Syndicate\VendorAnalyticsController::class, 'orders'])->name('vendors.analytics.orders');
    Route::get('vendors/{vendor}/analytics/returns', [\App\Http\Controllers\Api\Syndicate\VendorAnalyticsController::class, 'returns'])->name('vendors.analytics.returns');
    Route::get('vendors/{vendor}/report.pdf', [\App\Http\Controllers\Api\Syndicate\VendorAnalyticsController::class, 'report'])->middleware('throttle:dashboard.stats')->name('vendors.report');
    Route::get('products', [DashboardController::class, 'products'])->name('products');
    Route::get('orders', [DashboardController::class, 'orders'])->name('orders');
    Route::get('podcasts', [DashboardController::class, 'podcasts'])->name('podcasts');
    Route::get('reports', [DashboardController::class, 'reports'])->name('reports');
    Route::get('reports.pdf', [DashboardController::class, 'reportPdf'])->middleware('throttle:dashboard.stats')->name('reports.pdf');
});
