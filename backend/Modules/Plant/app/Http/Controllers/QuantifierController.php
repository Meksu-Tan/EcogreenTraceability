<?php

namespace Modules\Plant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuantifierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data'   => [],
            'message' => 'Quantifier module ready (implementations pending)'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => 'Quantifier stored successfully',
            'data'   => []
        ]);
    }
}
