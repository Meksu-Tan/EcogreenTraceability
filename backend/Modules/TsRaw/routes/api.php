<?php

use Illuminate\Support\Facades\Route;
use Modules\TsRaw\Http\Controllers\RmEntryController;
use Modules\TsRaw\Http\Controllers\TransferController;
use Modules\TsRaw\Http\Controllers\WipEntryController;
use Modules\TsRaw\Http\Controllers\BlendingController;
use Modules\TsRaw\Http\Controllers\PackageEntryController;
use Modules\TsRaw\Http\Controllers\ShipmentEntryController;

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
    
    Route::prefix('transactions/transfers')->group(function () {
        Route::get('storage-log', [TransferController::class, 'storageLog']);
        Route::get('feed-log', [TransferController::class, 'feedLog']);
        Route::get('debug-feed-log', [TransferController::class, 'debugFeedLog']);
        Route::post('/', [TransferController::class, 'transfer']);
        Route::delete('{id}', [TransferController::class, 'deactivate']);
        Route::get('source-entries', [TransferController::class, 'sourceEntries']);
        Route::get('dest-tanks', [TransferController::class, 'destTanks']);
    });

    Route::prefix('transactions/wip-entries')->group(function () {
        Route::get('/', [WipEntryController::class, 'index']);
        Route::post('/', [WipEntryController::class, 'store']);
        Route::delete('{id}', [WipEntryController::class, 'destroy']);
    });

    Route::prefix('transactions/blendings')->group(function () {
        Route::get('/', [BlendingController::class, 'index']);
        Route::post('/', [BlendingController::class, 'store']);
        Route::delete('{id}', [BlendingController::class, 'destroy']);
    });

    Route::prefix('transactions/package-entries')->group(function () {
        Route::get('/', [PackageEntryController::class, 'index']);
        Route::post('/', [PackageEntryController::class, 'store']);
        Route::delete('{id}', [PackageEntryController::class, 'destroy']);
    });

    Route::prefix('transactions/shipment-entries')->group(function () {
        Route::get('/', [ShipmentEntryController::class, 'index']);
        Route::post('/', [ShipmentEntryController::class, 'store']);
        Route::delete('{id}', [ShipmentEntryController::class, 'destroy']);
    });
});
