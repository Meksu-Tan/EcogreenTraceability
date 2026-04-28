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
    public static function generalFeed(array $feedData): array
    {
        // Pre-flight: fetch balance heads OUTSIDE the transaction so we can
        // return early without starting one unnecessarily.
        $sql = 'SELECT id_balance_head, qty, out_qty, trace_no, init_qty
                FROM t_balance_header
                WHERE status = 1
                    AND qty > "0.0001"
                    AND id_material = ?';

        $params = [$feedData['id_material']];

        if (!empty($feedData['id_tank'])) {
            $sql .= ' AND id_tank = ?';
            $params[] = $feedData['id_tank'];
        }

        $tracePrefixes = $feedData['trace_prefixes'] ?? null;
        if (!empty($tracePrefixes)) {
            $placeholders = implode(',', array_fill(0, count($tracePrefixes), '?'));
            $sql .= " AND SUBSTRING(trace_no,1,1) IN ($placeholders)";
            $params = array_merge($params, $tracePrefixes);
        }

        $sql .= ' ORDER BY id_balance_head ASC';

        $balHeads = DB::select($sql, $params);

        if (count($balHeads) === 0) {
            return [
                'response'       => 3,
                'trace_head_ids' => [],
                'used_heads'     => [],
                'total_out'      => 0,
                'feed_in_details' => [],
            ];
        }

        // Validate total available stock before opening a transaction.
        $totalAvailable = array_sum(array_column($balHeads, 'qty'));
        if (round($totalAvailable, 4) < round($feedData['qty'], 4)) {
            return [
                'response'       => 3,
                'trace_head_ids' => [],
                'used_heads'     => [],
                'total_out'      => 0,
                'feed_in_details' => [],
            ];
        }

        // All writes happen inside a single atomic transaction.
        return DB::transaction(function () use ($feedData, $balHeads) {

            $qtyWh        = $feedData['qty'];
            $traceHeadIds = [];
            $usedHeads    = [];
            $feedInDetails = [];
            $totalOut     = 0;

            foreach ($balHeads as $head) {

                if ($qtyWh <= 0) break;

                $idHead    = $head->id_balance_head;
                $fromTrace = $head->trace_no;

                if ($head->qty <= 0) continue;

                $balanceAfter = $head->qty - $qtyWh;

                if ($balanceAfter < 0) {
                    $useQty    = $head->qty;
                    $newBalance = 0;
                    $newOutQty  = $head->out_qty + $useQty;
                    $qtyWh    -= $useQty;
                } else {
                    $useQty    = $qtyWh;
                    $newBalance = round($head->qty - $qtyWh, 4);
                    $newOutQty  = $head->out_qty + $qtyWh;
                    $qtyWh     = 0;
                }

                $useQty    = round($useQty, 4);
                $totalOut += $useQty;

                // UPDATE BALANCE HEADER
                DB::update(
                    'UPDATE t_balance_header
                     SET qty = ?, out_qty = ?, updated_by = ?
                     WHERE id_balance_head = ?',
                    [$newBalance, round($newOutQty, 4), $feedData['user'], $idHead]
                );

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
                $usedHeads[]    = [
                    'id_balance_head' => $idHead,
                    'from_trace_no'   => $fromTrace,
                    'qty_used'        => $useQty,
                ];

                // FETCH & DEDUCT SUPPLIER TAILS (FIFO)
                $balTails = DB::select(
                    'SELECT a.id_balance_tail, a.id_supplier, a.batch_sap, a.qty, a.out_qty, a.init_qty
                       FROM t_balance_detail a
                       JOIN m_supplier b ON a.id_supplier = b.id_supplier
                      WHERE a.id_balance_head = ?
                        AND a.status = 1
                        AND a.qty > "0.0001"
                      ORDER BY a.id_balance_tail ASC',
                    [$idHead]
                );

                if (count($balTails) === 0) {
                    // HEAD HAS NO ACTIVE SUPPLIER DETAIL.
                    //
                    // This is a data integrity violation: a balance_head exists with
                    // qty > 0 but no corresponding t_balance_detail rows. This means
                    // supplier origin is unknown — a direct EUDR compliance breach.
                    //
                    // Possible causes:
                    //   1. The head originated from a Transfer whose source also had
                    //      no supplier detail (cascade from a broken transfer chain).
                    //   2. A Stock Initialization that skipped supplier rows.
                    //   3. A data repair that fixed the head qty but not the details.
                    //
                    // Throwing here causes DB::transaction() to roll back ALL writes
                    // made so far in this call (balance_header updates + trace_header
                    // inserts for previously processed heads in this same feed).
                    // This is intentional: a partial feed with missing supplier data
                    // is worse than a rejected feed the operator can investigate.
                    //
                    // Resolution: run the diagnostic query in EUDR_orphan_diagnostic.sql
                    // to identify the root cause for id_balance_head = $idHead, then
                    // use the repair script to backfill the missing t_balance_detail rows
                    // before retrying the feed entry.
                    throw new \RuntimeException(
                        'Feed::generalFeed - id_balance_head=' . $idHead .
                        ' (trace_no=' . $fromTrace . ') has qty > 0 but NO active' .
                        ' t_balance_detail rows. Supplier origin cannot be traced.' .
                        ' Feed rejected to prevent EUDR compliance gap.' .
                        ' Run orphan diagnostic SQL and repair before retrying.'
                    );
                }

                $qtyTail = $useQty;

                foreach ($balTails as $tail) {

                    if ($qtyTail <= 0) break;

                    $tailAfter = $tail->qty - $qtyTail;

                    if ($tailAfter < 0) {
                        $useTailQty  = $tail->qty;
                        $newTailQty  = 0;
                        $newTailOut  = $tail->out_qty + $useTailQty;
                        $qtyTail    -= $useTailQty;
                    } else {
                        $useTailQty  = $qtyTail;
                        $newTailQty  = round($tail->qty - $qtyTail, 4);
                        $newTailOut  = $tail->out_qty + $qtyTail;
                        $qtyTail     = 0;
                    }

                    $key = $tail->id_supplier . '|' . $tail->batch_sap;

                    if (!isset($feedInDetails[$key])) {
                        $feedInDetails[$key] = [
                            'id_supplier' => $tail->id_supplier,
                            'batch_sap'   => $tail->batch_sap,
                            'qty'         => 0,
                        ];
                    }
                    $feedInDetails[$key]['qty'] += $useTailQty;

                    DB::update(
                        'UPDATE t_balance_detail
                         SET qty = ?, out_qty = ?, updated_by = ?
                         WHERE id_balance_tail = ?',
                        [round($newTailQty, 4), round($newTailOut, 4), $feedData['user'], $tail->id_balance_tail]
                    );

                    DB::table('t_trace_detail')->insert([
                        'id_trace_head'   => $traceHeadId,
                        'id_balance_tail' => $tail->id_balance_tail,
                        'id_supplier'     => $tail->id_supplier,
                        'id_material'     => $feedData['id_material'],
                        'out_qty'         => round($useTailQty, 4),
                        'batch_sap'       => $tail->batch_sap,
                        'id_sloc'         => $feedData['id_tank'],
                        'id_tank_tail'    => $feedData['id_tank_tail'],
                        'id_plant'        => $feedData['id_plant'],
                        'created_by'      => $feedData['user'],
                    ]);
                }

                // PACKAGING-SPECIFIC hook (optional callback)
                if (!empty($feedData['afterSupplierFeed']) && is_callable($feedData['afterSupplierFeed'])) {
                    $feedData['afterSupplierFeed']($idHead, $useQty);
                }
            }

            // Final guard: if qty was not fully consumed, something went wrong
            // with the balance read. Roll back via exception.
            if (round($qtyWh, 4) > 0) {
                throw new \RuntimeException(
                    'Feed::generalFeed - insufficient balance after FIFO loop. ' .
                    'Remaining qty: ' . $qtyWh . ' for material: ' . $feedData['id_material']
                );
            }

            return [
                'response'        => 1,
                'trace_head_ids'  => $traceHeadIds,
                'used_heads'      => $usedHeads,
                'total_out'       => $totalOut,
                'feed_in_details' => array_values($feedInDetails),
            ];
        });
    }

    /**
     * Normalize supplier quantities across trace detail rows so they sum
     * exactly to $targetQty, correcting floating-point drift.
     *
     * NOTE: this method is intentionally run OUTSIDE any transaction because
     * it is a correction pass after generalFeed() has already committed.
     * If called inside a transaction, the outer transaction covers it.
     */
    public static function normalizeSupplierRundown(array $traceHeadIds, float $targetQty): void
    {
        if (empty($traceHeadIds) || $targetQty <= 0) return;

        $rows = DB::table('t_trace_detail')
            ->select('id_trace_tail', 'out_qty')
            ->whereIn('id_trace_head', $traceHeadIds)
            ->where('status', 1)
            ->get();

        if ($rows->isEmpty()) return;

        $total = $rows->sum('out_qty');
        if (round($total, 6) == 0) return;

        $factor   = $targetQty / $total;
        $newTotal = 0;
        $adjusted = [];

        foreach ($rows as $i => $row) {
            $val           = round($row->out_qty * $factor, 4);
            $adjusted[$i]  = ['id' => $row->id_trace_tail, 'out_qty' => $val];
            $newTotal     += $val;
        }

        // Apply rounding correction to last row
        $delta = round($targetQty - $newTotal, 4);
        $adjusted[count($adjusted) - 1]['out_qty'] += $delta;

        foreach ($adjusted as $item) {
            DB::table('t_trace_detail')
                ->where('id_trace_tail', $item['id'])
                ->update(['out_qty' => $item['out_qty']]);
        }
    }
}
