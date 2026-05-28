<?php declare(strict_types=1);
namespace Modules\Inquiry\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inquiry\Services\InquiryService;
use App\Helpers\ApiResponse;

class StockInquiryController extends Controller
{
    public function __construct(
        protected InquiryService $inquiryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['plant_id', 'material_id', 'storage_id', 'date_from', 'date_to']);
        $result = $this->inquiryService->getStockList($filters);

        return response()->json($result);
    }

    public function show($id): JsonResponse
    {
        $result = $this->inquiryService->getStockDetail((int) $id);

        return response()->json($result);
    }
}
