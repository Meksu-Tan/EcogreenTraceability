<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use Modules\Supplier\Services\Contracts\SupplierServiceInterface;

class SupplierService implements SupplierServiceInterface
{
    public function __construct(
        protected SupplierRepositoryInterface $supplierRepo
    ) {}

    public function listSuppliers(): array
    {
        return $this->supplierRepo->getAll();
    }

    public function storeSupplier(array $data): array
    {
        $result = $this->supplierRepo->create($data);

        return $result
            ? ['status' => 1, 'message' => 'Supplier created successfully']
            : ['status' => 0, 'message' => 'Supplier code already exists'];
    }

    public function updateSupplier(int $id, array $data): array
    {
        $result = $this->supplierRepo->update($id, $data);

        return $result
            ? ['status' => 1, 'message' => 'Supplier updated successfully']
            : ['status' => 0, 'message' => 'Failed to update supplier'];
    }

    public function deactivateSupplier(int $id, string $user): array
    {
        return $this->supplierRepo->deactivate($id, $user)
            ? ['status' => 1, 'message' => 'Supplier deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate'];
    }

    public function activateSupplier(int $id, string $user): array
    {
        return $this->supplierRepo->activate($id, $user)
            ? ['status' => 1, 'message' => 'Supplier activated']
            : ['status' => 0, 'message' => 'Failed to activate'];
    }

    public function getActiveSuppliers(): array
    {
        return $this->supplierRepo->getActive();
    }
}
