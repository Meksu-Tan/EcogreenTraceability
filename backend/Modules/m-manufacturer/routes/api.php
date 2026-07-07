<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Manufacturer\Http\Controllers\ManufacturerController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::get('manufacturers/active', [ManufacturerController::class, 'active']);
    Route::get('manufacturers', [ManufacturerController::class, 'index']);
    Route::post('manufacturers', [ManufacturerController::class, 'store']);
    Route::put('manufacturers/{id}', [ManufacturerController::class, 'update']);
    Route::delete('manufacturers/{id}', [ManufacturerController::class, 'destroy']);
});
