<?php

declare(strict_types=1);

namespace Modules\Inquiry\Repositories\Contracts;

interface PsPaReportRepositoryInterface
{
    public function getReportHeadList(mixed $plantId, ?int $userId = null, ?string $dateFrom = null, ?string $dateTo = null): array;

    public function getReportHead(int $reportId): ?object;

    public function getReportDetail(int $reportId): ?array;

    public function reportExists(string $period, mixed $plantId): bool;

    public function generateReport(string $user, mixed $plantId, string $period, array $data): array;

    public function calculateReport(int $reportId): array;

    public function approveReport(int $reportId, string $user): array;

    public function getMaterialStock(array $filters): array;
}
