<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TraceController extends Controller
{
    public function forward($id): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data'   => [],
            'message' => 'Forward Trace module ready (implementations pending)'
        ]);
    }

    public function backward($id): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data'   => [],
            'message' => 'Backward Trace module ready (implementations pending)'
        ]);
    }
}
