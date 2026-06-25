<?php
declare(strict_types=1);
namespace Modules\TsRmreport\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\TsRmreport\Services\Contracts\RmReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RmReportController extends Controller
{
    public function __construct(
        protected RmReportServiceInterface $rmReportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['plant_id', 'id_plant', 'supplier_id', 'material_id', 'date_from', 'date_to']);
            $filters['user_id'] = $request->user()?->id;
            $result = $this->rmReportService->getRmReport($filters);
            return ApiResponse::success($result['data'], $result['message'] ?? 'RM Report retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function detail(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['plant_id', 'id_plant', 'material_id', 'date_from', 'date_to']);
            $result = $this->rmReportService->getRmListDetail($filters);
            return ApiResponse::success($result['data'], $result['message'] ?? 'RM detail list retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function summary(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['plant_id', 'id_plant', 'year', 'selectedYear']);
            $result = $this->rmReportService->getRmSummaryRmPrd($filters);
            return ApiResponse::success($result['data'], $result['message'] ?? 'RM summary retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function detailOnTank(Request $request): JsonResponse
    {
        try {
            $batchSap = $request->input('batchSap', '');
            $result = $this->rmReportService->getRmDetailRmPrdOnTank($batchSap);
            return ApiResponse::success($result['data'], $result['message'] ?? 'RM detail on tank retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function detailOnAdjOut(Request $request): JsonResponse
    {
        try {
            $batchSap = $request->input('batchSap', '');
            $result = $this->rmReportService->getRmDetailRmPrdOnAdjOut($batchSap);
            return ApiResponse::success($result['data'], $result['message'] ?? 'RM detail on adj out retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function detailOnWarehouse(Request $request): JsonResponse
    {
        try {
            $batchSap = $request->input('batchSap', '');
            $result = $this->rmReportService->getRmDetailRmPrdOnWarehouse($batchSap);
            return ApiResponse::success($result['data'], $result['message'] ?? 'RM detail on warehouse retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function transfer(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['plant_id', 'id_plant', 'material_id']);
            $result = $this->rmReportService->getRmListTransfer($filters);
            return ApiResponse::success($result['data'], $result['message'] ?? 'RM transfer list retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }
}
