<?php

namespace App\Services;

use App\Models\BalanceHeader;
use App\Models\BalanceDetail;
use App\Models\TraceHeader;
use App\Models\TraceDetail;
use App\Models\Tank;
use App\Models\TankDetail;
use App\Models\Material;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TransferService
{
    protected $movSeq = '000';
    protected $typeTransfer = '7';

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
     * Get Storage Tank Log
     */
    public function getStorageLog($plantId)
    {
        return $this->getTankLog($plantId, 'STORAGE');
    }

    /**
     * Get Feed Tank Log
     */
    public function getFeedLog($plantId)
    {
        return $this->getTankLog($plantId, 'FEED');
    }

    /**
     * Generic Tank Log fetcher
     */
    protected function getTankLog($plantId, $tankType)
    {
        $plantId = $this->resolvePlantCode($plantId);
        $query = "SELECT a.id_trace_head, a.id_balance_head, a.entry_date, 
                         a.from_trace_no, a.to_trace_no, 
                         c.code AS material_code, c.description AS material_name,
                         d.description AS tank_name,
                         FORMAT(a.in_qty, 3) AS in_qty, 
                         FORMAT(a.out_qty, 3) AS out_qty,
                         a.created_by, a.created_at,
                         md.material_document, md.po_so
                    FROM t_trace_header a
                    JOIN m_material c ON a.id_material = c.id_material
                    JOIN m_sloc d ON a.id_sloc = d.id_sloc
                    LEFT JOIN t_material_document md ON a.id_trace_head = md.id_trace_head
                   WHERE a.status = 1
                     AND d.description LIKE CONCAT('%', ?, '%')
                     AND (d.plant_code = ? OR ? = 0)
                   ORDER BY a.id_trace_head DESC";

        return DB::connection('eudr_ts')->select($query, [$tankType, $plantId, $plantId]);
    }

    /**
     * Generate Transfer Trace Number (Starts with 7)
     */
    public function generateTransferNumber($plantId)
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
            [$this->movSeq, $plantId, $this->movSeq, $plantId]
        );

        return $result[0]->trace_no ?? null;
    }

    /**
     * Perform Transfer from Storage to Feed
     */
    public function transfer($data, $user)
    {
        $data['id_plant'] = $this->resolvePlantCode($data['id_plant'] ?? 0);
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $sourceBalance = BalanceHeader::findOrFail($data['id_balance_head']);
            $sourceTrace = TraceHeader::where('id_balance_head', $data['id_balance_head'])->first();

            if ($sourceBalance->qty < $data['qty']) {
                throw new Exception('Insufficient quantity in source tank');
            }

            $transferNo = $this->generateTransferNumber($data['id_plant']);

            // 1. Create new Balance Header for the destination (Feed Tank)
            $destBalance = BalanceHeader::create([
                'entry_date' => $data['entry_date'],
                'trace_no' => $transferNo,
                'id_material' => $sourceBalance->id_material,
                'id_tank' => $data['id_dest_tank'],
                'id_tank_tail' => json_encode($data['id_dest_tank_tail']),
                'id_plant' => $data['id_plant'],
                'qty' => $data['qty'],
                'in_qty' => $data['qty'],
                'out_qty' => 0,
                'init_qty' => $data['qty'],
                'status' => 1,
                'created_by' => $user,
            ]);

            // 2. Create Trace Header for the movement
            $traceHeader = TraceHeader::create([
                'id_balance_head' => $destBalance->id_balance_head,
                'entry_date' => $data['entry_date'],
                'from_trace_no' => $sourceBalance->trace_no,
                'to_trace_no' => $transferNo,
                'id_material' => $sourceBalance->id_material,
                'id_sloc' => $data['id_dest_tank'],
                'id_tank_tail' => json_encode($data['id_dest_tank_tail']),
                'id_plant' => $data['id_plant'],
                'in_qty' => $data['qty'],
                'out_qty' => 0,
                'status' => 1,
                'created_by' => $user,
            ]);

            // 3. Update Source Balance (Decrement)
            $sourceBalance->decrement('qty', $data['qty']);
            $sourceBalance->increment('out_qty', $data['qty']);

            // 4. Update Source Trace (Optional, but good for tracking)
            if ($sourceTrace) {
                $sourceTrace->increment('out_qty', $data['qty']);
            }

            // 5. Create audit log
            DB::connection('eudr_ts')->table('log_transactions')->insert([
                'log_module' => 'TRANSFER',
                'log_type' => 'ADD',
                'log_description' => "From: {$sourceBalance->trace_no} To: {$transferNo} | Qty: {$data['qty']}",
                'created_by' => $user,
                'created_at' => now(),
            ]);

            DB::connection('eudr_ts')->commit();

            return ['success' => true, 'transfer_no' => $transferNo];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            Log::error('Transfer Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deactivate Transfer
     */
    public function deactivateTransfer($id, $user)
    {
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $traceHead = TraceHeader::findOrFail($id);
            
            if ($traceHead->status == 0) {
                throw new Exception('Transfer already deactivated');
            }

            // 1. Revert Source Balance (Increment)
            $sourceTraceNo = $traceHead->from_trace_no;
            $sourceBalance = BalanceHeader::where('trace_no', $sourceTraceNo)->first();
            
            if ($sourceBalance) {
                $sourceBalance->increment('qty', $traceHead->in_qty);
                $sourceBalance->decrement('out_qty', $traceHead->in_qty);
                
                // Also revert source trace out_qty
                $sourceTrace = TraceHeader::where('id_balance_head', $sourceBalance->id_balance_head)->first();
                if ($sourceTrace) {
                    $sourceTrace->decrement('out_qty', $traceHead->in_qty);
                }
            }

            // 2. Deactivate Dest Balance
            BalanceHeader::where('id_balance_head', $traceHead->id_balance_head)
                ->update(['status' => 0, 'updated_by' => $user]);

            // 3. Deactivate Trace Head
            $traceHead->update(['status' => 0, 'updated_by' => $user]);

            // 4. Create audit log
            DB::connection('eudr_ts')->table('log_transactions')->insert([
                'log_module' => 'TRANSFER',
                'log_type' => 'DEACTIVATE',
                'log_description' => "ID: {$id} | Reverted From: {$traceHead->from_trace_no} | Qty: {$traceHead->in_qty}",
                'created_by' => $user,
                'created_at' => now(),
            ]);

            DB::connection('eudr_ts')->commit();

            return ['success' => true];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            Log::error('Transfer Deactivate Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
