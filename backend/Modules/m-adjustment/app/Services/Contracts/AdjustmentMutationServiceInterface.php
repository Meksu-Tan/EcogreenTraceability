<?php

declare(strict_types=1);

namespace Modules\Adjustment\Services\Contracts;

interface AdjustmentMutationServiceInterface
{
    public function storeAdjustment(string $user, array $data, mixed $plantId): array;

    public function destroyAdjustment(int $id, string $user): array;

    public function destroyAdjustmentWhx(int $id, string $user): array;

    public function addEntrySupplier(string $user, array $data, mixed $plantId): array;

    public function deleteSupplierTemp(int $id): array;

    public function adjustmentInit(string $user, array $data, mixed $plantId): array;

    public function adjustmentSupplier(string $user, array $data, mixed $plantId): array;

    public function adjustMaterialDocument(int $idAdjustHead, ?string $materialDoc, string $user): array;

    public function createAdjustmentHeader(string $user, array $data, mixed $plantId): array;

    public function createAdjustmentDetail(string $user, int $headerId, array $data): array;

    public function approveAdjustment(string $user, int $headerId, int $status): array;

    public function executeAdjustment(string $user, int $headerId): array;

    public function cancelAdjustment(string $user, int $headerId, string $reason): array;
}
