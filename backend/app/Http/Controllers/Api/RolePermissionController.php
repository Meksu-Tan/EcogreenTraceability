<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    /**
     * GET /api/roles
     */
    public function roles(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data'   => Role::with('permissions')->get(),
        ]);
    }

    /**
     * GET /api/permissions
     */
    public function permissions(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data'   => Permission::all(),
        ]);
    }
}
