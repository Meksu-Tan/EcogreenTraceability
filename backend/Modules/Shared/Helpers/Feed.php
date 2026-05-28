<?php declare(strict_types=1);
namespace Modules\Shared\Helpers;

use Illuminate\Support\Facades\DB;

class Feed
{
    protected $connection = 'eudr_ts';

    public static function getAvailableQty(array $feedData): float
    {
        $connection = 'eudr_ts';

        $sql = 'SELECT id_balance_head, qty, out_qty, trace_no, init_qty
                FROM t_balance_header
                WHERE status = 1
                    AND qty > "0.0001"
                    AND id_material = ?';

        $params = [$feedData['id_material']];

        if (!empty($feedData['id_sloc'])) {
            $sql .= ' AND id_sloc = ?';
            $params[] = $feedData['id_sloc'];
        } elseif (!empty($feedData['id_tank'])) {
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

        $balHeads = DB::connection($connection)->select($sql, $params);

        return round(array_sum(array_column($balHeads, 'qty')), 4);
    }

    public static function generalFeed(array $feedData): array
    {
        $connection = 'eudr_ts';

        $sql = 'SELECT id_balance_head, qty, out_qty, trace_no, init_qty
                FROM t_balance_header
                WHERE status = 1
                    AND qty > "0.0001"
                    AND id_material = ?';

        $params = [$feedData['id_material']];

        if (!empty($feedData['id_sloc'])) {
            $sql .= ' AND id_sloc = ?';
            $params[] = $feedData['id_sloc'];
        } elseif (!empty($feedData['id_tank'])) {
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

        $balHeads = DB::connection($connection)->select($sql, $params);

        if (count($balHeads) === 0) {
            return [
                'response' => 3,
                'trace_head_ids' => [],
                'used_heads' => [],
                'total_out' => 0,
                'feed_in_details' => [],
            ];
        }

        $totalAvailable = array_sum(array_column($balHeads, 'qty'));
        if (round($totalAvailable, 4) < round($feedData['qty'], 4)) {
            return [
                'response' => 3,
                'trace_head_ids' => [],
                'used_heads' => [],
                'total_out' => 0,
                'feed_in_details' => [],
            ];
        }

        return DB::connection($connection)->transaction(function () use ($feedData, $balHeads, $connection) {

            $qtyWh = $feedData['qty'];
            $traceHeadIds = [];
            $usedHeads = [];
            $feedInDetails = [];
            $totalOut = 0;

            foreach ($balHeads as $head) {
                if ($qtyWh <= 0) break;

                $idHead = $head->id_balance_head;
                $fromTrace = $head->trace_no;

                if ($head->qty <= 0) continue;

                $balanceAfter = $head->qty - $qtyWh;

                if ($balanceAfter < 0) {
                    $useQty = $head->qty;
                    $newBalance = 0;
                    $newOutQty = $head->out_qty + $useQty;
                    $qtyWh -= $useQty;
                } else {
                    $useQty = $qtyWh;
                    $newBalance = round($head->qty - $qtyWh, 4);
                    $newOutQty = $head->out_qty + $qtyWh;
                    $qtyWh = 0;
                }

                $useQty = round($useQty, 4);
                $totalOut += $useQty;

                DB::connection($connection)->update(
                    'UPDATE t_balance_header SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_head = ?',
                    [$newBalance, round($newOutQty, 4), $feedData['user'], $idHead]
                );

                // CREATE TRACE HEADER (FEED OUT)
                $traceHeadId = DB::connection($connection)->table('t_trace_header')->insertGetId([
                    'from_trace_no' => $fromTrace,
                    'to_trace_no' => $feedData['to_trace_no'],
                    'id_balance_head' => $idHead,
                    'id_material' => $feedData['id_material'],
                    'entry_date' => $feedData['entry_date'],
                    'id_sloc' => $feedData['id_tank'] ?? $feedData['id_sloc'] ?? 0,
                    'id_tank_tail' => $feedData['id_tank_tail'] ?? null,
                    'out_qty' => $useQty,
                    'last_qtf' => $feedData['last_qtf'] ?? 0,
                    'curr_qtf' => $feedData['qty'],
                    'id_plant' => $feedData['id_plant'],
                    'created_by' => $feedData['user'],
                ]);

                $traceHeadIds[] = $traceHeadId;
                $usedHeads[] = [
                    'id_balance_head' => $idHead,
                    'from_trace_no' => $fromTrace,
                    'qty_used' => $useQty,
                ];

                // FETCH & DEDUCT SUPPLIER TAILS (FIFO)
                $balTails = DB::connection($connection)->select(
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
                    throw new \RuntimeException(
                        'Feed::generalFeed - id_balance_head=' . $idHead .
                        ' (trace_no=' . $fromTrace . ') has qty > 0 but NO active t_balance_detail rows.'
                    );
                }

                $qtyTail = $useQty;

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
                        $newTailQty = round($tail->qty - $qtyTail, 4);
                        $newTailOut = $tail->out_qty + $qtyTail;
                        $qtyTail = 0;
                    }

                    $key = $tail->id_supplier . '|' . $tail->batch_sap;

                    if (!isset($feedInDetails[$key])) {
                        $feedInDetails[$key] = [
                            'id_supplier' => $tail->id_supplier,
                            'batch_sap' => $tail->batch_sap,
                            'qty' => 0,
                        ];
                    }
                    $feedInDetails[$key]['qty'] += $useTailQty;

                    DB::connection($connection)->update(
                        'UPDATE t_balance_detail SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_tail = ?',
                        [round($newTailQty, 4), round($newTailOut, 4), $feedData['user'], $tail->id_balance_tail]
                    );

                    // CREATE TRACE DETAIL (FEED OUT DETAIL)
                    DB::connection($connection)->table('t_trace_detail')->insert([
                        'id_trace_head' => $traceHeadId,
                        'id_balance_tail' => $tail->id_balance_tail,
                        'id_supplier' => $tail->id_supplier,
                        'id_material' => $feedData['id_material'],
                        'out_qty' => round($useTailQty, 4),
                        'batch_sap' => $tail->batch_sap,
                        'id_sloc' => $feedData['id_tank'] ?? $feedData['id_sloc'] ?? 0,
                        'id_tank_tail' => $feedData['id_tank_tail'] ?? null,
                        'id_plant' => $feedData['id_plant'],
                        'created_by' => $feedData['user'],
                    ]);
                }
            }

            if (round($qtyWh, 4) > 0) {
                throw new \RuntimeException(
                    'Feed::generalFeed - insufficient balance after FIFO loop. Remaining qty: ' . $qtyWh
                );
            }

            return [
                'response' => 1,
                'trace_head_ids' => $traceHeadIds,
                'used_heads' => $usedHeads,
                'total_out' => $totalOut,
                'feed_in_details' => array_values($feedInDetails),
            ];
        });
    }

    public static function debugStock(array $params): array
    {
        $connection = 'eudr_ts';

        $sql = 'SELECT id_balance_head, qty, out_qty, trace_no, init_qty, id_sloc, id_plant
                FROM t_balance_header
                WHERE status = 1
                    AND qty > "0.0001"
                    AND id_material = ?';

        $sqlParams = [$params['id_material']];

        if (!empty($params['id_sloc'])) {
            $sql .= ' AND id_sloc = ?';
            $sqlParams[] = $params['id_sloc'];
        } elseif (!empty($params['id_tank'])) {
            $sql .= ' AND id_tank = ?';
            $sqlParams[] = $params['id_tank'];
        }

        $tracePrefixes = $params['trace_prefixes'] ?? null;
        if (!empty($tracePrefixes)) {
            $placeholders = implode(',', array_fill(0, count($tracePrefixes), '?'));
            $sql .= " AND SUBSTRING(trace_no,1,1) IN ($placeholders)";
            $sqlParams = array_merge($sqlParams, $tracePrefixes);
        }

        $sql .= ' ORDER BY id_balance_head ASC';

        $balHeads = DB::connection($connection)->select($sql, $sqlParams);

        return [
            'total_available' => array_sum(array_column($balHeads, 'qty')),
            'stock_details' => $balHeads,
            'parameters' => $params
        ];
    }
}
