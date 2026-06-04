<?php declare(strict_types=1);

namespace Modules\Adjustment\Services\Contracts;

interface AdjustmentServiceInterface extends AdjustmentPeriodServiceInterface
{
    // ——— List & detail ———
    public function getAdjustmentList(mixed $plantId, ?int $userId = null, string $adjType = 'wip'): array;
    public function getAdjustmentDetail(int $headerId): ?array;

    // ——— Lookups ———
    public function getActiveMaterials(): array;
    public function getActiveMaterialWhx(): array;
    public function getActiveTanks(mixed $plantId): array;
    public function getActiveSpecificTanks(int $sloc): array;
    public function getActiveWhx(): array;
    public function getLockStatus(string $entryDate): array;
    public function getSupplierByFilter(int $idMaterial, int $idTank): array;
    public function getBatchBySupplier(int $idMaterial, int $idTank, int $idSupplier): array;
    public function getSupplierList(array $data, ?int $userId = null): array;
    public function getTotalQtySupplier(array $data, ?int $userId = null): ?float;
    public function getActiveSuppliers(string $search, ?int $userId = null): array;
    public function generateEntryNo(?string $entryDate, mixed $plantId): ?string;

    // ——— Core mutations ———
    public function createAdjustmentHeader(string $user, array $data, mixed $plantId): array;
    public function createAdjustmentDetail(string $user, int $headerId, array $data): array;
    public function approveAdjustment(string $user, int $headerId, int $status): array;
    public function executeAdjustment(string $user, int $headerId): array;
    public function cancelAdjustment(string $user, int $headerId, string $reason): array;

    // ——— Mutations with audit ———
    public function storeAdjustment(string $user, array $data, mixed $plantId): array;
    public function destroyAdjustment(int $id, string $user): array;
    public function addEntrySupplier(string $user, array $data, mixed $plantId): array;
    public function deleteSupplierTemp(int $id): array;
    public function adjustmentInit(string $user, array $data, mixed $plantId): array;
    public function adjustmentSupplier(string $user, array $data, mixed $plantId): array;
    public function adjustMaterialDocument(int $idAdjustHead, ?string $materialDoc, string $user): array;
}
