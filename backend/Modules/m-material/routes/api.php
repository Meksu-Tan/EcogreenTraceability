<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Material\Http\Controllers\MaterialController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::get('qty/fetch', [MaterialController::class, 'fetchQty']);
    Route::get('materials', [MaterialController::class, 'index']);
    Route::post('materials', [MaterialController::class, 'store']);
    Route::put('materials/{id}', [MaterialController::class, 'update']);
    Route::delete('materials/{id}', [MaterialController::class, 'destroy']);

    Route::get('material-packagings/source-products', [MaterialController::class, 'sourceProducts']);
    Route::get('material-packagings', [MaterialController::class, 'indexPackaging']);
    Route::post('material-packagings', [MaterialController::class, 'storePackaging']);
    Route::put('material-packagings/{id}', [MaterialController::class, 'updatePackaging']);
    Route::delete('material-packagings/{id}', [MaterialController::class, 'destroyPackaging']);
});
