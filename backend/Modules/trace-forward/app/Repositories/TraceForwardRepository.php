<?php declare(strict_types=1);

namespace Modules\TraceForward\Repositories;

use Illuminate\Database\Connection;
use Modules\TraceForward\Repositories\Concerns\ForwardDetailQuery;
use Modules\TraceForward\Repositories\Concerns\ForwardListQuery;
use Modules\TraceForward\Repositories\Concerns\ForwardSearchQuery;
use Modules\TraceForward\Repositories\Concerns\ForwardTraceQuery;
use Modules\TraceForward\Repositories\Contracts\TraceForwardRepositoryInterface;

class TraceForwardRepository implements TraceForwardRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ForwardListQuery $listQuery,
        private readonly ForwardDetailQuery $detailQuery,
        private readonly ForwardTraceQuery $traceQuery,
        private readonly ForwardSearchQuery $searchQuery,
    ) {}

    public function forwardTrace(string $traceNo, ?int $idMaterial = null, ?int $plantId = null, ?int $userId = null): array
    {
        return $this->traceQuery->execute($traceNo, $idMaterial, $plantId, $userId);
    }

    public function searchTraces(mixed $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array
    {
        return $this->searchQuery->execute($materialId, $batchNo, $plantId, $userId);
    }

    public function getForwardList(array $filters = []): array
    {
        return $this->listQuery->execute($filters);
    }

    public function getForwardTraceDetail(int $idHeader, string $traceNo, int $idMaterial, ?int $plantId = null, ?int $userId = null): array
    {
        $rows = $this->detailQuery->execute($traceNo, $idMaterial, $plantId, $userId);

        $initial = [];
        $chain = [];
        foreach ($rows as $row) {
            if ($row->level == 1) {
                $initial[] = $row;
            } else {
                $chain[] = $row;
            }
        }

        return [
            'initial' => $initial,
            'chain' => $chain,
        ];
    }
}
