<?php

declare(strict_types=1);

namespace Modules\TsRmreport\Services\Contracts;

interface RmReportServiceInterface
{
    public function getRmReport(array $filters = []): array;

    public function getRmListDetail(array $filters = []): array;

    public function getRmSummaryRmPrd(array $filters = []): array;

    public function getRmDetailRmPrdOnTank(string $batchSap): array;

    public function getRmDetailRmPrdOnAdjOut(string $batchSap): array;

    public function getRmDetailRmPrdOnWarehouse(string $batchSap): array;
}
