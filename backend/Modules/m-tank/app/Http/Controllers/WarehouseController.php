<?php

declare(strict_types=1);

namespace Modules\Tank\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tank\Http\Requests\WarehouseRequest;
use Modules\Tank\Repositories\Contracts\WarehouseRepositoryInterface;

class WarehouseController extends Controller
{
    public function __construct(
        protected WarehouseRepositoryInterface $warehouseRepo
    ) {}

    public function index(): JsonResponse
    {
        $warehouses = $this->warehouseRepo->getAll();

        return ApiResponse::success($warehouses, 'OK', 200);
    }

    public function store(WarehouseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user()->name ?? 'System';

        $result = $this->warehouseRepo->create($user, $data);

        if ($result !== false) {
            return ApiResponse::success(['id' => $result], 'Warehouse created', 201);
        }

        return ApiResponse::error('Failed to create warehouse (duplicate data or error)', 400);
    }

    public function update(WarehouseRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user()->name ?? 'System';

        $result = $this->warehouseRepo->update($id, $user, $data);

        if ($result) {
            return ApiResponse::success(null, 'Warehouse updated');
        }

        return ApiResponse::error('Failed to update warehouse (duplicate data or not found)', 400);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user = $request->user()->name ?? 'System';

        $result = ($action === 'activate')
            ? $this->warehouseRepo->activate($id, $user)
            : $this->warehouseRepo->deactivate($id, $user);

        if ($result) {
            return ApiResponse::success(null, $action === 'activate' ? 'Warehouse activated' : 'Warehouse deactivated');
        }

        return ApiResponse::error('Failed to process warehouse', 400);
    }
}
