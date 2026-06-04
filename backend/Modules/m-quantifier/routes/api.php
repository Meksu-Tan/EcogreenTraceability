<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Quantifier\Http\Controllers\QuantifierController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('master/quantifier')->group(function () {
        Route::get('/', [QuantifierController::class, 'index']);
        Route::get('flowmeters', [QuantifierController::class, 'getFlowmeters']);
        Route::get('{id}', [QuantifierController::class, 'show']);
        Route::post('/', [QuantifierController::class, 'store']);
        Route::post('{id}/activate', [QuantifierController::class, 'activate']);
        Route::post('{id}/deactivate', [QuantifierController::class, 'deactivate']);
    });
});
