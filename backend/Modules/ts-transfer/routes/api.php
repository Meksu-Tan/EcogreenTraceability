<?php

use Illuminate\Support\Facades\Route;
use Modules\TsTransfer\Http\Controllers\TransferController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/transfers')->group(function () {
        // All endpoints moved to ts-raw module
        // This module is now deprecated - all functionality moved to ts-raw
    });
});
