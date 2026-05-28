<?php declare(strict_types=1);
namespace Modules\Storage\Repositories\Contracts;

interface StorageWarehouseRepositoryInterface
{
    public function getAllWarehouses(): array;
    public function findWarehouseById(int $id): ?object;
    public function createWarehouse(array $data): bool;
    public function updateWarehouse(int $id, array $data): bool;
    public function deactivateWarehouse(int $id, string $user): bool;
    public function activateWarehouse(int $id, string $user): bool;
}