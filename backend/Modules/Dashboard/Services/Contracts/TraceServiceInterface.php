<?php
declare(strict_types=1);
namespace Modules\Dashboard\Services\Contracts;

interface TraceServiceInterface
{
    public function forwardTrace(string $traceNo, mixed $plantId = null, ?int $userId = null): array;
    public function backwardTrace(string $traceNo, mixed $plantId = null, ?int $userId = null): array;
    public function getTraceTree(string $traceNo, mixed $plantId = null, ?int $userId = null): array;
    public function searchTraces(mixed $materialId, ?string $batchNo = null, mixed $plantId = null, ?int $userId = null): array;
}