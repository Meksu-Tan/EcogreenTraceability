<?php

namespace Modules\TsRaw\Repositories\Contracts;

interface RmEntryRepositoryInterface
{
    // List & Fetch
    public function getRmList($plantId): array;
    public function getNewNumber($plantId): ?string;
    public function getTanks($plantId): array;
    public function getTankDetails($tankId, $plantId): array;
    public function getMaterials(): array;

    // Suppliers
    public function searchSuppliers(string $query): array;
    public function addSupplierTemp(array $data, string $user): object;
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

    // Temp Data
    public function getTempData(string $entryNo): array;
    public function clearTempData(string $entryNo, string $user): bool;

    // Log
    public function logTransaction(string $module, string $type, string $description, string $user): void;

    // Helpers
    public function resolvePlantCode($plantId);
    public function buildTraceNo(string $section, string $entryDate, string $warehouse, string $plantCode, int $sequence): string;
    public function traceNoToInt(string $traceNo): int;
}
