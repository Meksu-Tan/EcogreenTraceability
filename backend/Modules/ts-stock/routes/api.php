<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TsStock\Http\Controllers\StockController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::prefix('transactions/stock')->group(function () {
        Route::get('/', [StockController::class, 'index']);
        Route::get('active-materials', [StockController::class, 'getActiveMaterials']);
        Route::get('active-slocs', [StockController::class, 'getActiveSlocs']);
        Route::get('movements', [StockController::class, 'getMovements']);
        Route::get('{id}', [StockController::class, 'show']);
    });
});
