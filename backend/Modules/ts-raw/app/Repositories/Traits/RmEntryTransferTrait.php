<?php declare(strict_types=1);

namespace Modules\TsRaw\Repositories\Traits;

use Illuminate\Support\Facades\DB;

trait RmEntryTransferTrait
{
    public function getTransferNumber($plantId): ?string
    {
        $resolvedPlantId = $this->resolvePlantCode($plantId);
        $warehouse = '000';
        $section = '3';
        $tracePlantCode = ($resolvedPlantId == 0 || $resolvedPlantId == '0') ? '00' : str_pad(substr($resolvedPlantId, -2), 2, '0', STR_PAD_LEFT);

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

    public function getStorageLog($plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);

        $query = "SELECT a.id_trace_head, a.id_balance_head, a.entry_date,
                         a.from_trace_no, a.to_trace_no,
                         c.code AS material_code, c.description AS material_name,
                         MIN(d.description) AS tank_description,
                         GROUP_CONCAT(DISTINCT d.id_tank ORDER BY d.id_tank ASC SEPARATOR ' & ') AS tank_numbers,
                         MIN(d.id_sloc) AS main_sloc_id,
                         FORMAT(a.in_qty, 3) AS in_qty,
                         FORMAT(a.out_qty, 3) AS out_qty,
                         a.created_by, a.created_at,
                         md.material_document, md.po_so, p.code AS plant_code
                    FROM t_trace_header a
                    JOIN m_material c ON a.id_material = c.id_material
                    LEFT JOIN m_sloc d ON a.id_sloc = d.id_sloc
                    LEFT JOIN t_balance_header bh ON a.id_balance_head = bh.id_balance_head
                    LEFT JOIN t_material_document md ON a.id_trace_head = md.id_trace_head
                    LEFT JOIN m_plant p ON d.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                   WHERE a.status = 1
                     AND (d.description IS NULL OR d.description LIKE '%Storage%')
                     AND (CAST(? AS CHAR) = '0' OR CAST(d.id_plant AS CHAR) = CAST(? AS CHAR))
                   GROUP BY a.id_trace_head
                   ORDER BY a.id_trace_head DESC";

        $results = DB::connection('eudr_ts')->select($query, [$plantId, $plantId]);

        foreach ($results as &$row) {
            if (!empty($row->tank_numbers)) {
                $row->tank_name = $row->tank_description . ' | ' . $row->tank_numbers;
            } else {
                $row->tank_name = $row->tank_description;
            }
            unset($row->tank_numbers);
        }

        return $results;
    }

    public function getFeedLog($plantId): array
    {
        return $this->getTankLog($plantId, 'FEED');
    }

    public function debugFeedLog($plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);

        $query = "SELECT a.id_trace_head, a.id_balance_head, a.entry_date,
                         a.from_trace_no, a.to_trace_no,
                         c.code AS material_code, c.description AS material_name,
                         d.description AS tank_description,
                         d.id_sloc AS tank_number,
                         a.id_sloc_tail AS id_tank_tail,
                         FORMAT(a.in_qty, 3) AS in_qty,
                         FORMAT(a.out_qty, 3) AS out_qty,
                         a.created_by, a.created_at,
                         md.material_document, md.po_so,
                         (
                            SELECT GROUP_CONCAT(CONCAT(h.id_sloc, ' - ', h.description) ORDER BY h.id_sloc ASC SEPARATOR ' | ')
                            FROM m_sloc h
                            WHERE FIND_IN_SET(h.id_sloc, REPLACE(REPLACE(REPLACE(REPLACE(a.id_sloc_tail, '[', ''), ']', ''), '\"', ''), ' ', '')) > 0
                              AND h.status = 1
                         ) AS sub_slocs_raw, p.code AS plant_code
                    FROM t_trace_header a
                    JOIN m_material c ON a.id_material = c.id_material
                    JOIN m_sloc d ON a.id_sloc = d.id_sloc
                    LEFT JOIN t_material_document md ON a.id_trace_head = md.id_trace_head
                    LEFT JOIN m_plant p ON d.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                   WHERE a.status = 1
                     AND d.description LIKE '%FEED%'
                     AND (CAST(? AS CHAR) = '0' OR CAST(d.id_plant AS CHAR) = CAST(? AS CHAR))
                     AND SUBSTRING(a.to_trace_no,1,1) <> '7'
                     AND NOT EXISTS (SELECT 1 FROM t_balance_header bh2 WHERE CAST(bh2.trace_no AS CHAR) = CAST(a.to_trace_no AS CHAR) AND SUBSTRING(bh2.trace_no,1,1) = '7')
                   ORDER BY a.id_trace_head DESC";

        $rawData = DB::connection('eudr_ts')->select($query, [$plantId, $plantId]);

        $groupedData = [];
        foreach ($rawData as $row) {
            $key = $row->id_trace_head;

            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'id_trace_head' => $row->id_trace_head,
                    'id_balance_head' => $row->id_balance_head,
                    'entry_date' => $row->entry_date,
                    'from_trace_no' => $row->from_trace_no,
                    'to_trace_no' => $row->to_trace_no,
                    'material_code' => $row->material_code,
                    'material_name' => $row->material_name,
                    'tank_description' => $row->tank_description,
                    'tank_number' => $row->tank_number,
                    'id_tank_tail' => $row->id_tank_tail,
                    'in_qty' => $row->in_qty,
                    'out_qty' => $row->out_qty,
                    'created_by' => $row->created_by,
                    'created_at' => $row->created_at,
                    'material_document' => $row->material_document,
                    'po_so' => $row->po_so,
                    'plant_code' => $row->plant_code,
                    'sub_slocs' => []
                ];
            }

            if ($row->sub_slocs_raw) {
                $slocParts = explode(' | ', $row->sub_slocs_raw);
                foreach ($slocParts as $part) {
                    $parts = explode(' - ', $part, 2);
                    if (count($parts) === 2) {
                        $groupedData[$key]['sub_slocs'][] = [
                            'tf_number' => trim($parts[0]),
                            'description' => trim($parts[1])
                        ];
                    }
                }
            }
        }

        return array_values($groupedData);
    }

    public function getTankLog($plantId, string $tankType): array
    {
        $plantId = $this->resolvePlantCode($plantId);

        $query = "SELECT
                          a.id_balance_head,
                          MIN(a.id_trace_head) as id_trace_head,
                          a.entry_date,
                          a.to_trace_no,
                          COALESCE(GROUP_CONCAT(DISTINCT th.from_trace_no ORDER BY th.from_trace_no ASC SEPARATOR ', '), a.from_trace_no) as from_trace_no_agg,
                          c.code AS material_code, c.description AS material_name,
                          MIN(d.description) AS tank_description,
                          GROUP_CONCAT(DISTINCT d.id_tank ORDER BY d.id_tank ASC SEPARATOR ' & ') AS tank_numbers,
                          MIN(d.id_sloc) AS main_sloc_id,
                          FORMAT(MAX(a.in_qty), 3) AS in_qty,
                          FORMAT(MAX(a.out_qty), 3) AS out_qty,
                          MIN(a.created_by) AS created_by,
                          MIN(a.created_at) AS created_at,
                          md.material_document, md.po_so, p.code AS plant_code
                     FROM t_trace_header a
                     LEFT JOIN t_trace_header th ON a.from_trace_no = th.to_trace_no
                     JOIN m_material c ON a.id_material = c.id_material
                     LEFT JOIN m_sloc d ON a.id_sloc = d.id_sloc
                     LEFT JOIN t_balance_header bh ON a.id_balance_head = bh.id_balance_head
                     LEFT JOIN t_material_document md ON a.id_trace_head = md.id_trace_head
                     LEFT JOIN m_plant p ON d.id_plant = p.code_3 COLLATE utf8mb4_unicode_ci
                    WHERE a.status = 1
                      AND (d.description IS NULL OR d.description LIKE CONCAT('%', ?, '%'))
                      AND (CAST(? AS CHAR) = '0' OR CAST(d.id_plant AS CHAR) = CAST(? AS CHAR))
                      AND SUBSTRING(a.to_trace_no,1,1) <> '7'
                      AND NOT EXISTS (SELECT 1 FROM t_balance_header bh2 WHERE CAST(bh2.trace_no AS CHAR) = CAST(a.to_trace_no AS CHAR) AND SUBSTRING(bh2.trace_no,1,1) = '7')
                    GROUP BY a.id_balance_head, a.to_trace_no, a.entry_date, c.code, c.description,
                             md.material_document, md.po_so, p.code
                    ORDER BY MIN(a.id_trace_head) DESC";

        $results = DB::connection('eudr_ts')->select($query, [$tankType, $plantId, $plantId]);

        foreach ($results as &$row) {
            if (!empty($row->tank_numbers)) {
                $row->tank_name = $row->tank_description . ' | ' . $row->tank_numbers;
            } else {
                $row->tank_name = $row->tank_description;
            }
            $fromTraceNo = $row->from_trace_no_agg ?? '';
            if (!empty($fromTraceNo)) {
                $fromTraceArray = explode(', ', $fromTraceNo);
                $row->trace_pairs_array = array_map(function($trace) use ($row) {
                    return $trace . ' >>> ' . $row->to_trace_no;
                }, $fromTraceArray);
            } else {
                $row->trace_pairs_array = [];
            }
            $row->from_trace_no = $row->from_trace_no_agg;
            unset($row->tank_numbers);
            unset($row->from_trace_no_agg);
        }

        return $results;
    }

    public function generateTransferNumber($plantId, string $movSeq = '000'): ?string
    {
        $plantId = $this->resolvePlantCode($plantId);
        $result = DB::connection('eudr_ts')->select(
            'SELECT a.trace_no
               FROM (SELECT a.trace_no+1 AS trace_no
                       FROM t_balance_header a
                      WHERE SUBSTRING(a.trace_no,1,7) = CONCAT("7", DATE_FORMAT(CURDATE(), "%y%m%d"))
                        AND SUBSTRING(a.trace_no,8,3) = ?
                        AND a.status = 1
                        AND a.id_plant = ?
                      ORDER BY a.id_balance_head DESC
                      LIMIT 1) a
             UNION ALL
            SELECT CONCAT("7", DATE_FORMAT(CURDATE(), "%y%m%d"), ?, LPAD(RIGHT(?, 2), 2, "0"), "01") AS trace_no
               LIMIT 1',
            [$movSeq, $plantId, $movSeq, $plantId]
        );

        return $result[0]->trace_no ?? null;
    }

    public function findBalanceByTraceNo(string $traceNo): ?object
    {
        $result = DB::connection('eudr_ts')->select(
            'SELECT * FROM t_balance_header WHERE trace_no = ? AND status = 1 LIMIT 1',
            [$traceNo]
        );
        return $result[0] ?? null;
    }

    public function findTraceByBalanceHeadId(int $balanceHeadId): ?object
    {
        return DB::connection('eudr_ts')->table('t_trace_header')
            ->where('id_balance_head', $balanceHeadId)
            ->where('status', 1)
            ->first();
    }

    public function createTransferBalance(array $data): object
    {
        $result = DB::connection('eudr_ts')->table('t_balance_header')->insertGetId([
            'entry_date' => $data['entry_date'],
            'trace_no' => $data['trace_no'],
            'id_material' => $data['id_material'],
            'id_sloc' => $data['id_sloc'],
            'id_sloc_tail' => $data['id_sloc_tail'] ?? null,
            'id_tank' => null,
            'id_tank_tail' => null,
            'id_plant' => $data['id_plant'],
            'qty' => $data['qty'],
            'in_qty' => $data['qty'],
            'out_qty' => 0,
            'init_qty' => $data['qty'],
            'status' => 1,
            'created_by' => $data['created_by'],
        ]);

        return (object) ['id_balance_head' => $result];
    }

    public function createTransferTrace(array $data): object
    {
        $result = DB::connection('eudr_ts')->table('t_trace_header')->insertGetId([
            'id_balance_head' => $data['id_balance_head'],
            'entry_date' => $data['entry_date'],
            'from_trace_no' => $data['from_trace_no'],
            'to_trace_no' => $data['to_trace_no'],
            'id_material' => $data['id_material'],
            'id_sloc' => $data['id_sloc'],
            'id_tank_tail' => $data['id_tank_tail'] ?? null,
            'id_plant' => $data['id_plant'],
            'in_qty' => $data['qty'],
            'out_qty' => 0,
            'status' => 1,
            'created_by' => $data['created_by'],
        ]);

        return (object) ['id_trace_head' => $result];
    }

    public function updateSourceBalance(int $balanceId, float $qty): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_header')
            ->where('id_balance_head', $balanceId)
            ->update([
                'qty' => DB::raw('qty - ' . $qty),
                'out_qty' => DB::raw('out_qty + ' . $qty),
            ]);
    }

    public function updateSourceTrace(int $balanceHeadId, float $qty): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_trace_header')
            ->where('id_balance_head', $balanceHeadId)
            ->where('status', 1)
            ->update([
                'out_qty' => DB::raw('out_qty + ' . $qty),
            ]);
    }

    public function findTransferById(int $id): ?object
    {
        return DB::connection('eudr_ts')->table('t_trace_header')
            ->where('id_trace_head', $id)
            ->where('status', 1)
            ->first();
    }

    public function deactivateBalance(int $balanceId, string $user): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_header')
            ->where('id_balance_head', $balanceId)
            ->update(['status' => 0, 'updated_by' => $user]);
    }

    public function deactivateTrace(int $traceId, string $user): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_trace_header')
            ->where('id_trace_head', $traceId)
            ->update(['status' => 0, 'updated_by' => $user]);
    }

    public function revertSourceBalance(string $traceNo, float $qty): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_header')
            ->where('trace_no', $traceNo)
            ->where('status', 1)
            ->update([
                'qty' => DB::raw('qty + ' . $qty),
                'out_qty' => DB::raw('out_qty - ' . $qty),
            ]);
    }

    public function revertSourceTrace(int $balanceHeadId, float $qty): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_trace_header')
            ->where('id_balance_head', $balanceHeadId)
            ->where('status', 1)
            ->update([
                'out_qty' => DB::raw('out_qty - ' . $qty),
            ]);
    }

    public function getSourceEntries(int $plantId): array
    {
        return DB::connection('eudr_ts')->select(
            "SELECT bh.id_balance_head, bh.trace_no, m.description AS material,
                    sl.description AS tank, FORMAT(bh.qty, 3) AS qty
               FROM t_balance_header bh
               JOIN m_material m ON bh.id_material = m.id_material
               JOIN m_sloc sl ON bh.id_sloc = sl.id_sloc
              WHERE bh.status = 1
                AND SUBSTRING(bh.trace_no, 1, 1) = '1'
                AND bh.qty > 0
                AND (bh.id_plant = ? OR ? = 0)
                AND sl.description LIKE '%STORAGE%'
              ORDER BY bh.id_balance_head DESC",
            [$plantId, $plantId]
        );
    }

    public function getDestTanks(int $plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);
        return DB::connection('eudr_ts')->select(
            "SELECT DISTINCT description AS tank
               FROM m_sloc
              WHERE status = 1
                AND description LIKE '%FEED%'
                AND (CAST(? AS CHAR) = '0' OR CAST(id_plant AS CHAR) = CAST(? AS CHAR))
              ORDER BY description",
            [$plantId, $plantId]
        );
    }

    public function getTransferList($plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);

        $query = "SELECT
                    bh.id_balance_head, bh.id_material,
                    CAST(bh.trace_no AS CHAR) AS trace_no,
                    bh.qty, bh.init_qty,
                    bh.entry_date,
                    bh.created_by, bh.created_at,
                    bh.id_plant,
                    CONCAT(m.code, ' :: ', m.description) AS material,
                    sl.description AS sloc_description,
                    p.code AS plant_code,
                    f.id_trace_head,
                    f.from_trace_no, f.to_trace_no,
                    FORMAT(f.in_qty, 3) AS in_qty,
                    FORMAT(f.out_qty, 3) AS out_qty,
                    md.material_document, md.po_so
                FROM t_balance_header bh
                INNER JOIN m_material m ON bh.id_material = m.id_material
                LEFT JOIN m_sloc sl ON bh.id_sloc = sl.id_sloc AND sl.status = 1
                LEFT JOIN (
                    SELECT f2.id_balance_head, MAX(f2.id_trace_head) AS id_trace_head,
                           MAX(f2.from_trace_no) AS from_trace_no, MAX(f2.to_trace_no) AS to_trace_no,
                           SUM(f2.in_qty) AS in_qty, SUM(f2.out_qty) AS out_qty
                    FROM t_trace_header f2
                    WHERE f2.status = 1
                    GROUP BY f2.id_balance_head
                ) f ON bh.id_balance_head = f.id_balance_head
                LEFT JOIN t_material_document md ON f.id_trace_head = md.id_trace_head
                LEFT JOIN m_plant p ON bh.id_plant = p.code_3
                WHERE bh.status = 1
                  AND SUBSTRING(bh.trace_no,1,1) = '7'
                  AND (CAST(? AS CHAR) = '0' OR CAST(bh.id_plant AS CHAR) = CAST(? AS CHAR))
                ORDER BY bh.id_balance_head DESC";

        return DB::connection('eudr_ts')->select($query, [$plantId, $plantId]);
    }

    public function getActiveMaterialsForTransfer(): array
    {
        return DB::connection('eudr_ts')->select(
            'SELECT a.id_material, CONCAT(UPPER(a.description), " (", a.code, ")") AS material
               FROM m_material a
              WHERE a.status = 1
                AND a.id_rundown <> "-"
              GROUP BY a.code
              ORDER BY a.description ASC'
        );
    }

    public function generateTransferEntryNo(int $materialId, $plantId): ?string
    {
        $result = DB::connection('eudr_ts')->select(
            'SELECT a.entryNo
               FROM (SELECT b.trace_no + 1 AS entryNo
                       FROM m_material a
                       LEFT JOIN t_balance_header b
                         ON a.id_rundown = SUBSTRING(b.trace_no, 8,3) AND b.status = 1
                      WHERE a.id_material = ?
                        AND SUBSTRING(b.trace_no, 1, 7) = CONCAT(7, DATE_FORMAT(CURDATE(), "%y%m%d"))
                        AND a.status = 1
                      ORDER BY b.id_balance_head DESC
                      LIMIT 1) a
               UNION ALL
               SELECT CONCAT("7", DATE_FORMAT(CURDATE(), "%y%m%d"), IF(a.id_rundown <> "-", a.id_rundown, "000"), LPAD(RIGHT(?, 2), 2, "0"), "01") AS entryNo
                 FROM m_material a
                WHERE a.status = 1
                  AND a.id_material = ?
                LIMIT 1',
            [$materialId, $plantId, $materialId]
        );

        return $result[0]->entryNo ?? null;
    }

    public function getActiveTanksForTransfer(?int $materialId, $plantId): array
    {
        if ($materialId === null || $materialId === 0) {
            return DB::connection('eudr_ts')->select(
                'SELECT b.id_sloc AS id_tank, b.description AS tank
                   FROM m_sloc b
                  WHERE b.status = 1
                    AND b.id_plant <> ?
                  GROUP BY b.id_sloc
                  ORDER BY b.description ASC',
                [$plantId]
            );
        }

        return DB::connection('eudr_ts')->select(
            'SELECT b.id_sloc AS id_tank, b.description AS tank
               FROM m_material a
               LEFT JOIN m_sloc b
                 ON a.type = b.code_2 COLLATE utf8mb4_unicode_ci AND b.status = 1 AND b.id_plant = ?
              WHERE a.status = 1
                AND a.id_material = ?
              GROUP BY b.id_sloc',
            [$plantId, $materialId]
        );
    }

    public function getActiveSpecificTanksRundown(int $sloc): array
    {
        return DB::connection('eudr_ts')->select(
            'SELECT a.id_sloc AS id_tank_tail, a.id_tank AS tankNo
               FROM m_sloc a
              WHERE a.status = 1
                AND a.id_sloc = ?
              ORDER BY a.description ASC',
            [$sloc]
        );
    }

    public function getTotalStockMaterial(int $materialId, int $tankId): float
    {
        $result = DB::connection('eudr_ts')->select(
            'SELECT ROUND(SUM(c.in_qty) - SUM(c.out_qty), 3) AS total
               FROM m_material a
               LEFT JOIN (SELECT b.code, b.id_material
                            FROM m_material b
                           WHERE b.status = 1) b
                 ON a.code = b.code
               LEFT JOIN (SELECT c.id_material, c.in_qty, c.out_qty
                            FROM t_trace_header c
                           WHERE c.status = 1
                             AND c.id_sloc = ?
                           ) c
                 ON b.id_material = c.id_material
              WHERE a.status = 1
                AND a.id_material = ?',
            [$tankId, $materialId]
        );

        return floatval($result[0]->total ?? 0);
    }

    public function getSupplierMaterial(int $materialId, int $tankId, $plantId): ?object
    {
        $result = DB::connection('eudr_ts')->select(
            'SELECT a.id_supplier, a.batch_sap
               FROM t_balance_detail a
               LEFT JOIN t_balance_header b ON a.id_balance_head = b.id_balance_head
              WHERE a.status = 1
                AND a.id_material = ?
                AND b.id_sloc = ?
                AND a.qty > 0.0001
              ORDER BY b.created_at ASC, a.id_balance_tail ASC
              LIMIT 1',
            [$materialId, $tankId]
        );

        return $result[0] ?? null;
    }
}
