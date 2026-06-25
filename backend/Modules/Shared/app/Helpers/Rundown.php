<?php
declare(strict_types=1);
namespace Modules\Shared\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Rundown helper Ã¢â‚¬â€ balance header creation / accumulation engine.
 *
 * RAW SQL DEBT (C07): The t_balance_header EXISTS check and supplier
 * dedup queries remain as raw SQL because of JSON_CONTAINS predicates
 * and dynamic WHERE clauses that are impractical to express in
 * QueryBuilder. The read-then-write pattern inside the transaction
 * is safe against races (FOR UPDATE not strictly required here since
 * we INSERT-or-UPDATE on a unique combination per trace_no + id_sloc,
 * but a productionisation step would add a UNIQUE constraint and
 * use upsert (ON DUPLICATE KEY UPDATE) instead).
 */
class Rundown
{
    protected $connection = 'eudr_ts';



    /**
     * Insert a new rundown balance entry together with all its supplier rows.
     *
     * Returns:
     *   ['response' => 1, 'id_balance_head' => int, 'id_trace_head' => int]
     */
    public static function generalRundown(array $data): array
    {
        $connection = 'eudr_ts';

        return DB::connection($connection)->transaction(function () use ($data, $connection) {

            // Since id_sloc in t_balance_header has been migrated to INTEGER,
            // we extract the single integer value directly and query it as a plain integer.
            $idSloc = $data['id_sloc'];
            $decoded = is_string($idSloc) ? json_decode($idSloc, true) : null;
            if (is_array($decoded)) {
                $slocInt = (int) ($decoded[0] ?? 0);
            } elseif (is_array($idSloc)) {
                $slocInt = (int) ($idSloc[0] ?? 0);
            } else {
                $slocInt = (int) $idSloc;
            }

            $existingHead = DB::connection($connection)->select(
                "SELECT id_balance_head, qty, in_qty, init_qty
                   FROM t_balance_header
                  WHERE trace_no = ?
                    AND id_material = ?
                    AND id_sloc = ?
                    AND status = 1
                  LIMIT 1 FOR UPDATE",
                [$data['trace_no'], $data['id_material'], $slocInt]
            );

            if (!empty($existingHead)) {
                // UPDATE EXISTING BALANCE HEADER
                $idHead = $existingHead[0]->id_balance_head;
                $inQtyFloat = (float) $data['in_qty'];
                $newQty = round((float) $existingHead[0]->qty + $inQtyFloat, 4);
                $newInQty = round((float) $existingHead[0]->in_qty + $inQtyFloat, 4);
                $newInitQty = round((float) $existingHead[0]->init_qty + $inQtyFloat, 4);

                DB::connection($connection)->table('t_balance_header')
                    ->where('id_balance_head', $idHead)
                    ->update([
                        'qty' => $newQty,
                        'in_qty' => $newInQty,
                        'init_qty' => $newInitQty,
                        'updated_by' => $data['user'],
                    ]);

                $idTraceHead = DB::connection($connection)->table('t_trace_header')->insertGetId([
                    'from_trace_no' => $data['from_trace_no'] ?? null,
                    'to_trace_no' => $data['trace_no'],
                    'id_balance_head' => $idHead,
                    'id_material' => $data['id_material'],
                    'entry_date' => $data['entry_date'],
                    'id_sloc' => $data['id_sloc'] ?? '[]',
                    'in_qty' => $data['in_qty'],
                    'last_qtf' => $data['last_qtf'] ?? 0,
                    'curr_qtf' => $data['curr_qtf'] ?? 0,
                    'id_plant' => $data['id_plant'],
                    'created_by' => $data['user'],
                ], 'id_trace_head');
            } else {
                // INSERT NEW BALANCE HEADER
                $idHead = DB::connection($connection)->table('t_balance_header')->insertGetId([
                    'entry_date' => $data['entry_date'],
                    'trace_no' => $data['trace_no'],
                    'id_material' => $data['id_material'],
                    'id_sloc' => $slocInt,
                    'qty' => $data['in_qty'],
                    'in_qty' => $data['in_qty'],
                    'out_qty' => 0,
                    'init_qty' => $data['in_qty'],
                    'id_plant' => $data['id_plant'],
                    'created_by' => $data['user'],
                ], 'id_balance_head');

                // INSERT TRACE HEADER
                $idTraceHead = DB::connection($connection)->table('t_trace_header')->insertGetId([
                    'from_trace_no' => $data['from_trace_no'] ?? null,
                    'to_trace_no' => $data['trace_no'],
                    'id_balance_head' => $idHead,
                    'id_material' => $data['id_material'],
                    'entry_date' => $data['entry_date'],
                    'id_sloc' => $data['id_sloc'] ?? '[]',
                    'in_qty' => $data['in_qty'],
                    'last_qtf' => $data['last_qtf'] ?? 0,
                    'curr_qtf' => $data['curr_qtf'] ?? 0,
                    'id_plant' => $data['id_plant'],
                    'created_by' => $data['user'],
                ], 'id_trace_head');
            }

            // INSERT/UPDATE SUPPLIER ROWS (balance_detail + trace_detail)
            foreach ($data['supplier_rows'] as $row) {
                $idSupplier = $row['id_supplier'];
                $batchSap = $row['batch_sap'];
                $qty = round((float) $row['rundownSupplier'], 4);

                if ($qty <= 0) continue;

                // CHECK IF TRACE DETAIL ALREADY EXISTS FOR THIS SUPPLIER+BATCH UNDER THIS TRACE HEAD
                $existing = DB::connection($connection)->select(
                    'SELECT id_balance_tail, id_trace_tail, in_qty
                       FROM t_trace_detail
                      WHERE status = 1
                        AND id_trace_head = ?
                        AND id_supplier = ?
                        AND batch_sap = ?
                      LIMIT 1 FOR UPDATE',
                    [$idTraceHead, $idSupplier, $batchSap]
                );

                if (empty($existing)) {
                    // INSERT NEW BALANCE DETAIL
                    $idTail = DB::connection($connection)->table('t_balance_detail')->insertGetId([
                        'id_balance_head' => $idHead,
                        'id_supplier' => $idSupplier,
                        'id_manufacturer' => $row['id_manufacturer'] ?? null,
                        'id_material' => $data['id_material'],
                        'id_sloc' => $data['id_sloc'] ?? '[]',
                        'qty' => $qty,
                        'in_qty' => $qty,
                        'out_qty' => 0,
                        'init_qty' => $qty,
                        'batch_sap' => $batchSap,
                        'id_plant' => $data['id_plant'],
                        'created_by' => $data['user'],
                    ], 'id_balance_tail');

                    // INSERT TRACE DETAIL
                    DB::connection($connection)->table('t_trace_detail')->insert([
                        'id_trace_head' => $idTraceHead,
                        'id_balance_tail' => $idTail,
                        'id_supplier' => $idSupplier,
                        'id_manufacturer' => $row['id_manufacturer'] ?? null,
                        'id_material' => $data['id_material'],
                        'id_sloc' => $data['id_sloc'] ?? '[]',
                        'in_qty' => $qty,
                        'batch_sap' => $batchSap,
                        'id_plant' => $data['id_plant'],
                        'created_by' => $data['user'],
                    ]);
                } else {
                    // ACCUMULATE INTO EXISTING ROW (multi-feed scenario)
                    $idTail = $existing[0]->id_balance_tail;
                    $idTraceTail = $existing[0]->id_trace_tail;
                    $newQty = round((float) $existing[0]->in_qty + $qty, 4);

                    DB::connection($connection)->table('t_balance_detail')
                        ->where('id_balance_tail', $idTail)
                        ->update([
                            'qty' => $newQty,
                            'in_qty' => $newQty,
                            'init_qty' => $newQty,
                            'updated_by' => $data['user'],
                        ]);

                    DB::connection($connection)->table('t_trace_detail')
                        ->where('id_trace_tail', $idTraceTail)
                        ->update([
                            'in_qty' => $newQty,
                            'updated_by' => $data['user'],
                        ]);
                }
            }

            return [
                'response' => 1,
                'id_balance_head' => $idHead,
                'id_trace_head' => $idTraceHead,
            ];
        });
    }

    /**
     * Scale $rows so their 'rundownSupplier' values
     * sum exactly to $targetTotal, correcting floating-point drift.
     *
     * Mutates $rows in-place.
     */
    public static function adjustRundownToTotal(array &$rows, mixed $targetTotal): void
    {
        $targetTotal = self::normalizeNumber($targetTotal);

        $total = '0';
        foreach ($rows as $row) {
            $total = bcadd($total, self::normalizeNumber($row['rundownSupplier']), 10);
        }

        if (bccomp($total, '0', 10) === 0) return;

        $factor = bcdiv($targetTotal, $total, 10);

        $newTotal = '0';
        foreach ($rows as &$row) {
            $adjusted = bcmul(self::normalizeNumber($row['rundownSupplier']), $factor, 10);
            $adjusted = round((float) $adjusted, 4);

            $row['rundownSupplier'] = $adjusted;
            $newTotal = bcadd($newTotal, self::normalizeNumber($adjusted), 10);
        }
        unset($row);

        $lastIndex = array_key_last($rows);
        $delta = bcsub($targetTotal, $newTotal, 10);
        $rows[$lastIndex]['rundownSupplier'] += round((float) $delta, 4);
    }

    private static function normalizeNumber(mixed $num): string
    {
        if ($num === null) return '0';

        $str = (string) $num;

        if (stripos($str, 'e') !== false) {
            $str = sprintf('%.14F', (float) $str);
        }

        return $str;
    }
}
