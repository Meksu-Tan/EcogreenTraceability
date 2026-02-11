<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Rundown extends Model
{
    protected $connection = 'eudr_ts';

    public static function generalRundown(array $data) {
        $idHead = DB::table('t_balance_header')->insertGetId([
            'entry_date'   => $data['entry_date'],
            'trace_no'     => $data['trace_no'],
            'id_material'  => $data['id_material'],
            'id_tank'      => $data['id_tank'],
            'id_tank_tail' => $data['id_tank_tail'],
            'qty'          => $data['in_qty'],
            'in_qty'       => $data['in_qty'],
            'init_qty'     => $data['in_qty'],
            'id_plant'     => $data['id_plant'],
            'created_by'   => $data['user'],
        ]);

        $idTraceHead = DB::table('t_trace_header')->insertGetId([
            'from_trace_no'   => $data['from_trace_no'] ?? null,
            'to_trace_no'     => $data['trace_no'],
            'id_balance_head' => $idHead,
            'id_material'     => $data['id_material'],
            'entry_date'      => $data['entry_date'],
            'id_sloc'         => $data['id_tank'],
            'id_tank_tail'    => $data['id_tank_tail'],
            'in_qty'          => $data['in_qty'],
            'last_qtf'        => $data['last_qtf'],
            'curr_qtf'        => $data['curr_qtf'],
            'id_plant'        => $data['id_plant'],
            'created_by'      => $data['user'],
        ]);

        foreach ($data['supplier_rows'] as $row) {

            $idSupplier = $row['id_supplier'];
            $batchSap   = $row['batch_sap'];
            $qty        = round($row['rundownSupplier'], 4);

            if ($qty <= 0) {
                continue;
            }

            $existing = DB::select('SELECT id_balance_tail, id_trace_tail, in_qty
                                    FROM t_trace_detail
                                    WHERE status = 1
                                        AND id_trace_head = ?
                                        AND id_supplier = ?
                                        AND batch_sap = ?
                                        LIMIT 1', [$idTraceHead, $idSupplier, $batchSap]);

            if (empty($existing)) {

                $idTail = DB::table('t_balance_detail')->insertGetId([
                    'id_balance_head' => $idHead,
                    'id_supplier'     => $idSupplier,
                    'id_material'     => $data['id_material'],
                    'id_tank'         => $data['id_tank'],
                    'id_tank_tail'    => $data['id_tank_tail'],
                    'qty'             => $qty,
                    'in_qty'          => $qty,
                    'init_qty'        => $qty,
                    'batch_sap'       => $batchSap,
                    'id_plant'        => $data['id_plant'],
                    'created_by'      => $data['user'],
                ]);

                DB::table('t_trace_detail')->insert([
                    'id_trace_head'   => $idTraceHead,
                    'id_balance_tail' => $idTail,
                    'id_supplier'     => $idSupplier,
                    'id_material'     => $data['id_material'],
                    'id_sloc'         => $data['id_tank'],
                    'id_tank_tail'    => $data['id_tank_tail'],
                    'in_qty'          => $qty,
                    'batch_sap'       => $batchSap,
                    'id_plant'        => $data['id_plant'],
                    'created_by'      => $data['user'],
                ]);

            } else {

                $idTail = $existing[0]->id_balance_tail;
                $newQty = round($existing[0]->in_qty + $qty, 4);

                DB::update('UPDATE t_balance_detail
                            SET qty = ?, in_qty = ?, init_qty = ?, updated_by = ?
                            WHERE id_balance_tail = ?', [$newQty, $newQty, $newQty, $data['user'], $idTail]);

                DB::update('UPDATE t_trace_detail
                            SET in_qty = ?, updated_by = ?
                            WHERE id_trace_tail = ?', [$newQty, $data['user'], $existing[0]->id_trace_tail]);
            }
        }

        return [
            'response'       => 1,
            'id_balance_head'=> $idHead,
            'id_trace_head'  => $idTraceHead,
        ];
    }

    private static function normalizeNumber($num){
        if ($num === null) return '0';

        $numStr = (string)$num;

        if (stripos($numStr, 'e') !== false) {
            $numStr = sprintf('%.14F', (float)$numStr);
        }

        return $numStr;
    }

    public static function adjustRundownToTotal(array &$rows, $targetTotal){
        $targetTotal = self::normalizeNumber($targetTotal);

        // Step 1: calculate total
        $total = '0';
        foreach ($rows as $row) {
            $total = bcadd($total, self::normalizeNumber($row['rundownSupplier']), 10);
        }

        if (bccomp($total, '0', 10) === 0) {
            return;
        }

        // Step 2: factor
        $factor = bcdiv($targetTotal, $total, 10);

        // Step 3: apply factor
        $newTotal = '0';
        foreach ($rows as &$row) {
            $adjusted = bcmul(self::normalizeNumber($row['rundownSupplier']), $factor, 10);
            $adjusted = round((float)$adjusted, 4);

            $row['rundownSupplier'] = $adjusted;
            $newTotal = bcadd($newTotal, self::normalizeNumber($adjusted), 10);
        }
        unset($row);

        // Step 4: fix rounding delta
        $lastIndex = array_key_last($rows);
        $delta = bcsub($targetTotal, $newTotal, 10);
        $rows[$lastIndex]['rundownSupplier'] += round((float)$delta, 4);
    }
}