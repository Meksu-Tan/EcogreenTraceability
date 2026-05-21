<?php

namespace Modules\Supplier\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Supplier\Http\Requests\StoreSupplierRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierRequest;
use Modules\Supplier\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['status' => 1, 'data' => $this->supplierService->listSuppliers()]);
    }

    public function active(): JsonResponse
    {
        return response()->json(['status' => 1, 'data' => $this->supplierService->getActiveSuppliers()]);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $data   = array_merge($request->validated(), ['created_by' => $request->user()->name]);
        $result = $this->supplierService->storeSupplier($data);
        return response()->json($result, $result['status'] === 1 ? 201 : 422);
    }

    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        $data   = array_merge($request->validated(), ['updated_by' => $request->user()->name]);
        $result = $this->supplierService->updateSupplier($id, $data);
        return response()->json($result);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name;
        $result = $action === 'activate'
            ? $this->supplierService->activateSupplier($id, $user)
            : $this->supplierService->deactivateSupplier($id, $user);
        return response()->json($result);
    }
}
