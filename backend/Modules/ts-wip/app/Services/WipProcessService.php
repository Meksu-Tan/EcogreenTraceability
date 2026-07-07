<?php

declare(strict_types=1);

namespace Modules\TsWip\Services;

use Modules\TsWip\Repositories\Contracts\WipProcessRepositoryInterface;
use Modules\TsWip\Services\Contracts\WipProcessServiceInterface;

class WipProcessService implements WipProcessServiceInterface
{
    public function __construct(
        private WipProcessRepositoryInterface $repository
    ) {}

    public function sections(?string $plantId): array
    {
        return $this->repository->sections($plantId);
    }

    public function createSection(array $data): array
    {
        return $this->repository->createSection($data);
    }

    public function updateSection(int $id, array $data): array
    {
        return $this->repository->updateSection($id, $data);
    }

    public function deleteSection(int $id): bool
    {
        return $this->repository->deleteSection($id);
    }

    public function deleteAllSections(?string $plantId): bool
    {
        return $this->repository->deleteAllSections($plantId);
    }

    public function createStep(array $data): array
    {
        return $this->repository->createStep($data);
    }

    public function updateStep(int $id, array $data): array
    {
        return $this->repository->updateStep($id, $data);
    }

    public function deleteStep(int $id): bool
    {
        return $this->repository->deleteStep($id);
    }

    public function deleteAllSteps(int $sectionId): bool
    {
        return $this->repository->deleteAllSteps($sectionId);
    }

    public function reorderSections(array $items): bool
    {
        return $this->repository->reorderSections($items);
    }

    public function reorderSteps(array $items): bool
    {
        return $this->repository->reorderSteps($items);
    }
}
