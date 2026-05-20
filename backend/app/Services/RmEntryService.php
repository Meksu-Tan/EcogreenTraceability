<?php

namespace App\Services;

use App\Models\BalanceHeader;
use App\Models\BalanceDetail;
use App\Models\BalanceTemporary;
use App\Models\TraceHeader;
use App\Models\TraceDetail;
use App\Models\MaterialDocument;
use App\Models\Tank;
use App\Models\Material;
use App\Models\Supplier;
use App\Helpers\Rundown;
use App\Helpers\Feed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RmEntryService
{
    protected $movSeq = '000';
    protected $movType1 = '1';
    protected $movType2 = '9';
    protected $typeMaterial = 'RM';
    protected $idTankSrc = "T000"; // STORAGE TANK

    protected function resolvePlantCode($plantId)
    {
        if ($plantId) {
            $plant = \App\Models\Plant::find($plantId);
            if ($plant && $plant->code_3) {
                return $plant->code_3;
            }
        }
        return $plantId;
    }

    /**
     * Trace numbers are stored as bigint — must be digits only (14 chars).
     * Layout: [section 1][yymmdd 6][warehouse 3][plant 2][sequence 2]
     * Section: 1=storage, 3=feed
     * Warehouse: 000 for both storage & feed
     */
    protected function buildTraceNo(string $section, string $entryDate, string $warehouse, string $plantCode, int $sequence): string
    {
        $trace = $section
            . str_pad(substr($entryDate, 0, 6), 6, '0', STR_PAD_LEFT)
            . str_pad(substr(preg_replace('/\D/', '', $warehouse) ?: '000', 0, 3), 3, '0', STR_PAD_LEFT)
            . str_pad(substr(preg_replace('/\D/', '', $plantCode) ?: '0', -2, 2), 2, '0', STR_PAD_LEFT)
            . str_pad((string) max(1, min(99, $sequence)), 2, '0', STR_PAD_LEFT);

        return preg_replace('/\D/', '', $trace);
    }

    protected function traceNoToInt(string $traceNo): int
    {
        $digits = preg_replace('/\D/', '', $traceNo);

        return (int) ($digits !== '' ? $digits : 0);
    }

    /**
     * Generate new RM entry number - storage section (1)
     */
    public function generateRmNumber($plantId)
    {
        $resolvedPlantId = $this->resolvePlantCode($plantId);
        $warehouse = '000'; // Fixed warehouse for storage
        $section = '1'; // Storage section
        
        // For storage tank log, always use plant code 00 (not dependent on plant)
        $tracePlantCode = '00';
        
        // Get the highest sequence number for today to ensure uniqueness
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
        
        // Format: section(1) + yymmdd + warehouse(000) + plant(00) + sequence(2)
        $rmNumber = $this->buildTraceNo($section, date("ymd"), $warehouse, $tracePlantCode, $newSeq);

        return $rmNumber;
    }

    /**
     * Generate transfer number for RM - feed section (3)
     */
    public function generateTransferNumber($plantId)
    {
        $resolvedPlantId = $this->resolvePlantCode($plantId);
        $warehouse = '000'; // Fixed warehouse for feed
        $section = '3'; // Feed section
        
        // For feed transfer from storage without plant, use plant code 00 in trace number
        $tracePlantCode = ($resolvedPlantId == 0 || $resolvedPlantId == '0') ? '00' : str_pad(substr($resolvedPlantId, -2), 2, '0', STR_PAD_LEFT);
        
        // Get the highest sequence number for today to ensure uniqueness
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
        
        // Format: section(3) + yymmdd + warehouse(000) + plant(2) + sequence(2)
        $transferNumber = $this->buildTraceNo($section, date("ymd"), $warehouse, $tracePlantCode, $newSeq);

        return $transferNumber;
    }


    /**
     * Get RM entry list
     */
    public function getRmList($plantId)
    {
        $resolvedPlant = $this->resolvePlantCode($plantId);
        $allPlants = $resolvedPlant === null
            || $resolvedPlant === ''
            || $resolvedPlant === 0
            || $resolvedPlant === '0';

        $tankQuery = Tank::where('status', 1)
            ->where('description', 'like', '%STORAGE%');

        if (!$allPlants) {
            $tankQuery->where('plant_code', $resolvedPlant);
        }

        $idTankStorageIds = $tankQuery->pluck('id_sloc')->toArray();

        if (empty($idTankStorageIds)) {
            $idTankStorageIds = [0];
        }

        $inClause = implode(',', array_map('intval', $idTankStorageIds));
        $filterPlant = $allPlants ? 0 : $resolvedPlant;

        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $query = "SELECT a.id_balance_head, a.id_material, COALESCE(a.id_sloc, a.id_tank) AS id_tank, COALESCE(a.id_sloc_tail, a.id_tank_tail) AS id_tank_tail, a.status,
                         CAST(a.trace_no AS CHAR) AS trace_no, 
                         FORMAT(a.qty,3) AS qty, 
                         a.created_by, a.created_at,
                         COALESCE(
                            pl.description,
                            CAST(a.id_plant AS CHAR),
                            CAST(d.plant_code AS CHAR),
                            '-'
                         ) COLLATE utf8mb4_unicode_ci AS plant_code,
                         CONCAT(c.code, ' :: ', c.description) AS material, 
                         FORMAT(a.init_qty,3) AS init_qty,
                         CONCAT(d.description,
                            IF(
                                COALESCE(
                                    GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ' & '),
                                    d.tank_number
                                ) IS NULL,
                                '',
                                CONCAT(' | ', COALESCE(
                                    GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ' & '),
                                    d.tank_number
                                ))
                            )
                         ) AS tf_number, 
                         a.entry_date, b.batch_sap,
                         GROUP_CONCAT(DISTINCT b.id_balance_tail SEPARATOR ',') AS id_balance_detail,
                         GROUP_CONCAT(DISTINCT CONCAT(e.code, ' :: ', e.description, ' / ', b.batch_sap, ' / Qty : ', FORMAT(b.init_qty, 3), ' MT / ', IF(b.out_qty = 0, '-', 'BATCH TRANSFERRED')) SEPARATOR ' | ') AS supplier,
                         IF(b.out_qty = 0, 'N/A', '') AS traced, 
                         f.material_document, f.po_so, f.id_trace_head,
                         FORMAT(bs.supplier_qty,3) AS balance_supplier
                    FROM t_balance_header a
                    LEFT JOIN t_balance_detail b
                      ON a.id_balance_head = b.id_balance_head AND b.status = 1
                    LEFT JOIN m_material c
                      ON a.id_material = c.id_material
                    LEFT JOIN m_sloc d
                      ON COALESCE(a.id_sloc, a.id_tank) = d.id_sloc AND d.status = 1
                      AND (
                        d.description LIKE '%STORAGE%'
                        OR CAST(d.plant_code AS CHAR) COLLATE utf8mb4_unicode_ci = CAST(? AS CHAR) COLLATE utf8mb4_unicode_ci
                        OR ? = 0
                      )
                    LEFT JOIN m_plant pl
                      ON pl.status = 1
                      AND (
                        pl.code_3 COLLATE utf8mb4_unicode_ci = CAST(a.id_plant AS CHAR) COLLATE utf8mb4_unicode_ci
                        OR pl.code_3 COLLATE utf8mb4_unicode_ci = CAST(d.plant_code AS CHAR) COLLATE utf8mb4_unicode_ci
                      )
                    LEFT JOIN m_supplier e
                      ON e.id_supplier = b.id_supplier
                    LEFT JOIN (SELECT f.id_balance_head, g.material_document, g.po_so, f.id_trace_head
                                 FROM t_trace_header f
                                 LEFT JOIN t_material_document g
                                   ON f.id_trace_head = g.id_trace_head
                                WHERE f.status = 1
                                GROUP BY f.id_balance_head) f
                      ON f.id_balance_head = a.id_balance_head
                    LEFT JOIN m_sloc_detail h
                      ON JSON_CONTAINS(COALESCE(a.id_sloc_tail, a.id_tank_tail), JSON_QUOTE(CAST(h.id_sloc_tail AS CHAR)))
                    LEFT JOIN (
                        SELECT id_balance_head, SUM(init_qty) AS supplier_qty
                        FROM t_balance_detail
                        WHERE status = 1
                        GROUP BY id_balance_head
                    ) bs ON bs.id_balance_head = a.id_balance_head
                   WHERE c.type = ?
                     AND SUBSTRING(a.trace_no,1,1) = ? -- Storage section (1)
                     AND SUBSTRING(a.trace_no,8,3) = ? -- Warehouse (000)
                     AND a.status = 1
                     AND (a.id_plant = ? OR ? = 0)
                     AND COALESCE(a.id_sloc, a.id_tank) IN ($inClause)
                   GROUP BY a.id_balance_head
                   ORDER BY a.id_balance_head DESC";

        return DB::connection('eudr_ts')->select($query, [
            $filterPlant, $filterPlant,
            $this->typeMaterial, '1', '000', // Storage section (1), Warehouse (000)
            $filterPlant, $filterPlant
        ]);
    }

    /**
     * Generate batch code for supplier
     */
    public function generateBatchCode($supplierId)
    {
        $datSeq = DB::connection('eudr_ts')->select(
            'SELECT a.seq_no
               FROM (SELECT LPAD(SUBSTRING(a.batch_sap,7,2) + 1, 2,0) AS seq_no
                       FROM t_balance_detail a
                       LEFT JOIN t_balance_header b
                         ON a.id_balance_head = b.id_balance_head
                      WHERE a.status = 1
                        AND SUBSTRING(a.batch_sap,1,6) = DATE_FORMAT(NOW(), "%y%m%d")
                        AND SUBSTRING(b.trace_no,1,1) = 1
                      ORDER BY SUBSTRING(a.batch_sap,1,8) DESC
                      LIMIT 1) a
               UNION ALL
             SELECT "01" AS seq_no
               LIMIT 1'
        );
        $seqNo = $datSeq[0]->seq_no;

        $result = DB::connection('eudr_ts')->select(
            'SELECT CONCAT(DATE_FORMAT(NOW(), "%y%m%d"),?,"-",UCASE(a.batch_code)) AS batchCode
               FROM m_supplier a
              WHERE a.status = 1
                AND a.id_supplier = ?',
            [$seqNo, $supplierId]
        );

        return $result[0]->batchCode ?? null;
    }

    /**
     * Save RM Entry
     */
    public function saveRmEntry($data, $user)
    {
        $data['id_plant'] = $this->resolvePlantCode($data['id_plant'] ?? 0);
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $entry_no = $data['rm_number'];
            $qty = floatval($data['total_qty']);

            // Fetch temporary suppliers (remove material filter to ensure stock synchronization)
            $dat = DB::connection('eudr_ts')->select(
                'SELECT id_supplier, qty AS qty_tail, batch_sap, id_material
                   FROM t_balance_temporary
                  WHERE entry_no = ? AND status = 1',
                [$entry_no]
            );

            $supplierRows = [];
            foreach ($dat as $row) {
                if ($row->qty_tail <= 0) continue;
                if (empty($row->id_supplier)) continue; // skip non-supplier rows
                $supplierRows[] = [
                    'id_supplier' => $row->id_supplier,
                    'batch_sap' => $row->batch_sap,
                    'rundownSupplier' => round((float)$row->qty_tail, 4),
                ];
            }

            if (empty($supplierRows)) {
                throw new Exception('No supplier data found for this entry');
            }

            // Adjust rundown to total
            Rundown::adjustRundownToTotal($supplierRows, $qty);

            // Execute Rundown
            $tankTailJson = !empty($data['id_tank_tail']) ? json_encode(array_values($data['id_tank_tail'])) : null;
            $rundownResult = Rundown::generalRundown([
                'user' => $user,
                'entry_date' => $data['entry_date'],
                'from_trace_no' => null,
                'trace_no' => $entry_no,
                'id_material' => $data['id_material'],
                'id_tank' => $data['id_tank'],
                'id_tank_tail' => $tankTailJson,
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

            // Create Material Document
            if (!empty($data['material_document'])) {
                MaterialDocument::create([
                    'id_trace_head' => $idTraceHead,
                    'material_document' => $data['material_document'],
                    'po_so' => $data['po_so'] ?? null,
                    'created_by' => $user,
                ]);
            }

            // Clear temporary records
            BalanceTemporary::where('entry_no', $entry_no)
                ->update(['status' => 0, 'updated_by' => $user]);

            // Log transaction
            DB::connection('eudr_ts')->table('log_transactions')->insert([
                'log_module' => 'RM_ENTRY',
                'log_type' => 'ADD',
                'log_description' => 'ID: ' . $rundownResult['id_balance_head'] . ' | Trace No: ' . $entry_no,
                'created_by' => $user,
                'created_at' => now(),
            ]);

            DB::connection('eudr_ts')->commit();

            return ['success' => true, 'id' => $rundownResult['id_balance_head']];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            Log::error('RM Entry Save Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save RM Transfer to Feed Tank
     */
    public function saveRmTrfEntry($data, $user)
    {
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $entry_no = $data['entry_no'];
            $curr_entryDate = $data['entry_date'];
            $id_tankSource = $data['source_tank'];
            $id_tank = $data['trf_tank'];
            $materialDoc = $data['material_document'] ?? null;
            $id_tankSourceNo = $data['tank_no']; // Array
            $id_tankNo = $data['trf_tank_no']; // Array
            $idPlant = $this->resolvePlantCode($data['id_plant'] ?? 0);

            $id_tankSourceNo_json = !empty($id_tankSourceNo) ? json_encode(array_values($id_tankSourceNo)) : null;
            $id_tankNo_json = !empty($id_tankNo) ? json_encode(array_values($id_tankNo)) : null;

            $srcTankRec = DB::connection('eudr_ts')->select('SELECT tank_number as code, description, plant_code as id_plant FROM m_sloc WHERE id_sloc = ? AND status = 1 LIMIT 1', [$id_tankSource]);
            $tgtTankRec = DB::connection('eudr_ts')->select('SELECT tank_number as code FROM m_sloc WHERE id_sloc = ? AND status = 1 LIMIT 1', [$id_tank]);
            
            if (empty($srcTankRec) || empty($tgtTankRec)) {
                throw new Exception('Invalid tank selection');
            }

            $targetTankCode = $tgtTankRec[0]->code;
            $isStorageTank = (str_contains(strtoupper($srcTankRec[0]->description), 'STORAGE'));
            $balancePlant = $idPlant;
            if (!$balancePlant && !empty($srcTankRec[0]->id_plant)) {
                $balancePlant = $srcTankRec[0]->id_plant;
            }
            $datTempMaterial = DB::connection('eudr_ts')->select(
                'SELECT entry_no, id_tank, id_material, qty
                   FROM t_balance_temporary
                  WHERE status = 1 AND entry_no = ?',
                [$entry_no]
            );

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
                $out_qty = floatval($row->qty);

                $feedParams = [
                    'id_material' => $id_material,
                    'id_tank' => $id_tankSource,
                    'id_tank_tail' => $id_tankSourceNo_json,
                    'balance_plant' => $balancePlant,
                    'trace_prefixes' => ['1'], // Only storage section (1) for FIFO
                    'tank_matching' => 'flexible', // Use flexible matching for FIFO with parameter adjustment
                ];

                $availableQty = Feed::getAvailableQty($feedParams);
                if (round($availableQty, 4) < round($out_qty, 4)) {
                    $material = Material::find($id_material);
                    $matLabel = $material ? ($material->code . ' :: ' . $material->description) : (string) $id_material;
                    
                    // Check if this is a temporary entry issue
                    $tempCheck = DB::connection('eudr_ts')->select(
                        'SELECT COUNT(*) as count FROM t_balance_temporary 
                         WHERE entry_no = ? AND status = 1 AND id_material = ? AND qty > 0',
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

                // Trace layout: section(1)+yymmdd(6)+warehouse(3)+plant(2)+seq(2); storage=1, feed=3, warehouse=000
                $entryTrfNo_in = $this->buildTraceNo('1', $batch_entryDate, '000', $batch_idPlant, $batch_sequence);

                // Execute Feed (Deduct on-hand from storage — init_qty tidak diubah)
                $feedResult = Feed::generalFeed(array_merge($feedParams, [
                    'user' => $user,
                    'entry_date' => $curr_entryDate,
                    'id_plant' => $isStorageTank ? 0 : $idPlant,
                    'qty' => $out_qty,
                    'to_trace_no' => $this->traceNoToInt($entryTrfNo_in),
                    'tank_matching' => 'flexible', // Ensure flexible matching is passed through
                ]));

                if ($feedResult['response'] != 1) {
                    throw new Exception('Feed failed: ' . ($feedResult['response'] == 3 ? 'Insufficient stock' : 'Unknown error'));
                }

                // Execute Rundown for each used head (Add to Feed)
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
                        'id_tank' => $id_tank,
                        'id_tank_tail' => $id_tankNo_json,
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

            // Clear temporary records
            DB::connection('eudr_ts')->table('t_balance_temporary')
                ->where('entry_no', $entry_no)
                ->update(['status' => 0, 'updated_by' => $user]);

            // Log
            DB::connection('eudr_ts')->table('log_transactions')->insert([
                'log_module' => 'RMTRF_ENTRY',
                'log_type' => 'ADD',
                'log_description' => 'Transfer to Feed Tank | Entry No: ' . $entry_no,
                'created_by' => $user,
                'created_at' => now(),
            ]);

            DB::connection('eudr_ts')->commit();

            return ['success' => true];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            Log::error('RM Transfer Save Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Add supplier to temporary
     */
    public function addSupplierTemp($data, $user)
    {
        return BalanceTemporary::create([
            'entry_no' => $data['entry_no'],
            'id_supplier' => $data['id_supplier'] ?? null,
            'id_material' => $data['id_material'],
            'id_plant' => $data['id_plant'],
            'qty' => $data['qty'],
            'id_tank' => $data['id_tank'] ?? null,
            'batch_sap' => $data['batch_sap'] ?? null,
            'status' => 1,
            'created_by' => $user,
        ]);
    }

    /**
     * Get supplier list from temporary
     */
    public function getSupplierList($entryNo)
    {
        return BalanceTemporary::with(['supplier', 'material'])
            ->where('entry_no', $entryNo)
            ->where('status', 1)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id_balance_temp,
                    'supplier' => $item->supplier ? ($item->supplier->code . ' :: ' . $item->supplier->description) : 'N/A',
                    'material' => $item->material->code,
                    'batch_sap' => $item->batch_sap ?? 'N/A',
                    'qty' => number_format($item->qty, 3),
                ];
            });
    }

    /**
     * Delete supplier from temporary
     */
    public function deleteSupplierTemp($id, $user)
    {
        $temp = BalanceTemporary::findOrFail($id);
        $temp->update(['status' => 0, 'updated_by' => $user]);
        return true;
    }

    /**
     * Get total qty from temporary
     */
    public function getTotalQtyTemp($entryNo)
    {
        return BalanceTemporary::where('entry_no', $entryNo)
            ->where('status', 1)
            ->sum('qty');
    }

    /**
     * Verify that RM Entry creates separate balance headers
     */
    public function verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack = 24)
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

    /**
     * Check stock synchronization status
     */
    public function checkStockSynchronization($entryNo, $materialId = null)
    {
        try {
            // Check temporary records
            $tempQuery = 'SELECT COUNT(*) as temp_count, SUM(qty) as temp_qty 
                          FROM t_balance_temporary 
                          WHERE entry_no = ? AND status = 1';
            $tempParams = [$entryNo];
            
            if ($materialId) {
                $tempQuery .= ' AND id_material = ?';
                $tempParams[] = $materialId;
            }
            
            $tempData = DB::connection('eudr_ts')->select($tempQuery, $tempParams);
            $tempCount = $tempData[0]->temp_count ?? 0;
            $tempQty = $tempData[0]->temp_qty ?? 0;
            
            // Check if RM entry is already processed
            $balanceCheck = DB::connection('eudr_ts')->select(
                'SELECT COUNT(*) as balance_count, SUM(qty) as balance_qty 
                 FROM t_balance_header 
                 WHERE trace_no = ? AND status = 1',
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
        } catch (Exception $e) {
            Log::error('Stock Sync Check Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deactivate RM Entry
     */
    public function deactivateRmEntry($id, $user)
    {
        DB::connection('eudr_ts')->beginTransaction();

        try {
            // Check if used
            $used = TraceHeader::where('id_balance_head', $id)
                ->where('out_qty', '!=', 0)
                ->where('status', 1)
                ->count();

            if ($used > 0) {
                throw new Exception('RM Entry has been used and cannot be deactivated');
            }

            // Deactivate
            BalanceHeader::where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            BalanceDetail::where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            $traceHead = TraceHeader::where('id_balance_head', $id)
                ->where('status', 1)
                ->first();

            if ($traceHead) {
                $traceHead->update(['status' => 0, 'updated_by' => $user]);

                TraceDetail::where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);
            }

            // Log
            DB::connection('eudr_ts')->table('log_transactions')->insert([
                'log_module' => 'RM_ENTRY',
                'log_type' => 'DEACTIVATE',
                'log_description' => 'ID: ' . $id,
                'created_by' => $user,
                'created_at' => now(),
            ]);

            DB::connection('eudr_ts')->commit();

            return ['success' => true];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            Log::error('RM Entry Deactivate Error: ' . $e->getMessage());
            throw $e;
        }
    }
}

