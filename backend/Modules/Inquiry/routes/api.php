<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    // Inquiry routes have been moved to dedicated modules:
    // - ts-stock: /api/v1/transactions/stock
    // - ts-tsreport: /api/v1/transactions/ts-report
    // - ts-rmreport: /api/v1/transactions/rm-report
});
