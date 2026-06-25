<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\DashboardController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    // Trace routes have been moved to dedicated modules:
    // - trace-forward: /api/v1/trace/forward/{traceNo}
    // - trace-backward: /api/v1/trace/backward/{traceNo}
});
