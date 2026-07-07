<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Inquiry\Http\Controllers\PsPaReportController;

// PSPA inquiry routes
Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1/inquiries/pspa-report')->group(function () {
    Route::get('material-stock', [PsPaReportController::class, 'materialStock']);
});
