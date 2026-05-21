<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WipEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data'   => [],
            'message' => 'WIP Entry module ready (implementations pending)'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => 'WIP Entry stored successfully',
            'data'   => []
        ]);
    }

    public function destroy($id): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => 'WIP Entry deactivated'
        ]);
    }
}
