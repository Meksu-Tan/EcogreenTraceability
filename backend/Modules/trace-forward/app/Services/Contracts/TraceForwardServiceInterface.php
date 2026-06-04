<?php declare(strict_types=1);

namespace Modules\TraceForward\Services\Contracts;

interface TraceForwardServiceInterface
{
    public function forwardTrace(string $traceNo, ?int $idMaterial = null): array;
    public function searchTraces(int $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array;
    public function getForwardList(array $filters = []): array;
    public function getForwardTraceDetail(int $idHeader, string $traceNo, int $idMaterial, ?int $plantId = null, ?int $userId = null): array;
}
