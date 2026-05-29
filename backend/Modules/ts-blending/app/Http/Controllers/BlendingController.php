<?php declare(strict_types=1);

namespace Modules\TsBlending\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\TsBlending\Services\BlendingService;
use Modules\TsBlending\Http\Requests\StoreBlendingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlendingController extends Controller
{
    public function __construct(
        protected BlendingService $blendingService
    ) {}

    /**
     * Get blending list for DataTable
     */
    public function index(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);

        try {
            $data = $this->blendingService->getBlendingList($plantId);

            return ApiResponse::success($data, 'Blending list retrieved successfully', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve blending list: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a new blending entry or update existing
     */
    public function store(StoreBlendingRequest $request): JsonResponse
    {
        $flag = $request->input('flag');
        $mode = $request->input('mode', 'ADD');
        $user = $request->user()->name ?? 'system';
        $plantId = (int) $request->input('id_plant', 0);

        try {
            if ($flag === 'post_blendingEntryMaterial') {
                $result = $this->blendingService->addMaterialToBlending($user, [
                    'entryNo' => $request->input('entryNo'),
                    'idMaterialSource' => (int) $request->input('idMaterialSource'),
                    'qty' => $request->input('qty'),
                    'idTank' => (int) $request->input('idTank'),
                    'mode' => $mode,
                ], $plantId);

            } elseif ($flag === 'post_blendingEntry') {
                $result = $this->blendingService->executeBlending($user, [
                    'entry_no' => $request->input('entry_no'),
                    'entry_date' => $request->input('entry_date'),
                    'id_material' => (int) $request->input('id_material'),
                    'material_doc' => $request->input('material_doc'),
                    'qty' => $request->input('qty'),
                    'tankNo' => $request->input('tankNo', []),
                ], $plantId);

            } elseif ($flag === 'post_matlDocNumber') {
                $result = $this->blendingService->createMaterialDocument(
                    $user,
                    (int) $request->input('id'),
                    $request->input('number'),
                    $mode
                );

            } elseif ($flag === 'post_updateEntrySubTank') {
                $result = $this->blendingService->updateEntrySubTank(
                    $user,
                    (int) $request->input('idHead'),
                    $request->input('idTankTail', [])
                );

            } elseif ($flag === 'delete_blendingMaterial') {
                $id = (int) $request->input('id');
                $success = $this->blendingService->deleteBlendingMaterial($id);
                $result = ['response' => $success ? 1 : 0];

            } else {
                return ApiResponse::error('Unknown flag: ' . $flag, 400);
            }

            return $this->buildResponse($result, $flag, $mode, $request);

        } catch (\Exception $e) {
            return ApiResponse::error('Blending operation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get active materials
     */
    public function activeMaterials(): JsonResponse
    {
        try {
            $data = $this->blendingService->getActiveMaterials();
            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Generate new blending entry number
     */
    public function newEntryNo(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = (int) $request->input('id_material');

        try {
            $entryNo = $this->blendingService->generateEntryNo($materialId, $plantId);
            return ApiResponse::success([['entryNo' => $entryNo]]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get total stock material
     */
    public function totalStockMaterial(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = (int) $request->input('idMaterial');

        try {
            $total = $this->blendingService->getTotalStockMaterial($materialId, $plantId);
            return ApiResponse::success([['total' => number_format($total, 3)]]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get total qty material
     */
    public function totalQtyMaterial(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $mode = $request->input('mode');
        $entryNo = $request->input('entryNo');
        $idHead = $request->input('idHead');

        try {
            $total = $this->blendingService->getTotalQtyMaterial($mode, $entryNo, $idHead ? (int) $idHead : null, $plantId);
            return ApiResponse::success([['total' => number_format($total, 3)]]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get material list for DataTable
     */
    public function materialList(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $mode = $request->input('mode');
        $entryNo = $request->input('entryNo');
        $idHead = $request->input('idHead');

        try {
            $data = $this->blendingService->getMaterialList($mode, $entryNo, $idHead ? (int) $idHead : null, $plantId);
            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get active tanks rundown
     */
    public function activeTanksRundown(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = (int) $request->input('idMaterial');

        try {
            $data = $this->blendingService->getActiveTanksRundown($materialId, $plantId);
            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get active specific tanks rundown
     */
    public function activeSpecificTanksRundown(Request $request): JsonResponse
    {
        $sloc = (int) $request->input('sloc');

        try {
            $data = $this->blendingService->getActiveSpecificTanksRundown($sloc);
            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get all tanks (sloc) for dropdown (like rm-entry)
     */
    public function tanks(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);

        try {
            $data = $this->blendingService->getTanks($plantId > 0 ? $plantId : null);
            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get tank details (sub-sloc) for selected sloc (like rm-entry)
     */
    public function tankDetails(Request $request, string $tankId): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);

        try {
            $data = $this->blendingService->getTankDetails($tankId, $plantId > 0 ? $plantId : null);
            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Get all tanks (m_tank) for dropdown — independent of material
     */
    public function allTanks(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);

        try {
            $data = $this->blendingService->getAllTanks($plantId);
            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete blending entry
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user()->name ?? 'system';

        try {
            $result = $this->blendingService->deactivateBlending($id, $user);

            return $this->buildResponse($result, 'delete', 'delete', $request);

        } catch (\Exception $e) {
            return ApiResponse::error('Delete failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Build standardized response
     */
    protected function buildResponse(array $result, string $flag, string $mode, Request $request): JsonResponse
    {
        $response = $result['response'] ?? null;

        if ($response === null) {
            return ApiResponse::error('Unexpected error', 500);
        }

        $status = match ($response) {
            1 => 1,
            2 => 0,
            3 => 0,
            4 => 0,
            99 => 0,
            default => 0,
        };

        $message = match ($response) {
            1 => 'Success ' . $mode . ' ' . ucfirst(str_replace('post_', '', $flag)),
            2 => 'Material already exists in blending entry',
            3 => 'Entry Error' . (isset($result['error_detail']) ? ': ' . $result['error_detail'] : ''),
            4 => 'No Blend Material!',
            6 => 'No Trace found' . (isset($result['error_detail']) ? ': ' . $result['error_detail'] : ''),
            99 => 'Period Locked!',
            default => 'Operation failed' . (isset($result['message']) ? ': ' . $result['message'] : '') . (isset($result['error_detail']) ? ': ' . $result['error_detail'] : ''),
        };

        if ($status === 1) {
            $extra = $flag === 'post_blendingEntryMaterial' ? [
                'mode' => $mode,
                'idMaterial' => $request->input('idMaterial'),
                'entryDate' => $request->input('entryDate'),
                'entryNo' => $request->input('entryNo'),
                'idHead' => $request->input('idHead'),
                'materialDoc' => $request->input('materialDoc'),
                'idTank' => $request->input('idTank'),
            ] : [];
            return ApiResponse::success(null, $message, 200, $extra);
        }
        return ApiResponse::error($message, 422);
    }
}