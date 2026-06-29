<?php
declare(strict_types=1);
namespace Modules\TsPackage\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TsPackage\Services\Contracts\PackageServiceInterface;
use Modules\TsPackage\Http\Requests\StorePackageEntryRequest;
use Modules\TsPackage\Http\Requests\UpdatePackageEntryRequest;
use Modules\TsPackage\Http\Requests\GenerateTraceNoRequest;
use Modules\TsPackage\Http\Resources\PackageEntryResource;
use Modules\Shared\Helpers\ResponseCode;

class PackageEntryController extends Controller
{
    public function __construct(
        protected PackageServiceInterface $packageService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $plantId = (int) ($request->get('plant_context')['plant_code'] ?? $request->input('id_plant', 0));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 50)));

        try {
            $result = $this->packageService->getDtPckEntry($plantId, $page, $perPage);
            return ApiResponse::paginated(
                is_array($result['data']) ? $result['data'] : $result['data']->values()->toArray(),
                $result['total'],
                $page,
                $perPage,
                'Package entries retrieved successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve package entries: ' . $e->getMessage(), 500);
        }
    }

    public function store(StorePackageEntryRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'System';
            $data = $request->validated();
            // In case plant needs resolving
            $data['id_plant'] = $request->get('plant_context')['plant_code'] ?? (int) $request->input('id_plant', 0);
            
            $res = $this->packageService->store($user, $data);
            if ($res['response'] == ResponseCode::PERIOD_LOCKED) {
                return ApiResponse::error($res['message'] ?? 'Period is locked.', 422);
            }
            if ($res['response'] != 1) {
                return ApiResponse::error($res['message'] ?? 'Failed to store package entry.', 400);
            }

            return ApiResponse::success(null, 'Package entry stored successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to store package entry: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'System';
            $traceNo = $request->input('traceNo');
            if (!$traceNo) {
                return ApiResponse::error('Trace number is required for cancellation.', 422);
            }

            $res = $this->packageService->cancel($user, ['traceNo' => $traceNo]);
            if ($res['response'] == ResponseCode::PERIOD_LOCKED) {
                return ApiResponse::error($res['message'] ?? 'Period is locked.', 422);
            }
            if ($res['response'] != 1) {
                return ApiResponse::error($res['message'] ?? 'Failed to cancel package entry.', 400);
            }

            return ApiResponse::success(null, 'Package entry cancelled successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to cancel package entry: ' . $e->getMessage(), 500);
        }
    }

    public function updatePo(UpdatePackageEntryRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'System';
            $res = $this->packageService->updatePo($user, $request->validated());
            if ($res['response'] != 1) {
                return ApiResponse::error($res['message'] ?? 'Failed to update PO.', 400);
            }
            return ApiResponse::success(null, 'PO number updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update PO: ' . $e->getMessage(), 500);
        }
    }

    public function updateBatch(UpdatePackageEntryRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'System';
            $res = $this->packageService->updateBatch($user, $request->validated());
            if ($res['response'] != 1) {
                return ApiResponse::error($res['message'] ?? 'Failed to update batch.', 400);
            }
            return ApiResponse::success(null, 'Batch number and warehouse updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update batch: ' . $e->getMessage(), 500);
        }
    }

    public function updateSubTank(UpdatePackageEntryRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'System';
            $res = $this->packageService->updateSubTank($user, $request->validated());
            if ($res['response'] != 1) {
                return ApiResponse::error($res['message'] ?? 'Failed to update subtank.', 400);
            }
            return ApiResponse::success(null, 'Subtank selection updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update subtank: ' . $e->getMessage(), 500);
        }
    }

    public function getActiveFgProduct(): JsonResponse
    {
        try {
            $products = $this->packageService->getActiveFgProduct();
            return ApiResponse::success($products, 'Active finished goods products retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get active finished goods: ' . $e->getMessage(), 500);
        }
    }

    public function getWipMaterialByFgProduct(Request $request): JsonResponse
    {
        try {
            $plant = ($request->get('plant_context')['plant_code'] ?? null) ?? $request->input('id_plant') ?? $request->input('plant');
            $data = [
                'idMaterialPck' => $request->input('idMaterialPck'),
                'tank' => $request->input('tank'),
                'id_plant' => $plant,
            ];
            $results = $this->packageService->getWipMaterialByFgProduct($data);
            return ApiResponse::success($results, 'WIP materials retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get WIP materials: ' . $e->getMessage(), 500);
        }
    }

    public function getCmbActiveTankPck(Request $request): JsonResponse
    {
        try {
            $results = $this->packageService->getCmbActiveTankPck([
                'rundownID'  => $request->input('rundownID'),
                'plant_code' => $request->get('plant_context')['plant_code'] ?? $request->input('id_plant') ?? $request->input('plant_code'),
            ]);
            return ApiResponse::success($results, 'Active tanks retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get active tanks: ' . $e->getMessage(), 500);
        }
    }

    public function getCmbActiveWarehousePck(Request $request): JsonResponse
    {
        try {
            $results = $this->packageService->getCmbActiveWarehousePck([
                'batchNo' => $request->input('batchNo')
            ]);
            return ApiResponse::success($results, 'Active warehouses retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get warehouses: ' . $e->getMessage(), 500);
        }
    }

    public function getCmbActiveSpecificTank(Request $request): JsonResponse
    {
        try {
            $results = $this->packageService->getCmbActiveSpecificTank([
                'sloc' => $request->input('sloc'),
                'fgProduct' => $request->input('fgProduct'),
            ]);
            return ApiResponse::success($results, 'Active specific tanks retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get specific tanks: ' . $e->getMessage(), 500);
        }
    }

    public function newTraceNo(GenerateTraceNoRequest $request): JsonResponse
    {
        $materialId = (int) $request->validated('id_material');
        $plantId = (int) ($request->get('plant_context')['plant_code'] ?? $request->input('id_plant', 0));
        $warehouseId = $request->validated('warehouse') ? (int) $request->validated('warehouse') : null;
        $batchNo = $request->validated('batch_no');
        try {
            $traceNo = $this->packageService->generateTraceNo($materialId, $plantId, $warehouseId, $batchNo);
            return ApiResponse::success([['traceNo' => $traceNo]]);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to generate trace number: ' . $e->getMessage(), 500);
        }
    }

    public function getAllWarehouses(): JsonResponse
    {
        try {
            $warehouses = $this->packageService->getAllWarehouses();
            return ApiResponse::success($warehouses, 'Warehouses retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve warehouses: ' . $e->getMessage(), 500);
        }
    }
}
