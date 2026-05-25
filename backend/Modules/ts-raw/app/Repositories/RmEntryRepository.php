<?php

namespace Modules\TsRaw\Repositories;

use Modules\TsRaw\Repositories\Contracts\RmEntryRepositoryInterface;
use Modules\TsRaw\Models\BalanceHeader;
use Modules\TsRaw\Models\BalanceDetail;
use Modules\TsRaw\Models\BalanceTemporary;
use Modules\TsRaw\Models\TraceHeader;
use Modules\TsRaw\Models\TraceDetail;
use Modules\TsRaw\Models\MaterialDocument;
use Modules\Material\Models\Material;
use Modules\Supplier\Models\Supplier;
use Modules\Plant\Models\Plant;
use Modules\Shared\Helpers\Rundown;
use Modules\Shared\Helpers\Feed;
use Illuminate\Support\Facades\DB;
use Exception;

class RmEntryRepository implements RmEntryRepositoryInterface
{
    protected $movSeq = '000';
    protected $movType1 = '1';
    protected $movType2 = '9';
    protected $typeMaterial = 'RM';
    protected $idTankSrc = "T000";

    public function getRmList($plantId): array
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

        // Map to id_sloc (main identifier for storage tanks)
        $idTankStorageIds = array_map(function($s) {
            return $s->id_sloc;
        }, array_values($slocRows));

        if (empty($idTankStorageIds)) {
            // Try m_sloc table as fallback
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

        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        // Main RM List Query - use subquery approach for unique trace_no
        // First get distinct trace_no rows, then join other tables
        $query = "SELECT
                    bh.id_balance_head, bh.id_material, COALESCE(bh.id_sloc, bh.id_tank) AS id_tank,
                    COALESCE(bh.id_sloc_tail, bh.id_tank_tail) AS id_tank_tail, bh.status,
                    CAST(bh.trace_no AS CHAR) AS trace_no,
                    bh.qty,
                    bh.created_by, bh.created_at,
                    CONCAT(m.code, ' :: ', m.description) AS material,
                    bh.init_qty,
                    CONCAT(sl.description,
                        IF(
                            COALESCE(bh.id_sloc_tail, bh.id_tank_tail) IS NOT NULL
                            AND COALESCE(bh.id_sloc_tail, bh.id_tank_tail) != ''
                            AND COALESCE(bh.id_sloc_tail, bh.id_tank_tail) != '[]',
                            CONCAT(' | ',
                                COALESCE(
                                    (
                                        SELECT GROUP_CONCAT(h.tf_number ORDER BY h.tf_number ASC SEPARATOR ' & ')
                                        FROM m_sloc_detail h
                                        WHERE FIND_IN_SET(h.id_sloc_tail, REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(bh.id_sloc_tail, bh.id_tank_tail), '[', ''), ']', ''), '\"', ''), ' ', '')) > 0
                                          AND h.status = 1
                                    ),
                                    (
                                        SELECT GROUP_CONCAT(h.id_tank ORDER BY h.id_tank ASC SEPARATOR ' & ')
                                        FROM m_sloc h
                                        WHERE FIND_IN_SET(h.id_sloc, REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(bh.id_sloc_tail, bh.id_tank_tail), '[', ''), ']', ''), '\"', ''), ' ', '')) > 0
                                          AND h.status = 1
                                    ),
                                    COALESCE(bh.id_sloc_tail, bh.id_tank_tail)
                                )
                            ),
                            ''
                        )
                    ) AS tf_number,
                    bh.entry_date,
                    f.material_document, f.po_so, f.id_trace_head,
                    FORMAT(bs.supplier_qty,3) AS balance_supplier,
                    bh.id_plant, p.code AS plant_code
                    FROM (
                        SELECT id_balance_head, id_material, id_sloc, id_tank, id_sloc_tail, id_tank_tail,
                               status, trace_no, qty, init_qty, created_by, created_at, entry_date, id_plant
                        FROM t_balance_header
                        WHERE status = 1
                          AND SUBSTRING(trace_no,1,1) = '1'
                          AND SUBSTRING(trace_no,8,3) = '000'
                          AND (id_plant = ? OR ? = 0)
                          AND (id_sloc IN ($inClause) OR (id_sloc IS NULL AND id_tank IN (
                               SELECT mt.id_tank FROM m_tank mt 
                               JOIN m_sloc ms ON ms.code COLLATE utf8mb4_unicode_ci = mt.code COLLATE utf8mb4_unicode_ci 
                                             AND ms.id_plant COLLATE utf8mb4_unicode_ci = mt.id_plant COLLATE utf8mb4_unicode_ci
                               WHERE ms.id_sloc IN ($inClause)
                           )))
                        ORDER BY id_balance_head DESC
                    ) bh
                    INNER JOIN m_material m ON bh.id_material = m.id_material AND m.type = ?
                    LEFT JOIN m_sloc sl ON ((bh.id_sloc = sl.id_sloc) OR (bh.id_sloc IS NULL AND bh.id_tank = (SELECT mt.id_tank FROM m_tank mt WHERE mt.code COLLATE utf8mb4_unicode_ci = sl.code COLLATE utf8mb4_unicode_ci AND mt.id_plant COLLATE utf8mb4_unicode_ci = sl.id_plant COLLATE utf8mb4_unicode_ci LIMIT 1))) AND sl.status = 1
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
                    ORDER BY bh.id_balance_head DESC
                    LIMIT 100";

        $results = DB::connection('eudr_ts')->select($query, [
            $filterPlant, $filterPlant, $this->typeMaterial
        ]);

        // Get supplier details for each trace_no
        if (!empty($results)) {
            $traceNos = array_values(array_unique(array_column($results, 'trace_no')));
            $placeholders = implode(',', array_fill(0, count($traceNos), '?'));

            // Simplified supplier query without GROUP_CONCAT to avoid GROUP BY issues
            $supplierQuery = "SELECT bh.trace_no, bd.batch_sap, bd.init_qty, bd.out_qty,
                                   bd.id_balance_tail, sup.code, sup.description
                              FROM t_balance_detail bd
                              JOIN t_balance_header bh ON bd.id_balance_head = bh.id_balance_head
                              LEFT JOIN m_supplier sup ON bd.id_supplier = sup.id_supplier
                             WHERE bh.trace_no IN ($placeholders) AND bd.status = 1";

            $supplierDetails = DB::connection('eudr_ts')->select($supplierQuery, $traceNos);

            // Group by trace_no manually in PHP
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

            // Merge supplier details into results
            foreach ($results as &$r) {
                $traceKey = $r->trace_no;
                if (isset($supplierMap[$traceKey])) {
                    $r->supplier = implode(' | ', $supplierMap[$traceKey]['supplier']);
                    $r->id_balance_detail = implode(',', $supplierMap[$traceKey]['id_balance_detail']);
                } else {
                    $r->supplier = 'N/A';
                    $r->id_balance_detail = '';
                }
                // Calculate traced status based on out_qty in balance_detail
                $traceStatus = DB::connection('eudr_ts')->select(
                    "SELECT IF(SUM(td.out_qty) > 0, 'USED', 'N/A') AS traced
                     FROM t_trace_detail td
                     JOIN t_trace_header th ON td.id_trace_head = th.id_trace_head
                     JOIN t_balance_header bh ON th.id_balance_head = bh.id_balance_head
                     WHERE bh.trace_no = ? AND td.status = 1",
                    [$r->trace_no]
                );
                $r->traced = $traceStatus[0]->traced ?? 'N/A';
                // Format qty as string with thousand separators
                $r->qty = number_format($r->qty, 3);
                $r->init_qty = number_format($r->init_qty, 3);
                // Plant code lookup
                $plantRec = DB::connection('eudr_ts')->select(
                    "SELECT description FROM m_plant WHERE code_3 = ? AND status = 1 LIMIT 1",
                    [$r->id_plant]
                );
                $r->plant_code = $plantRec[0]->description ?? (string) $r->id_plant;
            }
            unset($r);
        }

        return $results;
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

        $query = "SELECT a.id_sloc_tail, a.id_sloc, a.tf_number, status,
                         b.qty_available, b.id_balance_head
                    FROM m_sloc_detail a
                    LEFT JOIN (
                        SELECT id_sloc_tail, SUM(qty) AS qty_available, MAX(id_balance_head) AS id_balance_head
                          FROM t_balance_header
                         WHERE status = 1
                           AND id_sloc_tail IS NOT NULL
                           AND id_sloc_tail != ''
                           AND id_sloc_tail != '[]'
                         GROUP BY id_sloc_tail
                    ) b ON b.id_sloc_tail = a.id_sloc_tail
                   WHERE a.status = 1
                     AND a.id_sloc = ?
                   ORDER BY a.tf_number";

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

    public function searchSuppliers(string $query): array
    {
        return DB::connection('eudr_ts')->select(
            "SELECT id_supplier, code, description
               FROM m_supplier
              WHERE status = '1'
                AND (code LIKE ? OR description LIKE ?)
              ORDER BY code
              LIMIT 20",
            ['%' . $query . '%', '%' . $query . '%']
        );
    }

    public function addSupplierTemp(array $data, string $user): object
    {
        $requiredKeys = ['entry_no', 'id_material', 'qty'];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new \Exception("Required field '{$key}' is missing");
            }
        }

        $insertData = [
            'entry_no' => $data['entry_no'],
            'id_supplier' => $data['id_supplier'] ?? null,
            'id_material' => $data['id_material'],
            'id_plant' => $data['id_plant'],
            'qty' => $data['qty'],
            'batch_sap' => $data['batch_sap'] ?? null,
            'status' => 1,
            'created_by' => $user,
        ];

        // Only add id_tank if it exists in the database table
        if (isset($data['id_tank'])) {
            $insertData['id_tank'] = $data['id_tank'];
        }

        $result = DB::connection('eudr_ts')->table('t_balance_temporary')->insertGetId($insertData);

        return (object) ['id_balance_temp' => $result];
    }

    public function getSupplierList(string $entryNo): array
    {
        $results = DB::connection('eudr_ts')->select(
            "SELECT a.id_balance_temp, a.id_supplier, a.id_material, a.batch_sap, a.qty,
                    b.code AS supplier_code, b.description AS supplier_name,
                    c.code AS material_code
               FROM t_balance_temporary a
               LEFT JOIN m_supplier b ON a.id_supplier = b.id_supplier
               LEFT JOIN m_material c ON a.id_material = c.id_material
              WHERE a.entry_no = ? AND a.status = 1
              ORDER BY a.id_balance_temp",
            [$entryNo]
        );

        return array_map(function ($item) {
            return [
                'id' => $item->id_balance_temp,
                'supplier' => $item->supplier_code ? ($item->supplier_code . ' :: ' . $item->supplier_name) : 'N/A',
                'material' => $item->material_code ?? 'N/A',
                'batch_sap' => $item->batch_sap ?? 'N/A',
                'qty' => number_format($item->qty, 3),
            ];
        }, $results);
    }

    public function deleteSupplierTemp(int $id, string $user): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_temporary')
            ->where('id_balance_temp', $id)
            ->update(['status' => 0, 'updated_by' => $user]);
    }

    public function getTotalQtyTemp(string $entryNo): float
    {
        $result = DB::connection('eudr_ts')->select(
            'SELECT COALESCE(SUM(qty), 0) AS total FROM t_balance_temporary WHERE entry_no = ? AND status = 1',
            [$entryNo]
        );
        return floatval($result[0]->total ?? 0);
    }

    public function generateBatchCode($supplierId): ?string
    {
        // Handle array from select2 or direct int
        if (is_array($supplierId)) {
            $supplierId = isset($supplierId['id']) ? (int) $supplierId['id'] : 0;
        } else {
            $supplierId = (int) $supplierId;
        }

        if (empty($supplierId)) {
            return null;
        }

        $datSeq = DB::connection('eudr_ts')->select(
            'SELECT a.seq_no
               FROM (SELECT LPAD(SUBSTRING(a.batch_sap,7,2) + 1, 2,0) AS seq_no
                       FROM t_balance_detail a
                       LEFT JOIN t_balance_header b ON a.id_balance_head = b.id_balance_head
                      WHERE a.status = 1
                        AND SUBSTRING(a.batch_sap,1,6) = DATE_FORMAT(NOW(), "%y%m%d")
                        AND SUBSTRING(b.trace_no,1,1) = 1
                      ORDER BY SUBSTRING(a.batch_sap,1,8) DESC
                      LIMIT 1) a
               UNION ALL SELECT "01" AS seq_no LIMIT 1',
            []
        );
        $seqNo = $datSeq[0]->seq_no ?? '01';

        $result = DB::connection('eudr_ts')->select(
            'SELECT CONCAT(DATE_FORMAT(NOW(), "%y%m%d"), ?, "-", UCASE(a.batch_code)) AS batchCode
               FROM m_supplier a
              WHERE a.status = 1 AND a.id_supplier = ?',
            [$seqNo, $supplierId]
        );

        return $result[0]->batchCode ?? null;
    }

    public function checkStockSynchronization(string $entryNo, int $materialId = null): array
    {
        $tempQuery = 'SELECT COUNT(*) as temp_count, SUM(qty) as temp_qty FROM t_balance_temporary WHERE entry_no = ? AND status = 1';
        $tempParams = [$entryNo];

        if ($materialId) {
            $tempQuery .= ' AND id_material = ?';
            $tempParams[] = $materialId;
        }

        $tempData = DB::connection('eudr_ts')->select($tempQuery, $tempParams);
        $tempCount = $tempData[0]->temp_count ?? 0;
        $tempQty = $tempData[0]->temp_qty ?? 0;

        $balanceCheck = DB::connection('eudr_ts')->select(
            'SELECT COUNT(*) as balance_count, SUM(qty) as balance_qty FROM t_balance_header WHERE trace_no = ? AND status = 1',
            [$entryNo]
        );
        $balanceCount = $balanceCheck[0]->balance_count ?? 0;
        $balanceQty = $balanceCheck[0]->balance_qty ?? 0;

        return [
            'has_temporary_data' => $tempCount > 0,
            'temporary_quantity' => floatval($tempQty),
            'has_balance_data' => $balanceCount > 0,
            'balance_quantity' => floatval($balanceQty),
            'is_synchronized' => $balanceCount > 0 && $tempCount == 0,
            'status' => $balanceCount > 0 ? 'processed' : ($tempCount > 0 ? 'pending' : 'no_data'),
            'message' => $balanceCount > 0 ? 'RM Entry has been processed and stock is synchronized' :
                        ($tempCount > 0 ? 'RM Entry has temporary data but not yet processed' : 'No data found for this entry')
        ];
    }

    public function debugFifoStock(array $params): array
    {
        return Feed::debugStock($params);
    }

    public function verifySeparateEntries(int $materialId, int $tankId, int $plantId, int $hoursBack = 24): array
    {
        $since = now()->subHours($hoursBack);

        $entries = DB::connection('eudr_ts')->select(
            'SELECT id_balance_head, trace_no, qty, init_qty, entry_date, created_at
               FROM t_balance_header
              WHERE id_material = ?
                AND COALESCE(id_sloc, id_tank) = ?
                AND id_plant = ?
                AND status = 1
                AND created_at >= ?
              ORDER BY id_balance_head ASC',
            [$materialId, $tankId, $plantId, $since]
        );

        return [
            'total_entries' => count($entries),
            'entries' => $entries,
            'total_qty' => array_sum(array_column($entries, 'qty')),
            'separate_entries_created' => count($entries) > 1,
            'parameters' => [
                'id_material' => $materialId,
                'id_tank' => $tankId,
                'id_plant' => $plantId,
                'hours_back' => $hoursBack
            ]
        ];
    }

    public function getTempData(string $entryNo): array
    {
        return DB::connection('eudr_ts')->select(
            'SELECT id_supplier, qty AS qty_tail, batch_sap, id_material
               FROM t_balance_temporary
              WHERE entry_no = ? AND status = 1',
            [$entryNo]
        );
    }

    public function clearTempData(string $entryNo, string $user): bool
    {
        return (bool) DB::connection('eudr_ts')->table('t_balance_temporary')
            ->where('entry_no', $entryNo)
            ->update(['status' => 0, 'updated_by' => $user]);
    }

    public function saveRmEntry(array $data, string $user): array
    {
        $data['id_plant'] = $this->resolvePlantCode($data['id_plant'] ?? 0);
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $entry_no = $data['rm_number'];
            $qty = floatval($data['total_qty']);

            $dat = $this->getTempData($entry_no);

            $supplierRows = [];
            foreach ($dat as $row) {
                if ($row->qty_tail <= 0) continue;
                if (empty($row->id_supplier)) continue;
                $supplierRows[] = [
                    'id_supplier' => $row->id_supplier,
                    'batch_sap' => $row->batch_sap,
                    'rundownSupplier' => round((float)$row->qty_tail, 4),
                ];
            }

            if (empty($supplierRows)) {
                throw new Exception('No supplier data found for this entry');
            }

            Rundown::adjustRundownToTotal($supplierRows, $qty);

            $tankTailVal = !empty($data['id_sloc_tail']) ? (is_array($data['id_sloc_tail']) ? json_encode(array_values($data['id_sloc_tail'])) : $data['id_sloc_tail']) : null;
            $rundownResult = Rundown::generalRundown([
                'user' => $user,
                'entry_date' => $data['entry_date'],
                'from_trace_no' => null,
                'trace_no' => $entry_no,
                'id_material' => $data['id_material'],
                'id_sloc' => $data['id_sloc'] ?? $data['id_tank'] ?? null,
                'id_sloc_tail' => $tankTailVal,
                'in_qty' => $qty,
                'last_qtf' => 0,
                'curr_qtf' => $qty,
                'id_plant' => $data['id_plant'],
                'supplier_rows' => $supplierRows,
            ]);

            if ($rundownResult['response'] != 1) {
                throw new Exception('Rundown failed');
            }

            $idTraceHead = $rundownResult['id_trace_head'];

            if (!empty($data['material_document'])) {
                DB::connection('eudr_ts')->table('t_material_document')->insert([
                    'id_trace_head' => $idTraceHead,
                    'material_document' => $data['material_document'],
                    'po_so' => $data['po_so'] ?? null,
                    'created_by' => $user,
                ]);
            }

            $this->clearTempData($entry_no, $user);

            $this->logTransaction('RM_ENTRY', 'ADD', 'ID: ' . $rundownResult['id_balance_head'] . ' | Trace No: ' . $entry_no, $user);

            DB::connection('eudr_ts')->commit();

            return ['success' => true, 'id' => $rundownResult['id_balance_head']];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            throw $e;
        }
    }

    public function saveRmTrfEntry(array $data, string $user): array
    {
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $entry_no = $data['entry_no'];
            $curr_entryDate = $data['entry_date'];
            $id_tankSource = $data['source_tank'];
            $id_tank = $data['trf_tank'];
            $materialDoc = $data['material_document'] ?? null;
            $id_tankSourceNo = $data['tank_no'] ?? [];
            $id_tankNo = $data['trf_tank_no'] ?? [];
            $idPlant = $this->resolvePlantCode($data['id_plant'] ?? 0);

            $id_tankSourceNo_val = !empty($id_tankSourceNo) ? (is_array($id_tankSourceNo) ? json_encode(array_values($id_tankSourceNo)) : $id_tankSourceNo) : null;
            $id_tankNo_val = !empty($id_tankNo) ? (is_array($id_tankNo) ? json_encode(array_values($id_tankNo)) : $id_tankNo) : null;

            $srcTankRec = DB::connection('eudr_ts')->select(
                'SELECT id_tank AS code, description, id_plant FROM m_sloc WHERE id_sloc = ? AND status = 1 LIMIT 1',
                [$id_tankSource]
            );
            $tgtTankRec = DB::connection('eudr_ts')->select(
                'SELECT id_tank AS code FROM m_sloc WHERE id_sloc = ? AND status = 1 LIMIT 1',
                [$id_tank]
            );

            if (empty($srcTankRec) || empty($tgtTankRec)) {
                throw new Exception('Invalid tank selection');
            }

            $targetTankCode = $tgtTankRec[0]->code;
            $isStorageTank = (str_contains(strtoupper($srcTankRec[0]->description), 'STORAGE'));
            $balancePlant = $idPlant;
            if (!$balancePlant && !empty($srcTankRec[0]->id_plant)) {
                $balancePlant = $srcTankRec[0]->id_plant;
            }

            $datTempMaterial = $this->getTempData($entry_no);

            if (empty($datTempMaterial)) {
                throw new Exception('No temporary material data found');
            }

            $batch_moveType = substr($entry_no, 0, 1);
            $batch_entryDate = substr($entry_no, 1, 6);
            $batch_idPlant = substr($entry_no, 10, 2);
            $batch_sequence = (int) substr($entry_no, -2);
            $feedSequence = $batch_sequence + 2;

            foreach ($datTempMaterial as $row) {
                $id_material = $row->id_material;
                $out_qty = floatval($row->qty_tail);

                $feedParams = [
                    'id_material' => $id_material,
                    'id_sloc' => $id_tankSource,
                    'id_sloc_tail' => $id_tankSourceNo_val,
                    'balance_plant' => $balancePlant,
                    'trace_prefixes' => ['1'],
                    'tank_matching' => 'flexible',
                ];

                $availableQty = Feed::getAvailableQty($feedParams);
                if (round($availableQty, 4) < round($out_qty, 4)) {
                    $material = Material::find($id_material);
                    $matLabel = $material ? ($material->code . ' :: ' . $material->description) : (string) $id_material;

                    $tempCheck = DB::connection('eudr_ts')->select(
                        'SELECT COUNT(*) as count FROM t_balance_temporary WHERE entry_no = ? AND status = 1 AND id_material = ? AND qty > 0',
                        [$entry_no, $id_material]
                    );

                    if ($tempCheck[0]->count > 0) {
                        throw new Exception(
                            'Stock synchronization issue detected. Material ' . $matLabel .
                            ' has temporary data but stock not updated. Available: ' . number_format($availableQty, 3) .
                            ' MT, requested: ' . number_format($out_qty, 3) . ' MT. Please complete RM Entry process first.'
                        );
                    }

                    throw new Exception(
                        'Insufficient stock for ' . $matLabel .
                        '. Available: ' . number_format($availableQty, 3) .
                        ' MT, requested: ' . number_format($out_qty, 3) . ' MT (FIFO sloc/sub-sloc/plant).'
                    );
                }

                $entryTrfNo_in = $this->buildTraceNo('1', $batch_entryDate, '000', $batch_idPlant, $batch_sequence);

                $feedResult = Feed::generalFeed(array_merge($feedParams, [
                    'user' => $user,
                    'entry_date' => $curr_entryDate,
                    'id_plant' => $isStorageTank ? 0 : $idPlant,
                    'qty' => $out_qty,
                    'to_trace_no' => $this->traceNoToInt($entryTrfNo_in),
                    'tank_matching' => 'flexible',
                ]));

                if ($feedResult['response'] != 1) {
                    throw new Exception('Feed failed: ' . ($feedResult['response'] == 3 ? 'Insufficient stock' : 'Unknown error'));
                }

                foreach ($feedResult['used_heads'] as $used) {
                    $entryFeedNo_in = $this->buildTraceNo('3', $batch_entryDate, '000', $batch_idPlant, $feedSequence);
                    $feedSequence += 2;

                    $in_qty = $used['qty_used'];
                    $headDetails = $used['feed_in_details'] ?? [];
                    if (empty($headDetails) && count($feedResult['used_heads']) === 1) {
                        $headDetails = $feedResult['feed_in_details'] ?? [];
                    }

                    $supplierRows = [];
                    foreach ($headDetails as $d) {
                        if (($d['qty'] ?? 0) <= 0) continue;
                        $supplierRows[] = [
                            'id_supplier' => $d['id_supplier'],
                            'batch_sap' => $d['batch_sap'],
                            'rundownSupplier' => round((float) $d['qty'], 4),
                        ];
                    }

                    if (empty($supplierRows)) {
                        throw new Exception(
                            'Supplier breakdown kosong untuk transfer ' . number_format($in_qty, 3) .
                            ' MT. Pastikan RM entry memiliki data supplier aktif.'
                        );
                    }

                    Rundown::adjustRundownToTotal($supplierRows, $in_qty);

                    $rundownResult = Rundown::generalRundown([
                        'user' => $user,
                        'entry_date' => $curr_entryDate,
                        'trace_no' => $this->traceNoToInt($entryFeedNo_in),
                        'from_trace_no' => $this->traceNoToInt($entryTrfNo_in),
                        'id_material' => $id_material,
                        'id_sloc' => $id_tank,
                        'id_sloc_tail' => $id_tankNo_val,
                        'id_plant' => $idPlant,
                        'in_qty' => $in_qty,
                        'last_qtf' => 0,
                        'curr_qtf' => $in_qty,
                        'supplier_rows' => $supplierRows,
                    ]);

                    if (($rundownResult['response'] ?? 0) != 1) {
                        throw new Exception('Rundown failed for feed tank');
                    }

                    if (!empty($materialDoc)) {
                        DB::connection('eudr_ts')->table('t_material_document')->insert([
                            'id_trace_head' => $rundownResult['id_trace_head'],
                            'material_document' => $materialDoc,
                            'created_by' => $user,
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            $this->clearTempData($entry_no, $user);

            $this->logTransaction('RMTRF_ENTRY', 'ADD', 'Transfer to Feed Tank | Entry No: ' . $entry_no, $user);

            DB::connection('eudr_ts')->commit();

            return ['success' => true];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            throw $e;
        }
    }

    public function deactivateRmEntry(int $id, string $user): array
    {
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $used = TraceHeader::where('id_balance_head', $id)
                ->where('out_qty', '!=', 0)
                ->where('status', 1)
                ->count();

            if ($used > 0) {
                throw new Exception('RM Entry has been used and cannot be deactivated');
            }

            DB::connection('eudr_ts')->table('t_balance_header')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            DB::connection('eudr_ts')->table('t_balance_detail')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            $traceHead = TraceHeader::where('id_balance_head', $id)
                ->where('status', 1)
                ->first();

            if ($traceHead) {
                DB::connection('eudr_ts')->table('t_trace_header')
                    ->where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);

                DB::connection('eudr_ts')->table('t_trace_detail')
                    ->where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);
            }

            $this->logTransaction('RM_ENTRY', 'DEACTIVATE', 'ID: ' . $id, $user);

            DB::connection('eudr_ts')->commit();

            return ['success' => true];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            throw $e;
        }
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

    /**
     * CRITICAL #6: Deactivate RM Entry created from Transfer
     * Restores balance header properly when cancelling transfer-created RM entries
     */
    public function deactivateRmEntryTrf(int $id, string $user): array
    {
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $head = DB::connection('eudr_ts')->selectOne(
                'SELECT trace_no FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
                [$id]
            );
            if (!$head) {
                throw new Exception('RM Entry not found');
            }

            $traceNo = $head->trace_no;

            $traceHead = DB::connection('eudr_ts')->selectOne(
                'SELECT id_trace_head, from_trace_no, out_qty FROM t_trace_header
                 WHERE from_trace_no = ? AND status = 1 LIMIT 1',
                [$traceNo]
            );

            if ($traceHead) {
                $sourceTraceNo = $traceHead->from_trace_no;
                $sourceTraceHead = DB::connection('eudr_ts')->selectOne(
                    'SELECT id_trace_head, id_balance_head FROM t_trace_header WHERE to_trace_no = ? AND status = 1 LIMIT 1',
                    [$sourceTraceNo]
                );

                if ($sourceTraceHead) {
                    $balanceHead = DB::connection('eudr_ts')->selectOne(
                        'SELECT id_balance_head, qty, out_qty FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
                        [$sourceTraceHead->id_balance_head]
                    );

                    if ($balanceHead) {
                        DB::connection('eudr_ts')->update(
                            'UPDATE t_balance_header SET qty = qty + ?, out_qty = out_qty - ?, updated_by = ? WHERE id_balance_head = ? AND status = 1',
                            [$traceHead->out_qty, $traceHead->out_qty, $user, $sourceTraceHead->id_balance_head]
                        );
                    }
                }
            }

            DB::connection('eudr_ts')->table('t_balance_header')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            DB::connection('eudr_ts')->table('t_balance_detail')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            $traceHead = DB::connection('eudr_ts')->selectOne(
                'SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ? AND status = 1 LIMIT 1',
                [$id]
            );

            if ($traceHead) {
                DB::connection('eudr_ts')->table('t_trace_header')
                    ->where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);

                DB::connection('eudr_ts')->table('t_trace_detail')
                    ->where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);
            }

            $this->logTransaction('RMTRF_ENTRY', 'DEACTIVATE', 'ID: ' . $id . ' | Trace: ' . $traceNo, $user);

            DB::connection('eudr_ts')->commit();
            return ['success' => true];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            throw $e;
        }
    }
}
