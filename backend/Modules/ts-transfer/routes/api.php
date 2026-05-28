<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TsTransfer\Http\Controllers\TransferController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/transfers')->group(function () {
        Route::get('/', [TransferController::class, 'index']);
        Route::post('/', [TransferController::class, 'store']);
        Route::delete('{id}', [TransferController::class, 'destroy']);

        Route::get('active-materials', [TransferController::class, 'activeMaterials']);
        Route::get('new-entry-no', [TransferController::class, 'newEntryNo']);
        Route::get('tanks-rundown', [TransferController::class, 'activeTanksRundown']);
        Route::get('specific-tanks-rundown', [TransferController::class, 'activeSpecificTanksRundown']);
        Route::get('total-stock', [TransferController::class, 'totalStockMaterial']);
        Route::post('matl-doc', [TransferController::class, 'matlDocNumber']);
        Route::post('update-sub-tank', [TransferController::class, 'updateEntrySubTank']);
        Route::get('supplier-code', [TransferController::class, 'supplierMaterialCode']);
    });
});
