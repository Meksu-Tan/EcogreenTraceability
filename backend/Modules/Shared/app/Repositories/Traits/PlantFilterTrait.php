<?php declare(strict_types=1);

namespace Modules\Shared\Repositories\Traits;

use Modules\Shared\Services\PlantContextService;

/**
 * PlantFilterTrait - Trait untuk auto-inject plant filter di repository queries
 *
 * Usage:
 * class MyRepository {
 *     use PlantFilterTrait;
 *
 *     public function getData($plantId, $userId) {
 *         $filter = $this->buildPlantFilter($plantId, $userId);
 *         // use $filter['sql'] and $filter['bindings'] in query
 *     }
 * }
 */
trait PlantFilterTrait
{
    /**
     * Build plant filter dari request parameters
     *
     * @param mixed $plantId Plant ID dari request
     * @param int|null $userId User ID untuk validasi access
     * @param string $defaultColumn Column default (e.g., 'id_plant')
     * @return array ['sql' => string, 'bindings' => array, 'plant_code' => string|null]
     */
    protected function buildPlantFilter(mixed $plantId, ?int $userId = null, string $defaultColumn = 'id_plant'): array
    {
        $resolvedCode = PlantContextService::resolvePlantId($plantId, $userId);

        return [
            'sql' => $resolvedCode ? "{$defaultColumn} = ?" : '1=1',
            'bindings' => $resolvedCode ? [$resolvedCode] : [],
            'plant_code' => $resolvedCode,
        ];
    }

    /**
     * Build plant filter dengan alias table
     *
     * @param string $tableAlias Table alias (e.g., 'bh', 'a')
     * @param mixed $plantId Plant ID
     * @param int|null $userId User ID
     * @return array
     */
    protected function buildTablePlantFilter(string $tableAlias, mixed $plantId, ?int $userId = null): array
    {
        $resolvedCode = PlantContextService::resolvePlantId($plantId, $userId);
        $column = "{$tableAlias}.id_plant";

        return [
            'sql' => $resolvedCode ? "{$column} = ?" : '1=1',
            'bindings' => $resolvedCode ? [$resolvedCode] : [],
            'plant_code' => $resolvedCode,
        ];
    }

    /**
     * Get all plants untuk "all plants" selection
     * Atau filter dengan plant tertentu
     *
     * @param mixed $plantId Plant ID atau null untuk all
     * @param int|null $userId User ID
     * @return string|null Plant code atau null
     */
    protected function resolvePlantFilter(mixed $plantId, ?int $userId = null): ?string
    {
        return PlantContextService::resolvePlantId($plantId, $userId);
    }

    /**
     * Add plant filter binding ke array
     *
     * @param array $bindings Existing bindings
     * @param mixed $plantId Plant ID
     * @param int|null $userId User ID
     * @return array Updated bindings
     */
    protected function addPlantBinding(array $bindings, mixed $plantId, ?int $userId = null): array
    {
        $resolvedCode = PlantContextService::resolvePlantId($plantId, $userId);

        if ($resolvedCode) {
            $bindings[] = $resolvedCode;
        }

        return $bindings;
    }

    /**
     * Check jika plant filter aktif (bukan "all plants")
     *
     * @param mixed $plantId
     * @return bool
     */
    protected function isPlantFiltered(mixed $plantId): bool
    {
        return $plantId !== null
            && $plantId !== ''
            && $plantId !== 0
            && $plantId !== '0'
            && $plantId !== 'all';
    }

    /**
     * Get user accessible plants
     *
     * @param int $userId
     * @return array
     */
    protected function getUserAccessiblePlants(int $userId): array
    {
        return PlantContextService::getUserPlants($userId);
    }

    /**
     * Validate plant access untuk user
     *
     * @param int $userId
     * @param string $plantCode
     * @return bool
     */
    protected function validatePlantAccess(int $userId, string $plantCode): bool
    {
        return PlantContextService::userHasAccessToPlant($userId, $plantCode);
    }
}