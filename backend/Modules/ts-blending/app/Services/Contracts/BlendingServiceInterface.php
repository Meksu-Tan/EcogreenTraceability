<?php

declare(strict_types=1);

namespace Modules\TsBlending\Services\Contracts;

use Illuminate\Support\Collection;

interface BlendingServiceInterface
{
    public function getActiveMaterials(): Collection;

    public function generateEntryNo(int $materialId, int $plantId): ?string;

    public function getTotalStockMaterial(int $materialId, int $plantId, $slocId = null): float;

    public function getTotalQtyMaterial(?string $mode, string $entryNo, ?int $idHead, int $plantId): float;

    public function getMaterialList(?string $mode, string $entryNo, ?int $idHead, int $plantId): Collection;

    public function getBlendingList(int $plantId, int $page = 1, int $perPage = 5): array;

    public function getActiveTanksRundown(int $materialId, int $plantId): Collection;

    public function getActiveSpecificTanksRundown(int $sloc): Collection;

    public function getTanks(?int $plantId = null): Collection;

    public function getTankDetails(string $tankDescription, ?int $plantId = null): Collection;

    public function getAllTanks(int $plantId, ?int $materialId = null): Collection;

    public function addMaterialToBlending(string $user, array $data, int $plantId): array;

    public function deleteBlendingMaterial(int $id): bool;

    public function createMaterialDocument(string $user, int $idTraceHead, string $materialDoc, string $mode): array;

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array;

    public function executeBlending(string $user, array $data, int $plantId): array;

    public function deactivateBlending(string $id, string $user): array;
}
