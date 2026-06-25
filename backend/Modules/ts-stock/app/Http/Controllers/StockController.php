<?php
declare(strict_types=1);
namespace Modules\TsStock\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\TsStock\Services\Contracts\StockServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Modules\TsStock\Http\Requests\StockInquiryRequest;

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
            return ApiResponse::success($result['data'] ?? $result);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve stock list: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->stockService->getStockDetail((int) $id);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success($result['data'] ?? $result);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve stock detail: ' . $e->getMessage(), 500);
        }
    }

    public function getActiveMaterials(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search');
            $type = $request->input('type'); // 'WIP' or 'WH'
            $result = $this->stockService->getActiveMaterialStock($search, $type);
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success($result['data'] ?? $result);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve active materials: ' . $e->getMessage(), 500);
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
            return ApiResponse::success($result['data'] ?? $result);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve stock movements: ' . $e->getMessage(), 500);
        }
    }

    public function getActiveSlocs(): JsonResponse
    {
        try {
            $result = $this->stockService->getActiveSlocs();
            if (isset($result['status']) && $result['status'] === 0) {
                return ApiResponse::error($result['message'] ?? 'Not found', 404);
            }
            return ApiResponse::success($result['data'] ?? $result);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve active slocs: ' . $e->getMessage(), 500);
        }
    }
}
