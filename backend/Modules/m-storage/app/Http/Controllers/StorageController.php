<?php

namespace Modules\Storage\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Storage\Http\Requests\StoreStorageTankRequest;
use Modules\Storage\Http\Requests\StoreStorageDetailRequest;
use Modules\Storage\Http\Requests\StoreWarehouseRequest;
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
        return response()->json(['status' => 1, 'data' => $this->storageService->listTanks()]);
    }

    public function storeTank(StoreStorageTankRequest $request): JsonResponse
    {
        $data   = array_merge($request->validated(), ['created_by' => $request->user()->name]);
        $result = $this->storageService->storeTank($data);
        return response()->json($result, $result['status'] === 1 ? 201 : 422);
    }

    public function updateTank(Request $request, int $id): JsonResponse
    {
        $data   = array_merge($request->only(['code_2', 'code_3', 'code_4', 'id_plant', 'description']), ['updated_by' => $request->user()->name]);
        $result = $this->storageService->updateTank($id, $data);
        return response()->json($result);
    }

    public function destroyTank(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name;
        $result = $action === 'activate'
            ? $this->storageService->activateTank($id, $user)
            : $this->storageService->deactivateTank($id, $user);
        return response()->json($result);
    }

    // -----------------------------------------------------------------------
    // Storage Detail
    // -----------------------------------------------------------------------
    public function indexDetails(Request $request): JsonResponse
    {
        $tankId = $request->query('id_tank');
        return response()->json(['status' => 1, 'data' => $this->storageService->listDetails((int) $tankId)]);
    }

    public function storeDetail(StoreStorageDetailRequest $request): JsonResponse
    {
        $data   = array_merge($request->validated(), ['created_by' => $request->user()->name]);
        $result = $this->storageService->storeDetail($data);
        return response()->json($result, $result['status'] === 1 ? 201 : 422);
    }

    public function updateDetail(Request $request, int $id): JsonResponse
    {
        $data   = array_merge($request->only(['tf_number']), ['updated_by' => $request->user()->name]);
        $result = $this->storageService->updateDetail($id, $data);
        return response()->json($result);
    }

    public function destroyDetail(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name;
        $result = $action === 'activate'
            ? $this->storageService->activateDetail($id, $user)
            : $this->storageService->deactivateDetail($id, $user);
        return response()->json($result);
    }

    // -----------------------------------------------------------------------
    // Warehouse
    // -----------------------------------------------------------------------
    public function indexWarehouses(): JsonResponse
    {
        return response()->json(['status' => 1, 'data' => $this->storageService->listWarehouses()]);
    }

    public function storeWarehouse(StoreWarehouseRequest $request): JsonResponse
    {
        $data   = array_merge($request->validated(), ['created_by' => $request->user()->name]);
        $result = $this->storageService->storeWarehouse($data);
        return response()->json($result, $result['status'] === 1 ? 201 : 422);
    }

    public function updateWarehouse(Request $request, int $id): JsonResponse
    {
        $data   = array_merge($request->only(['id_batch', 'code', 'description']), ['updated_by' => $request->user()->name]);
        $result = $this->storageService->updateWarehouse($id, $data);
        return response()->json($result);
    }

    public function destroyWarehouse(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name;
        $result = $action === 'activate'
            ? $this->storageService->activateWarehouse($id, $user)
            : $this->storageService->deactivateWarehouse($id, $user);
        return response()->json($result);
    }
}
