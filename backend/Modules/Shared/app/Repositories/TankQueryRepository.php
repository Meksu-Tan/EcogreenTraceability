<?php declare(strict_types=1);

namespace Modules\Shared\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Traits\TankNameFormatterTrait;

class TankQueryRepository
{
    use TankNameFormatterTrait;

    protected string $connection = 'eudr_ts';

    /**
     * Get active tanks for rundown based on material and plant.
     */
    public function getActiveTanksRundown(?int $materialId, int $plantId, bool $excludePlant = true): Collection
    {
        if ($materialId === null) {
            $operator = $excludePlant ? '<>' : '=';
            $result = collect(DB::connection($this->connection)->select(
                "SELECT b.id_sloc AS id_tank, b.description AS tank, b.id_plant
                   FROM m_sloc b
                  WHERE b.status = 1
                    AND b.id_plant {$operator} ?
                  GROUP BY b.id_sloc
                  ORDER BY b.description ASC",
                [$plantId]
            ));
        } else {
            $result = collect(DB::connection($this->connection)->select(
                'SELECT b.id_sloc AS id_tank, b.description AS tank, b.id_plant
                   FROM m_material a
                   LEFT JOIN m_sloc b ON a.type = b.code_2 COLLATE utf8mb4_unicode_ci AND b.status = 1 AND b.id_plant = ?
                  WHERE a.status = 1
                    AND a.id_material = ?
                  GROUP BY b.id_sloc',
                [$plantId, $materialId]
            ));
        }

        return $result->map(function ($item) {
            $item->tank = $this->formatTankName($item->tank);
            return $item;
        });
    }

    /**
     * Get specific active sub-tanks based on sloc ID.
     */
    public function getActiveSpecificTanksRundown(int $sloc): Collection
    {
        $tank = DB::connection($this->connection)->select(
            'SELECT description, id_plant FROM m_sloc WHERE id_sloc = ?', 
            [$sloc]
        );

        if (empty($tank)) {
            return collect([]);
        }

        $formattedName = $this->formatTankName($tank[0]->description);
        $plantId = $tank[0]->id_plant;

        $results = DB::connection($this->connection)->select(
            'SELECT id_sloc AS id_sloc_tail, id_sloc AS id_tank_tail, id_tank AS tankNo, description
               FROM m_sloc
              WHERE status = 1
                AND description = ?
                AND id_plant = ?
              ORDER BY id_sloc ASC',
            [$formattedName, $plantId]
        );

        return collect($results)->map(function ($item) {
            $item->tankName = $item->description . ' (' . $item->tankNo . ')';
            return $item;
        });
    }

    /**
     * Get active tanks filtered by description keywords or code (e.g. STORAGE, FEED, WIP).
     */
    public function getActiveTanksByKeywords(array $keywords, ?string $plantId): Collection
    {
        $query = DB::connection($this->connection)->table('m_sloc')
            ->where('status', 1);

        if ($plantId !== null && $plantId !== '0' && $plantId !== '') {
            // If plantId is numeric but stored as string/integer, keep it flexible
            $query->where(function ($q) use ($plantId) {
                $q->where('id_plant', $plantId)
                  ->orWhere('id_plant', (int) $plantId);
            });
        }

        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $word) {
                $q->orWhere('description', 'LIKE', '%' . $word . '%')
                  ->orWhere('code_2', 'LIKE', '%' . $word . '%')
                  ->orWhere('code_3', 'LIKE', '%' . $word . '%');
            }
        });

        $results = $query->orderBy('description', 'asc')->get();

        return $results->map(function ($item) {
            $item->tank = $this->formatTankName($item->description);
            // Standardize fields for backward compatibility
            $item->id_tank = $item->id_sloc;
            $item->tank_number = $item->id_tank;
            return $item;
        });
    }
}
