<?php

use App\Http\Controllers\Api\Employee\OrderController as EmployeeOrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api.authenticated')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::put('products/{product}', [ProductController::class, 'update'])->middleware(['throttle:api.write', 'throttle:uploads'])->name('products.update');

    // Gated by hasEmployeePermission('orders.view'), not the blanket employee
    // middleware — only employees whose assigned role grants order visibility
    // may reach these (stakeholder review #24).
    Route::get('orders', [EmployeeOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{orderId}', [EmployeeOrderController::class, 'show'])->name('orders.show');
});
