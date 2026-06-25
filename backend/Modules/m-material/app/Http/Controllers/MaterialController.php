<?php
declare(strict_types=1);
namespace Modules\Material\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Material\Http\Requests\StoreMaterialRequest;
use Modules\Material\Http\Requests\UpdateMaterialRequest;
use Modules\Material\Http\Requests\StoreMaterialPackagingRequest;
use Modules\Material\Http\Requests\UpdateMaterialPackagingRequest;
use Modules\Material\Services\Contracts\MaterialServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function __construct(
        protected MaterialServiceInterface $materialService
    ) {}

    // -----------------------------------------------------------------------
    // Material (WIP)
    // -----------------------------------------------------------------------

    /**
     * GET /api/v1/materials
     * Optional ?type=RM|WIP|... filter
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        return ApiResponse::success($this->materialService->listMaterials($type), 'OK', 200);
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
        return $result['status'] === 1
            ? ApiResponse::success($result, 'Material created.', 201)
            : ApiResponse::error('Failed to create material.', 422);
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
        return $result['status'] === 1
            ? ApiResponse::success($result, 'Material updated.', 200)
            : ApiResponse::error('Failed to update material.', 422);
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

        return $result['status'] === 1
            ? ApiResponse::success($result, 'Material ' . ($action === 'activate' ? 'activated.' : 'deactivated.'), 200)
            : ApiResponse::error('Failed to ' . $action . ' material.', 422);
    }

    // -----------------------------------------------------------------------
    // Material Packaging
    // -----------------------------------------------------------------------

    /**
     * GET /api/v1/material-packagings
     */
    public function indexPackaging(): JsonResponse
    {
        return ApiResponse::success($this->materialService->listPackagings(), 'OK', 200);
    }

    /**
     * GET /api/v1/material-packagings/source-products
     */
    public function sourceProducts(): JsonResponse
    {
        return ApiResponse::success($this->materialService->getActiveSourceProducts(), 'OK', 200);
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
        return $result['status'] === 1
            ? ApiResponse::success($result, 'Packaging created.', 201)
            : ApiResponse::error('Failed to create packaging.', 422);
    }

    /**
     * PUT /api/v1/material-packagings/{id}
     */
    public function updatePackaging(UpdateMaterialPackagingRequest $request, int $id): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'updated_by' => $request->user()->name,
        ]);
        $result = $this->materialService->updatePackaging($id, $data);
        return $result['status'] === 1
            ? ApiResponse::success($result, 'Packaging updated.', 200)
            : ApiResponse::error('Failed to update packaging.', 422);
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

        return $result['status'] === 1
            ? ApiResponse::success($result, 'Packaging ' . ($action === 'activate' ? 'activated.' : 'deactivated.'), 200)
            : ApiResponse::error('Failed to ' . $action . ' packaging.', 422);
    }

    /**
     * GET /api/v1/qty/fetch
     */
    public function fetchQty(Request $request): JsonResponse
    {
        $idMaterial = (int) $request->query('id_material', 0);
        $idPlant    = (int) $request->query('id_plant', 0);

        if ($idMaterial <= 0 || $idPlant <= 0) {
            return ApiResponse::error('id_material and id_plant are required', 422);
        }

        $result = $this->materialService->fetchBalance($idPlant, $idMaterial);
        return $result['status'] === 1
            ? ApiResponse::success($result['data'], 'Balance fetched', 200)
            : ApiResponse::error($result['message'] ?? 'Plant not found', 404);
    }
}
