<?php
declare(strict_types=1);
namespace Modules\TraceBackward\Repositories\Contracts;

interface TraceBackwardRepositoryInterface
{
    public function backwardTrace(string $traceNo, ?int $idMaterial = null): array;
    public function getBackwardList(array $filters = []): array;
    public function getBackwardTraceDetail(string $traceNo, ?int $idMaterial = null, ?int $plantId = null, ?int $userId = null): array;
    public function searchTraces(?int $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array;
    public function findShipmentBySo(string $soNo): ?object;
    public function findShipmentByTraceNo(string $traceNo): ?object;
}
