<?php declare(strict_types=1);
namespace Modules\TsRaw\Repositories\Contracts;

interface RmEntryRepositoryInterface
{
    // List & Fetch
    public function getRmList($plantId, int $page = 1, int $perPage = 5): array;
    public function getNewNumber($plantId): ?string;
    public function getRmEntryById($id): ?object;
    public function getTanks($plantId): array;
    public function getTankDetails($tankId, $plantId): array;
    public function getMaterials(): array;

    // Suppliers
    public function searchSuppliers(string $query): array;
    public function addSupplierTemp(array $data, string $user): array;
    public function getSupplierList(string $entryNo): array;
    public function deleteSupplierTemp(int $id, string $user): bool;
    public function getTotalQtyTemp(string $entryNo): float;

    // Batch Code
    public function generateBatchCode($supplierId): ?string;

    // Transfer Number
    public function getTransferNumber($plantId): ?string;

    // Stock Sync
    public function checkStockSynchronization(string $entryNo, int $materialId = null): array;
    public function debugFifoStock(array $params): array;
    public function verifySeparateEntries(int $materialId, int $tankId, int $plantId, int $hoursBack = 24): array;

    // Entry Operations
    public function saveRmEntry(array $data, string $user): array;
    public function saveRmTrfEntry(array $data, string $user): array;
    public function deactivateRmEntry(int $id, string $user): array;
    public function deactivateFeedLogEntry(int $id, string $user): array;

    // Sub Tank
    public function updateEntrySubTank(string $user, int $idHead, array $tails): array;

    // Temp Data
    public function getTempData(string $entryNo): array;
    public function clearTempData(string $entryNo, string $user): bool;

    // Log
    public function logTransaction(string $module, string $type, string $description, string $user): void;

    // Helpers
    public function resolvePlantCode($plantId);
    public function buildTraceNo(string $section, string $entryDate, string $warehouse, string $plantCode, int $sequence): string;
    public function traceNoToInt(string $traceNo): int;

    // Storage and Feed Log Methods (moved from ts-transfer)
    public function getStorageLog($plantId): array;
    public function getFeedLog($plantId): array;
    public function debugFeedLog($plantId): array;

    // Transfer Methods (moved from ts-transfer)
    public function generateTransferNumber($plantId, string $movSeq = '000'): ?string;
    public function findBalanceByTraceNo(string $traceNo): ?object;
    public function findTraceByBalanceHeadId(int $balanceHeadId): ?object;
    public function createTransferBalance(array $data): object;
    public function createTransferTrace(array $data): object;
    public function updateSourceBalance(int $balanceId, float $qty): bool;
    public function updateSourceTrace(int $balanceHeadId, float $qty): bool;
    public function findTransferById(int $id): ?object;
    public function deactivateBalance(int $balanceId, string $user): bool;
    public function deactivateTrace(int $traceId, string $user): bool;
    public function revertSourceBalance(string $traceNo, float $qty): bool;
    public function revertSourceTrace(int $balanceHeadId, float $qty): bool;
    public function getSourceEntries(int $plantId): array;
    public function getDestTanks(int $plantId): array;
    public function getTransferList($plantId): array;

    // Transfer Entry (standalone create, matching reference Transfer::post_transferEntry)
    public function getActiveMaterialsForTransfer(): array;
    public function generateTransferEntryNo(int $materialId, $plantId): ?string;
    public function getActiveTanksForTransfer(?int $materialId, $plantId): array;
    public function getActiveSpecificTanksRundown(int $sloc): array;
    public function getTotalStockMaterial(int $materialId, int $tankId): float;
    public function getSupplierMaterial(int $materialId, int $tankId, $plantId): ?object;
    public function getLockStatus(string $entryDate): bool;

    // Model access methods (to avoid direct model queries in Service)
    public function findBalanceHeaderById(int $id): ?object;
    public function findPlantById(int $plantId): ?object;
    public function getActiveMaterialsSearch(): array;
    public function getSuppliersSearch(string $search): array;
    public function getSourceEntriesList($plantId): array;
}
