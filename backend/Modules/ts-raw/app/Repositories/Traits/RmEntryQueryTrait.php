<?php

declare(strict_types=1);

namespace Modules\TsRaw\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Repositories\TankQueryRepository;
use Modules\Shared\Services\Contracts\PlantContextServiceInterface;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\TraceNumberGeneratorService;
use Modules\Shared\Services\TraceNumberService;
use Modules\Shared\Traits\DbCompatTrait;
use Modules\Shared\Traits\TransactionLoggerTrait;

trait RmEntryQueryTrait
{
    use DbCompatTrait, TransactionLoggerTrait;

    public function getRmList($plantId, int $page = 1, int $perPage = 5): array
    {
        $resolvedPlant = $this->resolvePlantCode($plantId);
        $allPlants = empty($resolvedPlant);

        $bhQuery = $this->buildBhSubquery($resolvedPlant, $allPlants, []);

        $total = DB::connection('eudr_ts')->table($bhQuery, 'bh')->count();
        if ($total == 0) {
            return ['data' => [], 'total' => 0];
        }

        $idsResult = DB::connection('eudr_ts')->table($bhQuery, 'bh')
            ->select('id_balance_head')
            ->orderByDesc('id_balance_head')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->pluck('id_balance_head')
            ->toArray();

        $idCsv = implode(',', array_map('intval', $idsResult));

        $groupConcatSloc = $this->dbGroupConcat('DISTINCT sl.tf_number', ', ', false, 'sl.tf_number ASC');
        $balanceFmt = $this->dbNumberFormat('bs.supplier_qty', 3);
        $query = DB::connection('eudr_ts')->table($bhQuery, 'bh')
            ->selectRaw("
                bh.id_balance_head, bh.id_material, bh.id_sloc AS tf_number,
                f.id_sloc_json AS id_sloc_tail_raw, bh.status,
                CAST(bh.trace_no AS TEXT) AS trace_no,
                {$this->dbNumberFormat('bh.qty', 3)} AS qty, bh.created_by, bh.created_at,
                CONCAT(m.code, ' :: ', m.description) AS material,
                {$this->dbNumberFormat('bh.init_qty', 3)} AS init_qty, MIN(parent_sl.description) AS sloc_description,
                {$groupConcatSloc} AS sloc_tank_number,
                bh.entry_date, f.material_document, f.po_so, f.id_trace_head,
                {$balanceFmt} AS balance_supplier,
                bh.id_plant, p.code AS plant_code, p.description AS plant_description
            ")
            ->join('m_material as m', function ($join) {
                $join->on('bh.id_material', '=', 'm.id_material')
                    ->where('m.type', '=', clone DB::raw("'{$this->typeMaterial}'"));
            })
            ->leftJoin('m_sloc as parent_sl', function ($join) {
                $join->on(DB::raw('bh.id_sloc'), '=', DB::raw('parent_sl.id_sloc'))
                    ->where('parent_sl.status', 1);
            })
            ->leftJoin(DB::raw("(SELECT f.id_balance_head, MAX(g.material_document) AS material_document, MAX(g.po_so) AS po_so, MAX(f.id_trace_head) AS id_trace_head, CAST(MAX(CAST(f.id_sloc AS TEXT)) AS jsonb) AS id_sloc_json FROM t_trace_header f LEFT JOIN t_material_document g ON f.id_trace_head = g.id_trace_head WHERE f.status = 1 AND f.id_balance_head IN ({$idCsv}) GROUP BY f.id_balance_head) as f"), 'f.id_balance_head', '=', 'bh.id_balance_head')
            ->leftJoin('m_sloc as sl', function ($join) {
                $slocClause = $this->dbSlocJsonClause('f.id_sloc_json', 'sl.id_sloc');
                $join->on(function ($q) use ($slocClause) {
                    $q->whereRaw($slocClause)
                      ->orWhere(function ($sub) {
                          $sub->whereNull('f.id_sloc_json')
                              ->orWhere('f.id_sloc_json', '[]')
                              ->whereColumn('bh.id_sloc', 'sl.id_sloc');
                      });
                })->where('sl.status', 1);
            })
            ->leftJoin(DB::raw("(SELECT id_balance_head, SUM(init_qty) AS supplier_qty FROM t_balance_detail WHERE status = 1 AND id_balance_head IN ({$idCsv}) GROUP BY id_balance_head) as bs"), 'bs.id_balance_head', '=', 'bh.id_balance_head')
            ->leftJoin('m_plant as p', DB::raw('bh.id_plant'), '=', DB::raw('p.code_3'))
            ->whereIn('bh.id_balance_head', $idsResult)
            ->groupBy('bh.id_balance_head', 'bh.id_material', 'bh.id_sloc', 'f.id_sloc_json', 'bh.status', 'bh.trace_no', 'bh.qty', 'bh.created_by', 'bh.created_at', 'bh.init_qty', 'bh.entry_date', 'bh.id_plant', 'm.code', 'm.description', 'f.material_document', 'f.po_so', 'f.id_trace_head', 'bs.supplier_qty', 'p.code', 'p.description')
            ->orderByDesc('bh.id_balance_head');

        $results = $query->get()->toArray();
        if (empty($results)) {
            return ['data' => [], 'total' => $total];
        }

        $traceNos = array_values(array_unique(array_column($results, 'trace_no')));
        $supplierMap = $this->fetchSupplierDetails($traceNos);
        $traceStatusMap = $this->fetchTraceStatus($traceNos);

        $mapped = $this->mapRmListResults($results, $supplierMap, $traceStatusMap);

        return ['data' => $mapped, 'total' => $total];
    }

    private function getStorageSlocIds($resolvedPlant, bool $allPlants): array
    {
        $slocQuery = DB::connection('eudr_ts')->table('m_sloc')
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('description', 'LIKE', '%STORAGE%')
                    ->orWhere('code_2', 'LIKE', '%STORAGE%')
                    ->orWhere('code_3', 'LIKE', '%STORAGE%');
            });

        if (! $allPlants && $resolvedPlant) {
            $slocQuery->where('id_plant', $resolvedPlant);
        }

        $ids = $slocQuery->pluck('id_sloc')->toArray();

        return empty($ids) ? [0] : $ids;
    }

    private function buildBhSubquery($resolvedPlant, bool $allPlants, array $idSlocStorageIds)
    {
        return DB::connection('eudr_ts')->table('t_balance_header')
            ->select('id_balance_head', 'id_material', 'id_sloc', 'status', 'trace_no', 'qty', 'init_qty', 'created_by', 'created_at', 'entry_date', 'id_plant')
            ->where('status', 1)
            ->whereRaw("SUBSTRING(CAST(trace_no AS TEXT),1,1) = '1'")
            ->where(function ($q) use ($resolvedPlant, $allPlants) {
                if (! $allPlants) {
                    $q->where('id_plant', $resolvedPlant);
                }
            })
            ->orderByDesc('id_balance_head');
    }

    private function fetchSupplierDetails(array $traceNos): array
    {
        $details = DB::connection('eudr_ts')->table('t_balance_detail as bd')
            ->join('t_balance_header as bh', 'bd.id_balance_head', '=', 'bh.id_balance_head')
            ->leftJoin('m_supplier as sup', 'bd.id_supplier', '=', 'sup.id_supplier')
            ->leftJoin('m_manufacturer as mf', 'bd.id_manufacturer', '=', 'mf.id_manufacturer')
            ->select('bh.trace_no', 'bd.batch_sap', 'bd.init_qty', 'bd.out_qty', 'bd.id_balance_tail', 'sup.code', 'sup.description', 'mf.description as manufacturer_name')
            ->where('bd.status', 1)->whereIn('bh.trace_no', $traceNos)
            ->get();

        $map = [];
        foreach ($details as $sd) {
            $map[$sd->trace_no]['supplier'][] = sprintf('%s :: %s / %s / Qty : %s MT / %s', $sd->code ?? 'N/A', $sd->description ?? 'Unknown', $sd->batch_sap ?? '', number_format((float) $sd->init_qty, 3), $sd->out_qty == 0 ? '-' : 'BATCH TRANSFERRED');
            $map[$sd->trace_no]['id_balance_detail'][] = $sd->id_balance_tail;
            if (! empty($sd->manufacturer_name)) {
                $map[$sd->trace_no]['manufacturer'][] = $sd->manufacturer_name;
            }
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
            ->selectRaw("bh.trace_no, CASE WHEN COALESCE(SUM(td.out_qty), 0) > 0 THEN 'USED' ELSE 'N/A' END AS traced")
            ->pluck('traced', 'trace_no')->toArray();
    }

    private function mapRmListResults(array $results, array $supplierMap, array $traceStatusMap): array
    {
        return array_map(function ($r) use ($supplierMap, $traceStatusMap) {
            $traceKey = $r->trace_no;
            $r->supplier = isset($supplierMap[$traceKey]) ? implode(' | ', $supplierMap[$traceKey]['supplier']) : 'N/A';
            $r->id_balance_detail = isset($supplierMap[$traceKey]) ? implode(',', $supplierMap[$traceKey]['id_balance_detail']) : '';
            $r->manufacturer_name = isset($supplierMap[$traceKey]['manufacturer']) ? implode(', ', array_unique($supplierMap[$traceKey]['manufacturer'])) : '-';
            $r->tf_number = ! empty($r->sloc_tank_number) ? $r->sloc_description.' | '.$r->sloc_tank_number : $r->sloc_description;
            $r->tank_name = $r->tf_number;
            $r->traced = $traceStatusMap[$traceKey] ?? 'N/A';
            $r->qty = number_format((float) $r->qty, 3, '.', '');
            $r->init_qty = number_format((float) $r->init_qty, 3, '.', '');
            $r->plant_code = $r->plant_description ?? (string) $r->id_plant;

            return $r;
        }, $results);
    }

    public function getRmEntryById($id): ?object
    {
        $slocJoin = 'LEFT JOIN m_sloc sl ON CAST(bh.id_sloc AS TEXT) = CAST(sl.id_sloc AS TEXT) AND sl.status = 1';

        $result = DB::connection('eudr_ts')->select(
            "SELECT bh.id_balance_head, bh.id_material, bh.id_sloc AS tf_number,
                    NULL AS id_sloc_tail_raw, bh.status,
                    CAST(bh.trace_no AS TEXT) AS trace_no,
                    bh.qty, bh.created_by, bh.created_at,
                    CONCAT(m.code, ' :: ', m.description) AS material,
                    bh.init_qty, sl.description AS sloc_description,
                    bh.entry_date, bh.id_plant, p.code AS plant_code, p.description AS plant_description
               FROM t_balance_header bh
               INNER JOIN m_material m ON bh.id_material = m.id_material
               {$slocJoin}
               LEFT JOIN m_plant p ON bh.id_plant = p.code_3
              WHERE bh.id_balance_head = ? AND bh.status = 1",
            [$id]
        );

        return $result[0] ?? null;
    }

    public function getNewNumber($plantId): ?string
    {
        $svc = app(TraceNumberService::class);
        $plantCode = $svc->resolvePlantCode($this->resolvePlantCode($plantId));

        return $svc->generate('1', date('ymd'), '000', $plantCode, 't_balance_header', 'trace_no');
    }

    public function getTanks($plantId): array
    {
        $resolvedPlant = $this->resolvePlantCode($plantId);
        $plantFilter = ($resolvedPlant && $resolvedPlant !== '0') ? $resolvedPlant : null;

        return app(TankQueryRepository::class)
            ->getActiveTanksByKeywords(['STORAGE'], $plantFilter)
            ->toArray();
    }

    public function getTankDetails($tankId, $plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);

        $query = "SELECT a.id_sloc AS id_sloc_tail, a.id_sloc, a.tf_number, a.description, status,
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
        return PeriodLockService::isLocked($entryDate);
    }

    public function resolvePlantCode($plantId)
    {
        return app(PlantContextServiceInterface::class)->resolvePlantId($plantId) ?: (string) $plantId;
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
