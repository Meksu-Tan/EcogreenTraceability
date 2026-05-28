<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TsRaw\Http\Controllers\RmEntryController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/rm-entries')->group(function () {
        Route::get('/', [RmEntryController::class, 'index']);
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
        // Storage and Feed Log Methods (moved from ts-transfer)
        Route::get('storage-log', [RmEntryController::class, 'storageLog']);
        Route::get('feed-log', [RmEntryController::class, 'feedLog']);
        Route::get('debug-feed-log', [RmEntryController::class, 'debugFeedLog']);
        // Transfer Methods (moved from ts-transfer)
        Route::get('source-entries', [RmEntryController::class, 'sourceEntries']);
        Route::get('dest-tanks', [RmEntryController::class, 'destTanks']);
        Route::post('inter-plant-transfer', [RmEntryController::class, 'interPlantTransfer']);
        Route::get('transfers', [RmEntryController::class, 'transfers']);
        Route::post('matl-doc', [RmEntryController::class, 'matlDoc']);
        Route::post('update-sub-tank', [RmEntryController::class, 'updateSubTank']);
        Route::delete('transfers/{id}', [RmEntryController::class, 'deactivateTransfer']);
        // Parameterized routes must come last to avoid conflicts
        Route::post('/', [RmEntryController::class, 'store']);
        Route::get('{id}', [RmEntryController::class, 'show']);
        Route::put('{id}', [RmEntryController::class, 'update']);
        Route::delete('{id}', [RmEntryController::class, 'destroy']);
    });
});
