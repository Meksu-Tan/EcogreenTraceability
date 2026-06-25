<?php
declare(strict_types=1);
use Modules\Manufacturer\Http\Controllers\ManufacturerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::get('manufacturers/active', [ManufacturerController::class, 'active']);
    Route::get('manufacturers', [ManufacturerController::class, 'index']);
    Route::post('manufacturers', [ManufacturerController::class, 'store']);
    Route::put('manufacturers/{id}', [ManufacturerController::class, 'update']);
    Route::delete('manufacturers/{id}', [ManufacturerController::class, 'destroy']);
});
