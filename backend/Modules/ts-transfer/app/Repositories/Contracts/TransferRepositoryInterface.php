<?php
declare(strict_types=1);
namespace Modules\TsTransfer\Repositories\Contracts;

use Illuminate\Support\Collection;

interface TransferRepositoryInterface
{
    public function getActiveMaterials(): Collection;

    public function generateTransferEntryNo(int $materialId, int $plantId): ?string;

    public function getTotalStockMaterial(int $materialId, int $tankId, int $plantId): float;

    public function getTransferList(int $plantId, int $page = 1, int $perPage = 5): array;

    public function getActiveTanksRundown(?int $materialId, int $plantId, bool $excludePlant = true): Collection;

    public function getActiveSpecificTanksRundown(int $sloc): Collection;

    public function getLockStatus(string $entryDate): bool;

    public function getUpdateSupplierMaterial(int $idMaterial, int $idSloc, int $plantId): ?object;

    public function postAdjEntrySupplier(string $user, string $adjNumber, int $idSupplier, int $idMaterial, float $qty, string $batchSap, int $plantId): array;

    public function createMaterialDocument(string $user, int $idTraceHead, string $materialDoc, string $mode): array;

    public function deactivateTransfer(string $id, string $user): array;

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array;

    public function checkTraceNoExists(string $traceNo): bool;

    public function logTransaction(string $module, string $type, string $description, string $user): void;

    public function getSlocPlant(int $sloc): ?int;

    public function findOrphanHeads(int $idMaterial, int $sloc, int $plantId): array;

    public function findPlantById(int $plantId): ?object;

    public function findPlantCode(int $plantId): string;

    public function createBalanceHeader(array $data): int;

    public function createBalanceDetail(array $data): int;

    public function createAdjustmentHeader(array $data): int;

    public function createAdjustmentDetail(array $data): bool;

    public function findMaterialRundown(int $idMaterial): string;

    public function generateAdjSequence(string $prefix12): string;

    public function getNextSequence(string $ymd, string $rundownCode, string $plantCode): string;
}
