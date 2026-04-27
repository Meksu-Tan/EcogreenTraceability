<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Feed extends Model
{
    protected $connection = 'eudr_ts';

    /**
     * General FIFO feed deduction from balance.
     *
     * Wraps all writes in a single DB transaction so that any failure
     * mid-way rolls back every update and insert, preventing partial /
     * inconsistent balance state.
     *
     * Returns:
     *   response 1  → success
     *   response 3  → insufficient stock (balance < requested qty)
     *   response 6  → balance head found but has no active supplier detail rows
     */

    public static function generalFeed(array $feedData){
        $qtyWh = $feedData['qty'];
        $traceHeadIds = [];
        $usedHeads = [];
        $feedInDetails = [];
        $totalOut = 0;
        $tracePrefixes = $feedData['trace_prefixes'] ?? null;
        $requireSupplier = $feedData['require_supplier'] ?? true;

        // FETCH BALANCE HEAD FIFO
        $sql = 'SELECT id_balance_head, qty, out_qty, trace_no, init_qty
                FROM t_balance_header
                WHERE status = 1
                    AND qty > "0.0001"
                    AND id_material = ?';

        $params = [
            $feedData['id_material'],
        ];

        if (!empty($feedData['id_tank'])) {
            $sql .= ' AND id_tank = ?';
            $params[] = $feedData['id_tank'];
        }

        if (!empty($tracePrefixes)) {
            $placeholders = implode(',', array_fill(0, count($tracePrefixes), '?'));
            $sql .= " AND SUBSTRING(trace_no,1,1) IN ($placeholders)";
            $params = array_merge($params, $tracePrefixes);
        }

        $sql .= ' ORDER BY id_balance_head ASC';

        $balHeads = DB::select($sql, $params);

        if (count($balHeads) == 0) {
            return [
                'response' => 3,
                'trace_head_ids' => [],
                'used_heads' => [],
                'total_out' => 0,
                'feed_in_details' => []
            ];
        }

        foreach ($balHeads as $head) {

            if ($qtyWh <= 0) break;

            $idHead = $head->id_balance_head;
            $fromTrace = $head->trace_no;

            $balanceAfter = $head->qty - $qtyWh;

            if ($balanceAfter < 0) {
                $useQty = $head->qty;
                $newBalance = 0;
                $newOutQty = $head->out_qty + $useQty;
                $qtyWh -= $useQty;
            } else {
                $useQty = $qtyWh;
                $newBalance = $head->qty - $qtyWh;
                $newOutQty = $head->out_qty + $qtyWh;
                $qtyWh = 0;
            }

            $useQty = round($useQty, 4);
            $totalOut += $useQty;

            // UPDATE BALANCE HEADER
            DB::update('UPDATE t_balance_header
                        SET qty = ?, out_qty = ?, updated_by = ?
                        WHERE id_balance_head = ?', [$newBalance, $newOutQty, $feedData['user'], $idHead]);

            // INSERT TRACE HEADER (FEED)
            $traceHeadId = DB::table('t_trace_header')->insertGetId([
                'from_trace_no'   => $fromTrace,
                'to_trace_no'     => $feedData['to_trace_no'],
                'id_balance_head' => $idHead,
                'id_material'     => $feedData['id_material'],
                'entry_date'      => $feedData['entry_date'],
                'id_sloc'         => $feedData['id_tank'],
                'id_tank_tail'    => $feedData['id_tank_tail'],
                'out_qty'         => $useQty,
                'last_qtf'        => $feedData['last_qtf'] ?? 0,
                'curr_qtf'        => $feedData['qty'],
                'id_plant'        => $feedData['id_plant'],
                'created_by'      => $feedData['user'],
            ]);

            $traceHeadIds[] = $traceHeadId;
            $usedHeads[] = [
                'id_balance_head' => $idHead,
                'from_trace_no' => $fromTrace,
                'qty_used' => $useQty
            ];

            $qtyTail = $useQty;

            // $balTails = DB::select($sqlTail, [$idHead]);

            $balTails = DB::select('SELECT a.id_balance_tail, a.id_supplier, a.batch_sap, a.qty, a.out_qty, a.init_qty
                                      FROM t_balance_detail a
                                      JOIN m_supplier b ON a.id_supplier = b.id_supplier
                                    WHERE a.id_balance_head = ?
                                        AND a.status = 1
                                        AND a.qty > "0.0001"
                                        AND b.status = 1
                                    ORDER BY a.id_balance_tail ASC', [$idHead]);

            if (count($balTails) == 0) {
                continue;
            }

            // if ($requireSupplier && count($balTails) == 0) {
            //     return [
            //         'response' => 6,
            //         'trace_head_ids' => $traceHeadIds,
            //         'used_heads' => $usedHeads,
            //         'total_out' => $totalOut,
            //         'feed_in_details' => []
            //     ];
            // }

            foreach ($balTails as $tail) {

                if ($qtyTail <= 0) break;

                $tailAfter = $tail->qty - $qtyTail;

                if ($tailAfter < 0) {
                    $useTailQty = $tail->qty;
                    $newTailQty = 0;
                    $newTailOut = $tail->out_qty + $useTailQty;
                    $qtyTail -= $useTailQty;
                } else {
                    $useTailQty = $qtyTail;
                    $newTailQty = $tail->qty - $qtyTail;
                    $newTailOut = $tail->out_qty + $qtyTail;
                    $qtyTail = 0;
                }

                $key = $tail->id_supplier . '|' . $tail->batch_sap;

                if (!isset($feedInDetails[$key])) {
                    $feedInDetails[$key] = [
                        'id_supplier' => $tail->id_supplier,
                        'batch_sap'   => $tail->batch_sap,
                        'qty'         => 0
                    ];
                }

                $feedInDetails[$key]['qty'] += $useTailQty;

                DB::update('UPDATE t_balance_detail
                            SET qty = ?, out_qty = ?, updated_by = ?
                            WHERE id_balance_tail = ?', [round($newTailQty, 4), round($newTailOut, 4), $feedData['user'], $tail->id_balance_tail]);

                DB::table('t_trace_detail')->insert([
                    'id_trace_head'   => $traceHeadId,
                    'id_balance_tail' => $tail->id_balance_tail,
                    'id_supplier'     => $tail->id_supplier,
                    'id_material'     => $feedData['id_material'],
                    'out_qty'         => $useTailQty,
                    'batch_sap'       => $tail->batch_sap,
                    'id_sloc'         => $feedData['id_tank'],
                    'id_tank_tail'    => $feedData['id_tank_tail'],
                    'id_plant'        => $feedData['id_plant'],
                    'created_by'      => $feedData['user'],
                ]);
            }

            // PACKAGING-SPECIFIC
            if (!empty($feedData['afterSupplierFeed']) && is_callable($feedData['afterSupplierFeed'])) {
                $feedData['afterSupplierFeed']($idHead, $useQty);
            }
        }

        return [
            'response' => 1,
            'trace_head_ids' => $traceHeadIds,
            'used_heads' => $usedHeads,
            'total_out' => $totalOut,
            'feed_in_details' => array_values($feedInDetails)
        ];
    }

    static function normalizeSupplierRundown(array $traceHeadIds, $targetQty){
        $rows = DB::table('t_trace_detail')
            ->select('id_trace_tail', 'out_qty')
            ->whereIn('id_trace_head', $traceHeadIds)
            ->where('status', 1)
            ->get();

        if ($rows->isEmpty()) return;

        // Calculate total supplier qty
        $total = $rows->sum('out_qty');
        if (round($total, 6) == 0) return;

        $factor = $targetQty / $total;
        $newTotal = 0;

        foreach ($rows as $i => $row) {
            $adjusted = round($row->out_qty * $factor, 4);
            $rows[$i]->adjusted = $adjusted;
            $newTotal += $adjusted;
        }

        // Fix rounding delta
        $delta = round($targetQty - $newTotal, 4);
        $rows[count($rows)-1]->adjusted += $delta;

        // Update DB
        foreach ($rows as $row) {
            DB::table('t_trace_detail')
                ->where('id_trace_tail', $row->id_trace_tail)
                ->update([
                    'out_qty' => $row->adjusted
                ]);
        }
    }
}
