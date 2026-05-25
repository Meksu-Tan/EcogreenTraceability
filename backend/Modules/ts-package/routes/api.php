<?php

use Illuminate\Support\Facades\Route;
use Modules\TsPackage\Http\Controllers\PackageEntryController;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::prefix('transactions/package-entries')->group(function () {
        Route::get('/', [PackageEntryController::class, 'index']);
        Route::post('/', [PackageEntryController::class, 'store']);
        Route::delete('{id}', [PackageEntryController::class, 'destroy']);
    });
});
