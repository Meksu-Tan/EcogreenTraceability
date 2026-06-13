<?php declare(strict_types=1);

namespace Modules\Shared\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PlantContextService — resolve/validate plant context from request.
 *
 * RAW SQL DEBT (C08): Simple single-table SELECT queries on m_plant
 * and m_plant_user have been converted to QueryBuilder below.
 * Complex JOIN queries (getUserPlants, getDefaultPlant) and the
 * SQL-string builders (buildPlantFilter, buildMultiPlantFilter) are
 * retained as raw SQL because QueryBuilder JOIN on eudr_ts would
 * require explicit from()/join() chains that add no clarity over
 * the equivalent SQL.
 */
/**
 * ANTI-PATTERN (tech debt — do NOT refactor without explicit approval):
 *
 * All methods are static, making this class impossible to mock or swap via
 * interface binding in ServiceProvider. This blocks unit testing of every
 * consumer that depends on plant-context resolution (controllers, services,
 * middleware, repositories that call PlantContextService::buildPlantFilter(),
 * PlantContextService::resolvePlantId(), etc.).
 *
 * Blast radius of a refactor:
 *   - PlantContextMiddleware, PlantScopeMiddleware
 *   - Every repository that calls buildPlantFilter() / buildMultiPlantFilter()
 *   - Every controller/service that calls resolvePlantId() or getUserPlants()
 *   - All existing Feature tests that exercise plant-scoped endpoints
 *
 * RECOMMENDED REFACTOR (when approved):
 *   1. Create Modules\Shared\Services\Contracts\PlantContextServiceInterface
 *   2. Drop `static` from every method
 *   3. Bind interface → concrete in SharedServiceProvider::register()
 *   4. Inject PlantContextServiceInterface via constructor in all consumers
 *   5. Replace self::method() calls with $this->method()
 *   6. Update all repository call-sites to use the injected instance
 *   7. Add unit tests for each method with mocked DB connections
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
        $plant = DB::connection('eudr_ts')->table('m_plant')
            ->select('code_3', 'id_plant')
            ->where('id_plant', $plantId)
            ->where('status', 1)
            ->first();

        if (!$plant) {
            // Try as code_3
            $plant = DB::connection('eudr_ts')->table('m_plant')
                ->select('code_3', 'id_plant')
                ->where('code_3', $plantId)
                ->where('status', 1)
                ->first();
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
        $plant = DB::connection('eudr_ts')->table('m_plant')
            ->select('code_3')
            ->where('code_3', $code)
            ->where('status', 1)
            ->first();

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
        $user = \App\Models\User::find($userId);
        if ($user && $user->hasRole(['super-admin', 'admin'])) {
            return self::getAllPlants();
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
        $user = \App\Models\User::find($userId);
        if (!$user) return false;

        // Super Admin & Admin memiliki akses global bypass
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        $result = DB::connection('eudr_ts')->table('m_plant_user')
            ->where('user_id', $userId)
            ->where('id_plant', $code_3)
            ->count();

        return $result > 0;
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
        $plant = DB::connection('eudr_ts')->table('m_plant')
            ->select('code_3', 'code_2', 'description', 'id_plant')
            ->where('id_plant', $id)
            ->where('status', 1)
            ->first();

        return $plant ? (array) $plant : null;
    }

    /**
     * Get plant info by code_3
     */
    public static function getPlantByCode(string $code_3): ?array
    {
        $plant = DB::connection('eudr_ts')->table('m_plant')
            ->select('code_3', 'code_2', 'description', 'id_plant')
            ->where('code_3', $code_3)
            ->where('status', 1)
            ->first();

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
