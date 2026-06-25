<?php
declare(strict_types=1);
namespace Modules\Manufacturer\Services\Contracts;

interface ManufacturerServiceInterface
{
    public function listManufacturers(): array;
    public function storeManufacturer(array $data): array;
    public function updateManufacturer(int $id, array $data): array;
    public function deactivateManufacturer(int $id, string $user): array;
    public function activateManufacturer(int $id, string $user): array;
    public function getActiveManufacturers(): array;
}