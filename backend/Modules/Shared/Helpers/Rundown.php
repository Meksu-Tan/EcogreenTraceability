<?php

namespace Modules\Shared\Helpers;

use Illuminate\Support\Facades\DB;

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
        $connection = $data['id_plant'] ?? 0;

        return DB::connection($connection)->transaction(function () use ($data, $connection) {

            // CHECK IF BALANCE HEADER ALREADY EXISTS FOR THIS TRACE_NO
            $existingHead = DB::connection($connection)->select(
                'SELECT id_balance_head, qty, in_qty, init_qty
                   FROM t_balance_header
                  WHERE trace_no = ?
                    AND id_material = ?
                    AND COALESCE(id_sloc, id_tank) = COALESCE(?, ?)
                    AND status = 1
                  LIMIT 1',
                [$data['trace_no'], $data['id_material'], $data['id_sloc'] ?? null, $data['id_tank'] ?? null]
            );

            if (!empty($existingHead)) {
                // UPDATE EXISTING BALANCE HEADER
                $idHead = $existingHead[0]->id_balance_head;
                $newQty = round($existingHead[0]->qty + $data['in_qty'], 4);
                $newInQty = round($existingHead[0]->in_qty + $data['in_qty'], 4);
                $newInitQty = round($existingHead[0]->init_qty + $data['in_qty'], 4);

                DB::connection($connection)->table('t_balance_header')
                    ->where('id_balance_head', $idHead)
                    ->update([
                        'qty' => $newQty,
                        'in_qty' => $newInQty,
                        'init_qty' => $newInitQty,
                        'updated_by' => $data['user'],
                    ]);
            } else {
                // INSERT NEW BALANCE HEADER
                $idHead = DB::connection($connection)->table('t_balance_header')->insertGetId([
                    'entry_date' => $data['entry_date'],
                    'trace_no' => $data['trace_no'],
                    'id_material' => $data['id_material'],
                    'id_tank' => $data['id_tank'],
                    'id_sloc' => $data['id_sloc'] ?? null,
                    'id_tank_tail' => $data['id_tank_tail'] ?? $data['id_sloc_tail'] ?? null,
                    'id_sloc_tail' => $data['id_sloc_tail'] ?? $data['id_tank_tail'] ?? null,
                    'qty' => $data['in_qty'],
                    'in_qty' => $data['in_qty'],
                    'out_qty' => 0,
                    'init_qty' => $data['in_qty'],
                    'id_plant' => $data['id_plant'],
                    'created_by' => $data['user'],
                ]);
            }

            // INSERT/UPDATE SUPPLIER ROWS (balance_detail only)
            foreach ($data['supplier_rows'] as $row) {
                $idSupplier = $row['id_supplier'];
                $batchSap = $row['batch_sap'];
                $qty = round($row['rundownSupplier'], 4);

                if ($qty <= 0) continue;

                // CHECK IF BALANCE DETAIL ALREADY EXISTS FOR THIS SUPPLIER+BATCH
                $existingTail = DB::connection($connection)->select(
                    'SELECT id_balance_tail, qty, in_qty, init_qty
                       FROM t_balance_detail
                      WHERE id_balance_head = ?
                        AND id_supplier = ?
                        AND batch_sap = ?
                        AND status = 1
                      LIMIT 1',
                    [$idHead, $idSupplier, $batchSap]
                );

                if (!empty($existingTail)) {
                    // UPDATE EXISTING BALANCE DETAIL
                    $idTail = $existingTail[0]->id_balance_tail;
                    $newQty = round($existingTail[0]->qty + $qty, 4);
                    $newInQty = round($existingTail[0]->in_qty + $qty, 4);
                    $newInitQty = round($existingTail[0]->init_qty + $qty, 4);

                    DB::connection($connection)->table('t_balance_detail')
                        ->where('id_balance_tail', $idTail)
                        ->update([
                            'qty' => $newQty,
                            'in_qty' => $newInQty,
                            'init_qty' => $newInitQty,
                            'updated_by' => $data['user'],
                        ]);
                } else {
                    // INSERT NEW BALANCE DETAIL
                    $idTail = DB::connection($connection)->table('t_balance_detail')->insertGetId([
                        'id_balance_head' => $idHead,
                        'id_supplier' => $idSupplier,
                        'id_material' => $data['id_material'],
                        'id_tank' => $data['id_tank'],
                        'id_sloc' => $data['id_sloc'] ?? null,
                        'id_tank_tail' => $data['id_tank_tail'] ?? $data['id_sloc_tail'] ?? null,
                        'id_sloc_tail' => $data['id_sloc_tail'] ?? $data['id_tank_tail'] ?? null,
                        'qty' => $qty,
                        'in_qty' => $qty,
                        'out_qty' => 0,
                        'init_qty' => $qty,
                        'batch_sap' => $batchSap,
                        'id_plant' => $data['id_plant'],
                        'created_by' => $data['user'],
                    ]);
                }
            }

            return [
                'response' => 1,
                'id_balance_head' => $idHead,
                'id_trace_head' => null,
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
        $delta = bcsub($targetTotal, $newTotal, 10);
        $rows[$lastIndex]['rundownSupplier'] += round((float) $delta, 4);
    }

    /**
     * Normalize a number string so bcmath never receives scientific notation.
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
}
