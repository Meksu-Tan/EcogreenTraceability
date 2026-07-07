<?php

declare(strict_types=1);

namespace Modules\Plant\Services\Contracts;

interface PlantServiceInterface
{
    public function listPlants(): array;

    public function storePlant(array $data): array;

    public function updatePlant(int $id, array $data): array;

    public function deactivatePlant(int $id, string $user): array;

    public function activatePlant(int $id, string $user): array;
}
