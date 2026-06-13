<?php

declare(strict_types=1);

namespace Modules\Shared\Services\Contracts;

interface PlantContextServiceInterface
{
    /**
     * Resolve plant ID dari request/input
     *
     * @param mixed $plantId Plant ID (bisa int, string, atau null)
     * @param int|null $userId User ID untuk cek plant access
     * @return string|null Resolved plant code_3 atau null untuk "all plants"
     */
    public static function resolvePlantId(mixed $plantId, ?int $userId = null): ?string;

    /**
     * Resolve plant by ID
     */
    public static function resolveById(int $plantId, ?int $userId = null): ?string;

    /**
     * Resolve plant by code_3
     */
    public static function resolveByCode(string $code, ?int $userId = null): ?string;

    /**
     * Get plants yang accessible oleh user
     */
    public static function getUserPlants(int $userId): array;

    /**
     * Get all active plants (for admin or all plants selector)
     */
    public static function getAllPlants(): array;

    /**
     * Check jika user punya akses ke plant
     */
    public static function userHasAccessToPlant(int $userId, string $code_3): bool;

    /**
     * Get default plant untuk user
     */
    public static function getDefaultPlant(int $userId): ?array;

    /**
     * Get plant info by ID
     */
    public static function getPlantById(int $id): ?array;

    /**
     * Get plant info by code_3
     */
    public static function getPlantByCode(string $code_3): ?array;

    /**
     * Build plant filter SQL clause
     *
     * @param string $tableAlias Table alias (e.g., 'bh', 'a', 't')
     * @param string|null $plantCode Resolved plant code (null = all plants)
     * @return array ['sql' => string, 'bindings' => array]
     */
    public static function buildPlantFilter(string $tableAlias, ?string $plantCode): array;

    /**
     * Build plant filter dengan multiple tables
     *
     * @param array $tableColumns ['alias.column' => 'binding_param']
     * @param string|null $plantCode
     * @return array ['sql' => string, 'bindings' => array]
     */
    public static function buildMultiPlantFilter(array $tableColumns, ?string $plantCode): array;
}
