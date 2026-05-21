<?php

use Illuminate\Support\Facades\Route;
use Modules\Inquiry\Http\Controllers\StockInquiryController;
use Modules\Inquiry\Http\Controllers\TsReportController;
use Modules\Inquiry\Http\Controllers\RmReportController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::get('inquiries/stock', [StockInquiryController::class, 'index']);
    Route::get('inquiries/stock/{id}', [StockInquiryController::class, 'show']);
    Route::get('inquiries/ts-report', [TsReportController::class, 'index']);
    Route::get('inquiries/rm-report', [RmReportController::class, 'index']);
});
