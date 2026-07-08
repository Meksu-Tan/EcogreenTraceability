<?php

declare(strict_types=1);

namespace Modules\TsRmreport\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\TsRmreport\Http\Requests\RmReportRequest;
use Modules\TsRmreport\Http\Resources\RmReportResource;
use Modules\TsRmreport\Services\Contracts\RmReportServiceInterface;

class RmReportController extends Controller
{
    public function __construct(
        protected RmReportServiceInterface $rmReportService
    ) {}

    public function index(RmReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $result = $this->rmReportService->getRmReport($filters);

            return ApiResponse::success(RmReportResource::collection($result['data']), $result['message'] ?? 'RM Report retrieved');
        } catch (\Exception $e) {
            Log::error('RmReport action failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve RM report data', 500);
        }
    }

    public function detail(RmReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $result = $this->rmReportService->getRmListDetail($filters);

            return ApiResponse::success(RmReportResource::collection($result['data']), $result['message'] ?? 'RM detail list retrieved');
        } catch (\Exception $e) {
            Log::error('RmReport action failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve RM report data', 500);
        }
    }

    public function summary(RmReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $result = $this->rmReportService->getRmSummaryRmPrd($filters);

            return ApiResponse::success(RmReportResource::collection($result['data']), $result['message'] ?? 'RM summary retrieved');
        } catch (\Exception $e) {
            Log::error('RmReport action failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve RM report data', 500);
        }
    }

    public function detailOnTank(RmReportRequest $request): JsonResponse
    {
        try {
            $batchSap = $request->validated('batchSap') ?? '';
            $result = $this->rmReportService->getRmDetailRmPrdOnTank($batchSap);

            return ApiResponse::success(RmReportResource::collection($result['data']), $result['message'] ?? 'RM detail on tank retrieved');
        } catch (\Exception $e) {
            Log::error('RmReport action failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve RM report data', 500);
        }
    }

    public function detailOnAdjOut(RmReportRequest $request): JsonResponse
    {
        try {
            $batchSap = $request->validated('batchSap') ?? '';
            $result = $this->rmReportService->getRmDetailRmPrdOnAdjOut($batchSap);

            return ApiResponse::success(RmReportResource::collection($result['data']), $result['message'] ?? 'RM detail on adj out retrieved');
        } catch (\Exception $e) {
            Log::error('RmReport action failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve RM report data', 500);
        }
    }

    public function detailOnWarehouse(RmReportRequest $request): JsonResponse
    {
        try {
            $batchSap = $request->validated('batchSap') ?? '';
            $result = $this->rmReportService->getRmDetailRmPrdOnWarehouse($batchSap);

            return ApiResponse::success(RmReportResource::collection($result['data']), $result['message'] ?? 'RM detail on warehouse retrieved');
        } catch (\Exception $e) {
            Log::error('RmReport action failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve RM report data', 500);
        }
    }
}
