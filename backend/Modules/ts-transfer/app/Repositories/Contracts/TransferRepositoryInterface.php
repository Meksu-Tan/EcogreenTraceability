<?php declare(strict_types=1);
namespace Modules\TsTransfer\Repositories\Contracts;

use Illuminate\Support\Collection;

interface TransferRepositoryInterface
{
    public function getActiveMaterials(): Collection;

    public function generateTransferEntryNo(int $materialId, int $plantId): ?string;

    public function getTotalStockMaterial(int $materialId, int $tankId, int $plantId): float;

    public function getTransferList(int $plantId): Collection;

    public function getActiveTanksRundown(?int $materialId, int $plantId): Collection;

    public function getActiveSpecificTanksRundown(int $sloc): Collection;

    public function getLockStatus(string $entryDate): bool;

    public function getUpdateSupplierMaterial(int $idMaterial, int $idTank, int $plantId): ?object;

    public function postAdjEntrySupplier(string $user, string $adjNumber, int $idSupplier, int $idMaterial, float $qty, string $batchSap, int $plantId): array;

    public function createMaterialDocument(string $user, int $idTraceHead, string $materialDoc, string $mode): array;

    public function deactivateTransfer(string $id, string $user): array;

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array;

    public function checkTraceNoExists(string $traceNo): bool;

    public function logTransaction(string $module, string $type, string $description, string $user): void;
}
