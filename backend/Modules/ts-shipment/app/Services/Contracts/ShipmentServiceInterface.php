<?php
declare(strict_types=1);
namespace Modules\TsShipment\Services\Contracts;

use Illuminate\Support\Collection;

interface ShipmentServiceInterface
{
    public function getDtShipEntry(int $plantId = 0, int $page = 1, int $perPage = 50): array;
    public function getActiveFgProduct(): Collection;
    public function getWipMaterialByFgProduct(array $data): Collection;
    public function getActiveBatchProduct(array $data): Collection;
    public function store(string $user, array $data): array;
    public function cancel(string $user, array $data): array;
    public function updateSo(string $user, array $data): array;
    public function generateTraceNo(int $materialId, int $plantId, ?string $batchNo = null): string;
    public function getShipmentBatchPackaging(array $data): Collection;
    public function getPreparationRecord(array $data): Collection;
    public function getRealBatchList(int $idShipHead): array;
    public function getLabel(array $data): Collection;
    public function getSpecialLabel(array $data): Collection;
    public function getCustomerMark(array $data): Collection;

    /**
     * Returns ['response' => code, 'data' => [...], 'message' => '...']
     * response: TransactionResponseCode::SUCCESS | GENERIC_FAILURE
     */
    public function getDatShipment(array $data): array;

    /**
     * Returns ['response' => code, 'data' => [...], 'message' => '...']
     * response: TransactionResponseCode::SUCCESS | GENERIC_FAILURE
     */
    public function getDatSoAllocation(array $data): array;
}
