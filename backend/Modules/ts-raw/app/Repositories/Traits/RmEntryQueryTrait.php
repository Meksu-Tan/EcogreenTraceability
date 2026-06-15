<?php declare(strict_types=1);

namespace Modules\TsRaw\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Material\Models\Material;
use Modules\Plant\Models\Plant;
use Exception;
use Modules\Shared\Traits\TransactionLoggerTrait;
use Modules\Shared\Services\TraceNumberGeneratorService;

trait RmEntryQueryTrait
{
    use TransactionLoggerTrait;

    public function getRmList($plantId, int $page = 1, int $perPage = 5): array
    {
        $resolvedPlant = $this->resolvePlantCode($plantId);
        $allPlants = empty($resolvedPlant);

        $idTankStorageIds = $this->getStorageSlocIds($resolvedPlant, $allPlants);
        $bhQuery = $this->buildBhSubquery($resolvedPlant, $allPlants, $idTankStorageIds);
        
        $query = DB::connection('eudr_ts')->table($bhQuery, 'bh')
            ->selectRaw("
                bh.id_balance_head, bh.id_material, bh.id_sloc AS id_tank,
                NULL AS id_tank_tail_raw, bh.status,
                CAST(bh.trace_no AS CHAR) AS trace_no,
                bh.qty, bh.created_by, bh.created_at,
                CONCAT(m.code, ' :: ', m.description) AS material,
                bh.init_qty, MIN(sl.description) AS sloc_description,
                GROUP_CONCAT(DISTINCT sl.id_tank ORDER BY sl.id_tank ASC SEPARATOR ' & ') AS sloc_tank_number,
                bh.entry_date, f.material_document, f.po_so, f.id_trace_head,
                FORMAT(bs.supplier_qty,3) AS balance_supplier,
                bh.id_plant, p.code AS plant_code, p.description AS plant_description
            ")
            ->join('m_material as m', function($join) {
                $join->on('bh.id_material', '=', 'm.id_material')
                     ->where('m.type', '=', clone DB::raw("'{$this->typeMaterial}'"));
            })
            ->leftJoin('m_sloc as sl', function($join) {
                $join->on(function($q) {
                    $q->whereRaw("JSON_CONTAINS(bh.id_sloc, CAST(sl.id_sloc AS JSON))");
                })->where('sl.status', 1);
            })
            ->leftJoin(DB::raw("(SELECT f.id_balance_head, MAX(g.material_document) AS material_document, MAX(g.po_so) AS po_so, MAX(f.id_trace_head) AS id_trace_head FROM t_trace_header f LEFT JOIN t_material_document g ON f.id_trace_head = g.id_trace_head WHERE f.status = 1 GROUP BY f.id_balance_head) as f"), 'f.id_balance_head', '=', 'bh.id_balance_head')
            ->leftJoin(DB::raw("(SELECT id_balance_head, SUM(init_qty) AS supplier_qty FROM t_balance_detail WHERE status = 1 GROUP BY id_balance_head) as bs"), 'bs.id_balance_head', '=', 'bh.id_balance_head')
            ->leftJoin('m_plant as p', DB::raw('bh.id_plant'), '=', DB::raw('p.code_3 COLLATE utf8mb4_unicode_ci'))
            ->groupBy('bh.id_balance_head')
            ->orderByDesc('bh.id_balance_head')
            ->limit(100);

        $results = $query->get()->toArray();
        if (empty($results)) return ['data' => [], 'total' => 0];

        $traceNos = array_values(array_unique(array_column($results, 'trace_no')));
        $supplierMap = $this->fetchSupplierDetails($traceNos);
        $traceStatusMap = $this->fetchTraceStatus($traceNos);
        
        $mapped = $this->mapRmListResults($results, $supplierMap, $traceStatusMap);
        
        $collection = collect($mapped);
        $total = $collection->count();
        $sliced = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        return ['data' => $sliced, 'total' => $total];
    }

    private function getStorageSlocIds($resolvedPlant, bool $allPlants): array
    {
        $slocQuery = DB::connection('eudr_ts')->table('m_sloc')
            ->where('status', 1)
            ->where(function($q) {
                $q->where('description', 'LIKE', '%STORAGE%')
                  ->orWhere('code_2', 'LIKE', '%STORAGE%')
                  ->orWhere('code_3', 'LIKE', '%STORAGE%');
            });

        if (!$allPlants && $resolvedPlant) {
            $slocQuery->where('id_plant', $resolvedPlant);
        }

        $ids = $slocQuery->pluck('id_sloc')->toArray();
        return empty($ids) ? [0] : $ids;
    }

    private function buildBhSubquery($resolvedPlant, bool $allPlants, array $idTankStorageIds)
    {
        return DB::connection('eudr_ts')->table('t_balance_header')
            ->select('id_balance_head', 'id_material', 'id_sloc', 'status', 'trace_no', 'qty', 'init_qty', 'created_by', 'created_at', 'entry_date', 'id_plant')
            ->where('status', 1)
            ->whereRaw("SUBSTRING(trace_no,1,1) = '1'")
            ->whereRaw("SUBSTRING(trace_no,8,3) = '000'")
            ->where(function($q) use ($resolvedPlant, $allPlants) {
                if (!$allPlants) $q->where('id_plant', $resolvedPlant);
            })
            ->where(function($q) use ($idTankStorageIds) {
                $q->where(function($q2) use ($idTankStorageIds) {
                    foreach ($idTankStorageIds as $slocId) {
                        $q2->orWhereRaw("JSON_CONTAINS(id_sloc, ?)", [json_encode($slocId)]);
                    }
                });
            })
            ->orderByDesc('id_balance_head');
    }

    private function fetchSupplierDetails(array $traceNos): array
    {
        $details = DB::connection('eudr_ts')->table('t_balance_detail as bd')
            ->join('t_balance_header as bh', 'bd.id_balance_head', '=', 'bh.id_balance_head')
            ->leftJoin('m_supplier as sup', 'bd.id_supplier', '=', 'sup.id_supplier')
            ->select('bh.trace_no', 'bd.batch_sap', 'bd.init_qty', 'bd.out_qty', 'bd.id_balance_tail', 'sup.code', 'sup.description')
            ->where('bd.status', 1)->whereIn('bh.trace_no', $traceNos)
            ->get();

        $map = [];
        foreach ($details as $sd) {
            $map[$sd->trace_no]['supplier'][] = sprintf('%s / %s / Qty: %s MT / %s', $sd->code ?? 'N/A', $sd->description ?? 'Unknown', number_format($sd->init_qty, 3), $sd->out_qty == 0 ? '-' : 'BATCH TRANSFERRED');
            $map[$sd->trace_no]['id_balance_detail'][] = $sd->id_balance_tail;
        }
        return $map;
    }

    private function fetchTraceStatus(array $traceNos): array
    {
        return DB::connection('eudr_ts')->table('t_trace_detail as td')
            ->join('t_trace_header as th', 'td.id_trace_head', '=', 'th.id_trace_head')
            ->join('t_balance_header as bh', 'th.id_balance_head', '=', 'bh.id_balance_head')
            ->where('td.status', 1)->whereIn('bh.trace_no', $traceNos)
            ->groupBy('bh.trace_no')
            ->selectRaw("bh.trace_no, IF(SUM(td.out_qty) > 0, 'USED', 'N/A') AS traced")
            ->pluck('traced', 'trace_no')->toArray();
    }

    private function mapRmListResults(array $results, array $supplierMap, array $traceStatusMap): array
    {
        return array_map(function($r) use ($supplierMap, $traceStatusMap) {
            $traceKey = $r->trace_no;
            $r->supplier = isset($supplierMap[$traceKey]) ? implode(' | ', $supplierMap[$traceKey]['supplier']) : 'N/A';
            $r->id_balance_detail = isset($supplierMap[$traceKey]) ? implode(',', $supplierMap[$traceKey]['id_balance_detail']) : '';
            $r->tf_number = !empty($r->sloc_tank_number) ? $r->sloc_description . ' | ' . $r->sloc_tank_number : $r->sloc_description;
            $r->traced = $traceStatusMap[$traceKey] ?? 'N/A';
            $r->qty = number_format((float)$r->qty, 3);
            $r->init_qty = number_format((float)$r->init_qty, 3);
            $r->plant_code = $r->plant_description ?? (string) $r->id_plant;
            return $r;
        }, $results);
    }

    public function getRmEntryById($id): ?object
    {
        $result = DB::connection('eudr_ts')->select(
            "SELECT bh.id_balance_head, bh.id_material, bh.id_sloc AS id_tank,
                    NULL AS id_tank_tail_raw, bh.status,
                    CAST(bh.trace_no AS CHAR) AS trace_no,
                    bh.qty, bh.created_by, bh.created_at,
                    CONCAT(m.code, ' :: ', m.description) AS material,
                    bh.init_qty, sl.description AS sloc_description,
                    bh.entry_date, bh.id_plant, p.code AS plant_code, p.description AS plant_description
               FROM t_balance_header bh
               INNER JOIN m_material m ON bh.id_material = m.id_material
               LEFT JOIN m_sloc sl ON JSON_CONTAINS(bh.id_sloc, CAST(sl.id_sloc AS JSON)) AND sl.status = 1
               LEFT JOIN m_plant p ON bh.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
              WHERE bh.id_balance_head = ? AND bh.status = 1",
            [$id]
        );

        return $result[0] ?? null;
    }

    public function getNewNumber($plantId): ?string
    {
        $resolvedPlantId = $this->resolvePlantCode($plantId);
        $warehouse = '000';
        $section = '1';
        $tracePlantCode = '00';

        $result = DB::connection('eudr_ts')->select(
            'SELECT MAX(CAST(RIGHT(trace_no, 2) AS UNSIGNED)) as max_seq
               FROM t_balance_header
              WHERE SUBSTRING(trace_no,1,1) = ?
                AND SUBSTRING(trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
                AND SUBSTRING(trace_no,8,3) = ?
                AND SUBSTRING(trace_no,11,2) = ?
                AND status = 1',
            [$section, $warehouse, $tracePlantCode]
        );

        $maxSeq = $result[0]->max_seq ?? 0;
        $newSeq = $maxSeq + 1;

        return $this->buildTraceNo($section, date("ymd"), $warehouse, $tracePlantCode, $newSeq);
    }

    public function getTanks($plantId): array
    {
        $resolvedPlant = $this->resolvePlantCode($plantId);
        return app(\Modules\Shared\Repositories\TankQueryRepository::class)
            ->getActiveTanksByKeywords(['STORAGE', 'FEED'], $resolvedPlant)
            ->toArray();
    }

    public function getTankDetails($tankId, $plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);

        $query = "SELECT a.id_sloc AS id_sloc_tail, a.id_sloc, a.description AS tf_number, status,
                         b.qty_available, b.id_balance_head
                    FROM m_sloc a
                    LEFT JOIN (
                        SELECT id_sloc_tail, SUM(qty) AS qty_available, MAX(id_balance_head) AS id_balance_head
                          FROM t_balance_header
                         WHERE status = 1
                           AND id_sloc_tail IS NOT NULL
                           AND id_sloc_tail != ''
                           AND id_sloc_tail != '[]'
                         GROUP BY id_sloc_tail
                    ) b ON b.id_sloc_tail = a.id_sloc
                   WHERE a.status = 1
                     AND a.id_sloc = ?
                   ORDER BY a.description";

        return DB::connection('eudr_ts')->select($query, [$tankId]);
    }

    public function getMaterials(): array
    {
        return DB::connection('eudr_ts')->select(
            "SELECT id_material, code, description, type
               FROM m_material
              WHERE status = '1' AND type = 'RM'
              ORDER BY code"
        );
    }

    public function getLockStatus(string $entryDate): bool
    {
        $lock = DB::connection('eudr_ts')->table('m_plant')
            ->where('status', 1)
            ->whereNotNull('id_sloc')
            ->first();
        return false;
    }

    public function resolvePlantCode($plantId)
    {
        if ($plantId) {
            $plant = Plant::find($plantId);
            if ($plant && $plant->code_3) {
                return $plant->code_3;
            }
        }
        return $plantId;
    }

    public function buildTraceNo(string $section, string $entryDate, string $warehouse, string $plantCode, int $sequence): string
    {
        return TraceNumberGeneratorService::format($section, $entryDate, $warehouse, $plantCode, $sequence);
    }

    public function traceNoToInt(string $traceNo): int
    {
        $digits = preg_replace('/\D/', '', $traceNo);
        return (int) ($digits !== '' ? $digits : 0);
    }


}
