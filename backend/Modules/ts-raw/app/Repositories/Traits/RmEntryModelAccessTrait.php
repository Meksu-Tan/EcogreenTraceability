<?php

declare(strict_types=1);

namespace Modules\TsRaw\Repositories\Traits;

// Cross-module model access — acceptable coupling for complex JOIN queries.
use Modules\Material\Models\Material;
use Modules\Plant\Models\Plant;
use Modules\Supplier\Models\Supplier;
use Modules\TsRaw\Models\BalanceHeader;

/**
 * RmEntryModelAccessTrait
 *
 * Centralized model access to avoid direct model queries in Service layer.
 * Implements repository methods for BalanceHeader, Plant, Material, Supplier.
 */
trait RmEntryModelAccessTrait
{
    /**
     * Find BalanceHeader by ID
     */
    public function findBalanceHeaderById(int $id): ?object
    {
        return BalanceHeader::find($id);
    }

    /**
     * Find Plant by ID
     */
    public function findPlantById(int|string $plantId): ?object
    {
        return Plant::find($plantId);
    }

    /**
     * Get active materials for search/dropdown
     */
    public function getActiveMaterialsSearch(): array
    {
        $materials = Material::where('status', 1)->get();

        $dupCodes = $materials->groupBy('code')->filter(fn ($g) => $g->count() > 1)->keys();

        return $materials->sortBy('description')->map(function ($item) use ($dupCodes) {
            $text = mb_strtoupper($item->description).' ('.$item->code;

            if ($dupCodes->contains($item->code)) {
                $text .= ' - '.($item->qtf_rundown ?? '');
            }

            $text .= ')';

            return ['id' => $item->id_material, 'text' => $text];
        })->values()->toArray();
    }

    /**
     * Search suppliers by description
     */
    public function getSuppliersSearch(string $search): array
    {
        $sanitizedSearch = str_replace(['%', '_'], ['\%', '\_'], $search);

        return Supplier::where('status', 1)
            ->where('description', 'like', '%'.$sanitizedSearch.'%')
            ->orderBy('description')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id_supplier,
                    'text' => $item->code.' :: '.$item->description,
                ];
            })
            ->toArray();
    }

    /**
     * Get source entries list for transfer
     */
    public function getSourceEntriesList($plantId): array
    {
        return BalanceHeader::active()
            ->rmEntry()
            ->where('qty', '>', 0)
            ->when($plantId, function ($query, $plantId) {
                return $query->where('id_plant', $plantId);
            })
            ->with(['material', 'sloc'])
            ->orderBy('id_balance_head', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id_balance_head,
                    'trace_no' => $item->trace_no,
                    'material' => $item->material->description ?? 'N/A',
                    'tank' => $item->sloc->description ?? 'N/A',
                    'qty' => (float) $item->qty,
                ];
            })
            ->toArray();
    }
}
