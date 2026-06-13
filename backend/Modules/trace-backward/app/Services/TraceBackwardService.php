<?php declare(strict_types=1);

namespace Modules\TraceBackward\Services;

use Modules\TraceBackward\Repositories\Contracts\TraceBackwardRepositoryInterface;
use Modules\TraceBackward\Services\Contracts\TraceBackwardServiceInterface;

class TraceBackwardService implements TraceBackwardServiceInterface
{
    public function __construct(
        protected TraceBackwardRepositoryInterface $traceBackwardRepository
    ) {}

    public function backwardTrace(string $traceNo, ?int $idMaterial = null): array
    {
        return $this->traceBackwardRepository->backwardTrace($traceNo, $idMaterial);
    }

    public function getBackwardList(array $filters = []): array
    {
        return $this->traceBackwardRepository->getBackwardList($filters);
    }

    public function getBackwardTraceDetail(string $traceNo, ?int $idMaterial = null, ?int $plantId = null, ?int $userId = null): array
    {
        return $this->traceBackwardRepository->getBackwardTraceDetail($traceNo, $idMaterial, $plantId, $userId);
    }

    public function searchTraces(mixed $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array
    {
        return $this->traceBackwardRepository->searchTraces($materialId, $batchNo, $plantId, $userId);
    }
}
