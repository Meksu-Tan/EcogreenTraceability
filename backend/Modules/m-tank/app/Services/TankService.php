<?php

declare(strict_types=1);

namespace Modules\Tank\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Tank\Models\Tank;
use Modules\Tank\Repositories\Contracts\TankRepositoryInterface;
use Modules\Tank\Services\Contracts\TankServiceInterface;

class TankService implements TankServiceInterface
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
            'data' => ['id' => $id],
        ];
    }

    public function updateTank(int $id, array $data): array
    {
        $updated = $this->tankRepo->update($id, $data);
        if (! $updated) {
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

    public function syncFromExternal(string $user, bool $refresh = false): array
    {
        $url = config('services.tankfarm.url');
        $token = config('services.tankfarm.token');

        if (! $url || ! $token) {
            return ['status' => 0, 'message' => 'Tank farm API URL or token not configured.'];
        }

        try {
            $response = Http::timeout(30)->withOptions(['verify' => false])->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ])->post($url, [
                'plantCodes' => [],
                'type' => 'tanks',
            ]);

            if (! $response->successful()) {
                return ['status' => 0, 'message' => 'Failed to fetch data from external API. HTTP '.$response->status()];
            }

            $data = $response->json();
            if (! isset($data['success']) || ! $data['success'] || ! isset($data['data'])) {
                return ['status' => 0, 'message' => 'Invalid response from external API.'];
            }
        } catch (ConnectionException $e) {
            return ['status' => 0, 'message' => 'Connection timeout. Please check network to tank farm API.'];
        } catch (\Exception $e) {
            return ['status' => 0, 'message' => 'Connection failed: '.$e->getMessage()];
        }

        $syncCount = 0;
        $plantCount = 0;
        $syncedTanks = [];

        foreach ($data['data'] as $plantData) {
            $plantCode = $plantData['plantCode'] ?? null;
            $plantName = $plantData['plantName'] ?? null;

            if (! $plantCode || empty($plantData['tanks'])) {
                continue;
            }
            $plantCount++;

            foreach ($plantData['tanks'] as $tankItem) {
                $tankNumber = $tankItem['tankNumber'] ?? null;
                $tankHeight = $tankItem['tankHeight'] ?? 0;
                $tankId = $tankItem['id'] ?? null;

                if (! $tankNumber) {
                    continue;
                }

                // Only add new tanks that don't exist yet
                $exists = Tank::where('id_plant', $plantCode)
                    ->where('tf_number', $tankNumber)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $maxId = Tank::max('id_sloc') ?? 0;
                $newId = $maxId + 1;

                Tank::create([
                    'id_sloc' => $newId,
                    'id_tankfarm' => $tankId,
                    'id_plant' => $plantCode,
                    'plant_name' => $plantName,
                    'tf_number' => $tankNumber,
                    'tank_height' => $tankHeight,
                    'description' => '',
                    'status' => '1',
                    'created_by' => $user,
                ]);

                $syncCount++;
                $syncedTanks[] = $tankNumber;
            }
        }

        // Save last sync timestamp
        \Cache::put('tank_last_sync_at', now()->toIso8601String(), 86400 * 7);
        \Cache::put('tank_last_sync_user', $user, 86400 * 7);
        \Cache::put('tank_last_sync_count', $syncCount, 86400 * 7);
        \Cache::put('tank_last_sync_tanks', $syncedTanks, 86400 * 7);

        if ($syncCount > 0) {
            return [
                'status' => 1,
                'message' => "Synced {$syncCount} tanks from {$plantCount} plants.",
                'synced' => $syncCount,
                'plants' => $plantCount,
                'tanks' => $syncedTanks,
            ];
        } else {
            return [
                'status' => 2,
                'message' => "All {$plantCount} plants are up to date. No changes needed.",
                'synced' => 0,
                'plants' => $plantCount,
                'tanks' => [],
            ];
        }
    }

    public function getLastSyncInfo(): array
    {
        return [
            'last_sync_at' => \Cache::get('tank_last_sync_at'),
            'last_sync_user' => \Cache::get('tank_last_sync_user'),
            'last_sync_count' => \Cache::get('tank_last_sync_count', 0),
            'last_sync_tanks' => \Cache::get('tank_last_sync_tanks', []),
        ];
    }
}
