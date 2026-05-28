<?php declare(strict_types=1);
namespace Modules\Supplier\Repositories\Contracts;

interface SupplierRepositoryInterface
{
    public function getAll(): array;
    public function findById(int $id): ?object;
    public function create(array $data): bool;
    public function update(int $id, array $data): bool;
    public function deactivate(int $id, string $user): bool;
    public function activate(int $id, string $user): bool;
    public function getActive(): array;
}
