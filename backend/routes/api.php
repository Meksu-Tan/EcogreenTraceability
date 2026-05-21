<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are now registered by their respective modules:
|
|   Auth        → Modules/Auth/routes/api.php
|   Admin       → Modules/Admin/routes/api.php
|   Dashboard   → Modules/Dashboard/routes/api.php
|   Material    → Modules/Material/routes/api.php
|   Storage     → Modules/Storage/routes/api.php
|   Supplier    → Modules/Supplier/routes/api.php
|   Manufacturer→ Modules/Manufacturer/routes/api.php
|   Tank        → Modules/Tank/routes/api.php
|   Plant       → Modules/Plant/routes/api.php
|   Transaction → Modules/Transaction/routes/api.php
|   Inquiry     → Modules/Inquiry/routes/api.php
|
| This file remains as a central documentation point.
|
*/

Route::middleware('auth:sanctum')->prefix('api/v1')->group(function () {
    // All endpoints are defined in their module route files.
    // See module list above for details.
});
