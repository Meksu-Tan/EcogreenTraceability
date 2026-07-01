<?php
declare(strict_types=1);
namespace Modules\TsShipment\Services;

use Modules\TsShipment\Services\Contracts\ShipmentServiceInterface;
use Modules\TsShipment\Repositories\Contracts\ShipmentRepositoryInterface;
use Modules\Shared\Constants\TransactionResponseCode;
use Modules\TsShipment\Support\DispatchType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShipmentService implements ShipmentServiceInterface
{
    public function __construct(
        protected ShipmentRepositoryInterface $shipmentRepo
    ) {}

    public function getDtShipEntry(int $plantId = 0, int $page = 1, int $perPage = 50): array
    {
        return $this->shipmentRepo->getDtShipEntry($plantId, $page, $perPage);
    }

    public function getActiveFgProduct(): Collection
    {
        return $this->shipmentRepo->getActiveFgProduct();
    }

    public function getWipMaterialByFgProduct(array $data): Collection
    {
        return $this->shipmentRepo->getWipMaterialByFgProduct($data);
    }

    public function getActiveBatchProduct(array $data): Collection
    {
        return $this->shipmentRepo->getActiveBatchProduct($data);
    }

    public function store(string $user, array $data): array
    {
        return $this->shipmentRepo->store($user, $data);
    }

    public function cancel(string $user, array $data): array
    {
        return $this->shipmentRepo->cancel($user, $data);
    }

    public function updateSo(string $user, array $data): array
    {
        return $this->shipmentRepo->updateSo($user, $data);
    }

    public function generateTraceNo(int $materialId, int $plantId, ?string $batchNo = null): string
    {
        return $this->shipmentRepo->generateTraceNo($materialId, $plantId, $batchNo);
    }

    public function getShipmentBatchPackaging(array $data): Collection
    {
        return $this->shipmentRepo->getShipmentBatchPackaging($data);
    }

    public function getPreparationRecord(array $data): Collection
    {
        return $this->shipmentRepo->getPreparationRecord($data);
    }

    public function getRealBatchList(int $idShipHead): array
    {
        return $this->shipmentRepo->getRealBatchList($idShipHead);
    }

    public function getLabel(array $data): Collection
    {
        return $this->shipmentRepo->getLabel($data);
    }

    public function getSpecialLabel(array $data): Collection
    {
        return $this->shipmentRepo->getSpecialLabel($data);
    }

    public function getCustomerMark(array $data): Collection
    {
        return $this->shipmentRepo->getCustomerMark($data);
    }

    public function getDatShipment(array $data): array
    {
        $soNo = trim((string) ($data['soNo'] ?? ''));
        $soItem = trim((string) ($data['soItem'] ?? ''));
        $batchNo = trim((string) ($data['batchNo'] ?? ''));

        if ($soNo === '') {
            return [
                'response' => TransactionResponseCode::GENERIC_FAILURE,
                'message' => 'SO number is required for SAP shipment inquiry.'
            ];
        }

        try {
            $sapUrl = $this->buildSapUrl('ZFM_EUDR_SHIPMENT', [
                'SO_NUM' => $soNo,
                'SO_ITEM' => $soItem,
            ], $batchNo);

            $response = Http::timeout(10)->get($sapUrl);

            if ($response->failed()) {
                Log::error('SAP ZFM_EUDR_SHIPMENT failed', [
                    'soNo' => $soNo,
                    'batchNo' => $batchNo,
                    'status' => $response->status()
                ]);
                return [
                    'response' => TransactionResponseCode::GENERIC_FAILURE,
                    'message' => 'SAP shipment inquiry failed. HTTP ' . $response->status()
                ];
            }

            return [
                'response' => TransactionResponseCode::SUCCESS,
                'data' => $response->json() ?? []
            ];
        } catch (\Throwable $e) {
            Log::error('SAP ZFM_EUDR_SHIPMENT exception: ' . $e->getMessage());
            return [
                'response' => TransactionResponseCode::GENERIC_FAILURE,
                'message' => 'SAP connection failed: ' . $e->getMessage()
            ];
        }
    }

    public function getDatSoAllocation(array $data): array
    {
        $batchNo = trim((string) ($data['batchNo'] ?? ''));
        $idShipHead = (int) ($data['idShipHead'] ?? 0);

        if ($batchNo === '') {
            return [
                'response' => TransactionResponseCode::GENERIC_FAILURE,
                'message' => 'Batch number is required for SO allocation inquiry.'
            ];
        }

        $batches = (DispatchType::isDispatch($batchNo) && $idShipHead > 0)
            ? $this->shipmentRepo->getRealBatchList($idShipHead)
            : [$batchNo];

        if ($batches === []) {
            return ['response' => TransactionResponseCode::SUCCESS, 'data' => []];
        }

        $allocations = [];
        foreach ($batches as $realBatch) {
            $allocations = array_merge($allocations, $this->fetchSoAllocation($realBatch));
        }

        return ['response' => TransactionResponseCode::SUCCESS, 'data' => $allocations];
    }

    private function fetchSoAllocation(string $batchNo): array
    {
        try {
            $sapUrl = $this->buildSapUrl('ZFM_AD001', ['BATCH_NO' => $batchNo]);
            $response = Http::timeout(10)->get($sapUrl);

            if ($response->failed()) {
                Log::error('SAP ZFM_AD001 failed', ['batchNo' => $batchNo, 'status' => $response->status()]);
                return [];
            }

            $sapData = $response->json() ?? [];
            return $sapData['IT_EXPORT'] ?? $sapData;
        } catch (\Throwable $e) {
            Log::error('SAP ZFM_AD001 exception: ' . $e->getMessage());
            return [];
        }
    }

    private function buildSapUrl(string $fm, array $params, ?string $batchNo = null): string
    {
        $sapReqUrl = config('eudr.sap_url');
        $sapClient = 'Client=' . config('eudr.sap_client');
        $sapFm = '&FM=' . $fm;

        $queryParts = [];
        foreach ($params as $key => $value) {
            $queryParts[] = '&' . $key . '=' . urlencode((string) $value);
        }

        $url = $sapReqUrl . $sapClient . $sapFm . implode('', $queryParts);

        if ($fm === 'ZFM_EUDR_SHIPMENT' && $batchNo !== null && !DispatchType::isDispatch($batchNo)) {
            $url .= '&BATCH=' . urlencode($batchNo);
        }

        return $url;
    }
}
