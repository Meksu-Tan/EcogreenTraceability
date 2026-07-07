<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TsBlending\Http\Controllers\BlendingController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::prefix('transactions/blendings')->group(function () {
        Route::get('/', [BlendingController::class, 'index']);
        Route::post('/', [BlendingController::class, 'store']);
        Route::delete('{id}', [BlendingController::class, 'destroy']);

        // REST Endpoints replacing monolithic store()
        Route::post('material', [BlendingController::class, 'storeMaterial']);
        Route::post('execute', [BlendingController::class, 'executeBlending']);
        Route::post('matl-doc', [BlendingController::class, 'createMatlDoc']);
        Route::post('update-sub-tank', [BlendingController::class, 'updateSubTank']);
        Route::delete('material/{id}', [BlendingController::class, 'deleteMaterial']);

        Route::get('active-materials', [BlendingController::class, 'activeMaterials']);
        Route::get('new-entry-no', [BlendingController::class, 'newEntryNo']);
        Route::get('total-stock-material', [BlendingController::class, 'totalStockMaterial']);
        Route::get('total-qty-material', [BlendingController::class, 'totalQtyMaterial']);
        Route::get('material-list', [BlendingController::class, 'materialList']);
        Route::get('active-tanks-rundown', [BlendingController::class, 'activeTanksRundown']);
        Route::get('active-specific-tanks-rundown', [BlendingController::class, 'activeSpecificTanksRundown']);

        Route::get('tanks', [BlendingController::class, 'tanks']);
        Route::get('tanks/{tankId}/details', [BlendingController::class, 'tankDetails']);
        Route::get('all-tanks', [BlendingController::class, 'allTanks']);
    });
});
