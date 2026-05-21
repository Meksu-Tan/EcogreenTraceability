<?php

use Modules\Plant\Http\Controllers\PlantController;
use Modules\Plant\Http\Controllers\AdjustmentController;
use Modules\Plant\Http\Controllers\QuantifierController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::get('plants', [PlantController::class, 'index']);
    Route::post('plants', [PlantController::class, 'store']);
    Route::put('plants/{id}', [PlantController::class, 'update']);
    Route::delete('plants/{id}', [PlantController::class, 'destroy']);

    Route::get('adjustments', [AdjustmentController::class, 'index']);
    Route::post('adjustments', [AdjustmentController::class, 'store']);

    Route::get('quantifiers', [QuantifierController::class, 'index']);
    Route::post('quantifiers', [QuantifierController::class, 'store']);
});
