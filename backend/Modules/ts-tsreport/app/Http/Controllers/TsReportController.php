<?php
declare(strict_types=1);
namespace Modules\TsTsreport\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\TsTsreport\Services\Contracts\TsReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TsReportController extends Controller
{
    public function __construct(
        protected TsReportServiceInterface $tsReportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['entry_date', 'plant_id', 'id_plant']);
            $filters['user_id'] = $request->user()?->id;
            $result = $this->tsReportService->getTsReport($filters);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success($result['data'] ?? $result);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getRmSection(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['entry_date', 'plant_id', 'id_plant']);
            $filters['user_id'] = $request->user()?->id;
            $result = $this->tsReportService->getTsReportRm($filters);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success($result['data'] ?? $result);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getPckSection(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['entry_date', 'plant_id', 'id_plant']);
            $filters['user_id'] = $request->user()?->id;
            $result = $this->tsReportService->getTsReportPck($filters);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success($result['data'] ?? $result);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getShipmentSection(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['entry_date', 'plant_id', 'id_plant']);
            $filters['user_id'] = $request->user()?->id;
            $result = $this->tsReportService->getTsReportShipment($filters);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success($result['data'] ?? $result);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getTransferSection(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['entry_date', 'plant_id', 'id_plant']);
            $filters['user_id'] = $request->user()?->id;
            return ApiResponse::success($this->tsReportService->getTsReportTransfer($filters));
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getWipSection(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['entry_date', 'plant_id', 'id_plant']);
            $filters['user_id'] = $request->user()?->id;
            return ApiResponse::success($this->tsReportService->getTsReport($filters));
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getAllSections(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['entry_date', 'plant_id', 'id_plant']);
            $filters['user_id'] = $request->user()?->id;

            if (empty($filters['entry_date'])) {
                $filters['entry_date'] = now()->toDateString();
            }

            return ApiResponse::success([
                'rm'        => $this->tsReportService->getTsReportRm($filters)['data'] ?? [],
                'pck'       => $this->tsReportService->getTsReportPck($filters)['data'] ?? [],
                'shipment'  => $this->tsReportService->getTsReportShipment($filters)['data'] ?? [],
                'transfer'  => $this->tsReportService->getTsReportTransfer($filters)['data'] ?? [],
                'wip'       => $this->tsReportService->getTsReport($filters)['data'] ?? [],
            ], 'All TS Report sections retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }
}
