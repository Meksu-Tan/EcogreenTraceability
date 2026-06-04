<?php declare(strict_types=1);

namespace Modules\Shared\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PlantContextService - Service untuk mengelola plant context/user scoping
 *
 * Fungsi:
 * 1. Resolve plant ID dari berbagai format (id, code_2, code_3)
 * 2. Get plants yang accessible oleh user
 * 3. Validate plant access
 * 4. Get default plant untuk user
 */
class PlantContextService
{
    /**
     * Resolve plant ID dari request/input
     *
     * @param mixed $plantId Plant ID (bisa int, string, atau null)
     * @param int|null $userId User ID untuk cek plant access
     * @return string|null Resolved plant code_3 atau null untuk "all plants"
     */
    public static function resolvePlantId(mixed $plantId, ?int $userId = null): ?string
    {
        // Handle null, empty, atau "all plants" indicator
        if ($plantId === null || $plantId === '' || $plantId === 0 || $plantId === '0' || $plantId === 'all') {
            return null; // null = all plants (no filter)
        }

        // Jika sudah numeric ID
        if (is_numeric($plantId)) {
            return self::resolveById((int) $plantId, $userId);
        }

        // Jika sudah code_3 format
        if (is_string($plantId) && strlen($plantId) <= 4) {
            return self::resolveByCode($plantId, $userId);
        }

        return (string) $plantId;
    }

    /**
     * Resolve plant by ID
     */
    public static function resolveById(int $plantId, ?int $userId = null): ?string
    {
        // Check if it's actually an ID or code_3
        $plant = DB::connection('eudr_ts')->selectOne(
            'SELECT code_3, id_plant FROM m_plant WHERE id_plant = ? AND status = 1',
            [$plantId]
        );

        if (!$plant) {
            // Try as code_3
            $plant = DB::connection('eudr_ts')->selectOne(
                'SELECT code_3, id_plant FROM m_plant WHERE code_3 = ? AND status = 1',
                [$plantId]
            );
        }

        if ($plant && $userId) {
            // Verify user has access to this plant
            if (!self::userHasAccessToPlant($userId, $plant->code_3)) {
                Log::warning('PlantContextService: User does not have access to plant', [
                    'user_id' => $userId,
                    'plant_id' => $plantId,
                ]);
                return null;
            }
        }

        return $plant ? (string) $plant->code_3 : null;
    }

    /**
     * Resolve plant by code_3
     */
    public static function resolveByCode(string $code, ?int $userId = null): ?string
    {
        $plant = DB::connection('eudr_ts')->selectOne(
            'SELECT code_3 FROM m_plant WHERE code_3 = ? AND status = 1',
            [$code]
        );

        if ($plant && $userId) {
            if (!self::userHasAccessToPlant($userId, $plant->code_3)) {
                return null;
            }
        }

        return $plant ? (string) $plant->code_3 : null;
    }

    /**
     * Get plants yang accessible oleh user
     */
    public static function getUserPlants(int $userId): array
    {
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

    /**
     * Get all active plants (for admin or all plants selector)
     */
    public static function getAllPlants(): array
    {
        return DB::connection('eudr_ts')->select(
            'SELECT code_3, code_2, description, id_plant
               FROM m_plant
              WHERE status = 1
              ORDER BY description ASC'
        );
    }

    /**
     * Check jika user punya akses ke plant
     */
    public static function userHasAccessToPlant(int $userId, string $code_3): bool
    {
        $result = DB::connection('eudr_ts')->selectOne(
            'SELECT COUNT(*) as cnt FROM m_plant_user
              WHERE user_id = ? AND id_plant = ?',
            [$userId, $code_3]
        );

        return ($result->cnt ?? 0) > 0;
    }

    /**
     * Get default plant untuk user
     */
    public static function getDefaultPlant(int $userId): ?array
    {
        // Check m_plant_user for user's assigned plant
        $result = DB::connection('eudr_ts')->selectOne(
            'SELECT p.code_3, p.code_2, p.description, p.id_plant
               FROM m_plant_user pu
               JOIN m_plant p ON pu.id_plant = p.code_3
              WHERE pu.user_id = ?
                AND p.status = 1
              ORDER BY pu.id
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

        // Fallback: user's id_plant column in users table
        $user = DB::table('users')->find($userId);
        if ($user && $user->id_plant) {
            return self::getPlantById($user->id_plant);
        }

        // Last fallback: first active plant
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

    /**
     * Get plant info by ID
     */
    public static function getPlantById(int $id): ?array
    {
        $plant = DB::connection('eudr_ts')->selectOne(
            'SELECT code_3, code_2, description, id_plant
               FROM m_plant
              WHERE id_plant = ? AND status = 1',
            [$id]
        );

        return $plant ? (array) $plant : null;
    }

    /**
     * Get plant info by code_3
     */
    public static function getPlantByCode(string $code_3): ?array
    {
        $plant = DB::connection('eudr_ts')->selectOne(
            'SELECT code_3, code_2, description, id_plant
               FROM m_plant
              WHERE code_3 = ? AND status = 1',
            [$code_3]
        );

        return $plant ? (array) $plant : null;
    }

    /**
     * Build plant filter SQL clause
     *
     * @param string $tableAlias Table alias (e.g., 'bh', 'a', 't')
     * @param string|null $plantCode Resolved plant code (null = all plants)
     * @return array ['sql' => string, 'bindings' => array]
     */
    public static function buildPlantFilter(string $tableAlias, ?string $plantCode): array
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

    /**
     * Build plant filter dengan multiple tables
     *
     * @param array $tableColumns ['alias.column' => 'binding_param']
     * @param string|null $plantCode
     * @return array ['sql' => string, 'bindings' => array]
     */
    public static function buildMultiPlantFilter(array $tableColumns, ?string $plantCode): array
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
            'sql' => '(' . implode(' OR ', $conditions) . ')',
            'bindings' => $bindings,
        ];
    }
}
