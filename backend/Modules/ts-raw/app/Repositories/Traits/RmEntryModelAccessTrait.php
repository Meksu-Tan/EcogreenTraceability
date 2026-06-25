<?php
declare(strict_types=1);
namespace Modules\TsRaw\Repositories\Traits;

// Cross-module model access — acceptable coupling for complex JOIN queries.
use Modules\TsRaw\Models\BalanceHeader;
use Modules\Material\Models\Material;
use Modules\Supplier\Models\Supplier;
use Modules\Plant\Models\Plant;

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
        return Material::where('status', 1)
            ->orderBy('description')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id_material,
                    'text' => $item->material_code . ' :: ' . $item->description
                ];
            })
            ->toArray();
    }

    /**
     * Search suppliers by description
     */
    public function getSuppliersSearch(string $search): array
    {
        $sanitizedSearch = str_replace(['%', '_'], ['\%', '\_'], $search);

        return Supplier::where('status', 1)
            ->where('description', 'like', '%' . $sanitizedSearch . '%')
            ->orderBy('description')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id_supplier,
                    'text' => $item->code . ' :: ' . $item->description
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
