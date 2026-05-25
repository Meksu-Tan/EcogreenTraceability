<?php

use Illuminate\Support\Facades\Route;
use Modules\TsTransfer\Http\Controllers\TransferController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/transfers')->group(function () {
        Route::get('storage-log', [TransferController::class, 'storageLog']);
        Route::get('feed-log', [TransferController::class, 'feedLog']);
        Route::get('debug-feed-log', [TransferController::class, 'debugFeedLog']);
        Route::post('/', [TransferController::class, 'transfer']);
        Route::delete('{id}', [TransferController::class, 'deactivate']);
        Route::get('source-entries', [TransferController::class, 'sourceEntries']);
        Route::get('dest-tanks', [TransferController::class, 'destTanks']);
    });
});
