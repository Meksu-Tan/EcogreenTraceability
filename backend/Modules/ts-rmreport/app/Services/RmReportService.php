<?php
declare(strict_types=1);
namespace Modules\TsRmreport\Services;

use Modules\TsRmreport\Repositories\Contracts\RmReportRepositoryInterface;
use Modules\TsRmreport\Services\Contracts\RmReportServiceInterface;

class RmReportService implements RmReportServiceInterface
{
    public function __construct(
        protected RmReportRepositoryInterface $rmReportRepository
    ) {}

    public function getRmReport(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->rmReportRepository->getRmReport($filters),
            'message' => 'RM Report retrieved',
        ];
    }

    public function getRmListDetail(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->rmReportRepository->getRmListDetail($filters),
            'message' => 'RM detail list retrieved',
        ];
    }

    public function getRmListTransfer(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->rmReportRepository->getRmListTransfer($filters),
            'message' => 'RM transfer list retrieved',
        ];
    }

    public function getRmSummaryRmPrd(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->rmReportRepository->getRmSummaryRmPrd($filters),
            'message' => 'RM summary retrieved',
        ];
    }

    public function getRmDetailRmPrdOnTank(string $batchSap): array
    {
        return [
            'status' => 1,
            'data'   => $this->rmReportRepository->getRmDetailRmPrdOnTank($batchSap),
            'message' => 'RM detail on tank retrieved',
        ];
    }

    public function getRmDetailRmPrdOnAdjOut(string $batchSap): array
    {
        return [
            'status' => 1,
            'data'   => $this->rmReportRepository->getRmDetailRmPrdOnAdjOut($batchSap),
            'message' => 'RM detail on adj out retrieved',
        ];
    }

    public function getRmDetailRmPrdOnWarehouse(string $batchSap): array
    {
        return [
            'status' => 1,
            'data'   => $this->rmReportRepository->getRmDetailRmPrdOnWarehouse($batchSap),
            'message' => 'RM detail on warehouse retrieved',
        ];
    }
}
