<?php declare(strict_types=1);

namespace Modules\Adjustment\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Adjustment\Services\Contracts\AdjustmentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Modules\Adjustment\Http\Requests\StoreAdjustmentHeaderRequest;
use Modules\Adjustment\Http\Requests\StoreAdjustmentDetailRequest;
use Modules\Adjustment\Http\Requests\StoreAdjustmentWhxRequest;
use Modules\Adjustment\Http\Requests\InitAdjustmentWhxRequest;
use Modules\Adjustment\Http\Requests\AdjustStatusRequest;
use Modules\Adjustment\Http\Requests\DestroyAdjustmentRequest;
use Modules\Adjustment\Http\Requests\AdjustmentActionRequest;
use Modules\Shared\Helpers\ResponseCode;

class AdjustmentController extends Controller
{
   const APPROVED_MSG = 'Adjustment approved';
   const REJECTED_MSG = 'Adjustment rejected';

    public function __construct(
        protected AdjustmentServiceInterface $adjustmentService
    ) {}


    public function index(Request $request): JsonResponse
    {
        try {
            $plantId = $request->input('id_plant');
            $userId = $request->user()?->id;
            $adjType = $request->input('adj_type', 'wip');
            
            $filters = [
                'page' => (int) $request->input('page', 1),
                'per_page' => (int) $request->input('per_page', 10),
            ];

            $data = $this->adjustmentService->getAdjustmentList($plantId, $userId, $adjType, $filters);
            return ApiResponse::success($data, 'Adjustment list retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $data = $this->adjustmentService->getAdjustmentDetail((int) $id);
            if (!$data) {
                return ApiResponse::error('Adjustment not found', 404);
            }
            return ApiResponse::success($data, 'Adjustment detail retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getSupplierList(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id;
            $data = $this->adjustmentService->getSupplierList([
                'mode' => $request->input('mode', 'ADD'),
                'number' => $request->input('number'),
                'id_balance_head' => $request->input('id_balance_head'),
            ], $userId);
            return ApiResponse::success($data, 'Supplier list retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getTotalQty(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id;
            $total = $this->adjustmentService->getTotalQtySupplier([
                'mode' => $request->input('mode', 'ADD'),
                'number' => $request->input('number'),
                'id_balance_head' => $request->input('id_balance_head'),
            ], $userId);
            return ApiResponse::success(['total' => $total], 'Total quantity retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function searchSuppliers(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id;
            $search = (string) $request->input('supplier', '');
            $data = $this->adjustmentService->getActiveSuppliers($search, $userId);
            return ApiResponse::success($data, 'Suppliers retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getEntryNo(Request $request): JsonResponse
    {
        try {
            $entryDate = $request->input('entry_date');
            $plantId = $request->input('id_plant');
            $entryNo = $this->adjustmentService->generateEntryNo($entryDate, $plantId);
            return ApiResponse::success(['entry_no' => $entryNo], 'Entry number generated', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function store(StoreAdjustmentHeaderRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $data = $request->validated();
            $result = $this->adjustmentService->createAdjustmentHeader($user, $data, $data['id_plant']);

            if ($result['response'] == ResponseCode::PERIOD_LOCKED) {
                return ApiResponse::error($result['message'] ?? 'Date is locked', 422);
            }
            if ($result['response'] == 1) {
                return ApiResponse::success($result, 'Adjustment header created', 200);
            }
            return ApiResponse::error('Failed to create adjustment', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function storeDetail(StoreAdjustmentDetailRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $data = $request->validated();
            $result = $this->adjustmentService->createAdjustmentDetail($user, (int) $data['id_adjust_head'], $data);

            if ($result['response'] == 1) {
                return ApiResponse::success($result, 'Adjustment detail created', 200);
            }
            return ApiResponse::error('Failed to create detail', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function approve(AdjustmentActionRequest $request, string $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $validated = $request->validated();
            $status = (int) ($validated['status'] ?? 2);
            $result = $this->adjustmentService->approveAdjustment($user, (int) $id, $status);

            if ($result['response'] == 1) {
                $message = $status == 2 ? self::APPROVED_MSG : self::REJECTED_MSG;
                return ApiResponse::success($result, $message, 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to process', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function execute(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $result = $this->adjustmentService->executeAdjustment($user, (int) $id);
            if ($result['response'] == 1) {
                return ApiResponse::success($result, 'Adjustment executed', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to execute', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function cancel(AdjustmentActionRequest $request, string $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $validated = $request->validated();
            $reason = $validated['reason'] ?? 'Cancelled by user';
            $result = $this->adjustmentService->cancelAdjustment($user, (int) $id, $reason);
            if ($result['response'] == 1) {
                return ApiResponse::success($result, 'Adjustment cancelled', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to cancel', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    // ========== New lookup endpoints ==========

    public function getActiveMaterials(): JsonResponse
    {
        try {
            $data = $this->adjustmentService->getActiveMaterials();
            return ApiResponse::success($data, 'Active materials retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getActiveMaterialWhx(): JsonResponse
    {
        try {
            $data = $this->adjustmentService->getActiveMaterialWhx();
            return ApiResponse::success($data, 'Active material WHX retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getActiveTanks(Request $request): JsonResponse
    {
        try {
            $plantId = $request->input('id_plant');
            $data = $this->adjustmentService->getActiveTanks($plantId);
            return ApiResponse::success($data, 'Active tanks retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getActiveSpecificTanks(Request $request, string $sloc): JsonResponse
    {
        try {
            $data = $this->adjustmentService->getActiveSpecificTanks((int) $sloc);
            return ApiResponse::success($data, 'Active specific tanks retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getActiveWhx(): JsonResponse
    {
        try {
            $data = $this->adjustmentService->getActiveWhx();
            return ApiResponse::success($data, 'Active WHX retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getLockStatus(Request $request): JsonResponse
    {
        try {
            $entryDate = $request->input('entry_date', date('Y-m-d'));
            $data = $this->adjustmentService->getLockStatus($entryDate);
            return ApiResponse::success($data, 'Lock status retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getSupplierByFilter(Request $request): JsonResponse
    {
        try {
            $data = $this->adjustmentService->getSupplierByFilter(
                (int) $request->input('id_material'),
                (int) $request->input('id_tank')
            );
            return ApiResponse::success($data, 'Suppliers retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getBatchBySupplier(Request $request): JsonResponse
    {
        try {
            $data = $this->adjustmentService->getBatchBySupplier(
                (int) $request->input('id_material'),
                (int) $request->input('id_tank'),
                (int) $request->input('id_supplier')
            );
            return ApiResponse::success($data, 'Batches retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    // ========== New mutation endpoints ==========

    public function storeAdjustment(AdjustmentActionRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $plantId = $request->input('id_plant');
            $result = $this->adjustmentService->storeAdjustment($user, $request->validated(), $plantId);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Adjustment stored', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to store adjustment', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function destroyAdjustment(DestroyAdjustmentRequest $request, string $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $result = $this->adjustmentService->destroyAdjustment((int) $id, $user);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Adjustment draft destroyed', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to destroy', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function addEntrySupplier(AdjustmentActionRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $plantId = $request->input('id_plant');
            $result = $this->adjustmentService->addEntrySupplier($user, $request->validated(), $plantId);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Supplier entry added', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to add supplier entry', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function deleteSupplierTemp(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->adjustmentService->deleteSupplierTemp((int) $id);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Supplier temp deleted', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to delete', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function adjustmentInit(AdjustmentActionRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $plantId = $request->input('id_plant');
            $result = $this->adjustmentService->adjustmentInit($user, $request->validated(), $plantId);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Init adjustment stored', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to init', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function adjustmentSupplier(AdjustmentActionRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $plantId = $request->input('id_plant');
            $result = $this->adjustmentService->adjustmentSupplier($user, $request->validated(), $plantId);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Supplier adjustment stored', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to adjust supplier', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function adjustMaterialDocument(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $materialDoc = $request->input('material_doc');
            $result = $this->adjustmentService->adjustMaterialDocument((int) $id, $materialDoc, $user);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Material document updated', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to update document', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    // ========== WHX (Warehouse) Endpoints ==========

    public function storeAdjustmentWhx(StoreAdjustmentWhxRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $data = $request->validated();
            $result = $this->adjustmentService->storeAdjustmentWhx($user, $data, $data['id_plant']);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'WHX adjustment stored', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to store WHX adjustment', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function adjustmentInitWhx(InitAdjustmentWhxRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $data = $request->validated();
            $result = $this->adjustmentService->adjustmentInitWhx($user, $data, $data['id_plant']);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'WHX init adjustment stored', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to init WHX adjustment', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getAdjustStatus(AdjustStatusRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $adjustNo = $data['adjust_no'] ?? null;
            $id = $data['id_adjust_head'] ?? null;
            $result = $this->adjustmentService->getAdjustStatus($adjustNo, $id);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Adjustment status retrieved', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Not found', 404);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    // ========== Period Adjustment Endpoints ==========

    public function getPeriodHeaders(): JsonResponse
    {
        try {
            $data = $this->adjustmentService->getPeriodHeaders();
            return ApiResponse::success($data, 'Period headers retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getPeriodViewData(Request $request): JsonResponse
    {
        try {
            $idHead = (int) $request->input('id_head');
            $data = $this->adjustmentService->getPeriodViewData($idHead);
            return ApiResponse::success($data, 'Period view data retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function periodHeadersUpload(AdjustmentActionRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $data = $request->validated();
            $file = $request->file('file');
            $result = $this->adjustmentService->periodHeadersUpload($user, $data, $file);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Period header uploaded', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to upload', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function periodViewOnHand(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $idHead = (int) $request->input('id_head');
            $result = $this->adjustmentService->periodViewOnHand($user, $idHead);
            return ApiResponse::success($result, 'Period on-hand retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function periodViewAdjustment(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $idHead = (int) $request->input('id_head');
            $result = $this->adjustmentService->periodViewAdjustment($user, $idHead);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Period adjustment calculated', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function periodHeaderLock(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $idHead = (int) $request->input('id_head');
            $result = $this->adjustmentService->periodHeaderLock($user, $idHead);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Period locked', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to lock', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function destroyAdjustmentPeriod(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'system';
            $result = $this->adjustmentService->destroyAdjustmentPeriod((int) $id, $user);
            if (($result['response'] ?? 0) == 1) {
                return ApiResponse::success($result, 'Adjustment period deleted', 200);
            }
            return ApiResponse::error($result['message'] ?? 'Failed to delete', 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }

    public function getLastRecord(Request $request): JsonResponse
    {
        try {
            $plantId = $request->input('id_plant');
            $data = $this->adjustmentService->getLastAdjustmentRecord($plantId);
            return ApiResponse::success($data, 'Last record retrieved', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed: ' . $e->getMessage(), 500);
        }
    }
}
