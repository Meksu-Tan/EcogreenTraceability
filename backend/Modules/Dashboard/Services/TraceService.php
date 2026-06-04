<?php declare(strict_types=1);

namespace Modules\Dashboard\Services;

use Modules\Dashboard\Repositories\TraceRepository;
use Modules\Dashboard\Services\Contracts\TraceServiceInterface;

class TraceService implements TraceServiceInterface
{
    public function __construct(
        protected TraceRepository $traceRepository
    ) {}

    /**
     * Forward Trace - Track material flow forward from source
     */
    public function forwardTrace(string $traceNo, mixed $plantId = null, ?int $userId = null): array
    {
        return $this->traceRepository->forwardTrace($traceNo, $plantId, $userId);
    }

    /**
     * Backward Trace - Track material back to suppliers/origin
     */
    public function backwardTrace(string $traceNo, mixed $plantId = null, ?int $userId = null): array
    {
        return $this->traceRepository->backwardTrace($traceNo, $plantId, $userId);
    }

    /**
     * Get complete trace tree for visualization
     */
    public function getTraceTree(string $traceNo, mixed $plantId = null, ?int $userId = null): array
    {
        $tree = [
            'root' => null,
            'nodes' => [],
            'edges' => [],
        ];

        // Get root trace header
        $root = $this->traceRepository->getTraceHeader($traceNo, $plantId, $userId);
        if (!$root) {
            return $tree;
        }

        $tree['root'] = $root;

        // Get all related traces (forward chain)
        $forwardTraces = $this->traceRepository->getForwardChain($traceNo, $plantId, $userId);
        foreach ($forwardTraces as $trace) {
            $tree['nodes'][] = [
                'id' => $trace->to_trace_no,
                'type' => 'trace',
                'data' => $trace,
            ];

            if ($trace->from_trace_no) {
                $tree['edges'][] = [
                    'from' => $trace->from_trace_no,
                    'to' => $trace->to_trace_no,
                    'type' => 'feeds_into',
                ];
            }
        }

        // Get backward chain (suppliers)
        $backwardTraces = $this->traceRepository->getBackwardChain($traceNo, $plantId, $userId);
        foreach ($backwardTraces as $trace) {
            // Check if node already exists
            $exists = collect($tree['nodes'])->contains('id', $trace->from_trace_no);

            if (!$exists) {
                $tree['nodes'][] = [
                    'id' => $trace->from_trace_no,
                    'type' => 'trace',
                    'data' => $trace,
                ];
            }

            $tree['edges'][] = [
                'from' => $trace->from_trace_no,
                'to' => $trace->to_trace_no,
                'type' => 'consumed_by',
            ];
        }

        return $tree;
    }

    /**
     * Search traces by material or batch
     */
    public function searchTraces(mixed $materialId, ?string $batchNo = null, mixed $plantId = null, ?int $userId = null): array
    {
        return $this->traceRepository->searchTraces($materialId, $batchNo, $plantId, $userId);
    }
}