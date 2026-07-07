<?php

declare(strict_types=1);

namespace Modules\TraceForward\Services;

use Modules\TraceForward\Repositories\TraceForwardRepositoryInterface;
use Modules\TraceForward\Services\Contracts\TraceForwardServiceInterface;

class TraceForwardService implements TraceForwardServiceInterface
{
    public function __construct(
        protected TraceForwardRepositoryInterface $traceForwardRepository
    ) {}

    public function forwardTrace(string $traceNo, ?int $idMaterial = null, ?int $plantId = null, ?int $userId = null): array
    {
        return $this->traceForwardRepository->forwardTrace($traceNo, $idMaterial, $plantId, $userId);
    }

    public function searchTraces(int $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array
    {
        return $this->traceForwardRepository->searchTraces($materialId, $batchNo, $plantId, $userId);
    }

    public function getForwardList(array $filters = []): array
    {
        return $this->traceForwardRepository->getForwardList($filters);
    }

    public function getForwardTraceDetail(string $traceNo, int $idMaterial, ?int $plantId = null, ?int $userId = null): array
    {
        return $this->traceForwardRepository->getForwardTraceDetail($traceNo, $idMaterial, $plantId, $userId);
    }
}
