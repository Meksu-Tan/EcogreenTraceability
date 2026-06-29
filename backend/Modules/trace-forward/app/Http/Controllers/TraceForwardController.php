<?php
declare(strict_types=1);
namespace Modules\TraceForward\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Modules\TraceForward\Services\Contracts\TraceForwardServiceInterface;
use Modules\TraceForward\Http\Requests\TraceForwardDetailRequest;
use Modules\TraceForward\Http\Requests\TraceForwardListRequest;
use Modules\TraceForward\Http\Requests\TraceForwardSearchRequest;
use Modules\TraceForward\Http\Resources\TraceForwardResource;
use Illuminate\Http\JsonResponse;

class TraceForwardController extends Controller
{
    public function __construct(
        protected TraceForwardServiceInterface $traceForwardService
    ) {}

    public function index(TraceForwardListRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;
            $data = $this->traceForwardService->getForwardList($filters);
            return ApiResponse::success($data, 'Forward list retrieved', 200);
        } catch (\Exception $e) {
            Log::error('TraceForward index failed', ['exception' => $e]);
            return ApiResponse::error('Failed to retrieve forward list', 500);
        }
    }

    public function search(TraceForwardSearchRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $materialId = (int) $data['id_material'];
            $batchNo = $data['batch_no'] ?? null;
            $result = $this->traceForwardService->searchTraces($materialId, $batchNo);
            return ApiResponse::success($result, 'Trace search results retrieved', 200);
        } catch (\Exception $e) {
            Log::error('TraceForward search failed', ['exception' => $e]);
            return ApiResponse::error('Failed to search traces', 500);
        }
    }

    public function traceDetail(TraceForwardDetailRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $userId = $request->user()?->id;
            $plantId = $data['id_plant'] ?? null;
            $idHeader = (int) ($data['id_header'] ?? 0);
            $traceNo = $data['trace_no'] ?? '';
            $idMaterial = (int) ($data['id_material'] ?? 0);

            $rows   = $this->traceForwardService->getForwardTraceDetail($idHeader, $traceNo, $idMaterial, $plantId, $userId);
            $result = [
                'initial' => array_values(array_filter((array) $rows, fn($r) => ($r->level ?? 0) === 1)),
                'chain'   => array_values(array_filter((array) $rows, fn($r) => ($r->level ?? 0) > 1)),
            ];
            return ApiResponse::success($result, 'Trace detail retrieved', 200);
        } catch (\Exception $e) {
            Log::error('TraceForward traceDetail failed', ['exception' => $e]);
            return ApiResponse::error('Failed to retrieve trace detail', 500);
        }
    }

    public function show(string $traceNo): JsonResponse
    {
        return ApiResponse::success(['trace_no' => $traceNo], 'Trace found', 200);
    }
}
