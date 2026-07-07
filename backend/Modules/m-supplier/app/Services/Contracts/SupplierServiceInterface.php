<?php

declare(strict_types=1);

namespace Modules\Supplier\Services\Contracts;

interface SupplierServiceInterface
{
    public function listSuppliers(): array;

    public function storeSupplier(array $data): array;

    public function updateSupplier(int $id, array $data): array;

    public function deactivateSupplier(int $id, string $user): array;

    public function activateSupplier(int $id, string $user): array;

    public function getActiveSuppliers(): array;
}
