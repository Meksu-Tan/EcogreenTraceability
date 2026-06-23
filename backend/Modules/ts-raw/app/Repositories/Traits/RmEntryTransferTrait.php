<?php
declare(strict_types=1);
namespace Modules\TsRaw\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Traits\DbCompatTrait;

trait RmEntryTransferTrait
{
    use DbCompatTrait;
    public function getTransferNumber($plantId): ?string
    {
        $resolvedPlantId = $this->resolvePlantCode($plantId);
        $warehouse = '000';
        $section = '3';
        $tracePlantCode = ($resolvedPlantId == 0 || $resolvedPlantId == '0') ? '00' : str_pad(substr($resolvedPlantId, -2), 2, '0', STR_PAD_LEFT);

        $dateFmt = $this->dbDateFormat($this->dbCurDate(), '%y%m%d');
        $castRight = "CAST(RIGHT(trace_no, 2) AS INTEGER)";
        $result = DB::connection('eudr_ts')->select(
            "SELECT MAX({$castRight}) as max_seq
               FROM t_balance_header
              WHERE SUBSTRING(trace_no,1,1) = ?
                AND SUBSTRING(trace_no,2,6) = {$dateFmt}
                AND SUBSTRING(trace_no,8,3) = ?
                AND SUBSTRING(trace_no,11,2) = ?
                AND status = 1",
            [$section, $warehouse, $tracePlantCode]
        );

        $maxSeq = $result[0]->max_seq ?? 0;
        $newSeq = $maxSeq + 1;

        return $this->buildTraceNo($section, date("ymd"), $warehouse, $tracePlantCode, $newSeq);
    }

    public function getStorageLog($plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);

        $qtyFmt = $this->dbNumberFormat('SUM(a.qty)', 3);
        $initQtyFmt = $this->dbNumberFormat('SUM(a.init_qty)', 3);

        $traceSub = "(SELECT STRING_AGG(DISTINCT CONCAT(CAST(th.from_trace_no AS TEXT), ' >>> ', CAST(th.to_trace_no AS TEXT)), ' | ')
                       FROM t_trace_header th
                      WHERE th.id_balance_head = a.id_balance_head AND th.status = 1)";

        $supplierAgg = "STRING_AGG(DISTINCT CONCAT(e.code, ' :: ', e.description, ' / ', b.batch_sap, ' / Qty : ', TRIM(TO_CHAR(ROUND(CAST(b.init_qty AS numeric),3), 'FM999999999999990.000')), ' MT / ', CASE WHEN COALESCE(b.out_qty,0) = 0 THEN '-' ELSE 'BATCH TRANSFERRED' END), ' | ' ORDER BY b.batch_sap)";

        $tankNumbersAgg = "STRING_AGG(DISTINCT d.tf_number, ', ' ORDER BY d.tf_number)";

        $query = "SELECT a.id_balance_head, a.id_material,
                         CAST(a.trace_no AS TEXT) AS trace_no,
                         {$qtyFmt} AS qty,
                         CONCAT(c.code, ' :: ', c.description) AS material,
                         {$initQtyFmt} AS init_qty,
                         MIN(d.description) AS tank_description,
                         {$tankNumbersAgg} AS tank_numbers,
                         a.entry_date,
                         {$supplierAgg} AS supplier,
                         f.material_document, f.po_so, f.id_trace_head,
                         {$traceSub} AS trace_info,
                         {$this->dbNumberFormat('SUM(b.init_qty)', 3)} AS balance_supplier
                    FROM t_balance_header a
                    LEFT JOIN t_balance_detail b ON a.id_balance_head = b.id_balance_head AND b.status = 1
                    JOIN m_material c ON a.id_material = c.id_material
                    LEFT JOIN m_sloc d ON a.id_sloc = d.id_sloc AND d.status = 1 AND d.code_3 = 'STORAGE'
                    LEFT JOIN m_supplier e ON e.id_supplier = b.id_supplier
                    LEFT JOIN (SELECT f.id_balance_head,
                                      MAX(g.material_document) AS material_document,
                                      MAX(g.po_so) AS po_so,
                                      MIN(f.id_trace_head) AS id_trace_head
                                 FROM t_trace_header f
                                 LEFT JOIN t_material_document g ON f.id_trace_head = g.id_trace_head
                                WHERE f.status = 1
                                GROUP BY f.id_balance_head) f
                      ON f.id_balance_head = a.id_balance_head
                   WHERE c.type = 'RM'
                     AND (CAST(SUBSTRING(a.trace_no,1,1) AS INTEGER) = 1 OR CAST(SUBSTRING(a.trace_no,1,1) AS INTEGER) = 9)
                     AND SUBSTRING(a.trace_no,8,3) = '000'
                     AND a.status = 1
                     AND (a.id_plant = ? OR ? = '0')
                   GROUP BY a.trace_no
                   ORDER BY MAX(a.id_balance_head) DESC";

        $results = DB::connection('eudr_ts')->select($query, [$plantId, $plantId]);

        foreach ($results as &$row) {
            $tankDesc = $row->tank_description ?? '';
            $tankNumbers = $row->tank_numbers ?? '';
            if (!empty($tankNumbers)) {
                $row->tank_name = $tankDesc . ' | ' . $tankNumbers;
            } else {
                $row->tank_name = $tankDesc;
            }
            unset($row->tank_description);
            unset($row->tank_numbers);
            $row->traced = ($row->material_document || $row->trace_info) ? 'N/A' : '';
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

        $inQtyFmt = "ROUND(MAX(CAST(a.in_qty AS numeric)), 3)";
        $outQtyFmt = "ROUND(MAX(CAST(a.out_qty AS numeric)), 3)";
        $subSlocConcatPg = $this->dbGroupConcat(
            "CONCAT(h.id_sloc, ' - ', h.description)",
            ' | ', false, 'h.id_sloc ASC'
        );

        $subSlocSql = "(SELECT {$subSlocConcatPg}
                            FROM m_sloc h
                            WHERE h.id_sloc = ANY(
                                SELECT unnest(
                                    string_to_array(
                                        regexp_replace(a.id_sloc_tail, '[][]|\"| ', '', 'g'),
                                        ','
                                    )
                                )::integer
                            )
                              AND h.status = 1)";

        $query = "SELECT a.id_trace_head, a.id_balance_head, a.entry_date,
                         a.from_trace_no, a.to_trace_no,
                         c.code AS material_code, c.description AS material_name,
                         d.description AS tank_description,
                         d.id_sloc AS tank_number,
                         a.id_sloc_tail AS id_sloc_tail,
                         {$inQtyFmt} AS in_qty,
                         {$outQtyFmt} AS out_qty,
                         a.created_by, a.created_at,
                         md.material_document, md.po_so,
                         {$subSlocSql} AS sub_slocs_raw, p.code AS plant_code
                    FROM t_trace_header a
                    JOIN m_material c ON a.id_material = c.id_material
                    JOIN m_sloc d ON a.id_sloc = d.id_sloc
                    LEFT JOIN t_material_document md ON a.id_trace_head = md.id_trace_head
                    LEFT JOIN m_plant p ON d.id_plant = p.code_3
                   WHERE a.status = 1
                     AND d.description LIKE '%FEED%'
                     AND (CAST(? AS TEXT) = '0' OR CAST(d.id_plant AS TEXT) = CAST(? AS TEXT))
                     AND SUBSTRING(a.to_trace_no,1,1) <> '7'
                     AND NOT EXISTS (SELECT 1 FROM t_balance_header bh2 WHERE CAST(bh2.trace_no AS TEXT) = CAST(a.to_trace_no AS TEXT) AND SUBSTRING(bh2.trace_no,1,1) = '7')
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
                    'id_sloc_tail' => $row->id_sloc_tail,
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

        $fromTraceAgg = $this->dbGroupConcat(
            'DISTINCT th.from_trace_no', ', ', false, 'th.from_trace_no ASC'
        );

        $inQtyFmt = $this->dbNumberFormat('MAX(CASE WHEN a.in_qty > 0.0001 THEN a.in_qty ELSE a.out_qty END)', 3);
        $outQtyFmt = $this->dbNumberFormat('MAX(a.out_qty)', 3);

        $isSqlite = DB::connection('eudr_ts')->getDriverName() === 'sqlite';
        $tankNamesSub = $isSqlite
            ? "(SELECT GROUP_CONCAT(DISTINCT h.description || CASE WHEN h.description IS NOT NULL AND h.description <> '' THEN ' | ' ELSE '' END || h.tf_number)
                  FROM m_sloc h
                 WHERE CONCAT(',', MAX(CAST(a.id_sloc AS TEXT)), ',') LIKE CONCAT('%,', CAST(h.id_sloc AS TEXT), ',%')
                   AND h.status = 1)"
            : "(SELECT STRING_AGG(DISTINCT CONCAT(h.description, CASE WHEN h.description IS NOT NULL AND h.description <> '' THEN ' | ' ELSE '' END, h.tf_number), ', ')
                            FROM m_sloc h
                            WHERE h.id_sloc = ANY(
                                SELECT unnest(
                                    string_to_array(
                                        regexp_replace(MAX(a.id_sloc::text), '[][]|\"| ', '', 'g'),
                                        ','
                                    )
                                )::integer
                            )
                              AND h.status = 1)";

        $subSlocJoin = "STRING_AGG(DISTINCT CASE WHEN COALESCE(td.in_qty, td.out_qty, bd.qty, bd.init_qty) > 0.0001 THEN CONCAT(COALESCE(sup.code, bsup.code), ' :: ', COALESCE(sup.description, bsup.description), ' / ', COALESCE(td.batch_sap, bd.batch_sap), ' / Qty : ', TRIM(TO_CHAR(ROUND(CAST(COALESCE(bd.init_qty, td.in_qty, bd.qty) AS numeric),3), 'FM999999999999990.000')), ' MT / ', CASE WHEN '{$tankType}' = 'STORAGE' THEN CASE WHEN COALESCE(bd.out_qty,0) = 0 THEN '-' ELSE 'BATCH TRANSFERRED' END ELSE CONCAT('Qty : ', TRIM(TO_CHAR(ROUND(CAST(COALESCE(td.in_qty, td.out_qty, bd.qty) AS numeric),3), 'FM999999999999990.000')), ' MT') END) ELSE NULL END, ' | ')";

        $query = "SELECT
                          a.id_balance_head,
                          MIN(a.id_trace_head) as id_trace_head,
                          MIN(a.entry_date) AS entry_date,
                          MIN(a.to_trace_no) AS to_trace_no,
                          COALESCE({$fromTraceAgg}, MIN(a.from_trace_no)) as from_trace_no_agg,
                          c.code AS material_code,
                          MAX(c.description) AS material_name,
                          {$tankNamesSub} AS tank_name,
                          {$inQtyFmt} AS in_qty,
                          {$outQtyFmt} AS out_qty,
                          MIN(a.created_by) AS created_by,
                          MIN(a.created_at) AS created_at,
                          MAX(md.material_document) AS material_document,
                          MAX(md.po_so) AS po_so,
                          MAX(p.code) AS plant_code,
                          {$subSlocJoin} AS supplier
                     FROM t_trace_header a
                     LEFT JOIN t_trace_header th ON a.from_trace_no = th.to_trace_no
                     JOIN m_material c ON a.id_material = c.id_material
                     LEFT JOIN t_balance_header bh ON a.id_balance_head = bh.id_balance_head
                     LEFT JOIN t_balance_detail bd ON bh.id_balance_head = bd.id_balance_head AND bd.status = 1
                     LEFT JOIN t_material_document md ON a.id_trace_head = md.id_trace_head
                     LEFT JOIN t_trace_detail td ON a.id_trace_head = td.id_trace_head AND td.status = 1
                     LEFT JOIN m_supplier sup ON td.id_supplier = sup.id_supplier
                     LEFT JOIN m_supplier bsup ON bd.id_supplier = bsup.id_supplier
                     LEFT JOIN m_plant p ON CAST(a.id_plant AS TEXT) = p.code_3
                    WHERE a.status = 1
                      AND (? = '0' OR CAST(a.id_plant AS TEXT) = CAST(? AS TEXT))
                      AND (
                          (? = 'FEED' AND SUBSTRING(a.to_trace_no,1,1) = '3')
                          OR (? <> 'FEED' AND SUBSTRING(a.to_trace_no,1,1) <> '7')
                      )
                    GROUP BY a.to_trace_no, a.id_balance_head, c.code, md.material_document, md.po_so, p.code, a.id_sloc::text
                    ORDER BY MIN(a.id_trace_head) DESC";

        $results = DB::connection('eudr_ts')->select($query, [$plantId, $plantId, $tankType, $tankType]);

        foreach ($results as &$row) {
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
            $row->supplier = $row->supplier ?: 'N/A';
            unset($row->from_trace_no_agg);
        }

        return $results;
    }

    public function generateTransferNumber($plantId, string $movSeq = '000'): ?string
    {
        $plantId = $this->resolvePlantCode($plantId);
        $dateFmt = $this->dbDateFormat($this->dbCurDate(), '%y%m%d');
        $result = DB::connection('eudr_ts')->select(
            "SELECT a.trace_no
               FROM (SELECT CAST(a.trace_no AS BIGINT) + 1 AS trace_no
                       FROM t_balance_header a
                      WHERE SUBSTRING(a.trace_no,1,7) = CONCAT('7', {$dateFmt})
                        AND SUBSTRING(a.trace_no,8,3) = ?
                        AND a.status = 1
                        AND a.id_plant = ?
                      ORDER BY a.id_balance_head DESC
                      LIMIT 1) a
             UNION ALL
            SELECT CONCAT('7', {$dateFmt}, ?, LPAD(RIGHT(?, 2), 2, '0'), '01') AS trace_no
               LIMIT 1",
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
        $idSloc = $data['id_sloc'];
        $decoded = is_string($idSloc) ? json_decode($idSloc, true) : null;
        if (is_array($decoded)) {
            $slocInt = (int) ($decoded[0] ?? 0);
        } elseif (is_array($idSloc)) {
            $slocInt = (int) ($idSloc[0] ?? 0);
        } else {
            $slocInt = (int) $idSloc;
        }

        $result = DB::connection('eudr_ts')->table('t_balance_header')->insertGetId([
            'entry_date' => $data['entry_date'],
            'trace_no' => $data['trace_no'],
            'id_material' => $data['id_material'],
            'id_sloc' => $slocInt,
            'id_sloc_tail' => $data['id_sloc_tail'] ?? null,
            'tf_number' => null,
            'id_sloc_tail' => null,
            'id_plant' => $data['id_plant'],
            'qty' => $data['qty'],
            'in_qty' => $data['qty'],
            'out_qty' => 0,
            'init_qty' => $data['qty'],
            'status' => 1,
            'created_by' => $data['created_by'],
        ], 'id_balance_head');

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
            'id_sloc_tail' => $data['id_sloc_tail'] ?? null,
            'id_plant' => $data['id_plant'],
            'in_qty' => $data['qty'],
            'out_qty' => 0,
            'status' => 1,
            'created_by' => $data['created_by'],
        ], 'id_trace_head');

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
        $qtyFmt = $this->dbNumberFormat('bh.qty', 3);
        return DB::connection('eudr_ts')->select(
            "SELECT bh.id_balance_head, bh.trace_no, m.description AS material,
                    sl.description AS tank, {$qtyFmt} AS qty
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
                AND (CAST(? AS TEXT) = '0' OR CAST(id_plant AS TEXT) = CAST(? AS TEXT))
              ORDER BY description",
            [$plantId, $plantId]
        );
    }

    public function getTransferList($plantId): array
    {
        $plantId = $this->resolvePlantCode($plantId);

        $inQtyFmt = $this->dbNumberFormat('f.in_qty', 3);
        $outQtyFmt = $this->dbNumberFormat('f.out_qty', 3);

        $query = "SELECT
                    bh.id_balance_head, bh.id_material,
                    CAST(bh.trace_no AS TEXT) AS trace_no,
                    bh.qty, bh.init_qty,
                    bh.entry_date,
                    bh.created_by, bh.created_at,
                    bh.id_plant,
                    CONCAT(m.code, ' :: ', m.description) AS material,
                    sl.description AS sloc_description,
                    p.code AS plant_code,
                    f.id_trace_head,
                    f.from_trace_no, f.to_trace_no,
                    {$inQtyFmt} AS in_qty,
                    {$outQtyFmt} AS out_qty,
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
                  AND (CAST(? AS TEXT) = '0' OR CAST(bh.id_plant AS TEXT) = CAST(? AS TEXT))
                ORDER BY bh.id_balance_head DESC";

        return DB::connection('eudr_ts')->select($query, [$plantId, $plantId]);
    }

    public function getActiveMaterialsForTransfer(): array
    {
        $sql = "SELECT a.id_material, CONCAT(UPPER(a.description), ' (', a.code, ')') AS material
                 FROM m_material a
                WHERE a.status = 1
                  AND a.id_rundown <> '-'
                GROUP BY a.code
                ORDER BY a.description ASC";

        return DB::connection('eudr_ts')->select($sql);
    }

    public function generateTransferEntryNo(int $materialId, $plantId): ?string
    {
        $dateFmt = $this->dbDateFormat($this->dbCurDate(), '%y%m%d');
        $result = DB::connection('eudr_ts')->select(
            "SELECT a.entryNo
               FROM (SELECT b.trace_no + 1 AS entryNo
                       FROM m_material a
                       LEFT JOIN t_balance_header b
                         ON a.id_rundown = SUBSTRING(b.trace_no, 8,3) AND b.status = 1
                      WHERE a.id_material = ?
                        AND SUBSTRING(b.trace_no, 1, 7) = CONCAT('7', {$dateFmt})
                        AND a.status = 1
                      ORDER BY b.id_balance_head DESC
                      LIMIT 1) a
               UNION ALL
               SELECT CONCAT('7', {$dateFmt}, CASE WHEN a.id_rundown <> '-' THEN a.id_rundown ELSE '000' END, LPAD(RIGHT(?, 2), 2, '0'), '01') AS entryNo
                 FROM m_material a
                WHERE a.status = 1
                  AND a.id_material = ?
                LIMIT 1",
            [$materialId, $plantId, $materialId]
        );

        return $result[0]->entryNo ?? null;
    }

    public function getActiveTanksForTransfer(?int $materialId, $plantId): array
    {
        if ($materialId === null || $materialId === 0) {
            return DB::connection('eudr_ts')->select(
                'SELECT b.id_sloc AS tf_number, b.description AS tank
                   FROM m_sloc b
                  WHERE b.status = 1
                    AND b.id_plant <> ?
                  GROUP BY b.id_sloc
                  ORDER BY b.description ASC',
                [$plantId]
            );
        }

        return DB::connection('eudr_ts')->select(
            'SELECT b.id_sloc AS tf_number, b.description AS tank
               FROM m_material a
               LEFT JOIN m_sloc b
                 ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
              WHERE a.status = 1
                AND a.id_material = ?
              GROUP BY b.id_sloc',
            [$plantId, $materialId]
        );
    }

    public function getActiveSpecificTanksRundown(int $sloc): array
    {
        $tank = DB::connection('eudr_ts')->select(
            'SELECT code_3, id_plant, description FROM m_sloc WHERE id_sloc = ?',
            [$sloc]
        );

        if (empty($tank)) {
            return [];
        }

        $code3   = $tank[0]->code_3 ?? '';
        $plantId = $tank[0]->id_plant ?? '';
        $desc    = $tank[0]->description ?? '';

        if (!empty($code3)) {
            return DB::connection('eudr_ts')->select(
                'SELECT a.id_sloc AS id_sloc_tail, a.tf_number AS tankNo, a.description, a.code_3
                   FROM m_sloc a
                  WHERE a.status = 1
                    AND a.code_3 = ?
                    AND a.id_plant = ?
                  ORDER BY a.description ASC',
                [$code3, $plantId]
            );
        }

        return DB::connection('eudr_ts')->select(
            'SELECT a.id_sloc AS id_sloc_tail, a.tf_number AS tankNo, a.description, a.code_3
               FROM m_sloc a
              WHERE a.status = 1
                AND a.description = ?
                AND a.id_plant = ?
              ORDER BY a.description ASC',
            [$desc, $plantId]
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
