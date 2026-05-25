<?php

use Illuminate\Support\Facades\Route;
use Modules\TsRaw\Http\Controllers\RmEntryController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/rm-entries')->group(function () {
        Route::get('/', [RmEntryController::class, 'index']);
        Route::post('/', [RmEntryController::class, 'store']);
        Route::delete('{id}', [RmEntryController::class, 'destroy']);
        Route::get('new-number', [RmEntryController::class, 'newNumber']);
        Route::get('tanks', [RmEntryController::class, 'tanks']);
        Route::get('tanks/{id}/details', [RmEntryController::class, 'tankDetails']);
        Route::get('materials', [RmEntryController::class, 'materials']);
        Route::get('suppliers/search', [RmEntryController::class, 'searchSuppliers']);
        Route::get('batch-code', [RmEntryController::class, 'batchCode']);
        Route::post('suppliers', [RmEntryController::class, 'addSupplier']);
        Route::get('suppliers/list', [RmEntryController::class, 'supplierList']);
        Route::delete('suppliers/clear/{entry_no}', [RmEntryController::class, 'clearTempSuppliers']);
        Route::delete('suppliers/{id}', [RmEntryController::class, 'deleteSupplier']);
        Route::get('total-qty', [RmEntryController::class, 'totalQty']);
        Route::post('transfer', [RmEntryController::class, 'transfer']);
        Route::get('transfer-number', [RmEntryController::class, 'transferNumber']);
        Route::get('stock-sync-check', [RmEntryController::class, 'checkStockSync']);
        Route::get('debug-fifo-stock', [RmEntryController::class, 'debugFifoStock']);
        Route::get('verify-separate-entries', [RmEntryController::class, 'verifySeparateEntries']);
    });
});
