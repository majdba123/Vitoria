<?php

use App\Http\Controllers\Api\ImportShipmentController;
use App\Http\Controllers\Api\PortController;
use Illuminate\Support\Facades\Route;

/*
| Import shipments (الشحنات) — shared by the admin, employee and syndicate API
| groups. The surrounding group supplies auth + role middleware and the name
| prefix; ImportShipment::scopeVisibleTo() scopes syndicates to their own rows.
*/
Route::get('ports', [PortController::class, 'index'])->name('ports.index');
Route::get('import-shipments/options', [ImportShipmentController::class, 'options'])->name('import-shipments.options');
Route::get('import-shipments/stats', [ImportShipmentController::class, 'stats'])->middleware('throttle:dashboard.stats')->name('import-shipments.stats');
Route::get('import-shipments', [ImportShipmentController::class, 'index'])->name('import-shipments.index');
Route::get('import-shipments/{importShipment}', [ImportShipmentController::class, 'show'])->whereNumber('importShipment')->name('import-shipments.show');
Route::middleware('throttle:api.write')->group(function () {
    Route::post('import-shipments', [ImportShipmentController::class, 'store'])->name('import-shipments.store');
    Route::put('import-shipments/{importShipment}', [ImportShipmentController::class, 'update'])->whereNumber('importShipment')->name('import-shipments.update');
    Route::delete('import-shipments/{importShipment}', [ImportShipmentController::class, 'destroy'])->whereNumber('importShipment')->name('import-shipments.destroy');
});
