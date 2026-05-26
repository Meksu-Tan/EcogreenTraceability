<?php

use Illuminate\Support\Facades\Route;
use Modules\TsBlending\Http\Controllers\BlendingController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/blendings')->group(function () {
        Route::get('/', [BlendingController::class, 'index']);
        Route::post('/', [BlendingController::class, 'store']);
        Route::delete('{id}', [BlendingController::class, 'destroy']);

        Route::get('active-materials', [BlendingController::class, 'activeMaterials']);
        Route::get('new-entry-no', [BlendingController::class, 'newEntryNo']);
        Route::get('total-stock-material', [BlendingController::class, 'totalStockMaterial']);
        Route::get('total-qty-material', [BlendingController::class, 'totalQtyMaterial']);
        Route::get('material-list', [BlendingController::class, 'materialList']);
        Route::get('active-tanks-rundown', [BlendingController::class, 'activeTanksRundown']);
        Route::get('active-specific-tanks-rundown', [BlendingController::class, 'activeSpecificTanksRundown']);
    });
});
