<?php

declare(strict_types=1);

namespace Modules\TsStock\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\TsStock\Http\Requests\ActiveMaterialStockRequest;
use Modules\TsStock\Http\Requests\StockInquiryRequest;
use Modules\TsStock\Http\Resources\StockResource;
use Modules\TsStock\Services\Contracts\StockServiceInterface;

class StockController extends Controller
{
    public function __construct(
        protected StockServiceInterface $stockService
    ) {}

    public function index(StockInquiryRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;

            $result = $this->stockService->getStockList($filters);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }

            return ApiResponse::success(StockResource::collection($result['data'] ?? $result));
        } catch (\Exception $e) {
            Log::error('Stock index failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve stock list', 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->stockService->getStockDetail((int) $id);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }

            return ApiResponse::success(new StockResource($result['data'] ?? $result));
        } catch (\Exception $e) {
            Log::error('Stock show failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve stock detail', 500);
        }
    }

    public function getActiveMaterials(ActiveMaterialStockRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $search = $validated['search'] ?? null;
            $type = $validated['type'] ?? null; // 'WIP' or 'WH'
            $result = $this->stockService->getActiveMaterialStock($search, $type);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }

            return ApiResponse::success(StockResource::collection($result['data'] ?? $result));
        } catch (\Exception $e) {
            Log::error('Stock getActiveMaterials failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve active materials', 500);
        }
    }

    public function getMovements(StockInquiryRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['user_id'] = $request->user()?->id;
            $result = $this->stockService->getStockMovement($filters);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }

            return ApiResponse::success(StockResource::collection($result['data'] ?? $result));
        } catch (\Exception $e) {
            Log::error('Stock getMovements failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve stock movements', 500);
        }
    }

    public function getActiveSlocs(): JsonResponse
    {
        try {
            $result = $this->stockService->getActiveSlocs();
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }

            return ApiResponse::success(StockResource::collection($result['data'] ?? $result));
        } catch (\Exception $e) {
            Log::error('Stock getActiveSlocs failed', ['exception' => $e]);

            return ApiResponse::error('Failed to retrieve active slocs', 500);
        }
    }
}
