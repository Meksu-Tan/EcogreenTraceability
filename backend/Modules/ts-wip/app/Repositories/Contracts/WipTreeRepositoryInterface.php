<?php

declare(strict_types=1);

namespace Modules\TsWip\Repositories\Contracts;

use Illuminate\Support\Collection;

interface WipTreeRepositoryInterface
{
    /**
     * Get active WIP sections for a plant.
     */
    public function getActiveSections(?string $idPlant): Collection;

    /**
     * Get active process steps for given section IDs.
     */
    public function getActiveStepsBySectionIds(array $sectionIds): Collection;

    /**
     * Fetch the latest traces for given trace prefixes and type.
     */
    public function fetchLatestTraces(array $prefixes, string $tracePrefix, ?string $idPlant): array;
}
