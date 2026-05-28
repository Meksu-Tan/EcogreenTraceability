<?php declare(strict_types=1);

namespace Modules\TsTransfer\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\TsTransfer\Services\TransferService;
use Modules\TsTransfer\Http\Requests\StoreTransferRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(
        protected TransferService $transferService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);

        try {
            $data = $this->transferService->getTransferList($plantId);

            return ApiResponse::success($data, 'Transfer list retrieved successfully', 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to retrieve transfer list: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreTransferRequest $request): JsonResponse
    {
        $flag = $request->input('flag');
        $mode = $request->input('mode', 'ADD');
        $user = $request->user()->name ?? 'system';
        $plantId = (int) $request->input('id_plant', 0);

        try {
            $result = match ($flag) {
                'post_transferEntry' => $this->handleTransferEntry($request, $user, $plantId),
                'post_matlDocNumber' => $this->transferService->createMaterialDocument(
                    $user,
                    (int) $request->input('id'),
                    $request->input('number'),
                    $mode
                ),
                'post_updateEntrySubTank' => $this->transferService->updateEntrySubTank(
                    $user,
                    (int) $request->input('idHead'),
                    $request->input('idTankTail', [])
                ),
                default => ['response' => 0],
            };

            return $this->buildResponse($result, $flag, $mode);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Transfer operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function handleTransferEntry(Request $request, string $user, int $plantId): array
    {
        $data = $request->all();
        $trfType = $request->input('trf_type', 'out');

        $result = $this->transferService->executeTransfer($user, $data, $plantId);

        // Auto-adjustment: if stock not enough (response 4) and not 'all' type
        if ($result['response'] == 4 && $trfType !== 'all') {
            $result = $this->transferService->executeTransferWithAdjustment($user, $data, $plantId);
        }

        return $result;
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user()->name ?? 'system';

        try {
            $result = $this->transferService->deactivateTransfer($id, $user);
            return $this->buildResponse($result, 'delete', 'delete');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function activeMaterials(): JsonResponse
    {
        try {
            $data = $this->transferService->getActiveMaterials();
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    public function newEntryNo(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = (int) $request->input('id_material');

        try {
            $entryNo = $this->transferService->generateEntryNo($materialId, $plantId);
            return response()->json(['data' => [['entryNo' => $entryNo]]]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    public function activeTanksRundown(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = $request->has('idMaterial')
            ? (int) $request->input('idMaterial')
            : null;

        try {
            $data = $this->transferService->getActiveTanksRundown($materialId, $plantId);
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    public function activeSpecificTanksRundown(Request $request): JsonResponse
    {
        $sloc = (int) $request->input('sloc');

        try {
            $data = $this->transferService->getActiveSpecificTanksRundown($sloc);
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    public function totalStockMaterial(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = (int) $request->input('idMaterial');
        $tankId = (int) $request->input('idTank');

        try {
            $total = $this->transferService->getTotalStockMaterial($materialId, $tankId, $plantId);
            return response()->json(['data' => [['total' => number_format($total, 3)]]]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    public function matlDocNumber(Request $request): JsonResponse
    {
        $flag = $request->input('flag', 'post_matlDocNumber');
        $mode = $request->input('mode', 'ADD');
        $user = $request->user()->name ?? 'system';

        try {
            $result = $this->transferService->createMaterialDocument(
                $user,
                (int) $request->input('id'),
                $request->input('number'),
                $mode
            );
            return $this->buildResponse($result, $flag, $mode);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateEntrySubTank(Request $request): JsonResponse
    {
        $flag = $request->input('flag', 'post_updateEntrySubTank');
        $mode = $request->input('mode', 'UPDATE');
        $user = $request->user()->name ?? 'system';

        try {
            $result = $this->transferService->updateEntrySubTank(
                $user,
                (int) $request->input('idHead'),
                $request->input('idTankTail', [])
            );
            return $this->buildResponse($result, $flag, $mode);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function supplierMaterialCode(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = (int) $request->input('idMaterial');
        $tankId = (int) $request->input('idTank');

        try {
            $result = $this->transferService->getUpdateSupplierMaterial($materialId, $tankId, $plantId);
            return response()->json(['data' => $result ? [$result] : []]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    protected function buildResponse(array $result, string $flag, string $mode): JsonResponse
    {
        $response = $result['response'] ?? null;

        if ($response === null) {
            return ApiResponse::error('Unexpected error', 500);
        }

        $status = match ($response) {
            1 => 1,
            2, 3, 4, 6, 9, 98, 99 => 0,
            default => 0,
        };

        $feature = match (true) {
            str_contains($flag, 'transfer') => 'TRANSFER',
            str_contains($flag, 'matlDoc') => 'MATL DOC NO',
            str_contains($flag, 'subTank') || str_contains($flag, 'updateEntrySubTank') => 'SUBTANK',
            $flag === 'delete' => 'DELETE',
            default => strtoupper($flag),
        };

        $message = match ($response) {
            1 => 'Success ' . $mode . ' ' . $feature,
            2 => $feature . ' already exists',
            3 => $feature . ' Entry Error',
            4 => $feature . ' Stock Not Enough',
            6 => $feature . ' Supplier Trace Missing',
            9 => 'Source or Destination Tank is inactive',
            98 => 'Entry data not found!',
            99 => 'Period Locked!',
            default => $result['message'] ?? 'Operation failed',
        };

        return response()->json(['status' => $status, 'message' => $message]);
    }
}
