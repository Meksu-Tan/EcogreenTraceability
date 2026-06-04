<?php declare(strict_types=1);

namespace Modules\TraceBackward\Repositories;

use Illuminate\Database\Connection;
use Modules\TraceBackward\Repositories\Concerns\BackwardDetailQuery;
use Modules\TraceBackward\Repositories\Concerns\BackwardListQuery;
use Modules\TraceBackward\Repositories\Concerns\BackwardSearchQuery;
use Modules\TraceBackward\Repositories\Concerns\BackwardTraceQuery;
use Modules\TraceBackward\Repositories\Contracts\TraceBackwardRepositoryInterface;

class TraceBackwardRepository implements TraceBackwardRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly BackwardListQuery $listQuery,
        private readonly BackwardDetailQuery $detailQuery,
        private readonly BackwardTraceQuery $traceQuery,
        private readonly BackwardSearchQuery $searchQuery,
    ) {}

    public function backwardTrace(string $traceNo, ?int $idMaterial = null, ?int $plantId = null, ?int $userId = null): array
    {
        return $this->traceQuery->execute($traceNo, $idMaterial, $plantId, $userId);
    }

    public function getBackwardList(array $filters = []): array
    {
        return $this->listQuery->execute($filters);
    }

    public function getBackwardTraceDetail(string $traceNo, ?int $idMaterial = null, ?int $plantId = null, ?int $userId = null): array
    {
        return $this->detailQuery->execute($traceNo, $idMaterial, $plantId, $userId);
    }

    public function searchTraces(mixed $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array
    {
        return $this->searchQuery->execute($materialId, $batchNo, $plantId, $userId);
    }
}
