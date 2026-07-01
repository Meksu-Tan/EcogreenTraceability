<?php
declare(strict_types=1);
namespace Modules\TsShipment\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TsShipment\Services\Contracts\ShipmentServiceInterface;
use Modules\TsShipment\Http\Requests\StoreShipmentEntryRequest;
use Modules\TsShipment\Http\Requests\GenerateTraceNoRequest;
use Modules\TsShipment\Http\Requests\UpdateShipmentSoRequest;
use Modules\TsShipment\Http\Requests\SapShipmentRequest;
use Modules\TsShipment\Http\Requests\SapSoAllocationRequest;
use Modules\TsShipment\Http\Resources\ShipmentEntryResource;
use Modules\Shared\Constants\TransactionResponseCode;
use Modules\Shared\Helpers\ResponseCode;

class ShipmentEntryController extends Controller
{
    public function __construct(
        protected ShipmentServiceInterface $shipmentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $plantId = (int) ($request->get('plant_context')['plant_code'] ?? $request->input('id_plant', 0));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));

        try {
            $result = $this->shipmentService->getDtShipEntry($plantId, $page, $perPage);
            return ApiResponse::paginated(
                ShipmentEntryResource::collection($result['data'])->resolve(),
                $result['total'],
                $page,
                $perPage,
                'Shipment entries retrieved successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve shipment entries: ' . $e->getMessage(), 500);
        }
    }

    public function store(StoreShipmentEntryRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'System';
            $data = $request->validated();
            $data['id_plant'] = $request->get('plant_context')['plant_code'] ?? $request->input('id_plant') ?? $request->input('plant') ?? $request->validated('id_plant');
            
            $res = $this->shipmentService->store($user, $data);
            if ($res['response'] == ResponseCode::PERIOD_LOCKED) {
                return ApiResponse::error($res['message'] ?? 'Period is locked.', 422);
            }
            if ($res['response'] != TransactionResponseCode::SUCCESS) {
                return ApiResponse::error($res['message'] ?? 'Failed to store shipment.', 400);
            }

            return ApiResponse::success(null, 'Shipment stored successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to store shipment: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'System';
            $traceNo = $request->input('traceNo');
            if (!$traceNo) {
                return ApiResponse::error('Trace number is required for cancellation.', 422);
            }

            $res = $this->shipmentService->cancel($user, ['traceNo' => $traceNo]);
            if ($res['response'] == ResponseCode::PERIOD_LOCKED) {
                return ApiResponse::error($res['message'] ?? 'Period is locked.', 422);
            }
            if ($res['response'] != TransactionResponseCode::SUCCESS) {
                return ApiResponse::error($res['message'] ?? 'Failed to cancel shipment.', 400);
            }

            return ApiResponse::success(null, 'Shipment cancelled successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to cancel shipment: ' . $e->getMessage(), 500);
        }
    }



    public function getActiveFgProduct(): JsonResponse
    {
        try {
            $products = $this->shipmentService->getActiveFgProduct();
            return ApiResponse::success($products, 'Active products retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get active products: ' . $e->getMessage(), 500);
        }
    }

    public function getWipMaterialByFgProduct(Request $request): JsonResponse
    {
        try {
            $plant = $request->get('plant_context')['plant_code'] ?? $request->input('id_plant') ?? $request->input('plant');
            $res = $this->shipmentService->getWipMaterialByFgProduct([
                'idMaterial' => $request->input('idMaterial'),
                'id_plant' => $plant
            ]);
            return ApiResponse::success($res, 'WIP materials retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get WIP materials: ' . $e->getMessage(), 500);
        }
    }

    public function getActiveBatchProduct(Request $request): JsonResponse
    {
        try {
            $plant = $request->get('plant_context')['plant_code'] ?? $request->input('id_plant') ?? $request->input('plant');
            $res = $this->shipmentService->getActiveBatchProduct([
                'idMaterial' => $request->input('idMaterial'),
                'id_plant' => $plant
            ]);
            return ApiResponse::success($res, 'Active batches retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get active batches: ' . $e->getMessage(), 500);
        }
    }

    public function getBatchPackaging(Request $request): JsonResponse
    {
        try {
            $res = $this->shipmentService->getShipmentBatchPackaging([
                'batchNo' => $request->input('batchNo'),
                'idShipHead' => $request->input('idShipHead'),
            ]);
            return ApiResponse::success($res, 'Batch packaging data retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get batch packaging: ' . $e->getMessage(), 500);
        }
    }

    public function getPreparationRecord(Request $request): JsonResponse
    {
        try {
            $res = $this->shipmentService->getPreparationRecord([
                'batchNo' => $request->input('batchNo'),
                'idShipHead' => $request->input('idShipHead'),
            ]);
            return ApiResponse::success($res, 'Preparation record retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get preparation record: ' . $e->getMessage(), 500);
        }
    }

    public function getLabel(Request $request): JsonResponse
    {
        try {
            $res = $this->shipmentService->getLabel([
                'label' => $request->input('label'),
            ]);
            return ApiResponse::success($res, 'Label data retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get label: ' . $e->getMessage(), 500);
        }
    }

    public function getSpecialLabel(Request $request): JsonResponse
    {
        try {
            $res = $this->shipmentService->getSpecialLabel([
                'label' => $request->input('label'),
            ]);
            return ApiResponse::success($res, 'Special label data retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get special label: ' . $e->getMessage(), 500);
        }
    }

    public function getCustomerMark(Request $request): JsonResponse
    {
        try {
            $res = $this->shipmentService->getCustomerMark([
                'label' => $request->input('label'),
            ]);
            return ApiResponse::success($res, 'Customer mark data retrieved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get customer mark: ' . $e->getMessage(), 500);
        }
    }

    public function getSapShipment(SapShipmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $res = $this->shipmentService->getDatShipment([
            'batchNo' => $validated['batchNo'] ?? '',
            'soNo' => $validated['soNo'],
            'soItem' => $validated['soItem'] ?? '',
        ]);

        if (($res['response'] ?? TransactionResponseCode::GENERIC_FAILURE) !== TransactionResponseCode::SUCCESS) {
            return ApiResponse::error($res['message'] ?? 'Failed to get SAP shipment.', 400);
        }

        return ApiResponse::success($res['data'] ?? [], 'SAP shipment data retrieved');
    }

    public function getSapSoAllocation(SapSoAllocationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $res = $this->shipmentService->getDatSoAllocation([
            'batchNo' => $validated['batchNo'],
            'idShipHead' => $validated['idShipHead'] ?? null,
        ]);

        if (($res['response'] ?? TransactionResponseCode::GENERIC_FAILURE) !== TransactionResponseCode::SUCCESS) {
            return ApiResponse::error($res['message'] ?? 'Failed to get SAP SO allocation.', 400);
        }

        return ApiResponse::success($res['data'] ?? [], 'SAP SO allocation data retrieved');
    }

    public function updateSo(UpdateShipmentSoRequest $request): JsonResponse
    {
        try {
            $user = $request->user()->name ?? 'System';
            $res = $this->shipmentService->updateSo($user, $request->validated());
            if ($res['response'] != TransactionResponseCode::SUCCESS) {
                return ApiResponse::error($res['message'] ?? 'Failed to update SO.', 400);
            }

            return ApiResponse::success(null, 'SO updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update SO: ' . $e->getMessage(), 500);
        }
    }

    public function newTraceNo(GenerateTraceNoRequest $request): JsonResponse
    {
        $plantId = (int) (data_get($request->get('plant_context'), 'plant_code') ?: $request->validated('id_plant'));
        $materialStr = $request->validated('id_material');
        $materialId = (int) str_replace('PCK|', '', $materialStr);
        $batchNo = $request->validated('batch_no');
        try {
            $traceNo = $this->shipmentService->generateTraceNo($materialId, $plantId, $batchNo);
            return ApiResponse::success([['traceNo' => $traceNo]]);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to generate trace number: ' . $e->getMessage(), 500);
        }
    }
}
