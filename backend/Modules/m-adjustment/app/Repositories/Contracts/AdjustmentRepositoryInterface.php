<?php
declare(strict_types=1);
namespace Modules\Adjustment\Repositories\Contracts;

interface AdjustmentRepositoryInterface
{
    // â€”â€”â€” Existing â€”â€”â€”
    public function getAdjustmentList(mixed $plantId, ?int $userId = null, string $adjType = 'wip', array $filters = []): array;
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

    // â€”â€”â€” Lookups â€”â€”â€”
    public function getActiveMaterials(): array;
    public function getActiveMaterialWhx(): array;
    public function getActiveTanks(mixed $plantId): array;
    public function getActiveSpecificTanks(int $sloc): array;
    public function getActiveWhx(): array;

    // â€”â€”â€” Supplier adjustments â€”â€”â€”
    public function getLockStatus(string $entryDate): array;
    public function getSupplierByFilter(int $idMaterial, int $idSloc): array;
    public function getBatchBySupplier(int $idMaterial, int $idSloc, int $idSupplier): array;

    // â€”â€”â€” Mutations â€”â€”â€”
    public function storeAdjustment(string $user, array $data, mixed $plantId): array;
    public function destroyAdjustment(int $id, string $user): array;
    public function addEntrySupplier(string $user, array $data, mixed $plantId): array;
    public function deleteSupplierTemp(int $id): array;
    public function adjustmentInit(string $user, array $data, mixed $plantId): array;
    public function adjustmentSupplier(string $user, array $data, mixed $plantId): array;
    public function adjustMaterialDocument(int $idAdjustHead, ?string $materialDoc, string $user): array;

    // â€”â€”â€” Period Adjustment â€”â€”â€”
    public function getPeriodHeaders(array $filters = []): array;
    public function getPeriodViewData(int $idHead): array;
    public function periodHeadersUpload(string $user, array $data, $file): array;
    public function periodViewOnHand(string $user, int $idHead): array;
    public function periodViewAdjustment(string $user, int $idHead): array;
    public function periodHeaderLock(string $user, int $idHead): array;
    public function destroyAdjustmentPeriod(int $id, string $user): array;
    public function getLastAdjustmentRecord(mixed $plantId): array;

    // â€”â€”â€” WHX â€”â€”â€”
    public function adjustmentInitWhx(string $user, array $data, mixed $plantId): array;
    public function storeAdjustmentWhx(string $user, array $data, mixed $plantId): array;
    public function getAdjustStatus(?string $adjustNo, ?int $idAdjustHead): array;
}
