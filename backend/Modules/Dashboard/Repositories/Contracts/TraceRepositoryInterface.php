<?php
declare(strict_types=1);
namespace Modules\Dashboard\Repositories\Contracts;

interface TraceRepositoryInterface
{
    public function forwardTrace(string $traceNo, mixed $plantId = null, ?int $userId = null): array;
    public function backwardTrace(string $traceNo, mixed $plantId = null, ?int $userId = null): array;
    public function getTraceHeader(string $traceNo, mixed $plantId = null, ?int $userId = null): ?object;
    public function getForwardChain(string $traceNo, mixed $plantId = null, ?int $userId = null): array;
    public function getBackwardChain(string $traceNo, mixed $plantId = null, ?int $userId = null): array;
    public function searchTraces(mixed $materialId, ?string $batchNo = null, mixed $plantId = null, ?int $userId = null): array;
}
