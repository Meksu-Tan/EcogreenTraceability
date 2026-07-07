<?php

declare(strict_types=1);

namespace Modules\TsTsreport\Repositories\Contracts;

interface TsReportRepositoryInterface
{
    public function getTsReport(array $filters): array;

    public function getTsReportRm(array $filters): array;

    public function getTsReportPck(array $filters): array;

    public function getTsReportShipment(array $filters): array;

    public function getTsReportTransfer(array $filters): array;

    public function getTsReportWip(array $filters): array;
}
