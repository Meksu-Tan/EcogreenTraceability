<?php
declare(strict_types=1);
namespace Modules\TsTsreport\Services;

use Modules\TsTsreport\Repositories\Contracts\TsReportRepositoryInterface;
use Modules\TsTsreport\Services\Contracts\TsReportServiceInterface;

class TsReportService implements TsReportServiceInterface
{
    public function __construct(
        protected TsReportRepositoryInterface $tsReportRepository
    ) {}

    public function getTsReport(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->tsReportRepository->getTsReport($filters),
            'message' => 'TS Report retrieved',
        ];
    }

    public function getTsReportRm(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->tsReportRepository->getTsReportRm($filters),
            'message' => 'TS Report RM section retrieved',
        ];
    }

    public function getTsReportPck(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->tsReportRepository->getTsReportPck($filters),
            'message' => 'TS Report PCK section retrieved',
        ];
    }

    public function getTsReportShipment(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->tsReportRepository->getTsReportShipment($filters),
            'message' => 'TS Report Shipment section retrieved',
        ];
    }

    public function getTsReportTransfer(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->tsReportRepository->getTsReportTransfer($filters),
            'message' => 'TS Report Transfer section retrieved',
        ];
    }

    public function getTsReportWip(array $filters = []): array
    {
        return [
            'status' => 1,
            'data'   => $this->tsReportRepository->getTsReportWip($filters),
            'message' => 'TS Report WIP section retrieved',
        ];
    }
}
