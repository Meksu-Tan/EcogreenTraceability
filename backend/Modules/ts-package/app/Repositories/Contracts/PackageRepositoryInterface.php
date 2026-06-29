<?php
declare(strict_types=1);
namespace Modules\TsPackage\Repositories\Contracts;

use Illuminate\Support\Collection;

interface PackageRepositoryInterface
{
    public function getDtPckEntry(int $plantId = 0, int $page = 1, int $perPage = 50): array;
    public function getActiveFgProduct(): Collection;
    public function getWipMaterialByFgProduct(array $data): Collection;
    public function getCmbActiveTankPck(array $data): Collection;
    public function getCmbActiveWarehousePck(array $data): Collection;
    public function getCmbActiveSpecificTank(array $data): Collection;
    public function store(string $user, array $data): array;
    public function cancel(string $user, array $data): array;
    public function updatePo(string $user, array $data): array;
    public function updateBatch(string $user, array $data): array;
    public function updateSubTank(string $user, array $data): array;
    public function generateTraceNo(int $materialId, int $plantId, ?int $warehouseId = null, ?string $batchNo = null): string;
    public function getAllWarehouses(): Collection;
}
