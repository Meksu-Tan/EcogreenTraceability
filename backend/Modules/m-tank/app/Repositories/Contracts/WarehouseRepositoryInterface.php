<?php

declare(strict_types=1);

namespace Modules\Tank\Repositories\Contracts;

interface WarehouseRepositoryInterface
{
    public function getAll(): array;

    public function findById(int $id): ?object;

    public function create(string $user, array $data): int|bool;

    public function update(int $id, string $user, array $data): bool;

    public function deactivate(int $id, string $user): bool;

    public function activate(int $id, string $user): bool;
}
