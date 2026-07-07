<?php

declare(strict_types=1);

namespace Modules\Inquiry\Services\Contracts;

interface PsPaReportServiceInterface
{
    public function getReportHeadList(mixed $plantId, ?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null): array;

    public function getReportDetail(int $reportId): ?array;

    public function generateReport(string $user, mixed $plantId, string $period, array $data): array;

    public function calculateReport(string $user, int $reportId): array;

    public function approveReport(string $user, int $reportId): array;

    public function getMaterialStock(array $filters): array;

    public function getOpeningStock(string $materialId, string $date, mixed $plantId): array;

    public function getClosingStock(string $materialId, string $date, mixed $plantId): array;
}
