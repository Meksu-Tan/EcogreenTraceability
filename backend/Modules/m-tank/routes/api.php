<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Tank\Http\Controllers\TankController;
use Modules\Tank\Http\Controllers\WarehouseController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::get('tanks', [TankController::class, 'index']);
    Route::get('tanks/last-sync', [TankController::class, 'lastSync']);
    Route::post('tanks/sync', [TankController::class, 'sync']);
    Route::post('tanks', [TankController::class, 'store']);
    Route::put('tanks/{id}', [TankController::class, 'update']);
    Route::delete('tanks/{id}', [TankController::class, 'destroy']);

    Route::get('warehouses', [WarehouseController::class, 'index']);
    Route::post('warehouses', [WarehouseController::class, 'store']);
    Route::put('warehouses/{id}', [WarehouseController::class, 'update']);
    Route::delete('warehouses/{id}', [WarehouseController::class, 'destroy']);
});
