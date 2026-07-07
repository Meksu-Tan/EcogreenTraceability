<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    /**
     * GET /api/roles
     */
    public function roles(): JsonResponse
    {
        return ApiResponse::success(Role::with('permissions')->get());
    }

    /**
     * GET /api/permissions
     */
    public function permissions(): JsonResponse
    {
        return ApiResponse::success(Permission::all());
    }
}
