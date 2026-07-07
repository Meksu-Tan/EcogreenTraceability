<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\SupplierController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::get('suppliers/active', [SupplierController::class, 'active']);
    Route::get('suppliers', [SupplierController::class, 'index']);
    Route::post('suppliers', [SupplierController::class, 'store']);
    Route::put('suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('suppliers/{id}', [SupplierController::class, 'destroy']);
});
