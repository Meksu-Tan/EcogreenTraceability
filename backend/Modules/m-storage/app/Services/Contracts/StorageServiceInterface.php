<?php declare(strict_types=1);
namespace Modules\Storage\Services\Contracts;

interface StorageServiceInterface
{
    public function listTanks(): array;
    public function storeTank(array $data): array;
    public function updateTank(int $id, array $data): array;
    public function deactivateTank(int $id, string $user): array;
    public function activateTank(int $id, string $user): array;
    public function listDetails(int $tankId): array;
    public function storeDetail(array $data): array;
    public function updateDetail(int $id, array $data): array;
    public function deactivateDetail(int $id, string $user): array;
    public function activateDetail(int $id, string $user): array;
    public function listWarehouses(): array;
    public function storeWarehouse(array $data): array;
    public function updateWarehouse(int $id, array $data): array;
    public function deactivateWarehouse(int $id, string $user): array;
    public function activateWarehouse(int $id, string $user): array;
}