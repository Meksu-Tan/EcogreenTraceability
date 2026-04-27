<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Rundown extends Model
{
    protected $connection = 'eudr_ts';

    /**
     * Insert a new rundown balance entry together with all its supplier rows.
     *
     * Wraps every INSERT/UPDATE in a single DB transaction so that a failure
     * at any point (e.g. supplier row loop) rolls back the balance header and
     * trace header that were already written, keeping the database consistent.
     *
     * Returns:
     *   response 1  → success, with id_balance_head and id_trace_head
     *   (throws on DB error — caller should catch and return response 3 or re-throw)
     */
    public static function generalRundown(array $data): array
    {
        return DB::transaction(function () use ($data) {

            // INSERT BALANCE HEADER
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

            // INSERT TRACE HEADER
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

            // INSERT SUPPLIER ROWS (balance_detail + trace_detail)
            foreach ($data['supplier_rows'] as $row) {

                $idSupplier = $row['id_supplier'];
                $batchSap   = $row['batch_sap'];
                $qty        = round($row['rundownSupplier'], 4);

                if ($qty <= 0) continue;

                // Guard: check if a detail row for this supplier+batch already
                // exists under this trace head (can happen with multi-head feeds).
                $existing = DB::select(
                    'SELECT id_balance_tail, id_trace_tail, in_qty
                       FROM t_trace_detail
                      WHERE status = 1
                        AND id_trace_head = ?
                        AND id_supplier = ?
                        AND batch_sap = ?
                      LIMIT 1',
                    [$idTraceHead, $idSupplier, $batchSap]
                );

                if (empty($existing)) {

                    // New supplier row for this rundown
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

                    // Accumulate into existing row (multi-feed scenario)
                    $idTail      = $existing[0]->id_balance_tail;
                    $idTraceTail = $existing[0]->id_trace_tail;
                    $newQty      = round($existing[0]->in_qty + $qty, 4);

                    DB::update(
                        'UPDATE t_balance_detail
                         SET qty = ?, in_qty = ?, init_qty = ?, updated_by = ?
                         WHERE id_balance_tail = ?',
                        [$newQty, $newQty, $newQty, $data['user'], $idTail]
                    );

                    DB::update(
                        'UPDATE t_trace_detail
                         SET in_qty = ?, updated_by = ?
                         WHERE id_trace_tail = ?',
                        [$newQty, $data['user'], $idTraceTail]
                    );
                }
            }

            return [
                'response'        => 1,
                'id_balance_head' => $idHead,
                'id_trace_head'   => $idTraceHead,
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Normalize a number string so bcmath never receives scientific notation
     * (e.g. "1.5E-4" → "0.00015000000000000").
     */
    private static function normalizeNumber(mixed $num): string
    {
        if ($num === null) return '0';

        $str = (string) $num;

        if (stripos($str, 'e') !== false) {
            $str = sprintf('%.14F', (float) $str);
        }

        return $str;
    }

    /**
     * Scale $rows so that their 'rundownSupplier' values sum exactly to
     * $targetTotal, correcting floating-point drift from yield multiplication.
     *
     * Mutates $rows in-place.
     */
    public static function adjustRundownToTotal(array &$rows, mixed $targetTotal): void
    {
        $targetTotal = self::normalizeNumber($targetTotal);

        // Step 1: sum of raw values
        $total = '0';
        foreach ($rows as $row) {
            $total = bcadd($total, self::normalizeNumber($row['rundownSupplier']), 10);
        }

        if (bccomp($total, '0', 10) === 0) return;

        // Step 2: scaling factor
        $factor = bcdiv($targetTotal, $total, 10);

        // Step 3: apply factor with 4dp rounding
        $newTotal = '0';
        foreach ($rows as &$row) {
            $adjusted = bcmul(self::normalizeNumber($row['rundownSupplier']), $factor, 10);
            $adjusted = round((float) $adjusted, 4);

            $row['rundownSupplier'] = $adjusted;
            $newTotal = bcadd($newTotal, self::normalizeNumber($adjusted), 10);
        }
        unset($row);

        // Step 4: push rounding remainder into the last row
        $lastIndex = array_key_last($rows);
        $delta     = bcsub($targetTotal, $newTotal, 10);
        $rows[$lastIndex]['rundownSupplier'] += round((float) $delta, 4);
    }
}
