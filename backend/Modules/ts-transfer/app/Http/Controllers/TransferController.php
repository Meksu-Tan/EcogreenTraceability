<?php
declare(strict_types=1);
namespace Modules\TsTransfer\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\TsTransfer\Services\Contracts\TransferServiceInterface;
use Modules\TsTransfer\Http\Requests\StoreTransferRequest;
use Modules\TsTransfer\Http\Requests\ApprovalActionRequest;
use Modules\TsTransfer\Http\Resources\TransferResource;
use Modules\Shared\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @todo Technical Debt: Controller is 386 lines (limit: 200).
 * Recommended: Split into TransferEntryController and TransferApprovalController.
 */
class TransferController extends Controller
{
    public function __construct(
        protected TransferServiceInterface $transferService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $plantId = (int) ($request->get('plant_context')['plant_code'] ?? $request->input('id_plant', 0));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 5)));

        try {
            $result = $this->transferService->getTransferList($plantId, $page, $perPage);

            $data = is_array($result['data']) ? $result['data'] : $result['data']->toArray();

            return ApiResponse::paginated(
                TransferResource::collection($data)->toArray($request),
                $result['total'],
                $page,
                $perPage,
                'Transfer list retrieved successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve transfer list: ' . $e->getMessage(), 500);
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
                    $request->input('idSlocTail', [])
                ),
                default => ['response' => 0],
            };

            return $this->buildResponse($result, $flag, $mode);

        } catch (\Exception $e) {
            return ApiResponse::error('Transfer operation failed: ' . $e->getMessage(), 500);
        }
    }

    protected function handleTransferEntry(Request $request, string $user, int $plantId): array
    {
        $data = $request->all();
        $trfType = $request->input('trf_type', 'out');

        /**
         * @todo This auto-adjustment business logic belongs in TransferService.
         * Refactor to pass $trfType to service and let service handle the logic.
         */
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

            // Audit log for delete operation
            if ($result['response'] === 1) {
                AuditService::log('TRANSFER', 'DELETE', 'Transfer deactivated | ID: ' . $id, $user);
            }

            return $this->buildResponse($result, 'delete', 'delete');
        } catch (\Exception $e) {
            AuditService::log('TRANSFER', 'DELETE_ERROR', 'Transfer delete failed | ID: ' . $id . ' | Error: ' . $e->getMessage(), $user);
            return ApiResponse::error('Delete failed: ' . $e->getMessage(), 500);
        }
    }

    public function activeMaterials(): JsonResponse
    {
        try {
            $data = $this->transferService->getActiveMaterials();
            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function newEntryNo(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = (int) $request->input('id_material');

        try {
            $entryNo = $this->transferService->generateEntryNo($materialId, $plantId);
            return ApiResponse::success([['entryNo' => $entryNo]]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function activeTanksRundown(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = $request->has('idMaterial')
            ? (int) $request->input('idMaterial')
            : null;
        $excludePlant = $request->has('exclude_plant')
            ? filter_var($request->input('exclude_plant'), FILTER_VALIDATE_BOOLEAN)
            : true;

        try {
            $data = $this->transferService->getActiveTanksRundown($materialId, $plantId, $excludePlant);
            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function activeSpecificTanksRundown(Request $request): JsonResponse
    {
        $sloc = (int) $request->input('sloc');

        try {
            $data = $this->transferService->getActiveSpecificTanksRundown($sloc);
            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function totalStockMaterial(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = (int) $request->input('idMaterial');
        $tankId = (int) $request->input('idSloc');

        try {
            $total = $this->transferService->getTotalStockMaterial($materialId, $tankId, $plantId);
            return ApiResponse::success([['total' => $total]]);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
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
            return ApiResponse::error($e->getMessage(), 500);
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
                $request->input('idSlocTail', [])
            );
            return $this->buildResponse($result, $flag, $mode);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function supplierMaterialCode(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);
        $materialId = (int) $request->input('idMaterial');
        $tankId = (int) $request->input('idSloc');

        try {
            $result = $this->transferService->getUpdateSupplierMaterial($materialId, $tankId, $plantId);
            return ApiResponse::success($result ? [$result] : []);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    // ========== APPROVAL WORKFLOW ENDPOINTS ==========

    /**
     * Submit transfer for approval.
     */
    public function submitForApproval(ApprovalActionRequest $request): JsonResponse
    {
        $user = $request->user()->name ?? 'system';
        $idBalanceHead = (int) $request->validated('id_balance_head');

        try {
            $result = $this->transferService->submitForApproval($idBalanceHead, $user);

            if ($result['response'] === 1) {
                return ApiResponse::success(null, $result['message'] ?? 'Transfer submitted for approval');
            }
            return ApiResponse::error($result['message'] ?? 'Failed to submit', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Submit failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Approve a transfer.
     */
    public function approveTransfer(ApprovalActionRequest $request): JsonResponse
    {
        $user = $request->user()->name ?? 'system';
        $idBalanceHead = (int) $request->validated('id_balance_head');
        $notes = $request->input('notes');

        try {
            $result = $this->transferService->approveTransfer($idBalanceHead, $user, $notes);

            if ($result['response'] === 1) {
                return ApiResponse::success(null, $result['message'] ?? 'Transfer approved');
            }
            return ApiResponse::error($result['message'] ?? 'Failed to approve', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Approve failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reject a transfer.
     */
    public function rejectTransfer(ApprovalActionRequest $request): JsonResponse
    {
        $user = $request->user()->name ?? 'system';
        $idBalanceHead = (int) $request->validated('id_balance_head');
        $reason = $request->input('reason', '');

        if (empty($reason)) {
            return ApiResponse::error('Rejection reason is required', 422);
        }

        try {
            $result = $this->transferService->rejectTransfer($idBalanceHead, $user, $reason);

            if ($result['response'] === 1) {
                return ApiResponse::success(null, $result['message'] ?? 'Transfer rejected');
            }
            return ApiResponse::error($result['message'] ?? 'Failed to reject', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Reject failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a transfer.
     */
    public function cancelTransfer(ApprovalActionRequest $request): JsonResponse
    {
        $user = $request->user()->name ?? 'system';
        $idBalanceHead = (int) $request->validated('id_balance_head');

        try {
            $result = $this->transferService->cancelTransfer($idBalanceHead, $user);

            if ($result['response'] === 1) {
                return ApiResponse::success(null, $result['message'] ?? 'Transfer cancelled');
            }
            return ApiResponse::error($result['message'] ?? 'Failed to cancel', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Cancel failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get pending approvals list.
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        $plantId = (int) $request->input('id_plant', 0);

        try {
            $data = $this->transferService->getPendingApprovals($plantId);
            return ApiResponse::success($data, 'Pending approvals retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve pending approvals: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get approval history.
     */
    public function approvalHistory(Request $request): JsonResponse
    {
        $idBalanceHead = (int) $request->input('id_balance_head');

        if (!$idBalanceHead) {
            return ApiResponse::error('id_balance_head is required', 422);
        }

        try {
            $data = $this->transferService->getApprovalHistory($idBalanceHead);
            return ApiResponse::success($data, 'Approval history retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve approval history: ' . $e->getMessage(), 500);
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

        if ($status === 1) {
            return ApiResponse::success(null, $message);
        }
        return ApiResponse::error($message, 422);
    }
}
