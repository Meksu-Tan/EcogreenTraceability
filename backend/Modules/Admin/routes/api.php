<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\UserController;

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1')->group(function () {
    Route::get('admin/users', [UserController::class, 'index']);
    Route::post('admin/users', [UserController::class, 'store']);
    Route::put('admin/users/{id}', [UserController::class, 'update']);
    Route::delete('admin/users/{id}', [UserController::class, 'destroy']);
});
