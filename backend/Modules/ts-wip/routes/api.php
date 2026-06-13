<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TsWip\Http\Controllers\WipEntryController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::prefix('transactions/wip-entries')->group(function () {
        Route::get('/', [WipEntryController::class, 'index']);
        Route::post('/', [WipEntryController::class, 'store']);
        Route::delete('{id}', [WipEntryController::class, 'destroy']);
        // GET endpoints for all read operations - MUST be last to avoid catching all routes
        Route::get('{action}', [WipEntryController::class, 'show'])->where('action', '.*');
    });
});
