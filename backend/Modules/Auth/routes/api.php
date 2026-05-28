<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\RolePermissionController;

Route::prefix('api')->group(function () {
    // Both v1 and non-v1 routes are supported for backward compatibility and spec alignment
    Route::post('login', [AuthController::class, 'login']);
    Route::post('v1/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('v1/logout', [AuthController::class, 'logout']);

        Route::get('user', [AuthController::class, 'user']);
        Route::get('v1/user', [AuthController::class, 'user']);

        Route::get('roles', [RolePermissionController::class, 'roles']);
        Route::get('v1/roles', [RolePermissionController::class, 'roles']);

        Route::get('permissions', [RolePermissionController::class, 'permissions']);
        Route::get('v1/permissions', [RolePermissionController::class, 'permissions']);
    });
});
