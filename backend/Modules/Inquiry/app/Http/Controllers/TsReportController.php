<?php declare(strict_types=1);
namespace Modules\Inquiry\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inquiry\Services\InquiryService;
use App\Helpers\ApiResponse;

class TsReportController extends Controller
{
    public function __construct(
        protected InquiryService $inquiryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['plant_id', 'tank_id', 'storage_id']);
        $result = $this->inquiryService->getTsReport($filters);

        return response()->json($result);
    }
}
