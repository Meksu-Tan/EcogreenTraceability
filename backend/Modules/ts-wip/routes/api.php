<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TsWip\Http\Controllers\WipEntryController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::prefix('transactions/wip-entries')->group(function () {
        Route::get('/', [WipEntryController::class, 'index'])->middleware('can:task-read');
        Route::delete('{id}', [WipEntryController::class, 'destroy'])->middleware('can:task-update');

        // POST endpoints
        Route::post('matl-doc', [WipEntryController::class, 'createMatlDoc'])->middleware('can:task-update');
        Route::post('feed', [WipEntryController::class, 'storeFeed'])->middleware('can:task-update');
        Route::post('feed/cancel', [WipEntryController::class, 'cancelFeed'])->middleware('can:task-update');
        Route::post('rundown', [WipEntryController::class, 'storeRundown'])->middleware('can:task-update');
        Route::post('rundown/cancel', [WipEntryController::class, 'cancelRundown'])->middleware('can:task-update');
        Route::post('update-sub-tank', [WipEntryController::class, 'updateSubTank'])->middleware('can:task-update');

        // GET endpoints
        Route::get('balance', [WipEntryController::class, 'getBalance'])->middleware('can:task-read');
        Route::get('feed', [WipEntryController::class, 'getFeed'])->middleware('can:task-read');
        Route::get('rundown', [WipEntryController::class, 'getRundown'])->middleware('can:task-read');
        Route::get('feed/new-batch', [WipEntryController::class, 'getFeedNewBatchNumber'])->middleware('can:task-read');
        Route::get('rundown/new-batch', [WipEntryController::class, 'getRundownNewBatchNumber'])->middleware('can:task-read');
        Route::get('feed/last-batch', [WipEntryController::class, 'getFeedLastBatch'])->middleware('can:task-read');
        Route::get('rundown/last-batch', [WipEntryController::class, 'getRundownLastBatch'])->middleware('can:task-read');
        Route::get('tanks/feed', [WipEntryController::class, 'getActiveTanksForFeed'])->middleware('can:task-read');
        Route::get('tanks/rundown', [WipEntryController::class, 'getActiveTanksForRundown'])->middleware('can:task-read');
        Route::get('tanks/specific', [WipEntryController::class, 'getActiveSpecificTanks'])->middleware('can:task-read');
        Route::get('quantifier', [WipEntryController::class, 'getQuantifierData'])->middleware('can:task-read');
        Route::get('tree', [WipEntryController::class, 'getWipTree'])->middleware('can:task-read');
        Route::get('feed/new-number', [WipEntryController::class, 'getNewFeedNumber'])->middleware('can:task-read');
        Route::get('rundown/new-number', [WipEntryController::class, 'getNewRundownNumber'])->middleware('can:task-read');
    });
});
