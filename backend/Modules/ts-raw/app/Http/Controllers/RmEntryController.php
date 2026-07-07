<?php

declare(strict_types=1);

namespace Modules\TsRaw\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Modules\Shared\Helpers\Feed;
use Modules\TsRaw\Http\Requests\StoreRmEntryRequest;
use Modules\TsRaw\Http\Requests\StoreSupplierTempRequest;
use Modules\TsRaw\Http\Requests\TransferEntryRequest;
use Modules\TsRaw\Http\Requests\UpdateRmEntryRequest;
use Modules\TsRaw\Http\Resources\RmEntryResource;
use Modules\TsRaw\Services\Contracts\RmEntryServiceInterface;

class RmEntryController extends Controller
{
    public function __construct(
        protected RmEntryServiceInterface $rmEntryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (Gate::denies('rmentry.view')) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 5)));

        $result = $this->rmEntryService->getRmList($plantId, $page, $perPage);

        $data = is_array($result['data']) ? $result['data'] : $result['data']->toArray();

        return ApiResponse::paginated(
            RmEntryResource::collection($data)->toArray($request),
            $result['total'],
            $page,
            $perPage,
            'OK'
        );
    }

    public function show($id): JsonResponse
    {
        $result = $this->rmEntryService->getRmEntryById($id);

        return ApiResponse::success($result, 'OK', 200);
    }

    public function store(StoreRmEntryRequest $request): JsonResponse
    {
        if (Gate::denies('rmentry.create')) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $data = $request->validated();
        $data['id_plant'] = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $user = Auth::user()?->name ?? 'System';

        $result = $this->rmEntryService->saveRmEntry($data, $user);

        return ApiResponse::success($result, 'RM Entry created successfully', 201);
    }

    public function update(UpdateRmEntryRequest $request, $id): JsonResponse
    {
        $data = $request->validated();
        $user = Auth::user()?->name ?? 'System';

        $result = $this->rmEntryService->updateRmEntry((int) $id, $data, $user);

        return ApiResponse::success($result, 'RM Entry updated successfully', 200);
    }

    public function destroy($id): JsonResponse
    {
        if (Gate::denies('rmentry.delete')) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $user = Auth::user()?->name ?? 'System';
        $result = $this->rmEntryService->deactivateRmEntry((int) $id, $user);

        return ApiResponse::success($result, 'RM Entry deactivated successfully', 200);
    }

    public function activate($id): JsonResponse
    {
        $user = Auth::user()?->name ?? 'System';
        $result = $this->rmEntryService->activateRmEntry((int) $id, $user);

        return ApiResponse::success($result, 'RM Entry activated successfully', 200);
    }

    public function newNumber(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $rmNumber = $this->rmEntryService->generateRmNumber($plantId);

        return ApiResponse::success(['rm_number' => $rmNumber], 'OK', 200);
    }

    public function tanks(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $tanks = $this->rmEntryService->getStorageTanks($plantId);

        return ApiResponse::success($tanks, 'OK', 200);
    }

    public function tankDetails(Request $request, $tankId): JsonResponse
    {
        $plantId = $request->input('id_plant');
        $details = $this->rmEntryService->getSpecificTankDetails($tankId, $plantId);

        return ApiResponse::success($details, 'OK', 200);
    }

    public function materials(): JsonResponse
    {
        $materials = $this->rmEntryService->getRmMaterials();

        return ApiResponse::success($materials, 'OK', 200);
    }

    public function searchSuppliers(Request $request): JsonResponse
    {
        $search = $request->input('q') ?? '';
        $suppliers = $this->rmEntryService->searchSuppliersList($search);

        return ApiResponse::success($suppliers);
    }

    public function batchCode(Request $request): JsonResponse
    {
        $supplierId = $request->input('id_supplier');
        $batchCode = $this->rmEntryService->generateBatchCode($supplierId);

        return ApiResponse::success(['batch_code' => $batchCode], 'OK', 200);
    }

    public function addSupplier(StoreSupplierTempRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['id_plant'] = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $user = Auth::user()?->name ?? 'System';

        $result = $this->rmEntryService->addSupplierTemp($data, $user);

        return ApiResponse::success($result, 'Supplier added successfully', 200);
    }

    public function supplierList(Request $request): JsonResponse
    {
        $entryNo = $request->input('entry_no') ?? '';
        $data = $this->rmEntryService->getSupplierList($entryNo);

        return ApiResponse::success($data, 'OK', 200);
    }

    public function deleteSupplier($id): JsonResponse
    {
        $user = Auth::user()?->name ?? 'System';
        $this->rmEntryService->deleteSupplierTemp((int) $id, $user);

        return ApiResponse::success(null, 'Supplier deleted successfully', 200);
    }

    public function clearTempSuppliers($entryNo): JsonResponse
    {
        $user = Auth::user()?->name ?? 'System';
        $this->rmEntryService->clearTempData($entryNo, $user);

        return ApiResponse::success(null, 'Temp suppliers cleared successfully', 200);
    }

    public function totalQty(Request $request): JsonResponse
    {
        $entryNo = $request->input('entry_no') ?? '';
        $total = $this->rmEntryService->getTotalQtyTemp($entryNo);

        return ApiResponse::success(['total' => (float) $total], 'OK', 200);
    }

    public function transfer(TransferEntryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['id_plant'] = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $user = Auth::user()?->name ?? 'System';

        $result = $this->rmEntryService->saveRmTrfEntry($data, $user);

        return ApiResponse::success($result, 'RM Transfer processed successfully', 200);
    }

    public function transferNumber(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $tankDesc = $request->input('tank_desc');
        $rmNumber = $this->rmEntryService->generateTransferNumber($plantId, $tankDesc);

        return ApiResponse::success(['rm_number' => $rmNumber], 'OK', 200);
    }

    public function checkStockSync(Request $request): JsonResponse
    {
        $entryNo = $request->input('entry_no');
        $materialId = $request->input('id_material') ? (int) $request->input('id_material') : null;

        if (! $entryNo) {
            return ApiResponse::error('Entry number is required', 422);
        }

        $syncStatus = $this->rmEntryService->checkStockSynchronization($entryNo, $materialId);

        return ApiResponse::success($syncStatus, 'OK', 200);
    }

    public function debugFifoStock(Request $request): JsonResponse
    {
        $materialId = $request->input('id_material');
        $tankId = $request->input('tf_number');
        $tankTail = $request->input('id_sloc_tail');
        $plantId = $request->input('id_plant');
        $tankMatching = $request->input('tank_matching', 'flexible');

        if (! $materialId) {
            return ApiResponse::error('Material ID is required', 422);
        }

        $tankTailJson = null;
        if (! empty($tankTail)) {
            if (is_array($tankTail)) {
                $tankTailJson = json_encode(array_map('strval', array_values($tankTail)));
            } else {
                $decoded = json_decode($tankTail, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $tankTailJson = json_encode(array_map('strval', array_values($decoded)));
                } else {
                    $tankTailJson = json_encode([(string) $tankTail]);
                }
            }
        }

        $feedData = [
            'id_material' => (int) $materialId,
            'id_sloc' => $tankId,
            'id_sloc_tail' => $tankTailJson,
            'balance_plant' => $plantId,
            'trace_prefixes' => ['1'],
            'tank_matching' => $tankMatching,
        ];

        $fifoDetails = Feed::getDetailedFifoStock($feedData);

        return ApiResponse::success($fifoDetails, 'OK', 200);
    }

    public function verifySeparateEntries(Request $request): JsonResponse
    {
        $materialId = (int) $request->input('id_material');
        $tankId = (int) $request->input('tf_number');
        $plantId = (int) $request->input('id_plant');
        $hoursBack = (int) $request->input('hours_back', 24);

        if (! $materialId || ! $tankId || ! $plantId) {
            return ApiResponse::error('Material ID, Tank ID, and Plant ID are required', 422);
        }

        $verification = $this->rmEntryService->verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack);

        return ApiResponse::success($verification, 'OK', 200);
    }

    public function storageLog(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $data = $this->rmEntryService->getStorageLog($plantId);

        return ApiResponse::success($data, 'OK', 200);
    }

    public function feedLog(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $data = $this->rmEntryService->getFeedLog($plantId);

        return ApiResponse::success($data, 'OK', 200);
    }

    public function debugFeedLog(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $data = $this->rmEntryService->debugFeedLog($plantId);

        return ApiResponse::success($data, 'OK', 200);
    }

    public function sourceEntries(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $entries = $this->rmEntryService->getSourceEntriesList($plantId);

        return ApiResponse::success($entries, 'OK', 200);
    }

    public function destTanks(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $tanks = $this->rmEntryService->getDestTanksList($plantId);

        return ApiResponse::success($tanks, 'OK', 200);
    }

    public function transfers(Request $request): JsonResponse
    {
        $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $data = $this->rmEntryService->getTransferList($plantId);

        return ApiResponse::success($data, 'OK', 200);
    }

    public function interPlantTransfer(Request $request): JsonResponse
    {
        $data = $request->all();
        $data['id_plant'] = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
        $user = Auth::user()?->name ?? 'System';

        $result = $this->rmEntryService->transfer($data, $user);

        return ApiResponse::success($result, 'Transfer completed successfully', 200);
    }

    public function matlDoc(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:ADD,UPDATE',
            'id' => 'required|integer',
            'number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation error', 422, $validator->errors()->toArray());
        }

        $mode = $request->input('mode');
        $id = (int) $request->input('id');
        $number = $request->input('number');
        $user = Auth::user()?->name ?? 'System';

        $result = $this->rmEntryService->saveMatlDoc($mode, $id, $number, $user);

        return ApiResponse::success($result, 'Material Document saved successfully', 200);
    }

    public function updateSubTank(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_head' => 'required|integer',
            'id_sloc_tail' => 'required',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation error', 422, $validator->errors()->toArray());
        }

        $idHead = (int) $request->input('id_head');
        $idSlocTail = $request->input('id_sloc_tail');
        $user = Auth::user()?->name ?? 'System';

        $result = $this->rmEntryService->updateSubTankSlocTail($idHead, $idSlocTail, $user);

        return ApiResponse::success($result, 'Sub Tank updated successfully', 200);
    }

    public function deactivateTransfer(Request $request, $id): JsonResponse
    {
        $user = Auth::user()?->name ?? 'System';
        $result = $this->rmEntryService->deactivateTransfer((int) $id, $user);

        return ApiResponse::success($result, 'Transfer deactivated successfully', 200);
    }

    public function deactivateFeedLog(Request $request, $id): JsonResponse
    {
        $user = Auth::user()?->name ?? 'System';
        $result = $this->rmEntryService->deactivateFeedLogEntry((int) $id, $user);

        return ApiResponse::success($result, 'Feed log entry deactivated successfully', 200);
    }
}
