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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RmEntryService
{
    protected $movSeq = '000';
    protected $movType1 = '1';
    protected $movType2 = '9';
    protected $typeMaterial = 'RM';

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
     * Get RM entry list
     */
    public function getRmList($plantId)
    {
        $idTankStorage = Tank::where('status', 1)
            ->where('code_3', 'STORAGE')
            ->value('id_tank');

        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $query = "SELECT a.id_balance_head, a.id_material, a.id_tank, a.id_tank_tail, a.status,
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
                      ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(h.id_tank_tail AS CHAR)))
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
            // Validate total qty
            $totalTemp = BalanceTemporary::where('entry_no', $data['rm_number'])
                ->where('status', 1)
                ->sum('qty');

            if (abs($totalTemp - $data['total_qty']) > 0.001) {
                throw new Exception('Total quantity mismatch');
            }

            // Create Balance Header
            $balanceHeader = BalanceHeader::create([
                'entry_date' => $data['entry_date'],
                'trace_no' => $data['rm_number'],
                'id_material' => $data['id_material'],
                'id_tank' => $data['id_tank'],
                'id_tank_tail' => json_encode($data['id_tank_tail']),
                'id_plant' => $data['id_plant'],
                'qty' => $data['total_qty'],
                'in_qty' => $data['total_qty'],
                'out_qty' => 0,
                'init_qty' => $data['total_qty'],
                'status' => 1,
                'created_by' => $user,
            ]);

            // Create Balance Details from temporary
            $tempRecords = BalanceTemporary::where('entry_no', $data['rm_number'])
                ->where('status', 1)
                ->get();

            foreach ($tempRecords as $temp) {
                BalanceDetail::create([
                    'id_balance_head' => $balanceHeader->id_balance_head,
                    'id_supplier' => $temp->id_supplier,
                    'id_material' => $temp->id_material,
                    'batch_sap' => $temp->batch_sap,
                    'qty' => $temp->qty,
                    'in_qty' => $temp->qty,
                    'out_qty' => 0,
                    'init_qty' => $temp->qty,
                    'status' => 1,
                    'created_by' => $user,
                ]);
            }

            // Create Trace Header
            $traceHeader = TraceHeader::create([
                'id_balance_head' => $balanceHeader->id_balance_head,
                'entry_date' => $data['entry_date'],
                'to_trace_no' => $data['rm_number'],
                'id_material' => $data['id_material'],
                'id_sloc' => $data['id_tank'],
                'id_tank_tail' => json_encode($data['id_tank_tail']),
                'id_plant' => $data['id_plant'],
                'in_qty' => $data['total_qty'],
                'out_qty' => 0,
                'status' => 1,
                'created_by' => $user,
            ]);

            // Create Trace Details
            foreach ($tempRecords as $temp) {
                TraceDetail::create([
                    'id_trace_head' => $traceHeader->id_trace_head,
                    'id_supplier' => $temp->id_supplier,
                    'id_material' => $temp->id_material,
                    'batch_sap' => $temp->batch_sap,
                    'in_qty' => $temp->qty,
                    'out_qty' => 0,
                    'status' => 1,
                    'created_by' => $user,
                ]);
            }

            // Create Material Document
            if (!empty($data['material_document'])) {
                MaterialDocument::create([
                    'id_trace_head' => $traceHeader->id_trace_head,
                    'material_document' => $data['material_document'],
                    'po_so' => $data['po_so'] ?? null,
                    'created_by' => $user,
                ]);
            }

            // Clear temporary records
            BalanceTemporary::where('entry_no', $data['rm_number'])
                ->update(['status' => 0, 'updated_by' => $user]);

            // Log transaction
            DB::connection('eudr_ts')->table('log_transactions')->insert([
                'log_module' => 'RM_ENTRY',
                'log_type' => 'ADD',
                'log_description' => 'ID: ' . $balanceHeader->id_balance_head . ' | Trace No: ' . $data['rm_number'],
                'created_by' => $user,
                'created_at' => now(),
            ]);

            DB::connection('eudr_ts')->commit();

            return ['success' => true, 'id' => $balanceHeader->id_balance_head];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            Log::error('RM Entry Save Error: ' . $e->getMessage());
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
            'id_supplier' => $data['id_supplier'],
            'id_material' => $data['id_material'],
            'id_plant' => $data['id_plant'],
            'qty' => $data['qty'],
            'batch_sap' => $data['batch_sap'],
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
                    'supplier' => $item->supplier->code . ' :: ' . $item->supplier->description,
                    'material' => $item->material->code,
                    'batch_sap' => $item->batch_sap,
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
