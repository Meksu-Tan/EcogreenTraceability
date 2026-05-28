<?php declare(strict_types=1);

namespace Modules\Storage\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Storage\Http\Requests\StoreStorageTankRequest;
use Modules\Storage\Http\Requests\StoreStorageDetailRequest;
use Modules\Storage\Http\Requests\StoreWarehouseRequest;
use Modules\Storage\Http\Requests\UpdateTankRequest;
use Modules\Storage\Http\Requests\UpdateDetailRequest;
use Modules\Storage\Http\Requests\UpdateWarehouseRequest;
use Modules\Storage\Http\Requests\DestroyTankRequest;
use Modules\Storage\Http\Requests\DestroyDetailRequest;
use Modules\Storage\Http\Requests\DestroyWarehouseRequest;
use Modules\Storage\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorageController extends Controller
{
    public function __construct(
        protected StorageService $storageService
    ) {}

    // -----------------------------------------------------------------------
    // Storage Tank
    // -----------------------------------------------------------------------
    public function indexTanks(): JsonResponse
    {
        return ApiResponse::success($this->storageService->listTanks(), 'OK', 200);
    }

    public function storeTank(StoreStorageTankRequest $request): JsonResponse
    {
        $data   = array_merge($request->validated(), ['created_by' => $request->user()->name]);
        $result = $this->storageService->storeTank($data);
        return $result['status'] === 1
            ? ApiResponse::success($result, 'Storage tank created.', 201)
            : ApiResponse::error('Failed to create storage tank.', 422);
    }

    public function updateTank(UpdateTankRequest $request, int $id): JsonResponse
    {
        $data   = array_merge($request->validated(), ['updated_by' => $request->user()->name]);
        $result = $this->storageService->updateTank($id, $data);
        return $result['status'] === 1
            ? ApiResponse::success($result, 'Storage tank updated.', 200)
            : ApiResponse::error('Failed to update storage tank.', 422);
    }

    public function destroyTank(DestroyTankRequest $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name;
        $result = $action === 'activate'
            ? $this->storageService->activateTank($id, $user)
            : $this->storageService->deactivateTank($id, $user);

        return $result['status'] === 1
            ? ApiResponse::success($result, 'Storage tank ' . ($action === 'activate' ? 'activated.' : 'deactivated.'), 200)
            : ApiResponse::error('Failed to ' . $action . ' storage tank.', 422);
    }

    // -----------------------------------------------------------------------
    // Storage Detail
    // -----------------------------------------------------------------------
    public function indexDetails(Request $request): JsonResponse
    {
        $tankId = $request->query('id_tank');
        return ApiResponse::success($this->storageService->listDetails((int) $tankId), 'OK', 200);
    }

    public function storeDetail(StoreStorageDetailRequest $request): JsonResponse
    {
        $data   = array_merge($request->validated(), ['created_by' => $request->user()->name]);
        $result = $this->storageService->storeDetail($data);
        return $result['status'] === 1
            ? ApiResponse::success($result, 'Storage detail created.', 201)
            : ApiResponse::error('Failed to create storage detail.', 422);
    }

    public function updateDetail(UpdateDetailRequest $request, int $id): JsonResponse
    {
        $data   = array_merge($request->validated(), ['updated_by' => $request->user()->name]);
        $result = $this->storageService->updateDetail($id, $data);
        return $result['status'] === 1
            ? ApiResponse::success($result, 'Storage detail updated.', 200)
            : ApiResponse::error('Failed to update storage detail.', 422);
    }

    public function destroyDetail(DestroyDetailRequest $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name;
        $result = $action === 'activate'
            ? $this->storageService->activateDetail($id, $user)
            : $this->storageService->deactivateDetail($id, $user);

        return $result['status'] === 1
            ? ApiResponse::success($result, 'Storage detail ' . ($action === 'activate' ? 'activated.' : 'deactivated.'), 200)
            : ApiResponse::error('Failed to ' . $action . ' storage detail.', 422);
    }

    // -----------------------------------------------------------------------
    // Warehouse
    // -----------------------------------------------------------------------
    public function indexWarehouses(): JsonResponse
    {
        return ApiResponse::success($this->storageService->listWarehouses(), 'OK', 200);
    }

    public function storeWarehouse(StoreWarehouseRequest $request): JsonResponse
    {
        $data   = array_merge($request->validated(), ['created_by' => $request->user()->name]);
        $result = $this->storageService->storeWarehouse($data);
        return $result['status'] === 1
            ? ApiResponse::success($result, 'Warehouse created.', 201)
            : ApiResponse::error('Failed to create warehouse.', 422);
    }

    public function updateWarehouse(UpdateWarehouseRequest $request, int $id): JsonResponse
    {
        $data   = array_merge($request->validated(), ['updated_by' => $request->user()->name]);
        $result = $this->storageService->updateWarehouse($id, $data);
        return $result['status'] === 1
            ? ApiResponse::success($result, 'Warehouse updated.', 200)
            : ApiResponse::error('Failed to update warehouse.', 422);
    }

    public function destroyWarehouse(DestroyWarehouseRequest $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name;
        $result = $action === 'activate'
            ? $this->storageService->activateWarehouse($id, $user)
            : $this->storageService->deactivateWarehouse($id, $user);

        return $result['status'] === 1
            ? ApiResponse::success($result, 'Warehouse ' . ($action === 'activate' ? 'activated.' : 'deactivated.'), 200)
            : ApiResponse::error('Failed to ' . $action . ' warehouse.', 422);
    }
}
