<?php

use Illuminate\Support\Facades\Route;
use Modules\TsBlending\Http\Controllers\BlendingController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/blendings')->group(function () {
        Route::get('/', [BlendingController::class, 'index']);
        Route::post('/', [BlendingController::class, 'store']);
        Route::delete('{id}', [BlendingController::class, 'destroy']);
    });
});
