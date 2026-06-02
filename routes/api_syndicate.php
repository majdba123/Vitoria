<?php

use App\Http\Controllers\Api\Syndicate\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('overview', [DashboardController::class, 'overview'])->middleware('throttle:dashboard.stats')->name('overview');
Route::get('categories', [DashboardController::class, 'categories'])->name('categories');
Route::get('vendors', [DashboardController::class, 'vendors'])->name('vendors');
Route::get('products', [DashboardController::class, 'products'])->name('products');
Route::get('orders', [DashboardController::class, 'orders'])->name('orders');
Route::get('podcasts', [DashboardController::class, 'podcasts'])->name('podcasts');
Route::get('reports', [DashboardController::class, 'reports'])->name('reports');
