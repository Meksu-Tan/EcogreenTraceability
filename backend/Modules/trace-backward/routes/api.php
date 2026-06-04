<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\TraceBackward\Http\Controllers\TraceBackwardController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('trace')->group(function () {
        Route::get('backward', [TraceBackwardController::class, 'index']);
        Route::get('backward/detail', [TraceBackwardController::class, 'traceDetail']);
        Route::get('backward/search', [TraceBackwardController::class, 'search']);
        Route::get('backward/{traceNo}', [TraceBackwardController::class, 'backward']);
    });
});
