<?php declare(strict_types=1);
namespace Modules\Shared\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Feed helper — FIFO balance consumption engine.
 *
 * RAW SQL DEBT (C06): The t_balance_header and t_balance_detail queries
 * in generalFeed() remain as raw SQL because of dynamic JSON_CONTAINS
 * and SUBSTRING WHERE clauses that are impractical to express in
 * QueryBuilder. These are deliberately wrapped in a transaction with
 * FOR UPDATE (C09) to prevent race conditions. getAvailableQty() and
 * debugStock() are read-only and intentionally raw for the same reason.
 *
 * Long-term: extract the sloc-JSON predicate builder into a shared
 * scope class and migrate to QueryBuilder.
 */
class Feed
{
    protected $connection = 'eudr_ts';

    public static function getAvailableQty(array $feedData): float
    {
        $connection = 'eudr_ts';

        $sql = 'SELECT id_balance_head, qty, out_qty, trace_no, init_qty
                FROM t_balance_header
                WHERE status = 1
                    AND qty > 0.0001
                    AND id_material = ?';

        $params = [$feedData['id_material']];

        if (!empty($feedData['id_sloc'])) {
            $slocVal = $feedData['id_sloc'];
            $slocIds = [];
            if (is_array($slocVal)) {
                $slocIds = $slocVal;
            } else {
                $decoded = json_decode($slocVal, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $slocIds = $decoded;
                } else {
                    $slocIds = [$slocVal];
                }
            }
            $slocIds = array_map('strval', array_filter($slocIds));

            if (!empty($slocIds)) {
                $conditions = [];
                foreach ($slocIds as $id) {
                    $conditions[] = "id_sloc = ? OR (JSON_VALID(id_sloc) AND JSON_CONTAINS(id_sloc, CAST(? AS JSON))) OR (JSON_VALID(id_sloc) AND JSON_CONTAINS(id_sloc, JSON_QUOTE(?)))";
                    $params[] = $id;
                    $params[] = $id;
                    $params[] = $id;
                }
                $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
            }
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
                    AND qty > 0.0001
                    AND id_material = ?';

        $params = [$feedData['id_material']];

        if (!empty($feedData['id_sloc'])) {
            $slocVal = $feedData['id_sloc'];
            $slocIds = [];
            if (is_array($slocVal)) {
                $slocIds = $slocVal;
            } else {
                $decoded = json_decode($slocVal, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $slocIds = $decoded;
                } else {
                    $slocIds = [$slocVal];
                }
            }
            $slocIds = array_map('strval', array_filter($slocIds));

            if (!empty($slocIds)) {
                $conditions = [];
                foreach ($slocIds as $id) {
                    $conditions[] = "id_sloc = ? OR (JSON_VALID(id_sloc) AND JSON_CONTAINS(id_sloc, CAST(? AS JSON))) OR (JSON_VALID(id_sloc) AND JSON_CONTAINS(id_sloc, JSON_QUOTE(?)))";
                    $params[] = $id;
                    $params[] = $id;
                    $params[] = $id;
                }
                $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
            }
        }

        $tracePrefixes = $feedData['trace_prefixes'] ?? null;
        if (!empty($tracePrefixes)) {
            $placeholders = implode(',', array_fill(0, count($tracePrefixes), '?'));
            $sql .= " AND SUBSTRING(trace_no,1,1) IN ($placeholders)";
            $params = array_merge($params, $tracePrefixes);
        }

        $sql .= ' ORDER BY id_balance_head ASC';

        return DB::connection($connection)->transaction(function () use ($feedData, $sql, $params, $connection) {

            $balHeads = DB::connection($connection)->select($sql . ' FOR UPDATE', $params);

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

                DB::connection($connection)->table('t_balance_header')
                    ->where('id_balance_head', $idHead)
                    ->update([
                        'qty' => $newBalance,
                        'out_qty' => round($newOutQty, 4),
                        'updated_by' => $feedData['user'],
                    ]);

                // CREATE TRACE HEADER (FEED OUT)
                $traceHeadId = DB::connection($connection)->table('t_trace_header')->insertGetId([
                    'from_trace_no' => $fromTrace,
                    'to_trace_no' => $feedData['to_trace_no'],
                    'id_balance_head' => $idHead,
                    'id_material' => $feedData['id_material'],
                    'entry_date' => $feedData['entry_date'],
                    'id_sloc' => $feedData['id_sloc'] ?? '[]',
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
                // FIFO: consume oldest entry first by entry_date, then by id_balance_tail
                $balTails = DB::connection($connection)->select(
                    'SELECT a.id_balance_tail, a.id_supplier, a.id_manufacturer, a.batch_sap, a.qty, a.out_qty, a.init_qty,
                            COALESCE(b.created_at, a.created_at) AS entry_ts
                       FROM t_balance_detail a
                       JOIN m_supplier b ON a.id_supplier = b.id_supplier
                       LEFT JOIN t_balance_header h ON a.id_balance_head = h.id_balance_head
                      WHERE a.id_balance_head = ?
                        AND a.status = 1
                        AND a.qty > 0.0001
                      ORDER BY entry_ts ASC, a.id_balance_tail ASC
                      FOR UPDATE',
                    [$idHead]
                );

                if (count($balTails) === 0) {
                    \Log::warning('Feed::generalFeed - No balance details for balance header', [
                        'id_balance_head' => $idHead,
                        'trace_no' => $fromTrace,
                        'qty' => $head->qty,
                        'out_qty' => $head->out_qty
                    ]);
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

                    $key = $tail->id_supplier . '|' . $tail->id_manufacturer . '|' . $tail->batch_sap;

                    if (!isset($feedInDetails[$key])) {
                        $feedInDetails[$key] = [
                            'id_supplier' => $tail->id_supplier,
                            'id_manufacturer' => $tail->id_manufacturer ?? null,
                            'batch_sap' => $tail->batch_sap,
                            'qty' => 0,
                        ];
                    }
                    $feedInDetails[$key]['qty'] += $useTailQty;

                    DB::connection($connection)->table('t_balance_detail')
                        ->where('id_balance_tail', $tail->id_balance_tail)
                        ->update([
                            'qty' => round($newTailQty, 4),
                            'out_qty' => round($newTailOut, 4),
                            'updated_by' => $feedData['user'],
                        ]);

                    // CREATE TRACE DETAIL (FEED OUT DETAIL)
                    DB::connection($connection)->table('t_trace_detail')->insert([
                        'id_trace_head' => $traceHeadId,
                        'id_balance_tail' => $tail->id_balance_tail,
                        'id_supplier' => $tail->id_supplier,
                        'id_manufacturer' => $tail->id_manufacturer ?? null,
                        'id_material' => $feedData['id_material'],
                        'out_qty' => round($useTailQty, 4),
                        'batch_sap' => $tail->batch_sap,
                        'id_sloc' => $feedData['id_sloc'] ?? '[]',
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
                    AND qty > 0.0001
                    AND id_material = ?';

        $sqlParams = [$params['id_material']];

        if (!empty($params['id_sloc'])) {
            $slocVal = $params['id_sloc'];
            $slocIds = [];
            if (is_array($slocVal)) {
                $slocIds = $slocVal;
            } else {
                $decoded = json_decode($slocVal, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $slocIds = $decoded;
                } else {
                    $slocIds = [$slocVal];
                }
            }
            $slocIds = array_map('strval', array_filter($slocIds));

            if (!empty($slocIds)) {
                $conditions = [];
                foreach ($slocIds as $id) {
                    $conditions[] = "id_sloc = ? OR (JSON_VALID(id_sloc) AND JSON_CONTAINS(id_sloc, CAST(? AS JSON))) OR (JSON_VALID(id_sloc) AND JSON_CONTAINS(id_sloc, JSON_QUOTE(?)))";
                    $sqlParams[] = $id;
                    $sqlParams[] = $id;
                    $sqlParams[] = $id;
                }
                $sql .= ' AND (' . implode(' OR ', $conditions) . ')';
            }
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

    /**
     * Stub for normalizeSupplierRundown to prevent fatal errors.
     * Original logic seems to have normalized supplier proportion for rundown.
     */
    public static function normalizeSupplierRundown(array $traceHeadIds, float $outQty): array
    {
        // TODO: Implement actual normalization logic or route to Rundown::adjustRundownToTotal
        return [];
    }

    /**
     * Stub for getDetailedFifoStock to prevent fatal errors.
     * Original logic seems to have returned detailed FIFO stock info.
     */
    public static function getDetailedFifoStock(array $feedData): array
    {
        // TODO: Implement actual detailed FIFO stock query
        return [];
    }
}
