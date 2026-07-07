<?php

declare(strict_types=1);

namespace Modules\Manufacturer\Services;

use Modules\Manufacturer\Repositories\Contracts\ManufacturerRepositoryInterface;
use Modules\Manufacturer\Services\Contracts\ManufacturerServiceInterface;

class ManufacturerService implements ManufacturerServiceInterface
{
    public function __construct(
        protected ManufacturerRepositoryInterface $manufacturerRepo
    ) {}

    public function listManufacturers(): array
    {
        return $this->manufacturerRepo->getAll();
    }

    public function storeManufacturer(array $data): array
    {
        $result = $this->manufacturerRepo->create($data);

        return $result
            ? ['status' => 1, 'message' => 'Manufacturer created successfully']
            : ['status' => 0, 'message' => 'Manufacturer code already exists'];
    }

    public function updateManufacturer(int $id, array $data): array
    {
        $result = $this->manufacturerRepo->update($id, $data);

        return $result
            ? ['status' => 1, 'message' => 'Manufacturer updated successfully']
            : ['status' => 0, 'message' => 'Failed to update manufacturer'];
    }

    public function deactivateManufacturer(int $id, string $user): array
    {
        return $this->manufacturerRepo->deactivate($id, $user)
            ? ['status' => 1, 'message' => 'Manufacturer deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate'];
    }

    public function activateManufacturer(int $id, string $user): array
    {
        return $this->manufacturerRepo->activate($id, $user)
            ? ['status' => 1, 'message' => 'Manufacturer activated']
            : ['status' => 0, 'message' => 'Failed to activate'];
    }

    public function getActiveManufacturers(): array
    {
        return $this->manufacturerRepo->getActive();
    }
}
