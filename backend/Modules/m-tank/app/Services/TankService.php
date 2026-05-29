<?php declare(strict_types=1);
namespace Modules\Tank\Services;

use Modules\Tank\Repositories\Contracts\TankRepositoryInterface;

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

    public function syncFromExternal(string $user): array
    {
        $url = config('services.tankfarm.url');
        $token = config('services.tankfarm.token');

        $response = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ])->post($url, [
            'plantCodes' => ['1007'],
            'type' => 'tanks'
        ]);

        if (!$response->successful()) {
            return ['status' => 0, 'message' => 'Failed to fetch data from external API'];
        }

        $data = $response->json();
        if (!isset($data['success']) || !$data['success'] || !isset($data['data'])) {
            return ['status' => 0, 'message' => 'Invalid response from external API'];
        }

        $syncCount = 0;
        foreach ($data['data'] as $plantData) {
            $plantCode = $plantData['plantCode'] ?? null;
            $plantName = $plantData['plantName'] ?? null;

            if (!$plantCode || empty($plantData['tanks'])) continue;

            foreach ($plantData['tanks'] as $tankItem) {
                $tankNumber = $tankItem['tankNumber'] ?? null;
                $tankHeight = $tankItem['tankHeight'] ?? 0;

                if (!$tankNumber) continue;

                $updated = $this->tankRepo->syncUpdateOrCreate([
                    'plant_code' => $plantCode,
                    'plant_name' => $plantName,
                    'tank_number' => $tankNumber,
                    'tank_height' => $tankHeight,
                ], $user);

                if ($updated) {
                    $syncCount++;
                }
            }
        }

        if ($syncCount > 0) {
            return ['status' => 1, 'message' => "Successfully synced {$syncCount} tanks from external API."];
        } else {
            return ['status' => 1, 'message' => "All tanks are up to date. No updates needed."];
        }
    }
}
