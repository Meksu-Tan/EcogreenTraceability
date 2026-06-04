<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Plant\Http\Controllers\PlantController;
use Modules\Plant\Http\Controllers\QuantifierController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    // Plant Management
    Route::get('plants', [PlantController::class, 'index']);
    Route::post('plants', [PlantController::class, 'store']);
    Route::put('plants/{id}', [PlantController::class, 'update']);
    Route::delete('plants/{id}', [PlantController::class, 'destroy']);

    // Adjustment moved to m-adjustment module
    // Quantifier moved to m-quantifier module
});
