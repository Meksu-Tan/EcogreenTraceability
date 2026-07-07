<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Services\Contracts\PlantContextServiceInterface;

class PlantContextService implements PlantContextServiceInterface
{
    public function resolvePlantId(mixed $plantId, ?int $userId = null): ?string
    {
        if ($plantId === null || $plantId === '' || $plantId === 0 || $plantId === '0' || $plantId === 'all') {
            return null;
        }

        if (is_numeric($plantId)) {
            return $this->resolveById((int) $plantId, $userId);
        }

        if (is_string($plantId) && strlen($plantId) <= 4) {
            return $this->resolveByCode($plantId, $userId);
        }

        return (string) $plantId;
    }

    public function resolveById(int $plantId, ?int $userId = null): ?string
    {
        $plant = DB::connection('eudr_ts')->table('m_plant')
            ->select('code_3', 'id_plant')
            ->where('id_plant', $plantId)
            ->where('status', 1)
            ->first();

        if (! $plant) {
            $plant = DB::connection('eudr_ts')->table('m_plant')
                ->select('code_3', 'id_plant')
                ->where('code_3', $plantId)
                ->where('status', 1)
                ->first();
        }

        if ($plant && $userId) {
            if (! $this->userHasAccessToPlant($userId, $plant->code_3)) {
                Log::warning('PlantContextService: User does not have access to plant', [
                    'user_id' => $userId,
                    'plant_id' => $plantId,
                ]);

                return null;
            }
        }

        return $plant ? (string) $plant->code_3 : null;
    }

    public function resolveByCode(string $code, ?int $userId = null): ?string
    {
        $plant = DB::connection('eudr_ts')->table('m_plant')
            ->select('code_3')
            ->where('code_3', $code)
            ->where('status', 1)
            ->first();

        if ($plant && $userId) {
            if (! $this->userHasAccessToPlant($userId, $plant->code_3)) {
                return null;
            }
        }

        return $plant ? (string) $plant->code_3 : null;
    }

    public function getUserPlants(int $userId): array
    {
        $user = User::find($userId);
        if ($user && $user->hasRole(['super-admin', 'admin'])) {
            return $this->getAllPlants();
        }

        return DB::connection('eudr_ts')->select(
            'SELECT p.code_3, p.code_2, p.description, p.id_plant
                FROM m_plant_user pu
                JOIN m_plant p ON pu.id_plant = p.code_3
               WHERE pu.user_id = ?
                 AND p.status = 1
               ORDER BY p.description ASC',
            [$userId]
        );
    }

    public function getAllPlants(): array
    {
        return DB::connection('eudr_ts')->select(
            'SELECT code_3, code_2, description, id_plant
                FROM m_plant
               WHERE status = 1
               ORDER BY description ASC'
        );
    }

    public function userHasAccessToPlant(int $userId, string $code_3): bool
    {
        $user = User::find($userId);
        if (! $user) {
            return false;
        }

        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        $result = DB::connection('eudr_ts')->table('m_plant_user')
            ->where('user_id', $userId)
            ->where('id_plant', $code_3)
            ->count();

        return $result > 0;
    }

    public function getDefaultPlant(int $userId): ?array
    {
        $result = DB::connection('eudr_ts')->selectOne(
            'SELECT p.code_3, p.code_2, p.description, p.id_plant
                FROM m_plant_user pu
                JOIN m_plant p ON pu.id_plant = p.code_3
               WHERE pu.user_id = ?
                 AND p.status = 1
               ORDER BY pu.user_id ASC
               LIMIT 1',
            [$userId]
        );

        if ($result) {
            return [
                'code_3' => $result->code_3,
                'code_2' => $result->code_2,
                'description' => $result->description,
                'id_plant' => $result->id_plant,
            ];
        }

        $user = DB::table('users')->find($userId);
        if ($user && $user->id_plant) {
            return $this->getPlantById($user->id_plant);
        }

        return DB::connection('eudr_ts')->selectOne(
            'SELECT code_3, code_2, description, id_plant
                FROM m_plant
               WHERE status = 1
               ORDER BY id_plant ASC
               LIMIT 1'
        ) ? (array) DB::connection('eudr_ts')->selectOne(
            'SELECT code_3, code_2, description, id_plant
                FROM m_plant
               WHERE status = 1
               ORDER BY id_plant ASC
               LIMIT 1'
        ) : null;
    }

    public function getPlantById(int $id): ?array
    {
        $plant = DB::connection('eudr_ts')->table('m_plant')
            ->select('code_3', 'code_2', 'description', 'id_plant')
            ->where('id_plant', $id)
            ->where('status', 1)
            ->first();

        return $plant ? (array) $plant : null;
    }

    public function getPlantByCode(string $code_3): ?array
    {
        $plant = DB::connection('eudr_ts')->table('m_plant')
            ->select('code_3', 'code_2', 'description', 'id_plant')
            ->where('code_3', $code_3)
            ->where('status', 1)
            ->first();

        return $plant ? (array) $plant : null;
    }

    public function buildPlantFilter(string $tableAlias, ?string $plantCode): array
    {
        $column = "{$tableAlias}.id_plant";

        if ($plantCode === null) {
            return [
                'sql' => '1=1',
                'bindings' => [],
            ];
        }

        return [
            'sql' => "{$column} = ?",
            'bindings' => [$plantCode],
        ];
    }

    public function buildMultiPlantFilter(array $tableColumns, ?string $plantCode): array
    {
        if ($plantCode === null) {
            return [
                'sql' => '1=1',
                'bindings' => [],
            ];
        }

        $conditions = [];
        $bindings = [];

        foreach ($tableColumns as $column => $param) {
            $conditions[] = "{$column} = ?";
            $bindings[] = $plantCode;
        }

        return [
            'sql' => '('.implode(' OR ', $conditions).')',
            'bindings' => $bindings,
        ];
    }
}
