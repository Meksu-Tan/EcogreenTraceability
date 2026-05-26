<?php

namespace Modules\TsBlending\Repositories\Contracts;

use Illuminate\Support\Collection;

interface BlendingRepositoryInterface
{
    /**
     * Get active materials for blending dropdown
     */
    public function getActiveMaterials(): Collection;

    /**
     * Generate new blending entry number
     */
    public function generateBlendingEntryNo(int $materialId, int $plantId): ?string;

    /**
     * Get total stock for a material
     */
    public function getTotalStockMaterial(int $materialId, int $plantId): float;

    /**
     * Get total quantity for material entry (temporary or balance detail)
     */
    public function getTotalQtyMaterial(string $mode, string $entryNo, ?int $idHead, int $plantId): float;

    /**
     * Get material list (from temporary or balance detail)
     */
    public function getMaterialList(string $mode, string $entryNo, ?int $idHead, int $plantId): Collection;

    /**
     * Get blending list with all related data
     */
    public function getBlendingList(int $plantId): Collection;

    /**
     * Get main tanks for dropdown (based on material type)
     */
    public function getActiveTanksRundown(int $materialId, int $plantId): Collection;

    /**
     * Get sub tanks (tank details) for specific tank
     */
    public function getActiveSpecificTanksRundown(int $sloc): Collection;

    /**
     * Add material to blending entry (temporary storage)
     */
    public function addBlendingEntryMaterial(string $user, string $entryNo, int $idMaterial, float $qty, int $idTank, int $plantId): array;

    /**
     * Delete blending material from temporary
     */
    public function deleteBlendingMaterial(int $id): bool;

    /**
     * Get lock status for period
     */
    public function getLockStatus(string $entryDate): bool;

    /**
     * Create material document number
     */
    public function createMaterialDocument(string $user, int $idTraceHead, string $materialDoc, string $mode): array;

    /**
     * Deactivate blending entry
     */
    public function deactivateBlending(string $id, string $user): array;

    /**
     * Update entry subtank
     */
    public function updateEntrySubTank(string $user, int $idHead, array $tails): array;

    /**
     * Get material document by trace head
     */
    public function getMaterialDocument(int $idTraceHead): ?object;

    /**
     * Check if material already exists in temporary
     */
    public function checkMaterialInTemporary(int $idMaterial, string $entryNo, int $plantId): bool;

    /**
     * Get item count in temporary balance
     */
    public function getTemporaryItemCount(string $entryNo): int;

    /**
     * Get temporary entries for entry number
     */
    public function getTemporaryEntries(string $entryNo): Collection;
}