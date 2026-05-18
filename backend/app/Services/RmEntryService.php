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

    /**
     * Generate new RM entry number
     */
    public function generateRmNumber($plantId)
    {
        $result = DB::connection('eudr_ts')->select(
            'SELECT a.rm_number
               FROM (SELECT a.trace_no+1 AS rm_number
                       FROM t_balance_header a
                      WHERE SUBSTRING(a.trace_no,1,7) = CONCAT("1", DATE_FORMAT(CURDATE(), "%y%m%d"))
                        AND SUBSTRING(a.trace_no,8,3) = ?
                        AND a.status = 1 
                        AND a.id_plant = ?
                      ORDER BY a.id_balance_head DESC
                      LIMIT 1) a
             UNION ALL
            SELECT CONCAT("1", DATE_FORMAT(CURDATE(), "%y%m%d"), ?, LPAD(RIGHT(?, 2), 2, "0"), "01") AS rm_number
             LIMIT 1',
            [$this->movSeq, $plantId, $this->movSeq, $plantId]
        );

        return $result[0]->rm_number ?? null;
    }

    /**
     * Generate transfer number for RM (Starts with 1, like RM entry)
     */
    public function generateTransferNumber($plantId)
    {
        // Logic follow monorepo get_rmNewEntryNumberTrf
        $idTankSrc = "T000"; // STORAGE
        $result = DB::connection('eudr_ts')->select(
            'SELECT CONCAT(SUBSTRING(a.rm_number,1,7), ?, SUBSTRING(a.rm_number,11,4)) + 1 AS rm_number
               FROM (SELECT a.trace_no AS rm_number
                       FROM t_balance_header a
                      WHERE SUBSTRING(a.trace_no,1,7) = CONCAT("1", DATE_FORMAT(CURDATE(), "%y%m%d"))
                        AND SUBSTRING(a.trace_no,2,9) = CONCAT(DATE_FORMAT(CURDATE(), "%y%m%d"), ?)
                        AND a.status = 1 
                        AND a.id_plant = ?
                      ORDER BY a.id_balance_head DESC
                      LIMIT 1 ) a
             UNION ALL
            SELECT CONCAT("1", DATE_FORMAT(CURDATE(), "%y%m%d"), ?, LPAD(RIGHT(?, 2), 2, "0"), "01") AS rm_number
             LIMIT 1',
            [substr($idTankSrc,1,3), "000", $plantId, substr($idTankSrc,1,3), $plantId]
        );

        return $result[0]->rm_number ?? null;
    }


    /**
     * Get RM entry list
     */
    public function getRmList($plantId)
    {
        $idTankStorage = Tank::where('status', 1)
            ->where('code_3', 'STORAGE')
            ->value('id_tank');

        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $query = "SELECT a.id_balance_head, a.id_material, a.id_tank, a.id_tank_tail, a.id_plant,
                         COALESCE(p.code_2, p.code_3, a.id_plant) AS plant_code,
                         COALESCE(p.description, p.code_2, a.id_plant) AS plant_name,
                         a.status,
                         CAST(a.trace_no AS CHAR) AS trace_no, 
                         FORMAT(SUM(DISTINCT a.qty),3) AS qty, 
                         a.created_by, a.created_at,
                         CONCAT(c.code, ' :: ', c.description) AS material, 
                         FORMAT(SUM(DISTINCT a.init_qty),3) AS init_qty,
                         CONCAT(d.description,
                            IF(GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ', ') IS NULL,
                                '',
                                CONCAT(' | ', GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ', '))
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
                    LEFT JOIN m_plant p
                      ON p.code_3 = a.id_plant
                    LEFT JOIN m_tank d
                      ON a.id_tank = d.id_tank AND d.status = 1 AND (d.code_3 = 'STORAGE' OR d.id_plant = ? OR ? = 0)
                    LEFT JOIN m_supplier e
                      ON e.id_supplier = b.id_supplier
                    LEFT JOIN (SELECT f.id_balance_head, g.material_document, g.po_so, f.id_trace_head
                                 FROM t_trace_header f
                                 LEFT JOIN t_material_document g
                                   ON f.id_trace_head = g.id_trace_head
                                WHERE f.status = 1
                                GROUP BY f.id_balance_head) f
                      ON f.id_balance_head = a.id_balance_head
                    LEFT JOIN m_tank_detail h
                      ON (JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(h.id_tank_tail AS CHAR)))
                           OR JSON_CONTAINS(a.id_tank_tail, CAST(h.id_tank_tail AS JSON)))
                    LEFT JOIN (
                        SELECT id_balance_head, SUM(init_qty) AS supplier_qty
                        FROM t_balance_detail
                        WHERE status = 1
                        GROUP BY id_balance_head
                    ) bs ON bs.id_balance_head = a.id_balance_head
                   WHERE c.type = ?
                     AND (SUBSTRING(a.trace_no,1,1) = ? OR SUBSTRING(a.trace_no,1,1) = ?)
                     AND SUBSTRING(a.trace_no,8,3) = ?
                     AND a.status = 1
                     AND (a.id_plant = ? OR ? = 0)
                     AND a.id_tank = ?
                   GROUP BY a.trace_no
                   ORDER BY a.id_balance_head DESC";

        return DB::connection('eudr_ts')->select($query, [
            $plantId, $plantId,
            $this->typeMaterial, $this->movType1, $this->movType2, $this->movSeq,
            $plantId, $plantId, $idTankStorage
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
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $entry_no = $data['rm_number'];
            $qty = floatval($data['total_qty']);

            // Fetch temporary suppliers
            $dat = DB::connection('eudr_ts')->select(
                'SELECT id_supplier, qty AS qty_tail, batch_sap
                   FROM t_balance_temporary
                  WHERE entry_no = ? AND status = 1',
                [$entry_no]
            );

            $supplierRows = [];
            foreach ($dat as $row) {
                if ($row->qty_tail <= 0) continue;
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
            $rundownResult = Rundown::generalRundown([
                'user' => $user,
                'entry_date' => $data['entry_date'],
                'from_trace_no' => null,
                'trace_no' => $entry_no,
                'id_material' => $data['id_material'],
                'id_tank' => $data['id_tank'],
                'id_tank_tail' => json_encode($data['id_tank_tail']),
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
            $idPlant = $data['id_plant'];

            $id_tankSourceNo_json = json_encode($id_tankSourceNo);
            $id_tankNo_json = json_encode($id_tankNo);

            $srcTankRec = DB::connection('eudr_ts')->select('SELECT code, code_3, id_plant FROM m_tank WHERE id_tank = ? AND status = 1 LIMIT 1', [$id_tankSource]);
            $tgtTankRec = DB::connection('eudr_ts')->select('SELECT code FROM m_tank WHERE id_tank = ? AND status = 1 LIMIT 1', [$id_tank]);
            
            if (empty($srcTankRec) || empty($tgtTankRec)) {
                throw new Exception('Invalid tank selection');
            }

            $targetTankCode = $tgtTankRec[0]->code;
            $isStorageTank = (strtoupper($srcTankRec[0]->code_3) === 'STORAGE');

            $datTempMaterial = DB::connection('eudr_ts')->select(
                'SELECT entry_no, id_tank, id_material, qty
                   FROM t_balance_temporary
                  WHERE status = 1 AND entry_no = ?',
                [$entry_no]
            );

            if (empty($datTempMaterial)) {
                throw new Exception('No temporary material data found');
            }

            foreach ($datTempMaterial as $row) {
                $id_material = $row->id_material;
                $out_qty = floatval($row->qty);

                // Create entry numbers following the monorepo logic
                $batchTrf_id = substr($targetTankCode, 1, 3);
                $batchFeed_id = "000";
                $batch_moveType = substr($entry_no, 0, 1);
                $batch_entryDate = substr($entry_no, 1, 6);
                $batch_idPlant = substr($entry_no, 10, 2);
                $batch_sequence = substr($entry_no, -2);

                $entryTrfNo_in = $batch_moveType . $batch_entryDate . $batchTrf_id . $batch_idPlant . $batch_sequence;
                $entryFeedNo_in = $batch_moveType . $batch_entryDate . $batchFeed_id . $batch_idPlant . $batch_sequence;

                // Execute Feed (Deduct from Storage)
                $feedResult = Feed::generalFeed([
                    'user' => $user,
                    'entry_date' => $curr_entryDate,
                    'id_material' => $id_material,
                    'id_tank' => $id_tankSource,
                    'id_tank_tail' => $id_tankSourceNo_json,
                    'id_plant' => $isStorageTank ? 0 : $idPlant,
                    'qty' => $out_qty,
                    'to_trace_no' => $entryTrfNo_in,
                ]);

                if ($feedResult['response'] != 1) {
                    throw new Exception('Feed failed: ' . ($feedResult['response'] == 3 ? 'Insufficient stock' : 'Unknown error'));
                }

                // Execute Rundown for each used head (Add to Feed)
                foreach ($feedResult['used_heads'] as $used) {
                    $in_qty = $used['qty_used'];

                    $supplierRows = [];
                    foreach ($feedResult['feed_in_details'] as $d) {
                        if ($d['qty'] <= 0) continue;
                        $supplierRows[] = [
                            'id_supplier' => $d['id_supplier'],
                            'batch_sap' => $d['batch_sap'],
                            'rundownSupplier' => round((float)$d['qty'], 4),
                        ];
                    }

                    Rundown::adjustRundownToTotal($supplierRows, $in_qty);

                    $rundownResult = Rundown::generalRundown([
                        'user' => $user,
                        'entry_date' => $curr_entryDate,
                        'trace_no' => $entryFeedNo_in,
                        'from_trace_no' => $entryTrfNo_in,
                        'id_material' => $id_material,
                        'id_tank' => $id_tank,
                        'id_tank_tail' => $id_tankNo_json,
                        'id_plant' => $idPlant,
                        'in_qty' => $in_qty,
                        'last_qtf' => 0,
                        'curr_qtf' => $in_qty,
                        'supplier_rows' => $supplierRows,
                    ]);

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
