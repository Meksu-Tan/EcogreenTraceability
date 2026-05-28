<?php declare(strict_types=1);

namespace Modules\Plant\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuantifierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success([], 'Quantifier module ready (implementations pending)', 200);
    }

    public function store(Request $request): JsonResponse
    {
        return ApiResponse::success([], 'Quantifier stored successfully', 200);
    }
}
