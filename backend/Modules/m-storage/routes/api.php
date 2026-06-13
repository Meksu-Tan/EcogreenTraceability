<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Storage\Http\Controllers\StorageController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::get('storage-tanks', [StorageController::class, 'indexTanks']);
    Route::post('storage-tanks', [StorageController::class, 'storeTank']);
    Route::put('storage-tanks/{id}', [StorageController::class, 'updateTank']);
    Route::delete('storage-tanks/{id}', [StorageController::class, 'destroyTank']);
    Route::get('storage-details', [StorageController::class, 'indexDetails']);
    Route::post('storage-details', [StorageController::class, 'storeDetail']);
    Route::put('storage-details/{id}', [StorageController::class, 'updateDetail']);
    Route::delete('storage-details/{id}', [StorageController::class, 'destroyDetail']);
    Route::get('warehouses', [StorageController::class, 'indexWarehouses']);
    Route::post('warehouses', [StorageController::class, 'storeWarehouse']);
    Route::put('warehouses/{id}', [StorageController::class, 'updateWarehouse']);
    Route::delete('warehouses/{id}', [StorageController::class, 'destroyWarehouse']);
});
