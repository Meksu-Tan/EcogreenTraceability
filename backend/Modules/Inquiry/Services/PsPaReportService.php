<?php
declare(strict_types=1);
namespace Modules\Inquiry\Services;

use Modules\Inquiry\Services\Contracts\PsPaReportServiceInterface;
use Modules\Inquiry\Repositories\PsPaReportRepository;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\AuditService;
use Illuminate\Support\Facades\DB;

class PsPaReportService implements PsPaReportServiceInterface
{
    public function __construct(
        protected PsPaReportRepository $repository,
        protected PeriodLockService $periodLockService,
        protected AuditService $auditService
    ) {}

    /**
     * Get PSPA report head list.
     */
    public function getReportHeadList(mixed $plantId, ?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return $this->repository->getReportHeadList($plantId, $userId, $dateFrom, $dateTo);
    }

    /**
     * Get PSPA report detail with tail data.
     */
    public function getReportDetail(int $reportId): ?array
    {
        return $this->repository->getReportDetail($reportId);
    }

    /**
     * Generate new PSPA report.
     */
    public function generateReport(string $user, mixed $plantId, string $period, array $data): array
    {
        // Check if period is locked
        if (!$this->periodLockService->isUnlocked($period)) {
            return ['response' => 99, 'message' => 'Period is locked'];
        }

        // Check if report for this period already exists
        if ($this->repository->reportExists($period, $plantId)) {
            return ['response' => 2, 'message' => 'Report for this period already exists'];
        }

        return DB::connection('eudr_ts')->transaction(function () use ($user, $plantId, $period, $data) {
            $result = $this->repository->generateReport($user, $plantId, $period, $data);

            if ($result['response'] == 1) {
                $this->auditService->log('PSPA_REPORT', 'GENERATE',
                    'PSPA Report generated | Period: ' . $period . ' | ID: ' . $result['id'],
                    $user, ['period' => $period, 'id_report_head' => $result['id']]);
            }

            return $result;
        });
    }

    /**
     * Calculate PSPA report with actual stock data.
     */
    public function calculateReport(string $user, int $reportId): array
    {
        $report = $this->repository->getReportHead($reportId);
        if (!$report) {
            return ['response' => 0, 'message' => 'Report not found'];
        }

        if ($report->status != 1) {
            return ['response' => 2, 'message' => 'Report already calculated or approved'];
        }

        return DB::connection('eudr_ts')->transaction(function () use ($user, $reportId, $report) {
            $result = $this->repository->calculateReport($reportId);

            if ($result['response'] == 1) {
                $this->auditService->log('PSPA_REPORT', 'CALCULATE',
                    'PSPA Report calculated | ID: ' . $reportId,
                    $user, ['id_report_head' => $reportId]);
            }

            return $result;
        });
    }

    /**
     * Approve PSPA report.
     */
    public function approveReport(string $user, int $reportId): array
    {
        $report = $this->repository->getReportHead($reportId);
        if (!$report) {
            return ['response' => 0, 'message' => 'Report not found'];
        }

        if ($report->status != 2) {
            return ['response' => 2, 'message' => 'Report must be calculated before approval'];
        }

        return DB::connection('eudr_ts')->transaction(function () use ($user, $reportId) {
            $result = $this->repository->approveReport($reportId, $user);

            if ($result['response'] == 1) {
                $this->auditService->log('PSPA_REPORT', 'APPROVE',
                    'PSPA Report approved | ID: ' . $reportId,
                    $user, ['id_report_head' => $reportId]);
            }

            return $result;
        });
    }

    /**
     * Get material stock summary.
     */
    public function getMaterialStock(array $filters): array
    {
        return $this->repository->getMaterialStock($filters);
    }

    /**
     * Get opening stock for a material at a date.
     * Formula: Opening = Sum of all stock movements before the date
     */
    public function getOpeningStock(string $materialId, string $date, mixed $plantId): array
    {
        return $this->repository->getOpeningStock($materialId, $date, $plantId);
    }

    /**
     * Get closing stock for a material at a date.
     * Formula: Closing = Opening + Receive - Production - Transfer - Adjustment
     */
    public function getClosingStock(string $materialId, string $date, mixed $plantId): array
    {
        return $this->repository->getClosingStock($materialId, $date, $plantId);
    }
}