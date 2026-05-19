<?php

namespace App\Services\Tank;

use App\Contracts\Tank\TankRepositoryInterface;

class TankService
{
    public function __construct(
        protected TankRepositoryInterface $tankRepo
    ) {}

    public function listTanks(): array
    {
        return $this->tankRepo->getAll();
    }

    public function storeTank(array $data): array
    {
        $id = $this->tankRepo->create($data);
        if ($id === false) {
            return ['status' => 0, 'message' => 'Tank already exists for this plant'];
        }
        return [
            'status' => 1, 
            'message' => 'Tank created successfully',
            'data' => ['id' => $id]
        ];
    }

    public function updateTank(int $id, array $data): array
    {
        $updated = $this->tankRepo->update($id, $data);
        if (!$updated) {
            return ['status' => 0, 'message' => 'Failed to update tank'];
        }
        return ['status' => 1, 'message' => 'Tank updated successfully'];
    }

    public function deactivateTank(int $id, string $user): array
    {
        $result = $this->tankRepo->deactivate($id, $user);
        return $result
            ? ['status' => 1, 'message' => 'Tank deactivated']
            : ['status' => 0, 'message' => 'Failed to deactivate tank'];
    }

    public function activateTank(int $id, string $user): array
    {
        $result = $this->tankRepo->activate($id, $user);
        return $result
            ? ['status' => 1, 'message' => 'Tank activated']
            : ['status' => 0, 'message' => 'Failed to activate tank'];
    }
}
