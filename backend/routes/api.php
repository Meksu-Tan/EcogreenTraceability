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

        // ──────────────────────────────────────────────────────────
        // Setup — Additional Modules (Placeholder endpoints)
        // ──────────────────────────────────────────────────────────
        Route::get('adjustments', function() {
            return response()->json(['data' => [], 'message' => 'Adjustment module under development']);
        });
        
        Route::get('quantifiers', function() {
            return response()->json(['data' => [], 'message' => 'Quantifier module under development']);
        });
        
        Route::get('plants',    [\App\Http\Controllers\Api\Plant\PlantController::class, 'index']);
        Route::post('plants',   [\App\Http\Controllers\Api\Plant\PlantController::class, 'store']);
        Route::put('plants/{id}',    [\App\Http\Controllers\Api\Plant\PlantController::class, 'update']);
        Route::delete('plants/{id}', [\App\Http\Controllers\Api\Plant\PlantController::class, 'destroy']);

        // ──────────────────────────────────────────────────────────
        // Transaction Modules — RM Entry
        // ──────────────────────────────────────────────────────────
        Route::prefix('transactions/rm-entries')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'store']);
            Route::delete('{id}', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'destroy']);
            Route::get('new-number', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'newNumber']);
            Route::get('tanks', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'tanks']);
            Route::get('tanks/{id}/details', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'tankDetails']);
            Route::get('materials', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'materials']);
            Route::get('suppliers/search', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'searchSuppliers']);
            Route::get('batch-code', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'batchCode']);
            Route::post('suppliers', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'addSupplier']);
            Route::get('suppliers/list', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'supplierList']);
            Route::delete('suppliers/{id}', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'deleteSupplier']);
            Route::get('total-qty', [\App\Http\Controllers\Api\Transaction\RmEntryController::class, 'totalQty']);
        });
        
        Route::get('transactions/wip-entries', function() {
            return response()->json(['data' => [], 'message' => 'WIP Entry module under development']);
        });
        
        Route::get('transactions/blendings', function() {
            return response()->json(['data' => [], 'message' => 'Blending module under development']);
        });
        
        Route::get('transactions/package-entries', function() {
            return response()->json(['data' => [], 'message' => 'Package Entry module under development']);
        });
        
        Route::get('transactions/shipment-entries', function() {
            return response()->json(['data' => [], 'message' => 'Shipment Entry module under development']);
        });
        
        Route::prefix('transactions/transfers')->group(function () {
            Route::get('storage-log', [\App\Http\Controllers\Api\Transaction\TransferController::class, 'storageLog']);
            Route::get('feed-log', [\App\Http\Controllers\Api\Transaction\TransferController::class, 'feedLog']);
            Route::post('/', [\App\Http\Controllers\Api\Transaction\TransferController::class, 'transfer']);
            Route::delete('{id}', [\App\Http\Controllers\Api\Transaction\TransferController::class, 'deactivate']);
            Route::get('source-entries', [\App\Http\Controllers\Api\Transaction\TransferController::class, 'sourceEntries']);
            Route::get('dest-tanks', [\App\Http\Controllers\Api\Transaction\TransferController::class, 'destTanks']);
        });

        // ──────────────────────────────────────────────────────────
        // Inquiry Modules (Placeholder endpoints)
        // ──────────────────────────────────────────────────────────
        Route::get('inquiries/stock', function() {
            return response()->json(['data' => [], 'message' => 'Stock Inquiry module under development']);
        });
        
        Route::get('inquiries/ts-report', function() {
            return response()->json(['data' => [], 'message' => 'TS Report module under development']);
        });
        
        Route::get('inquiries/rm-report', function() {
            return response()->json(['data' => [], 'message' => 'RM Report module under development']);
        });

        // ──────────────────────────────────────────────────────────
        // Trace Modules (Placeholder endpoints)
        // ──────────────────────────────────────────────────────────
        Route::get('trace/forward/{id}', function($id) {
            return response()->json(['data' => [], 'message' => 'Forward Trace module under development']);
        });
        
        Route::get('trace/backward/{id}', function($id) {
            return response()->json(['data' => [], 'message' => 'Backward Trace module under development']);
        });

        // ──────────────────────────────────────────────────────────
        // Admin Modules
        // ──────────────────────────────────────────────────────────
        Route::get('admin/users',       [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
        Route::post('admin/users',      [\App\Http\Controllers\Api\Admin\UserController::class, 'store']);
        Route::put('admin/users/{id}',  [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
        Route::delete('admin/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
    });
});
