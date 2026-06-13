<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\TsShipment\Http\Controllers\ShipmentEntryController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::prefix('transactions/shipment-entries')->group(function () {
        Route::get('/', [ShipmentEntryController::class, 'index']);
        Route::post('/', [ShipmentEntryController::class, 'store']);
        Route::delete('{id}', [ShipmentEntryController::class, 'destroy']);
        Route::put('so', [ShipmentEntryController::class, 'updateSo']);

        // Helpers
        Route::get('active-fg-products', [ShipmentEntryController::class, 'getActiveFgProduct']);
        Route::get('wip-materials', [ShipmentEntryController::class, 'getWipMaterialByFgProduct']);
        Route::get('active-batches', [ShipmentEntryController::class, 'getActiveBatchProduct']);
        Route::get('batch-packaging', [ShipmentEntryController::class, 'getBatchPackaging']);
        Route::get('preparation-record', [ShipmentEntryController::class, 'getPreparationRecord']);
        Route::get('label', [ShipmentEntryController::class, 'getLabel']);
        Route::get('special-label', [ShipmentEntryController::class, 'getSpecialLabel']);
        Route::get('customer-mark', [ShipmentEntryController::class, 'getCustomerMark']);
        Route::get('sap-shipment', [ShipmentEntryController::class, 'getSapShipment']);
        Route::get('sap-so-allocation', [ShipmentEntryController::class, 'getSapSoAllocation']);
        Route::get('new-trace-no', [ShipmentEntryController::class, 'newTraceNo']);
    });
});
