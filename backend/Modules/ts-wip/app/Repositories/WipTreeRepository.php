<?php

declare(strict_types=1);

namespace Modules\TsWip\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\TsWip\Repositories\Contracts\WipTreeRepositoryInterface;

class WipTreeRepository implements WipTreeRepositoryInterface
{
    public function getActiveSections(?string $idPlant): Collection
    {
        return DB::connection('eudr_ts')->table('m_wip_section')
            ->where('status', 1)
            ->where(function ($query) use ($idPlant): void {
                $query->whereNull('plant_id');
                if ($idPlant && $idPlant !== '0') {
                    $query->orWhere('plant_id', $idPlant);
                }
            })
            ->orderBy('sort_order')
            ->get();
    }

    public function getActiveStepsBySectionIds(array $sectionIds): Collection
    {
        return DB::connection('eudr_ts')->table('m_wip_process_step')
            ->whereIn('section_id', $sectionIds)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get();
    }

    public function fetchLatestTraces(array $prefixes, string $tracePrefix, ?string $idPlant): array
    {
        if (empty($prefixes)) {
            return [];
        }

        $placeholders = [];
        $bindings = [];

        foreach ($prefixes as $p) {
            $padded = str_pad((string) $p, 3, '0', STR_PAD_LEFT);
            $placeholders[] = '?';
            $bindings[] = $padded;
        }

        $where = implode(',', $placeholders);
        $plantFilter = ($idPlant && $idPlant !== '0') ? 'AND a.id_plant = ?' : '';

        if ($plantFilter) {
            $bindings[] = $idPlant;
        }

        $sql = DB::connection('eudr_ts')->getDriverName() === 'sqlite'
            ? "
                SELECT wh, trace_no, entry_date, curr_qtf
                  FROM (
                    SELECT SUBSTRING(CAST(a.to_trace_no AS TEXT), 8, 3) AS wh,
                           CAST(a.to_trace_no AS TEXT) AS trace_no,
                           a.entry_date,
                           a.curr_qtf,
                           ROW_NUMBER() OVER (
                               PARTITION BY SUBSTRING(CAST(a.to_trace_no AS TEXT), 8, 3)
                               ORDER BY a.id_trace_head DESC
                           ) AS rn
                      FROM t_trace_header a
                     WHERE SUBSTRING(CAST(a.to_trace_no AS TEXT), 1, 1) = ?
                       AND LENGTH(CAST(a.to_trace_no AS TEXT)) >= 14
                       AND SUBSTRING(CAST(a.to_trace_no AS TEXT), 8, 3) IN ({$where})
                       AND a.status = 1
                       {$plantFilter}
                  ) latest
                 WHERE rn = 1
            "
            : "
                SELECT DISTINCT ON (wh)
                       SUBSTRING(CAST(a.to_trace_no AS TEXT), 8, 3) AS wh,
                       CAST(a.to_trace_no AS TEXT) AS trace_no,
                       a.entry_date,
                       a.curr_qtf
                  FROM t_trace_header a
                 WHERE SUBSTRING(CAST(a.to_trace_no AS TEXT), 1, 1) = ?
                   AND CHAR_LENGTH(CAST(a.to_trace_no AS TEXT)) >= 14
                   AND SUBSTRING(CAST(a.to_trace_no AS TEXT), 8, 3) IN ({$where})
                   AND a.status = 1
                   {$plantFilter}
                 ORDER BY wh, a.id_trace_head DESC
            ";

        array_unshift($bindings, $tracePrefix);

        $rows = DB::connection('eudr_ts')->select($sql, $bindings);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->wh] = [
                'traceNo' => $row->trace_no,
                'entryDate' => $row->entry_date,
                'qty' => (float) ($row->curr_qtf ?? 0),
            ];
        }

        return $map;
    }
}
