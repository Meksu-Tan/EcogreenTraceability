<?php

namespace Modules\TsRaw\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data'   => [],
            'message' => 'Package Entry module ready (implementations pending)'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => 'Package Entry stored successfully',
            'data'   => []
        ]);
    }

    public function destroy($id): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => 'Package Entry deactivated'
        ]);
    }
}
