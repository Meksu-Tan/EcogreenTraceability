<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Adjustment\Http\Controllers\AdjustmentController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::prefix('master/adjustment')->group(function () {
        // â€”â€”â€” Lookups (exact routes BEFORE {id}) â€”â€”â€”
        Route::get('supplier-list', [AdjustmentController::class, 'getSupplierList']);
        Route::get('total-qty', [AdjustmentController::class, 'getTotalQty']);
        Route::get('suppliers/search', [AdjustmentController::class, 'searchSuppliers']);
        Route::get('entry-no', [AdjustmentController::class, 'getEntryNo']);
        Route::get('active-materials', [AdjustmentController::class, 'getActiveMaterials']);
        Route::get('active-material-whx', [AdjustmentController::class, 'getActiveMaterialWhx']);
        Route::get('active-tanks', [AdjustmentController::class, 'getActiveTanks']);
        Route::get('active-specific-tanks/{sloc}', [AdjustmentController::class, 'getActiveSpecificTanks']);
        Route::get('active-whx', [AdjustmentController::class, 'getActiveWhx']);
        Route::get('lock-status', [AdjustmentController::class, 'getLockStatus']);
        Route::get('supplier-by-filter', [AdjustmentController::class, 'getSupplierByFilter']);
        Route::get('batch-by-supplier', [AdjustmentController::class, 'getBatchBySupplier']);

        // â€”â€”â€” Period Adjustment â€”â€”â€”
        Route::get('period-headers', [AdjustmentController::class, 'getPeriodHeaders']);
        Route::get('period-view-data', [AdjustmentController::class, 'getPeriodViewData']);
        Route::post('period-headers-upload', [AdjustmentController::class, 'periodHeadersUpload']);
        Route::post('period-view-on-hand', [AdjustmentController::class, 'periodViewOnHand']);
        Route::post('period-view-adjustment', [AdjustmentController::class, 'periodViewAdjustment']);
        Route::post('period-header-lock', [AdjustmentController::class, 'periodHeaderLock']);
        Route::post('period-header-unlock', [AdjustmentController::class, 'periodHeaderUnlock']);
        Route::delete('destroy-adjustment-period/{id}', [AdjustmentController::class, 'destroyAdjustmentPeriod']);
        Route::get('last-record', [AdjustmentController::class, 'getLastRecord']);

        // â€”â€”â€” Mutations (exact routes BEFORE {id}) â€”â€”â€”
        Route::post('detail', [AdjustmentController::class, 'storeDetail']);
        Route::post('approve/{id}', [AdjustmentController::class, 'approve']);
        Route::post('execute/{id}', [AdjustmentController::class, 'execute']);
        Route::post('cancel/{id}', [AdjustmentController::class, 'cancel']);

        // New combined / direct mutations
        Route::post('store-adjustment', [AdjustmentController::class, 'storeAdjustment']);
        Route::delete('destroy/{id}', [AdjustmentController::class, 'destroyAdjustment']);
        Route::delete('destroy-whx/{id}', [AdjustmentController::class, 'destroyAdjustmentWhx']);
        Route::post('add-entry-supplier', [AdjustmentController::class, 'addEntrySupplier']);
        Route::delete('delete-supplier-temp/{id}', [AdjustmentController::class, 'deleteSupplierTemp']);
        Route::post('init', [AdjustmentController::class, 'adjustmentInit']);
        Route::post('supplier-adjust', [AdjustmentController::class, 'adjustmentSupplier']);
        Route::post('store-adjustment-whx', [AdjustmentController::class, 'storeAdjustmentWhx']);
        Route::post('adjustment-init-whx', [AdjustmentController::class, 'adjustmentInitWhx']);
        Route::get('adjust-status', [AdjustmentController::class, 'getAdjustStatus']);
        Route::put('material-document/{id}', [AdjustmentController::class, 'adjustMaterialDocument']);

        // â€”â€”â€” Generic (must be LAST) â€”â€”â€”
        Route::get('/', [AdjustmentController::class, 'index']);
        Route::get('{id}', [AdjustmentController::class, 'show']);
        Route::post('/', [AdjustmentController::class, 'store']);
    });
});
