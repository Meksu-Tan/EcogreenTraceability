<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Supplier\Http\Requests\StoreSupplierRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierRequest;
use Modules\Supplier\Services\SupplierService;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success($this->supplierService->listSuppliers(), 'OK', 200);
    }

    public function active(): JsonResponse
    {
        return ApiResponse::success($this->supplierService->getActiveSuppliers(), 'OK', 200);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), ['created_by' => $request->user()->name]);
        $result = $this->supplierService->storeSupplier($data);

        if ($result['status'] === 1) {
            return ApiResponse::success($result, 'Supplier created', 201);
        }

        return ApiResponse::error($result['message'] ?? 'Failed to create supplier', 422);
    }

    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        $data = array_merge($request->validated(), ['updated_by' => $request->user()->name]);
        $result = $this->supplierService->updateSupplier($id, $data);

        if ($result['status'] === 1) {
            return ApiResponse::success($result, 'Supplier updated');
        }

        return ApiResponse::error($result['message'] ?? 'Failed to update supplier', 422);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user = $request->user()->name;
        $result = $action === 'activate'
            ? $this->supplierService->activateSupplier($id, $user)
            : $this->supplierService->deactivateSupplier($id, $user);

        if ($result['status'] === 1) {
            return ApiResponse::success($result, $action === 'activate' ? 'Supplier activated' : 'Supplier deactivated');
        }

        return ApiResponse::error($result['message'] ?? 'Failed to process supplier', 422);
    }
}
