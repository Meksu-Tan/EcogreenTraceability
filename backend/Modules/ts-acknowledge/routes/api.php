<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Modules\TsAcknowledge\Http\Controllers\TsAcknowledgeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum', 'plant.context'])->prefix('api/v1/ts-acknowledge')->group(function () {
    Route::get('/dashboard', [TsAcknowledgeController::class, 'getDashboard']);
    Route::post('/fetch-dcs', [TsAcknowledgeController::class, 'fetchDcs']);
    Route::post('/save', [TsAcknowledgeController::class, 'store']);
});
