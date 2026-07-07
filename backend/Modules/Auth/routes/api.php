<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\RolePermissionController;

Route::prefix('api/v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::get('roles', [RolePermissionController::class, 'roles']);
        Route::get('permissions', [RolePermissionController::class, 'permissions']);
    });
});
