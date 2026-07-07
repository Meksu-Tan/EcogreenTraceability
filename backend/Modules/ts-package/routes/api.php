<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TsPackage\Http\Controllers\PackageEntryController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::prefix('transactions/package-entries')->group(function () {
        Route::get('/', [PackageEntryController::class, 'index']);
        Route::post('/', [PackageEntryController::class, 'store']);
        Route::delete('{id}', [PackageEntryController::class, 'destroy']);
        Route::put('po', [PackageEntryController::class, 'updatePo']);
        Route::put('batch', [PackageEntryController::class, 'updateBatch']);
        Route::put('subtank', [PackageEntryController::class, 'updateSubTank']);

        // Helper Dropdown Endpoints
        Route::get('active-fg-products', [PackageEntryController::class, 'getActiveFgProduct']);
        Route::get('wip-materials', [PackageEntryController::class, 'getWipMaterialByFgProduct']);
        Route::get('active-tanks', [PackageEntryController::class, 'getCmbActiveTankPck']);
        Route::get('active-warehouses', [PackageEntryController::class, 'getCmbActiveWarehousePck']);
        Route::get('specific-tanks', [PackageEntryController::class, 'getCmbActiveSpecificTank']);
        Route::get('new-trace-no', [PackageEntryController::class, 'newTraceNo']);
        Route::get('all-warehouses', [PackageEntryController::class, 'getAllWarehouses']);
    });
});
