<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\TsRmreport\Http\Controllers\RmReportController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/rm-report')->group(function () {
        Route::get('/', [RmReportController::class, 'index']);
        Route::get('summary', [RmReportController::class, 'summary']);
        Route::get('detail/tank', [RmReportController::class, 'detailOnTank']);
        Route::get('detail/adj-out', [RmReportController::class, 'detailOnAdjOut']);
        Route::get('detail/warehouse', [RmReportController::class, 'detailOnWarehouse']);
        Route::get('detail', [RmReportController::class, 'detail']);
        Route::get('transfer', [RmReportController::class, 'transfer']);
    });
});
