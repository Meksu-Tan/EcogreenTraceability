<?php

declare(strict_types=1);

namespace Modules\TsWip\Services\Contracts;

interface WipProcessServiceInterface
{
    public function sections(?string $plantId): array;

    public function createSection(array $data): array;

    public function updateSection(int $id, array $data): array;

    public function deleteSection(int $id): bool;

    public function deleteAllSections(?string $plantId): bool;

    public function createStep(array $data): array;

    public function updateStep(int $id, array $data): array;

    public function deleteStep(int $id): bool;

    public function deleteAllSteps(int $sectionId): bool;

    public function reorderSections(array $items): bool;

    public function reorderSteps(array $items): bool;
}
