<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\DashboardController;
use Modules\Dashboard\Http\Controllers\TraceController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('trace/forward/{id}', [TraceController::class, 'forward']);
    Route::get('trace/backward/{id}', [TraceController::class, 'backward']);
});
