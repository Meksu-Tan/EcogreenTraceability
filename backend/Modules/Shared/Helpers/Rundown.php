<?php declare(strict_types=1);
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
        $connection = 'eudr_ts';

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

                // INSERT TRACE HEADER (represents new inbound flow into existing balance)
                $idTraceHead = DB::connection($connection)->table('t_trace_header')->insertGetId([
                    'from_trace_no' => $data['from_trace_no'] ?? null,
                    'to_trace_no' => $data['trace_no'],
                    'id_balance_head' => $idHead,
                    'id_material' => $data['id_material'],
                    'entry_date' => $data['entry_date'],
                    'id_sloc' => $data['id_tank'] ?? $data['id_sloc'] ?? 0,
                    'id_tank_tail' => $data['id_tank_tail'] ?? null,
                    'in_qty' => $data['in_qty'],
                    'last_qtf' => $data['last_qtf'] ?? 0,
                    'curr_qtf' => $data['curr_qtf'] ?? 0,
                    'id_plant' => $data['id_plant'],
                    'created_by' => $data['user'],
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

                // INSERT TRACE HEADER
                $idTraceHead = DB::connection($connection)->table('t_trace_header')->insertGetId([
                    'from_trace_no' => $data['from_trace_no'] ?? null,
                    'to_trace_no' => $data['trace_no'],
                    'id_balance_head' => $idHead,
                    'id_material' => $data['id_material'],
                    'entry_date' => $data['entry_date'],
                    'id_sloc' => $data['id_tank'] ?? $data['id_sloc'] ?? 0,
                    'id_tank_tail' => $data['id_tank_tail'] ?? null,
                    'in_qty' => $data['in_qty'],
                    'last_qtf' => $data['last_qtf'] ?? 0,
                    'curr_qtf' => $data['curr_qtf'] ?? 0,
                    'id_plant' => $data['id_plant'],
                    'created_by' => $data['user'],
                ]);
            }

            // INSERT/UPDATE SUPPLIER ROWS (balance_detail + trace_detail)
            foreach ($data['supplier_rows'] as $row) {
                $idSupplier = $row['id_supplier'];
                $batchSap = $row['batch_sap'];
                $qty = round($row['rundownSupplier'], 4);

                if ($qty <= 0) continue;

                // CHECK IF TRACE DETAIL ALREADY EXISTS FOR THIS SUPPLIER+BATCH UNDER THIS TRACE HEAD
                $existing = DB::connection($connection)->select(
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

                    // INSERT TRACE DETAIL
                    DB::connection($connection)->table('t_trace_detail')->insert([
                        'id_trace_head' => $idTraceHead,
                        'id_balance_tail' => $idTail,
                        'id_supplier' => $idSupplier,
                        'id_material' => $data['id_material'],
                        'id_sloc' => $data['id_tank'] ?? $data['id_sloc'] ?? 0,
                        'id_tank_tail' => $data['id_tank_tail'] ?? null,
                        'in_qty' => $qty,
                        'batch_sap' => $batchSap,
                        'id_plant' => $data['id_plant'],
                        'created_by' => $data['user'],
                    ]);
                } else {
                    // ACCUMULATE INTO EXISTING ROW (multi-feed scenario)
                    $idTail = $existing[0]->id_balance_tail;
                    $idTraceTail = $existing[0]->id_trace_tail;
                    $newQty = round($existing[0]->in_qty + $qty, 4);

                    DB::connection($connection)->update(
                        'UPDATE t_balance_detail SET qty = ?, in_qty = ?, init_qty = ?, updated_by = ? WHERE id_balance_tail = ?',
                        [$newQty, $newQty, $newQty, $data['user'], $idTail]
                    );

                    DB::connection($connection)->update(
                        'UPDATE t_trace_detail SET in_qty = ?, updated_by = ? WHERE id_trace_tail = ?',
                        [$newQty, $data['user'], $idTraceTail]
                    );
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
