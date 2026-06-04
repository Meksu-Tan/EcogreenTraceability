<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TsShipment\Http\Controllers\ShipmentEntryController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/shipment-entries')->group(function () {
        Route::get('/', [ShipmentEntryController::class, 'index']);
        Route::post('/', [ShipmentEntryController::class, 'store']);
        Route::delete('{id}', [ShipmentEntryController::class, 'destroy']);

        // Backward Trace Detail Modals support
        Route::get('batch-packaging', [ShipmentEntryController::class, 'getShipmentBatchPackaging']);
        Route::get('preparation-record', [ShipmentEntryController::class, 'getPreparationRecord']);
        Route::get('label', [ShipmentEntryController::class, 'getLabel']);
        Route::get('special-label', [ShipmentEntryController::class, 'getSpecialLabel']);
        Route::get('customer-mark', [ShipmentEntryController::class, 'getCustomerMark']);
        Route::get('sap-shipment', [ShipmentEntryController::class, 'getDatShipment']);
        Route::get('sap-so-allocation', [ShipmentEntryController::class, 'getDatSoAllocation']);
    });
});
