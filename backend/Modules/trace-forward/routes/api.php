<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TraceForward\Http\Controllers\TraceForwardController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::prefix('trace')->group(function () {
        Route::get('forward', [TraceForwardController::class, 'index']);
        Route::get('forward/detail', [TraceForwardController::class, 'traceDetail']);
        Route::get('forward/search', [TraceForwardController::class, 'search']);
    });
});
