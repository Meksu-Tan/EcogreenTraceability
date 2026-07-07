<?php

declare(strict_types=1);

namespace Modules\TraceForward\Repositories;

interface TraceForwardRepositoryInterface
{
    public function forwardTrace(string $traceNo, ?int $idMaterial = null, ?int $plantId = null, ?int $userId = null): array;

    public function searchTraces(?int $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array;

    public function getForwardList(array $filters = []): array;

    public function getForwardTraceDetail(string $traceNo, int $idMaterial, ?int $plantId = null, ?int $userId = null): array;
}
