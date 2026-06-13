<?php declare(strict_types=1);

namespace Modules\TraceBackward\Repositories;

use Illuminate\Database\Connection;
use Modules\TraceBackward\Repositories\Concerns\BackwardDetailQuery;
use Modules\TraceBackward\Repositories\Concerns\BackwardListQuery;
use Modules\TraceBackward\Repositories\Concerns\BackwardSearchQuery;
use Modules\TraceBackward\Repositories\Concerns\BackwardTraceQuery;
use Modules\TraceBackward\Repositories\Contracts\TraceBackwardRepositoryInterface;

/**
 * @todo Technical Debt: This class is 42 lines in this file but delegates to 4 Concern classes
 * (BackwardListQuery, BackwardDetailQuery, BackwardTraceQuery, BackwardSearchQuery).
 * The effective class size across all concerns likely exceeds 200 lines. Requires audit of concern line counts.
 * - Split into: TraceBackwardListQuery, TraceBackwardDetailQuery, TraceBackwardSearchQuery
 * Current concern-based decomposition is already a good pattern — verify each concern stays under 200 lines.
 */
class TraceBackwardRepository implements TraceBackwardRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly BackwardListQuery $listQuery,
        private readonly BackwardDetailQuery $detailQuery,
        private readonly BackwardTraceQuery $traceQuery,
        private readonly BackwardSearchQuery $searchQuery,
    ) {}

    public function backwardTrace(string $traceNo, ?int $idMaterial = null): array
    {
        return $this->traceQuery->execute($traceNo, $idMaterial);
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
