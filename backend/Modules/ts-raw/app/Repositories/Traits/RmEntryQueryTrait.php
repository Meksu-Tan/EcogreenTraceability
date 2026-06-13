<?php declare(strict_types=1);

namespace Modules\TsRaw\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Material\Models\Material;
use Modules\Plant\Models\Plant;
use Exception;

trait RmEntryQueryTrait
{
    public function getRmList($plantId, int $page = 1, int $perPage = 5): array
    {
        $resolvedPlant = $this->resolvePlantCode($plantId);
        $allPlants = $resolvedPlant === null || $resolvedPlant === '' || $resolvedPlant === 0 || $resolvedPlant === '0';

        // Get storage sloc IDs from m_sloc table
        $slocRows = DB::connection('eudr_ts')->select(
            "SELECT id_sloc, id_tank, description, id_plant FROM m_sloc WHERE status = 1 AND description LIKE '%STORAGE%'"
        );

        if (!$allPlants && $resolvedPlant) {
            $slocRows = array_filter($slocRows, function($s) use ($resolvedPlant) {
                return $s->id_plant == $resolvedPlant;
            });
        }

        // Map to id_sloc
        $idTankStorageIds = array_map(function($s) {
            return $s->id_sloc;
        }, array_values($slocRows));

        if (empty($idTankStorageIds)) {
            $slocRows = DB::connection('eudr_ts')->select(
                "SELECT id_sloc FROM m_sloc WHERE status = 1 AND description LIKE '%STORAGE%'"
            );
            $idTankStorageIds = array_map(function($t) { return $t->id_sloc; }, $slocRows);
        }

        if (empty($idTankStorageIds)) {
            $idTankStorageIds = [0];
        }

        $inClause = implode(',', array_map('intval', $idTankStorageIds));
        $filterPlant = $allPlants ? 0 : $resolvedPlant;

        $slocConditions = [];
        $slocConditions[] = "id_sloc IN ($inClause)";
        foreach ($idTankStorageIds as $slocId) {
            $slocConditions[] = "JSON_CONTAINS(id_sloc, JSON_QUOTE('" . $slocId . "'))";
            $slocConditions[] = "JSON_CONTAINS(id_sloc, '" . $slocId . "')";
        }
        $slocFilterSql = '(' . implode(' OR ', $slocConditions) . ')';

        $query = "SELECT
                    bh.id_balance_head, bh.id_material, bh.id_sloc AS id_tank,
                    NULL AS id_tank_tail_raw, bh.status,
                    CAST(bh.trace_no AS CHAR) AS trace_no,
                    bh.qty,
                    bh.created_by, bh.created_at,
                    CONCAT(m.code, ' :: ', m.description) AS material,
                    bh.init_qty,
                    MIN(sl.description) AS sloc_description,
                    GROUP_CONCAT(DISTINCT sl.id_tank ORDER BY sl.id_tank ASC SEPARATOR ' & ') AS sloc_tank_number,
                    bh.entry_date,
                    f.material_document, f.po_so, f.id_trace_head,
                    FORMAT(bs.supplier_qty,3) AS balance_supplier,
                    bh.id_plant, p.code AS plant_code, p.description AS plant_description
                    FROM (
                        SELECT id_balance_head, id_material, id_sloc,
                               status, trace_no, qty, init_qty, created_by, created_at, entry_date, id_plant
                        FROM t_balance_header
                        WHERE status = 1
                          AND SUBSTRING(trace_no,1,1) = '1'
                          AND SUBSTRING(trace_no,8,3) = '000'
                          AND (id_plant = ? OR ? = 0)
                          AND ($slocFilterSql)
                        ORDER BY id_balance_head DESC
                    ) bh
                    INNER JOIN m_material m ON bh.id_material = m.id_material AND m.type = ?
                    LEFT JOIN m_sloc sl ON (
                         (bh.id_sloc = sl.id_sloc)
                         OR JSON_CONTAINS(bh.id_sloc, JSON_QUOTE(CAST(sl.id_sloc AS CHAR)))
                         OR JSON_CONTAINS(bh.id_sloc, CAST(sl.id_sloc AS CHAR))
                    ) AND sl.status = 1
                    LEFT JOIN (
                        SELECT f.id_balance_head, MAX(g.material_document) AS material_document,
                               MAX(g.po_so) AS po_so, MAX(f.id_trace_head) AS id_trace_head
                          FROM t_trace_header f
                          LEFT JOIN t_material_document g ON f.id_trace_head = g.id_trace_head
                         WHERE f.status = 1 GROUP BY f.id_balance_head
                     ) f ON f.id_balance_head = bh.id_balance_head
                    LEFT JOIN (
                        SELECT id_balance_head, SUM(init_qty) AS supplier_qty
                        FROM t_balance_detail WHERE status = 1 GROUP BY id_balance_head
                    ) bs ON bs.id_balance_head = bh.id_balance_head
                    LEFT JOIN m_plant p ON bh.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                    GROUP BY bh.id_balance_head
                    ORDER BY bh.id_balance_head DESC
                    LIMIT 100";

        $results = DB::connection('eudr_ts')->select($query, [
            $filterPlant, $filterPlant, $this->typeMaterial
        ]);

        if (!empty($results)) {
            $traceNos = array_values(array_unique(array_column($results, 'trace_no')));
            $placeholders = implode(',', array_fill(0, count($traceNos), '?'));

            $supplierQuery = "SELECT bh.trace_no, bd.batch_sap, bd.init_qty, bd.out_qty,
                                   bd.id_balance_tail, sup.code, sup.description
                              FROM t_balance_detail bd
                              JOIN t_balance_header bh ON bd.id_balance_head = bh.id_balance_head
                              LEFT JOIN m_supplier sup ON bd.id_supplier = sup.id_supplier
                             WHERE bh.trace_no IN ($placeholders) AND bd.status = 1";

            $supplierDetails = DB::connection('eudr_ts')->select($supplierQuery, $traceNos);

            $supplierMap = [];
            foreach ($supplierDetails as $sd) {
                $traceNo = $sd->trace_no;
                if (!isset($supplierMap[$traceNo])) {
                    $supplierMap[$traceNo] = [
                        'supplier' => [],
                        'id_balance_detail' => []
                    ];
                }
                $supplierMap[$traceNo]['supplier'][] = sprintf(
                    '%s / %s / Qty: %s MT / %s',
                    $sd->code ?? 'N/A',
                    $sd->description ?? 'Unknown',
                    number_format($sd->init_qty, 3),
                    $sd->out_qty == 0 ? '-' : 'BATCH TRANSFERRED'
                );
                $supplierMap[$traceNo]['id_balance_detail'][] = $sd->id_balance_tail;
            }

            // Batch-fetch trace status to avoid N+1
            $traceStatusRows = DB::connection('eudr_ts')->select(
                "SELECT bh.trace_no,
                        IF(SUM(td.out_qty) > 0, 'USED', 'N/A') AS traced
                   FROM t_trace_detail td
                   JOIN t_trace_header th ON td.id_trace_head = th.id_trace_head
                   JOIN t_balance_header bh ON th.id_balance_head = bh.id_balance_head
                  WHERE bh.trace_no IN ($placeholders) AND td.status = 1
                  GROUP BY bh.trace_no",
                $traceNos
            );

            $traceStatusMap = [];
            foreach ($traceStatusRows as $ts) {
                $traceStatusMap[$ts->trace_no] = $ts->traced;
            }

            foreach ($results as &$r) {
                $traceKey = $r->trace_no;
                if (isset($supplierMap[$traceKey])) {
                    $r->supplier = implode(' | ', $supplierMap[$traceKey]['supplier']);
                    $r->id_balance_detail = implode(',', $supplierMap[$traceKey]['id_balance_detail']);
                } else {
                    $r->supplier = 'N/A';
                    $r->id_balance_detail = '';
                }

                $r->tf_number = $r->sloc_description;
                if (!empty($r->sloc_tank_number)) {
                    $r->tf_number = $r->sloc_description . ' | ' . $r->sloc_tank_number;
                }

                $r->traced = $traceStatusMap[$traceKey] ?? 'N/A';
                $r->qty = number_format($r->qty, 3);
                $r->init_qty = number_format($r->init_qty, 3);

                $r->plant_code = $r->plant_description ?? (string) $r->id_plant;
            }
            unset($r);
        }

        $collection = collect($results);
        $total = $collection->count();
        $offset = ($page - 1) * $perPage;
        $sliced = $collection->slice($offset, $perPage)->values();

        return ['data' => $sliced, 'total' => $total];
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
               LEFT JOIN m_sloc sl ON (
                    (bh.id_sloc = sl.id_sloc)
                    OR JSON_CONTAINS(bh.id_sloc, JSON_QUOTE(CAST(sl.id_sloc AS CHAR)))
                    OR JSON_CONTAINS(bh.id_sloc, CAST(sl.id_sloc AS CHAR))
               ) AND sl.status = 1
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
        $plantId = $this->resolvePlantCode($plantId);
        $query = "SELECT id_sloc, description, id_plant, plant_name, id_tank AS tank_number
                    FROM m_sloc
                   WHERE status = 1
                     AND (description LIKE '%STORAGE%' OR description LIKE '%FEED%')
                     AND (id_plant = ? OR ? = 0 OR ? = '')
                   ORDER BY description";

        return DB::connection('eudr_ts')->select($query, [$plantId, $plantId, $plantId]);
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
        $trace = $section
            . str_pad(substr($entryDate, 0, 6), 6, '0', STR_PAD_LEFT)
            . str_pad(substr(preg_replace('/\D/', '', $warehouse) ?: '000', 0, 3), 3, '0', STR_PAD_LEFT)
            . str_pad(substr(preg_replace('/\D/', '', $plantCode) ?: '0', -2, 2), 2, '0', STR_PAD_LEFT)
            . str_pad((string) max(1, min(99, $sequence)), 2, '0', STR_PAD_LEFT);

        return preg_replace('/\D/', '', $trace);
    }

    public function traceNoToInt(string $traceNo): int
    {
        $digits = preg_replace('/\D/', '', $traceNo);
        return (int) ($digits !== '' ? $digits : 0);
    }

    public function logTransaction(string $module, string $type, string $description, string $user): void
    {
        DB::connection('eudr_ts')->table('log_transactions')->insert([
            'log_module' => $module,
            'log_type' => $type,
            'log_description' => $description,
            'created_by' => $user,
            'created_at' => now(),
        ]);
    }
}
