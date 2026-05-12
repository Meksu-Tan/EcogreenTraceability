<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\Material\MaterialController;
use App\Http\Controllers\Api\Storage\StorageController;
use App\Http\Controllers\Api\Supplier\SupplierController;
use Illuminate\Support\Facades\Route;

// ============================================================
// Public Routes (no auth required)
// ============================================================
Route::post('/login', [AuthController::class, 'login']);

// ============================================================
// Protected Routes (requires Sanctum session auth)
// ============================================================
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user',    [AuthController::class, 'user']);

    // Roles & Permissions
    Route::get('/roles',       [RolePermissionController::class, 'roles']);
    Route::get('/permissions', [RolePermissionController::class, 'permissions']);

    // ──────────────────────────────────────────────────────────
    // API v1
    // ──────────────────────────────────────────────────────────
    Route::prefix('v1')->group(function () {

        // Setup Material — WIP
        Route::get('materials',    [MaterialController::class, 'index']);
        Route::post('materials',   [MaterialController::class, 'store']);
        Route::put('materials/{id}',    [MaterialController::class, 'update']);
        Route::delete('materials/{id}', [MaterialController::class, 'destroy']);

        // Setup Material — Packaging
        Route::get('material-packagings/source-products', [MaterialController::class, 'sourceProducts']);
        Route::get('material-packagings',    [MaterialController::class, 'indexPackaging']);
        Route::post('material-packagings',   [MaterialController::class, 'storePackaging']);
        Route::put('material-packagings/{id}',    [MaterialController::class, 'updatePackaging']);
        Route::delete('material-packagings/{id}', [MaterialController::class, 'destroyPackaging']);

        // Setup Storage — Tanks
        Route::get('storage-tanks',       [StorageController::class, 'indexTanks']);
        Route::post('storage-tanks',      [StorageController::class, 'storeTank']);
        Route::put('storage-tanks/{id}',  [StorageController::class, 'updateTank']);
        Route::delete('storage-tanks/{id}', [StorageController::class, 'destroyTank']);

        // Setup Storage — Details
        Route::get('storage-details',       [StorageController::class, 'indexDetails']);
        Route::post('storage-details',      [StorageController::class, 'storeDetail']);
        Route::put('storage-details/{id}',  [StorageController::class, 'updateDetail']);
        Route::delete('storage-details/{id}', [StorageController::class, 'destroyDetail']);

        // Setup Storage — Warehouse
        Route::get('warehouses',       [StorageController::class, 'indexWarehouses']);
        Route::post('warehouses',      [StorageController::class, 'storeWarehouse']);
        Route::put('warehouses/{id}',  [StorageController::class, 'updateWarehouse']);
        Route::delete('warehouses/{id}', [StorageController::class, 'destroyWarehouse']);

        // Setup Supplier
        Route::get('suppliers/active', [SupplierController::class, 'active']);
        Route::get('suppliers',        [SupplierController::class, 'index']);
        Route::post('suppliers',       [SupplierController::class, 'store']);
        Route::put('suppliers/{id}',   [SupplierController::class, 'update']);
        Route::delete('suppliers/{id}', [SupplierController::class, 'destroy']);
    });
});
