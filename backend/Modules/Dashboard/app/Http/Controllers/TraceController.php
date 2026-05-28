<?php declare(strict_types=1);

namespace Modules\Dashboard\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TraceController extends Controller
{
    public function forward($id): JsonResponse
    {
        return ApiResponse::success([], 'Forward Trace module ready (implementations pending)', 200);
    }

    public function backward($id): JsonResponse
    {
        return ApiResponse::success([], 'Backward Trace module ready (implementations pending)', 200);
    }
}
