<?php declare(strict_types=1);
namespace Modules\TsRaw\Services\Contracts;

use Illuminate\Support\Collection;

interface RmEntryServiceInterface
{
    public function getRmList($plantId, int $page = 1, int $perPage = 5): array;
    public function getRmEntryById($id): ?array;
    public function generateRmNumber($plantId): ?string;
    public function generateTransferNumber($plantId): ?string;
    public function getTanks($plantId): array;
    public function getTankDetails($tankId, $plantId): array;
    public function getMaterials(): array;
    public function searchSuppliers($query): array;
    public function addSupplierTemp($data, $user): array;
    public function getSupplierList($entryNo): array;
    public function deleteSupplierTemp($id, $user): void;
    public function getTotalQtyTemp($entryNo): float;
    public function generateBatchCode($supplierId): ?string;
    public function saveRmEntry($data, $user): array;
    public function saveRmTrfEntry($data, $user): array;
    public function checkStockSynchronization($entryNo, $materialId = null): array;
    public function debugFifoStock($params): array;
    public function verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack = 24): array;
    public function deactivateRmEntry($id, $user): array;
    public function updateRmEntry($id, $data, $user): array;
    public function getStorageLog($plantId): array;
    public function getFeedLog($plantId): array;
    public function debugFeedLog($plantId): array;
    public function getTransferList($plantId): array;
    public function transfer($data, $user): array;
    public function deactivateTransfer($id, $user): array;
    public function deactivateFeedLogEntry($id, $user): array;
    public function getDestTanksList($plantId): array;
    public function searchSuppliersList(string $search): array;
    public function clearTempData($entryNo, $user): void;
}
