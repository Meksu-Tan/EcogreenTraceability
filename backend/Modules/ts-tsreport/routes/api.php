<?php
declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TsTsreport\Http\Controllers\TsReportController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::prefix('transactions/ts-report')->group(function () {
        Route::get('/', [TsReportController::class, 'index']);
        Route::get('all', [TsReportController::class, 'getAllSections']);
        Route::get('rm', [TsReportController::class, 'getRmSection']);
        Route::get('wip', [TsReportController::class, 'getWipSection']);
        Route::get('pck', [TsReportController::class, 'getPckSection']);
        Route::get('shipment', [TsReportController::class, 'getShipmentSection']);
        Route::get('transfer', [TsReportController::class, 'getTransferSection']);
    });
});
