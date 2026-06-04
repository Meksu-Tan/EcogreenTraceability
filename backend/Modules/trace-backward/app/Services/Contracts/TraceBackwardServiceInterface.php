<?php declare(strict_types=1);

namespace Modules\TraceBackward\Services\Contracts;

interface TraceBackwardServiceInterface
{
    public function backwardTrace(string $traceNo, ?int $idMaterial = null, ?int $plantId = null, ?int $userId = null): array;
    public function getBackwardList(array $filters = []): array;
    public function getBackwardTraceDetail(string $traceNo, ?int $idMaterial = null, ?int $plantId = null, ?int $userId = null): array;
    public function searchTraces(mixed $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array;
}
