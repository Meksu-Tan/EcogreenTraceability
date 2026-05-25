<?php

use Modules\Tank\Http\Controllers\TankController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    Route::get('tanks', [TankController::class, 'index']);
    Route::post('tanks', [TankController::class, 'store']);
    Route::put('tanks/{id}', [TankController::class, 'update']);
    Route::delete('tanks/{id}', [TankController::class, 'destroy']);
});
