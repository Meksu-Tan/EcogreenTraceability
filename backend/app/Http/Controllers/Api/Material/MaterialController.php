<?php

namespace App\Http\Controllers\Api\Material;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\StoreMaterialRequest;
use App\Http\Requests\Material\UpdateMaterialRequest;
use App\Http\Requests\Material\StoreMaterialPackagingRequest;
use App\Services\Material\MaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function __construct(
        protected MaterialService $materialService
    ) {}

    // -----------------------------------------------------------------------
    // Material (WIP)
    // -----------------------------------------------------------------------

    /**
     * GET /api/v1/materials
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data'   => $this->materialService->listMaterials(),
        ]);
    }

    /**
     * POST /api/v1/materials
     */
    public function store(StoreMaterialRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'created_by' => $request->user()->name,
        ]);
        $result = $this->materialService->storeMaterial($data);
        return response()->json($result, $result['status'] === 1 ? 201 : 422);
    }

    /**
     * PUT /api/v1/materials/{id}
     */
    public function update(UpdateMaterialRequest $request, int $id): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'updated_by' => $request->user()->name,
        ]);
        $result = $this->materialService->updateMaterial($id, $data);
        return response()->json($result, $result['status'] === 1 ? 200 : 422);
    }

    /**
     * DELETE /api/v1/materials/{id}
     * Soft-delete (deactivate) or activate based on ?action= param
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name;

        $result = $action === 'activate'
            ? $this->materialService->activateMaterial($id, $user)
            : $this->materialService->deactivateMaterial($id, $user);

        return response()->json($result);
    }

    // -----------------------------------------------------------------------
    // Material Packaging
    // -----------------------------------------------------------------------

    /**
     * GET /api/v1/material-packagings
     */
    public function indexPackaging(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data'   => $this->materialService->listPackagings(),
        ]);
    }

    /**
     * GET /api/v1/material-packagings/source-products
     */
    public function sourceProducts(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data'   => $this->materialService->getActiveSourceProducts(),
        ]);
    }

    /**
     * POST /api/v1/material-packagings
     */
    public function storePackaging(StoreMaterialPackagingRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'created_by' => $request->user()->name,
        ]);
        $result = $this->materialService->storePackaging($data);
        return response()->json($result, $result['status'] === 1 ? 201 : 422);
    }

    /**
     * PUT /api/v1/material-packagings/{id}
     */
    public function updatePackaging(Request $request, int $id): JsonResponse
    {
        $data = array_merge($request->only(['code', 'code_noneudr', 'description', 'id_material']), [
            'updated_by' => $request->user()->name,
        ]);
        $result = $this->materialService->updatePackaging($id, $data);
        return response()->json($result);
    }

    /**
     * DELETE /api/v1/material-packagings/{id}
     */
    public function destroyPackaging(Request $request, int $id): JsonResponse
    {
        $action = $request->query('action', 'deactivate');
        $user   = $request->user()->name;
        $result = $action === 'activate'
            ? $this->materialService->activatePackaging($id, $user)
            : $this->materialService->deactivatePackaging($id, $user);
        return response()->json($result);
    }
}
