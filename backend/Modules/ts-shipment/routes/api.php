<?php

use Illuminate\Support\Facades\Route;
use Modules\TsShipment\Http\Controllers\ShipmentEntryController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/shipment-entries')->group(function () {
        Route::get('/', [ShipmentEntryController::class, 'index']);
        Route::post('/', [ShipmentEntryController::class, 'store']);
        Route::delete('{id}', [ShipmentEntryController::class, 'destroy']);
    });
});
