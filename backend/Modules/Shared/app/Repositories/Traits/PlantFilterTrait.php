<?php
declare(strict_types=1);
namespace Modules\Shared\Repositories\Traits;

use Modules\Shared\Services\Contracts\PlantContextServiceInterface;

trait PlantFilterTrait
{
    protected function getPlantContextService(): PlantContextServiceInterface
    {
        return resolve(PlantContextServiceInterface::class);
    }

    protected function buildPlantFilter(mixed $plantId, ?int $userId = null, string $defaultColumn = 'id_plant'): array
    {
        $resolvedCode = $this->getPlantContextService()->resolvePlantId($plantId, $userId);

        return [
            'sql' => $resolvedCode ? "{$defaultColumn} = ?" : '1=1',
            'bindings' => $resolvedCode ? [$resolvedCode] : [],
            'plant_code' => $resolvedCode,
        ];
    }

    protected function buildTablePlantFilter(string $tableAlias, mixed $plantId, ?int $userId = null): array
    {
        $resolvedCode = $this->getPlantContextService()->resolvePlantId($plantId, $userId);
        $column = "{$tableAlias}.id_plant";

        return [
            'sql' => $resolvedCode ? "{$column} = ?" : '1=1',
            'bindings' => $resolvedCode ? [$resolvedCode] : [],
            'plant_code' => $resolvedCode,
        ];
    }

    protected function resolvePlantFilter(mixed $plantId, ?int $userId = null): ?string
    {
        return $this->getPlantContextService()->resolvePlantId($plantId, $userId);
    }

    protected function addPlantBinding(array $bindings, mixed $plantId, ?int $userId = null): array
    {
        $resolvedCode = $this->getPlantContextService()->resolvePlantId($plantId, $userId);

        if ($resolvedCode) {
            $bindings[] = $resolvedCode;
        }

        return $bindings;
    }

    protected function isPlantFiltered(mixed $plantId): bool
    {
        return $plantId !== null
            && $plantId !== ''
            && $plantId !== 0
            && $plantId !== '0'
            && $plantId !== 'all';
    }

    protected function getUserAccessiblePlants(int $userId): array
    {
        return $this->getPlantContextService()->getUserPlants($userId);
    }

    protected function validatePlantAccess(int $userId, string $plantCode): bool
    {
        return $this->getPlantContextService()->userHasAccessToPlant($userId, $plantCode);
    }
}