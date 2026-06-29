<?php
declare(strict_types=1);
namespace Modules\TsTsreport\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\TsTsreport\Services\Contracts\TsReportServiceInterface;
use Modules\TsTsreport\Http\Requests\TsReportRequest;
use Modules\TsTsreport\Http\Resources\TsReportResource;
use Illuminate\Http\JsonResponse;

class TsReportController extends Controller
{
    public function __construct(
        protected TsReportServiceInterface $tsReportService
    ) {}

    public function index(TsReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;
            $result = $this->tsReportService->getTsReport($filters);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success(TsReportResource::collection($result['data'] ?? $result));
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getRmSection(TsReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;
            $result = $this->tsReportService->getTsReportRm($filters);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success(TsReportResource::collection($result['data'] ?? $result));
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getPckSection(TsReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;
            $result = $this->tsReportService->getTsReportPck($filters);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success(TsReportResource::collection($result['data'] ?? $result));
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getShipmentSection(TsReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;
            $result = $this->tsReportService->getTsReportShipment($filters);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success(TsReportResource::collection($result['data'] ?? $result));
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getTransferSection(TsReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;
            return ApiResponse::success(TsReportResource::collection($this->tsReportService->getTsReportTransfer($filters)));
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getWipSection(TsReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;
            return ApiResponse::success(TsReportResource::collection($this->tsReportService->getTsReportWip($filters)));
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getAllSections(TsReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;

            if (empty($filters['entry_date'])) {
                $filters['entry_date'] = now()->toDateString();
            }

            return ApiResponse::success(new TsReportResource([
                'rm'        => $this->tsReportService->getTsReportRm($filters)['data'] ?? [],
                'pck'       => $this->tsReportService->getTsReportPck($filters)['data'] ?? [],
                'shipment'  => $this->tsReportService->getTsReportShipment($filters)['data'] ?? [],
                'transfer'  => $this->tsReportService->getTsReportTransfer($filters)['data'] ?? [],
                'wip'       => $this->tsReportService->getTsReportWip($filters)['data'] ?? [],
            ]), 'All TS Report sections retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }
}
