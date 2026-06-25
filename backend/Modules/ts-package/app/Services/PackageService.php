<?php
declare(strict_types=1);
namespace Modules\TsPackage\Services;

use Modules\TsPackage\Services\Contracts\PackageServiceInterface;
use Modules\TsPackage\Repositories\Contracts\PackageRepositoryInterface;
use Illuminate\Support\Collection;

class PackageService implements PackageServiceInterface
{
    public function __construct(
        protected PackageRepositoryInterface $packageRepo
    ) {}

    public function getDtPckEntry(int $plantId = 0, int $page = 1, int $perPage = 50): array
    {
        return $this->packageRepo->getDtPckEntry($plantId, $page, $perPage);
    }

    public function getActiveFgProduct(): Collection
    {
        return $this->packageRepo->getActiveFgProduct();
    }

    public function getWipMaterialByFgProduct(array $data): Collection
    {
        return $this->packageRepo->getWipMaterialByFgProduct($data);
    }

    public function getCmbActiveTankPck(array $data): Collection
    {
        return $this->packageRepo->getCmbActiveTankPck($data);
    }

    public function getCmbActiveWarehousePck(array $data): Collection
    {
        return $this->packageRepo->getCmbActiveWarehousePck($data);
    }

    public function getCmbActiveSpecificTank(array $data): Collection
    {
        return $this->packageRepo->getCmbActiveSpecificTank($data);
    }

    public function store(string $user, array $data): array
    {
        return $this->packageRepo->store($user, $data);
    }

    public function cancel(string $user, array $data): array
    {
        return $this->packageRepo->cancel($user, $data);
    }

    public function updatePo(string $user, array $data): array
    {
        return $this->packageRepo->updatePo($user, $data);
    }

    public function updateBatch(string $user, array $data): array
    {
        return $this->packageRepo->updateBatch($user, $data);
    }

    public function updateSubTank(string $user, array $data): array
    {
        return $this->packageRepo->updateSubTank($user, $data);
    }

    public function generateTraceNo(int $materialId, int $plantId): string
    {
        return $this->packageRepo->generateTraceNo($materialId, $plantId);
    }

    public function getAllWarehouses(): Collection
    {
        return $this->packageRepo->getAllWarehouses();
    }
}
