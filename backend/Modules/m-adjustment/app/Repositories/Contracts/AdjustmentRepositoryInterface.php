<?php declare(strict_types=1);

namespace Modules\Adjustment\Repositories\Contracts;

interface AdjustmentRepositoryInterface
{
    // ——— Existing ———
    public function getAdjustmentList(mixed $plantId, ?int $userId = null, string $adjType = 'wip'): array;
    public function getSupplierList(array $data, ?int $userId = null): array;
    public function getTotalQtySupplier(array $data, ?int $userId = null): ?float;
    public function getActiveSuppliers(string $search, ?int $userId = null): array;
    public function generateEntryNo(?string $entryDate, mixed $plantId): ?string;
    public function createAdjustmentHeader(string $user, array $data, mixed $plantId): array;
    public function createAdjustmentDetail(string $user, int $headerId, array $data): array;
    public function approveAdjustment(int $headerId, int $status, string $user): array;
    public function executeAdjustment(int $headerId): array;
    public function cancelAdjustment(int $headerId, string $reason, string $user): array;
    public function getAdjustmentDetail(int $headerId): ?array;
    public function getAdjustmentHeader(int $headerId): ?object;

    // ——— Lookups ———
    public function getActiveMaterials(): array;
    public function getActiveMaterialWhx(): array;
    public function getActiveTanks(mixed $plantId): array;
    public function getActiveSpecificTanks(int $sloc): array;
    public function getActiveWhx(): array;

    // ——— Supplier adjustments ———
    public function getLockStatus(string $entryDate): array;
    public function getSupplierByFilter(int $idMaterial, int $idTank): array;
    public function getBatchBySupplier(int $idMaterial, int $idTank, int $idSupplier): array;

    // ——— Mutations ———
    public function storeAdjustment(string $user, array $data, mixed $plantId): array;
    public function destroyAdjustment(int $id, string $user): array;
    public function addEntrySupplier(string $user, array $data, mixed $plantId): array;
    public function deleteSupplierTemp(int $id): array;
    public function adjustmentInit(string $user, array $data, mixed $plantId): array;
    public function adjustmentSupplier(string $user, array $data, mixed $plantId): array;
    public function adjustMaterialDocument(int $idAdjustHead, ?string $materialDoc, string $user): array;

    // ——— Period Adjustment ———
    public function getPeriodHeaders(): array;
    public function getPeriodViewData(int $idHead): array;
    public function periodHeadersUpload(string $user, array $data, $file): array;
    public function periodViewOnHand(string $user, int $idHead): array;
    public function periodViewAdjustment(string $user, int $idHead): array;
    public function periodHeaderLock(string $user, int $idHead): array;
    public function destroyAdjustmentPeriod(int $id, string $user): array;
    public function getLastAdjustmentRecord(mixed $plantId): array;

    // ——— WHX ———
    public function adjustmentInitWhx(string $user, array $data, mixed $plantId): array;
    public function storeAdjustmentWhx(string $user, array $data, mixed $plantId): array;
    public function getAdjustStatus(?string $adjustNo, ?int $idAdjustHead): array;
}
