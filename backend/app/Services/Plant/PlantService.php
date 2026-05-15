<?php

namespace App\Services\Plant;

use App\Contracts\Plant\PlantRepositoryInterface;

class PlantService
{
    public function __construct(
        protected PlantRepositoryInterface $plantRepo
    ) {}

    public function listPlants(): array
    {
        return $this->plantRepo->getAll();
    }

    public function storePlant(array $data): array
    {
        $id = $this->plantRepo->create($data);
        if ($id === false) {
            return ['status' => 0, 'message' => 'Plant code already exists'];
        }
        return [
            'status' => 1, 
            'message' => 'Plant created successfully',
            'data' => ['id_plant' => $id]
        ];
    }

    public function updatePlant(int $id, array $data): array
    {
        $updated = $this->plantRepo->update($id, $data);
        if (!$updated) {
            return ['status' => 0, 'message' => 'Failed to update plant'];
        }
        return ['status' => 1, 'message' => 'Plant updated successfully'];
    }

    public function deactivatePlant(int $id, string $user): array
    {
        $result = $this->plantRepo->deactivate($id, $user);
        return $result
            ? ['status' => 1, 'message' => 'Plant deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate plant'];
    }

    public function activatePlant(int $id, string $user): array
    {
        $result = $this->plantRepo->activate($id, $user);
        return $result
            ? ['status' => 1, 'message' => 'Plant activated']
            : ['status' => 0, 'message' => 'Failed to activate plant'];
    }
}
