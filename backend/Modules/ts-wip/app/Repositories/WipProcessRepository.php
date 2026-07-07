<?php

declare(strict_types=1);

namespace Modules\TsWip\Repositories;

use Modules\TsWip\Models\WipProcessStep;
use Modules\TsWip\Models\WipSection;
use Modules\TsWip\Repositories\Contracts\WipProcessRepositoryInterface;

class WipProcessRepository implements WipProcessRepositoryInterface
{
    public function sections(?string $plantId): array
    {
        return WipSection::query()
            ->with(['steps' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->when($plantId && $plantId !== '0', function ($query) use ($plantId): void {
                $query->where(function ($inner) use ($plantId): void {
                    $inner->whereNull('plant_id')->orWhere('plant_id', $plantId);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    public function createSection(array $data): array
    {
        return WipSection::query()->create($data)->fresh()->toArray();
    }

    public function updateSection(int $id, array $data): array
    {
        $section = WipSection::query()->findOrFail($id);
        $section->update($data);

        return $section->fresh()->toArray();
    }

    public function deleteSection(int $id): bool
    {
        return (bool) WipSection::query()->whereKey($id)->delete();
    }

    public function deleteAllSections(?string $plantId): bool
    {
        $query = WipSection::query();
        if ($plantId) {
            $query->where('plant_id', $plantId);
        } else {
            $query->whereNull('plant_id');
        }
        $sectionIds = $query->pluck('id')->toArray();

        if (! empty($sectionIds)) {
            WipProcessStep::query()->whereIn('section_id', $sectionIds)->delete();
            $query->delete();
        }

        return true;
    }

    public function createStep(array $data): array
    {
        return WipProcessStep::query()->create($data)->fresh()->toArray();
    }

    public function updateStep(int $id, array $data): array
    {
        $step = WipProcessStep::query()->findOrFail($id);
        $step->update($data);

        return $step->fresh()->toArray();
    }

    public function deleteStep(int $id): bool
    {
        return (bool) WipProcessStep::query()->whereKey($id)->delete();
    }

    public function deleteAllSteps(int $sectionId): bool
    {
        return (bool) WipProcessStep::query()->where('section_id', $sectionId)->delete();
    }

    public function reorderSections(array $items): bool
    {
        // Ponytail: A simple loop is the laziest and easiest to maintain here since the array is small.
        foreach ($items as $item) {
            WipSection::query()->whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return true;
    }

    public function reorderSteps(array $items): bool
    {
        foreach ($items as $item) {
            WipProcessStep::query()->whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return true;
    }
}
