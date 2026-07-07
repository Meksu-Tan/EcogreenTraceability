<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Traits\DbCompatTrait;
use Modules\Shared\Traits\TankNameFormatterTrait;

class TankQueryRepository
{
    use DbCompatTrait, TankNameFormatterTrait;

    protected string $connection = 'eudr_ts';

    /**
     * Get active tanks for rundown based on material and plant.
     */
    public function getActiveTanksRundown(?int $materialId, int $plantId, bool $excludePlant = true): Collection
    {
        $plants = $this->loadPlantAbbreviations();

        if ($materialId === null) {
            $wherePlant = $excludePlant ? 'AND id_plant <> ?' : 'AND id_plant = ?';
            $bindings = [(string) $plantId];

            $result = collect(DB::connection($this->connection)->select(
                "SELECT MIN(id_sloc) AS tf_number,
                        COALESCE(MAX(NULLIF(code_3,'')), '') AS code_3,
                        COALESCE(MIN(NULLIF(description,'')), MIN(code_3)) AS tank,
                        id_plant
                   FROM m_sloc
                  WHERE status = 1
                    {$wherePlant}
                  GROUP BY COALESCE(NULLIF(code_3,''), description), id_plant
                  ORDER BY COALESCE(MIN(NULLIF(description,'')), MIN(code_3)) ASC",
                $bindings
            ));

            return $result->map(function ($item) use ($plants) {
                $code3 = strtoupper($item->code_3 ?? '');
                $abbr = $plants[$item->id_plant ?? ''] ?? '';
                if (! empty($code3)) {
                    $label = ($code3 === 'PRD') ? 'PRODUCT' : $code3;
                    $item->tank = trim($label.($abbr ? ' '.$abbr : ''));
                } else {
                    $item->tank = $this->formatTankName($item->tank) ?: $item->tank;
                }
                $item->id_sloc = $item->tf_number;

                return $item;
            });
        }

        $wherePlant = $excludePlant ? 'AND b.id_plant <> ?' : 'AND b.id_plant = ?';
        $bindings = [$plantId, $materialId];

        $result = collect(DB::connection($this->connection)->select(
            "SELECT MIN(b.id_sloc) AS tf_number,
                    COALESCE(MAX(NULLIF(b.code_3,'')), '') AS code_3,
                    COALESCE(MIN(NULLIF(b.description,'')), MIN(b.code_3)) AS tank,
                    b.id_plant
               FROM m_material a
               LEFT JOIN m_sloc b ON 
                 (b.code_3 = a.type OR (UPPER(a.type) = 'RM' AND UPPER(b.code_3) IN ('FEED', 'STORAGE'))) 
                 AND b.status = 1 {$wherePlant}
              WHERE a.status = 1
                AND a.id_material = ?
              GROUP BY COALESCE(NULLIF(b.code_3,''), b.description), b.id_plant",
            $bindings
        ));

        return $result->map(function ($item) use ($plants) {
            $code3 = strtoupper($item->code_3 ?? '');
            $abbr = $plants[$item->id_plant ?? ''] ?? '';
            if (! empty($code3)) {
                $label = ($code3 === 'PRD') ? 'PRODUCT' : $code3;
                $item->tank = trim($label.($abbr ? ' '.$abbr : ''));
            } else {
                $item->tank = $this->formatTankName($item->tank) ?: $item->tank;
            }
            $item->id_sloc = $item->tf_number;

            return $item;
        });
    }

    /**
     * Get specific active sub-tanks based on sloc ID.
     * Groups siblings by code_3 + id_plant (type + plant) to include all
     * tanks of the same type regardless of whether description is populated.
     */
    public function getActiveSpecificTanksRundown($sloc): Collection
    {
        if (is_numeric($sloc)) {
            $tank = DB::connection($this->connection)->select(
                'SELECT description, id_plant, code_3 FROM m_sloc WHERE id_sloc = ?',
                [(int) $sloc]
            );
        } else {
            $tank = DB::connection($this->connection)->select(
                'SELECT description, id_plant, code_3 FROM m_sloc WHERE description = ? OR code_3 = ? LIMIT 1',
                [$sloc, $sloc]
            );
        }

        if (empty($tank)) {
            return collect([]);
        }

        $originalDescription = $tank[0]->description ?? '';
        $plantId = $tank[0]->id_plant;
        $code3 = $tank[0]->code_3 ?? '';

        // Prefer code_3 + id_plant lookup — includes siblings with empty description.
        // Fall back to description + id_plant only when code_3 is absent.
        if (! empty($code3)) {
            $results = DB::connection($this->connection)->select(
                'SELECT id_sloc, id_sloc AS id_sloc_tail, tf_number, description, code_3
                   FROM m_sloc
                  WHERE status = 1
                    AND code_3 = ?
                    AND id_plant = ?
                  ORDER BY id_sloc ASC',
                [$code3, $plantId]
            );
        } elseif (! empty($originalDescription)) {
            $results = DB::connection($this->connection)->select(
                'SELECT id_sloc, id_sloc AS id_sloc_tail, tf_number, description, code_3
                   FROM m_sloc
                  WHERE status = 1
                    AND description = ?
                    AND id_plant = ?
                  ORDER BY id_sloc ASC',
                [$originalDescription, $plantId]
            );
        } else {
            $results = DB::connection($this->connection)->select(
                'SELECT id_sloc, id_sloc AS id_sloc_tail, tf_number, description, code_3
                   FROM m_sloc
                  WHERE status = 1
                    AND id_sloc = ?',
                [is_numeric($sloc) ? (int) $sloc : 0]
            );
        }

        return collect($results)->map(function ($item) {
            $item->description = $this->formatTankName($item->description);
            $tfNumber = $item->tf_number ?? '';
            $item->tankName = $tfNumber !== ''
                ? ($item->description.' ('.$tfNumber.')')
                : $item->description.' ['.$item->id_sloc_tail.']';

            return $item;
        });
    }

    /**
     * Get active tanks filtered by description keywords or code (e.g. STORAGE, FEED, WIP).
     */
    public function getActiveTanksByKeywords(array $keywords, ?string $plantId): Collection
    {
        $query = DB::connection($this->connection)->table('m_sloc')
            ->where('status', 1)
            ->whereNotNull('id_plant')
            ->where('id_plant', '!=', '');

        if ($plantId !== null && $plantId !== '0' && $plantId !== '') {
            $query->where(function ($q) use ($plantId) {
                $q->where('id_plant', $plantId)
                    ->orWhere('id_plant', (int) $plantId);
            });
        }

        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $word) {
                $q->orWhere('description', 'ILIKE', '%'.$word.'%')
                    ->orWhere('code_2', 'ILIKE', '%'.$word.'%')
                    ->orWhere('code_3', 'ILIKE', '%'.$word.'%');
            }
        });

        // Group by code_3+plant so each sloc type appears once as parent, regardless
        // of whether description is populated. Prevents duplicate entries when both
        // empty-description and filled-description rows exist for the same type+plant.
        // Specific sub-tanks are retrieved separately via getActiveSpecificTanksRundown.
        $results = $query
            ->select(
                DB::raw('MIN(id_sloc) AS id_sloc'),
                DB::raw("COALESCE(MIN(NULLIF(description,'')), MIN(code_3)) AS description"),
                DB::raw('MIN(code_2) AS code_2'),
                DB::raw("COALESCE(MAX(NULLIF(code_3,'')), '') AS code_3"),
                'id_plant',
                DB::raw('MIN(tf_number) AS tf_number')
            )
            ->groupBy(DB::raw("COALESCE(NULLIF(code_3,''), description)"), 'id_plant')
            ->orderBy(DB::raw("COALESCE(MIN(NULLIF(description,'')), MIN(code_3))"), 'asc')
            ->get();

        $plants = $this->loadPlantAbbreviations();

        return $results->map(function ($item) use ($plants) {
            $desc = $item->description ?? '';
            if (empty($desc)) {
                $code3 = strtoupper($item->code_3 ?? '');
                $label = ($code3 === 'PRD') ? 'PRODUCT' : $code3;
                $abbr = $plants[$item->id_plant ?? ''] ?? '';
                $desc = trim($label.($abbr ? ' '.$abbr : ''));
                $item->description = $desc;
            }
            $item->tank = $this->formatTankName($desc) ?? $desc;
            $item->tf_number = $item->id_sloc;
            $item->tank_number = $item->tf_number;

            return $item;
        });
    }

    private function loadPlantAbbreviations(): array
    {
        try {
            return DB::connection()
                ->table('m_plant')
                ->select('code_3', 'code_2')
                ->get()
                ->pluck('code_2', 'code_3')
                ->toArray();
        } catch (\Exception) {
            return [];
        }
    }
}
