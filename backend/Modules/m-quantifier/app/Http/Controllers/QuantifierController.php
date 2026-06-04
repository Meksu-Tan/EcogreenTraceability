<?php declare(strict_types=1);

namespace Modules\Quantifier\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Quantifier\Services\Contracts\QuantifierServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Modules\Quantifier\Http\Requests\StoreQuantifierRequest;

class QuantifierController extends Controller
{
    public function __construct(
        protected QuantifierServiceInterface $quantifierService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->input('status'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'flowmeter' => $request->input('flowmeter'),
                'limit' => $request->input('limit', 100),
                'offset' => $request->input('offset', 0),
            ];
            
            $result = $this->quantifierService->getQuantifierList($filters);
            return ApiResponse::success($result['data'] ?? []);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getFlowmeters(): JsonResponse
    {
        try {
            $result = $this->quantifierService->getActiveFlowmeters();
            return ApiResponse::success($result['data'] ?? []);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $data = $this->quantifierService->getQuantifierDetail((int) $id);
            if (!$data) {
                return ApiResponse::error('Quantifier not found', 404);
            }
            return ApiResponse::success(['status' => 1, 'data' => $data]);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function store(StoreQuantifierRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $data = $request->validated();
            $data['mode'] = $data['mode'] ?? 'ADD';

            $result = $this->quantifierService->storeQuantifier($user, $data);

            if (($result['response'] ?? 0) === 1) {
                return ApiResponse::success($result, $result['message'] ?? 'Quantifier saved', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function deactivate(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $result = $this->quantifierService->deactivateQuantifier($user, (int) $id);
            if (($result['response'] ?? 0) === 1) {
                return ApiResponse::success($result, 'Quantifier deactivated', 200);
            }
            return ApiResponse::error('Failed to deactivate', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function activate(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $result = $this->quantifierService->activateQuantifier($user, (int) $id);
            if (($result['response'] ?? 0) === 1) {
                return ApiResponse::success($result, 'Quantifier activated', 200);
            }
            return ApiResponse::error('Failed to activate', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }
}
