<?php declare(strict_types=1);

namespace Modules\Inquiry\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Inquiry\Services\PsPaReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PsPaReportController extends Controller
{
    public function __construct(
        protected PsPaReportService $psPaReportService
    ) {}

    /**
     * Get PSPA report list.
     * GET /api/v1/inquiries/pspa-report
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $plantId = $request->input('id_plant');
            $userId = $request->user()?->id;
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            $data = $this->psPaReportService->getReportHeadList($plantId, $userId, $dateFrom, $dateTo);
            return ApiResponse::success($data, 'PSPA report list retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get PSPA report detail.
     * GET /api/v1/inquiries/pspa-report/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $data = $this->psPaReportService->getReportDetail((int) $id);

            if (!$data) {
                return ApiResponse::error('Report not found', 404);
            }

            return ApiResponse::success($data, 'PSPA report detail retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate new PSPA report.
     * POST /api/v1/inquiries/pspa-report
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $plantId = $request->input('id_plant');
            $period = $request->input('period');
            $data = $request->all();

            if (!$period) {
                return ApiResponse::error('Period is required', 422);
            }

            $result = $this->psPaReportService->generateReport($user, $plantId, $period, $data);

            if ($result['response'] == 99) {
                return ApiResponse::error($result['message'] ?? 'Period is locked', 422);
            }

            if ($result['response'] == 2) {
                return ApiResponse::error($result['message'] ?? 'Report already exists', 422);
            }

            if ($result['response'] == 1) {
                return ApiResponse::success($result, 'PSPA report generated', 200);
            }

            return ApiResponse::error('Failed to generate report', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate PSPA report.
     * POST /api/v1/inquiries/pspa-report/calculate/{id}
     */
    public function calculate(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $result = $this->psPaReportService->calculateReport($user, (int) $id);

            if ($result['response'] == 1) {
                return ApiResponse::success($result, 'PSPA report calculated', 200);
            }

            return ApiResponse::error($result['message'] ?? 'Failed to calculate', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Approve PSPA report.
     * POST /api/v1/inquiries/pspa-report/approve/{id}
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $result = $this->psPaReportService->approveReport($user, (int) $id);

            if ($result['response'] == 1) {
                return ApiResponse::success($result, 'PSPA report approved', 200);
            }

            return ApiResponse::error($result['message'] ?? 'Failed to approve', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get material stock summary.
     * GET /api/v1/inquiries/pspa-report/material-stock
     */
    public function materialStock(Request $request): JsonResponse
    {
        try {
            $filters = [
                'plant_id' => $request->input('id_plant'),
                'user_id' => $request->user()?->id,
                'material_id' => $request->input('id_material'),
            ];

            $data = $this->psPaReportService->getMaterialStock($filters);
            return ApiResponse::success($data, 'Material stock retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }
}