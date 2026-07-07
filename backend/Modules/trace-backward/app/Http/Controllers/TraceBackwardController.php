<?php

declare(strict_types=1);

namespace Modules\TraceBackward\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\TraceBackward\Http\Requests\ShipmentTraceVerificationRequest;
use Modules\TraceBackward\Http\Requests\TraceBackwardDetailRequest;
use Modules\TraceBackward\Http\Requests\TraceBackwardListRequest;
use Modules\TraceBackward\Http\Requests\TraceBackwardSearchRequest;
use Modules\TraceBackward\Services\Contracts\ShipmentTraceVerificationServiceInterface;
use Modules\TraceBackward\Services\Contracts\TraceBackwardServiceInterface;

class TraceBackwardController extends Controller
{
    public function __construct(
        protected TraceBackwardServiceInterface $traceBackwardService,
        protected ShipmentTraceVerificationServiceInterface $verificationService,
    ) {}

    public function index(TraceBackwardListRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;
            $data = $this->traceBackwardService->getBackwardList($filters);

            return ApiResponse::success($data, 'Backward list retrieved', 200);
        } catch (\Exception $e) {
            Log::error('TraceBackward index failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve backward list', 500);
        }
    }

    public function search(TraceBackwardSearchRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $materialId = (int) $data['id_material'];
            $batchNo = $data['batch_no'] ?? null;
            $plantId = isset($data['id_plant']) ? (int) $data['id_plant'] : null;
            $userId = $request->user()?->id;
            $result = $this->traceBackwardService->searchTraces($materialId, $batchNo, $plantId, $userId);

            return ApiResponse::success($result, 'Backward search results retrieved', 200);
        } catch (\Exception $e) {
            Log::error('TraceBackward search failed', ['exception' => $e]);

            return ApiResponse::error('Failed to search backward traces', 500);
        }
    }

    public function traceDetail(TraceBackwardDetailRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $userId = $request->user()?->id;
            $plantId = $data['id_plant'] ?? null;
            $traceNo = $data['trace_no'];
            $idMaterial = isset($data['id_material']) ? (int) $data['id_material'] : null;

            $rows = $this->traceBackwardService->getBackwardTraceDetail($traceNo, $idMaterial, $plantId, $userId);
            $result = [
                'initial' => array_values(array_filter((array) $rows, fn ($r) => ($r->level ?? 0) === 1)),
                'chain' => array_values(array_filter((array) $rows, fn ($r) => ($r->level ?? 0) > 1)),
            ];

            return ApiResponse::success($result, 'Backward trace detail retrieved', 200);
        } catch (\Exception $e) {
            Log::error('TraceBackward traceDetail failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve backward trace detail', 500);
        }
    }

    public function verify(ShipmentTraceVerificationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $result = isset($data['so_no'])
                ? $this->verificationService->verifyBySoNo($data['so_no'])
                : $this->verificationService->verifyByTraceNo($data['trace_no']);

            return ApiResponse::success($result, 'Backward trace verification retrieved', 200);
        } catch (\Exception $e) {
            Log::error('TraceBackward verify failed', ['exception' => $e]);

            return ApiResponse::error('Failed to verify backward trace', 500);
        }
    }
}
