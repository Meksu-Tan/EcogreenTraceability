<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class Feed
{
    /**
     * Build FIFO balance-header query filtered by material, sloc, plant, sub-sloc, trace type.
     * Multiple storage logs with the same parameters are consumed oldest-first (id_balance_head ASC).
     *
     * @return array{0: string, 1: array}
     */
    private static function buildFifoHeadQuery(array $feedData): array
    {
        $sql = 'SELECT id_balance_head, qty, out_qty, trace_no, init_qty, id_sloc, id_plant, COALESCE(id_sloc_tail, id_tank_tail) as tank_tail
                FROM t_balance_header
                WHERE status = 1
                    AND qty > "0.0001"
                    AND id_material = ?';

        $params = [$feedData['id_material']];

        // Enhanced parameter matching logic - prioritize exact matches but allow flexibility
        $tankMatching = $feedData['tank_matching'] ?? 'exact'; // exact, flexible, any
        
        if ($tankMatching === 'exact') {
            // Exact tank matching
            if (!empty($feedData['id_tank'])) {
                $sql .= ' AND id_sloc = ?';
                $params[] = $feedData['id_tank'];
            }
        } elseif ($tankMatching === 'flexible') {
            // Flexible tank matching - try exact first, then same tank type
            if (!empty($feedData['id_tank'])) {
                $sql .= ' AND (id_sloc = ? OR id_sloc IN (
                    SELECT id_sloc FROM m_sloc 
                    WHERE description = (SELECT description FROM m_sloc WHERE id_sloc = ?) 
                    AND status = 1
                ))';
                $params[] = $feedData['id_tank'];
                $params[] = $feedData['id_tank'];
            }
        }
        // 'any' mode - no tank filtering

        $balancePlant = $feedData['balance_plant'] ?? ($feedData['id_plant'] ?? null);
        if ($balancePlant !== null && $balancePlant !== '' && $balancePlant !== 0 && $balancePlant !== '0') {
            $sql .= ' AND (id_plant = ? OR id_plant = 0)';
            $params[] = $balancePlant;
        }

        // Enhanced tank tail matching
        if (!empty($feedData['id_tank_tail'])) {
            $tailIds = json_decode($feedData['id_tank_tail'], true);
            if (is_array($tailIds) && count($tailIds) > 0) {
                $validTails = [];
                foreach ($tailIds as $tailId) {
                    if ($tailId === '' || $tailId === null) {
                        continue;
                    }
                    if (is_numeric($tailId) || (is_string($tailId) && ctype_digit($tailId))) {
                        $validTails[] = (string) $tailId;
                    }
                }
                
                if ($tankMatching === 'flexible' && count($validTails) > 0) {
                    // Flexible tail matching - allow partial matches
                    $tailConditions = [];
                    foreach ($validTails as $tailId) {
                        $tailConditions[] = 'JSON_CONTAINS(COALESCE(id_sloc_tail, id_tank_tail), JSON_QUOTE(?))';
                        $params[] = $tailId;
                    }
                    // Also allow empty tail matches for flexibility
                    $tailConditions[] = '(COALESCE(id_sloc_tail, id_tank_tail) IS NULL OR COALESCE(id_sloc_tail, id_tank_tail) = "" OR COALESCE(id_sloc_tail, id_tank_tail) = "[]")';
                    $sql .= ' AND (' . implode(' OR ', $tailConditions) . ')';
                } elseif (count($validTails) === 1) {
                    $sql .= ' AND JSON_CONTAINS(COALESCE(id_sloc_tail, id_tank_tail), JSON_QUOTE(?))';
                    $params[] = $validTails[0];
                } elseif (count($validTails) > 1) {
                    $tailConditions = [];
                    foreach ($validTails as $tailId) {
                        $tailConditions[] = 'JSON_CONTAINS(COALESCE(id_sloc_tail, id_tank_tail), JSON_QUOTE(?))';
                        $params[] = $tailId;
                    }
                    $sql .= ' AND (' . implode(' OR ', $tailConditions) . ')';
                }
            }
        }

        $tracePrefixes = $feedData['trace_prefixes'] ?? null;
        if (!empty($tracePrefixes)) {
            $placeholders = implode(',', array_fill(0, count($tracePrefixes), '?'));
            $sql .= " AND SUBSTRING(trace_no,1,1) IN ($placeholders)";
            $params = array_merge($params, $tracePrefixes);
        }

        // Always order by creation date for true FIFO
        $sql .= ' ORDER BY id_balance_head ASC';

        return [$sql, $params];
    }

    /**
     * Sum on-hand (qty) available for transfer parameters (no mutation).
     */
    public static function getAvailableQty(array $feedData): float
    {
        [$sql, $params] = self::buildFifoHeadQuery($feedData);
        // Ordering is already handled in buildFifoHeadQuery

        $balHeads = DB::connection('eudr_ts')->select($sql, $params);

        return round(array_sum(array_map(static fn ($h) => (float) $h->qty, $balHeads)), 4);
    }

    /**
     * General FIFO feed deduction from balance (reduces on-hand qty + supplier tail qty).
     */
    public static function generalFeed(array $feedData): array
    {
        [$sql, $params] = self::buildFifoHeadQuery($feedData);
        // Ordering is already handled in buildFifoHeadQuery

        $balHeads = DB::connection('eudr_ts')->select($sql, $params);

        if (count($balHeads) === 0) {
            return [
                'response'       => 3,
                'trace_head_ids' => [],
                'used_heads'     => [],
                'total_out'      => 0,
                'feed_in_details' => [],
            ];
        }

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

        return DB::connection('eudr_ts')->transaction(function () use ($feedData, $balHeads) {

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
                DB::connection('eudr_ts')->table('t_balance_header')
                    ->where('id_balance_head', $idHead)
                    ->update([
                        'qty' => $newBalance,
                        'out_qty' => round($newOutQty, 4),
                        'updated_by' => $feedData['user'],
                        'updated_at' => now(),
                    ]);

                // INSERT TRACE HEADER (FEED)
                $toTraceNo = is_numeric($feedData['to_trace_no'])
                    ? (int) $feedData['to_trace_no']
                    : (int) preg_replace('/\D/', '', (string) $feedData['to_trace_no']);
                $fromTraceNo = is_numeric($fromTrace)
                    ? (int) $fromTrace
                    : (int) preg_replace('/\D/', '', (string) $fromTrace);

                $traceHeadId = DB::connection('eudr_ts')->table('t_trace_header')->insertGetId([
                    'from_trace_no'   => $fromTraceNo,
                    'to_trace_no'     => $toTraceNo,
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
                    'created_at'      => now(),
                ]);

                $traceHeadIds[] = $traceHeadId;
                $headFeedInDetails = [];

                // FETCH & DEDUCT SUPPLIER TAILS (FIFO)
                $balTails = DB::connection('eudr_ts')->select(
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
                        ' has qty > 0 but NO active t_balance_detail rows.'
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

                    $useTailQty = round($useTailQty, 4);
                    if ($useTailQty <= 0) {
                        continue;
                    }

                    $key = $tail->id_supplier . '|' . $tail->batch_sap;

                    if (!isset($feedInDetails[$key])) {
                        $feedInDetails[$key] = [
                            'id_supplier' => $tail->id_supplier,
                            'batch_sap'   => $tail->batch_sap,
                            'qty'         => 0,
                        ];
                    }
                    if (!isset($headFeedInDetails[$key])) {
                        $headFeedInDetails[$key] = [
                            'id_supplier' => $tail->id_supplier,
                            'batch_sap'   => $tail->batch_sap,
                            'qty'         => 0,
                        ];
                    }
                    $feedInDetails[$key]['qty'] += $useTailQty;
                    $headFeedInDetails[$key]['qty'] += $useTailQty;

                    DB::connection('eudr_ts')->table('t_balance_detail')
                        ->where('id_balance_tail', $tail->id_balance_tail)
                        ->update([
                            'qty' => round($newTailQty, 4),
                            'out_qty' => round($newTailOut, 4),
                            'updated_by' => $feedData['user'],
                            'updated_at' => now(),
                        ]);

                    DB::connection('eudr_ts')->table('t_trace_detail')->insert([
                        'id_trace_head'   => $traceHeadId,
                        'id_balance_tail' => $tail->id_balance_tail,
                        'id_supplier'     => $tail->id_supplier,
                        'id_material'     => $feedData['id_material'],
                        'out_qty'         => round($useTailQty, 4),
                        'batch_sap'       => $tail->batch_sap,
                        'id_sloc'         => $feedData['id_tank'],
                        'id_sloc_tail'    => $feedData['id_tank_tail'],
                        'id_plant'        => $feedData['id_plant'],
                        'created_by'      => $feedData['user'],
                        'created_at'      => now(),
                    ]);
                }

                $usedHeads[] = [
                    'id_balance_head' => $idHead,
                    'from_trace_no'   => $fromTrace,
                    'qty_used'        => $useQty,
                    'feed_in_details' => array_values($headFeedInDetails),
                ];
            }

            if (round($qtyWh, 4) > 0) {
                throw new \RuntimeException('Feed::generalFeed - insufficient balance after FIFO loop.');
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
     * Get detailed FIFO stock information for debugging and analysis
     */
    public static function getDetailedFifoStock(array $feedData): array
    {
        [$sql, $params] = self::buildFifoHeadQuery($feedData);
        
        $balHeads = DB::connection('eudr_ts')->select($sql, $params);
        
        $detailedStock = [];
        foreach ($balHeads as $head) {
            $detailedStock[] = [
                'id_balance_head' => $head->id_balance_head,
                'trace_no' => $head->trace_no,
                'qty' => floatval($head->qty),
                'out_qty' => floatval($head->out_qty),
                'init_qty' => floatval($head->init_qty),
                'id_sloc' => $head->id_sloc,
                'id_plant' => $head->id_plant,
                'tank_tail' => $head->tank_tail,
                'available_qty' => floatval($head->qty),
                'created_date' => null // Will be populated if needed
            ];
        }
        
        return [
            'total_available' => array_sum(array_column($detailedStock, 'available_qty')),
            'stock_details' => $detailedStock,
            'parameters_used' => [
                'id_material' => $feedData['id_material'],
                'id_tank' => $feedData['id_tank'] ?? null,
                'id_tank_tail' => $feedData['id_tank_tail'] ?? null,
                'balance_plant' => $feedData['balance_plant'] ?? null,
                'tank_matching' => $feedData['tank_matching'] ?? 'exact'
            ]
        ];
    }

    public static function normalizeSupplierRundown(array $traceHeadIds, float $targetQty): void
    {
        if (empty($traceHeadIds) || $targetQty <= 0) return;

        $rows = DB::connection('eudr_ts')->table('t_trace_detail')
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

        $delta = round($targetQty - $newTotal, 4);
        $adjusted[count($adjusted) - 1]['out_qty'] += $delta;

        foreach ($adjusted as $item) {
            DB::connection('eudr_ts')->table('t_trace_detail')
                ->where('id_trace_tail', $item['id'])
                ->update(['out_qty' => $item['out_qty']]);
        }
    }
}
