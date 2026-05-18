<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\StorageTank;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => [
                'materials' => Material::count(),
                'storage_tanks' => StorageTank::count(),
                'suppliers' => Supplier::count(),
                'users' => User::count(),
            ],
        ]);
    }
}
