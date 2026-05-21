<?php

namespace Modules\Shared\Helpers;

use Illuminate\Support\Facades\DB;

class Rundown
{
    /**
     * Insert a new rundown balance entry together with all its supplier rows.
     */
    public static function generalRundown(array $data): array
    {
        return DB::connection('eudr_ts')->transaction(function () use ($data) {

            $traceNo = is_numeric($data['trace_no'])
                ? (int) $data['trace_no']
                : (int) preg_replace('/\D/', '', (string) $data['trace_no']);
            $fromTraceNo = null;
            if (!empty($data['from_trace_no'])) {
                $fromTraceNo = is_numeric($data['from_trace_no'])
                    ? (int) $data['from_trace_no']
                    : (int) preg_replace('/\D/', '', (string) $data['from_trace_no']);
            }

            // INSERT BALANCE HEADER
            $idHead = DB::connection('eudr_ts')->table('t_balance_header')->insertGetId([
                'entry_date'   => $data['entry_date'],
                'trace_no'     => $traceNo,
                'id_material'  => $data['id_material'],
                'id_sloc'      => $data['id_tank'],
                'id_sloc_tail' => $data['id_tank_tail'],
                'id_tank'      => null,
                'id_tank_tail' => null,
                'qty'          => $data['in_qty'],
                'in_qty'       => $data['in_qty'],
                'init_qty'     => $data['in_qty'],
                'id_plant'     => $data['id_plant'],
                'created_by'   => $data['user'],
                'created_at'   => now(),
            ]);

            // INSERT TRACE HEADER
            $idTraceHead = DB::connection('eudr_ts')->table('t_trace_header')->insertGetId([
                'from_trace_no'   => $fromTraceNo,
                'to_trace_no'     => $traceNo,
                'id_balance_head' => $idHead,
                'id_material'     => $data['id_material'],
                'entry_date'      => $data['entry_date'],
                'id_sloc'         => $data['id_tank'],
                'id_tank_tail'    => $data['id_tank_tail'],
                'in_qty'          => $data['in_qty'],
                'last_qtf'        => $data['last_qtf'] ?? 0,
                'curr_qtf'        => $data['curr_qtf'] ?? 0,
                'id_plant'        => $data['id_plant'],
                'created_by'      => $data['user'],
                'created_at'      => now(),
            ]);

            // INSERT SUPPLIER ROWS (balance_detail + trace_detail)
            foreach ($data['supplier_rows'] as $row) {

                $idSupplier = $row['id_supplier'];
                $batchSap   = $row['batch_sap'];
                $qty        = round($row['rundownSupplier'], 4);

                if ($qty <= 0) continue;

                // Guard: check if a detail row for this supplier+batch already
                // exists under this trace head (can happen with multi-head feeds).
                $existing = DB::connection('eudr_ts')->select(
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
                    $idTail = DB::connection('eudr_ts')->table('t_balance_detail')->insertGetId([
                        'id_balance_head' => $idHead,
                        'id_supplier'     => $idSupplier,
                        'id_material'     => $data['id_material'],
                        'id_tank'         => null,
                        'id_tank_tail'    => null,
                        'qty'             => $qty,
                        'in_qty'          => $qty,
                        'init_qty'        => $qty,
                        'batch_sap'       => $batchSap,
                        'id_plant'        => $data['id_plant'],
                        'status'          => 1,
                        'created_by'      => $data['user'],
                        'created_at'      => now(),
                    ]);

                    DB::connection('eudr_ts')->table('t_trace_detail')->insert([
                        'id_trace_head'   => $idTraceHead,
                        'id_balance_tail' => $idTail,
                        'id_supplier'     => $idSupplier,
                        'id_material'     => $data['id_material'],
                        'id_sloc'         => $data['id_tank'],
                        'id_sloc_tail'    => $data['id_tank_tail'],
                        'in_qty'          => $qty,
                        'batch_sap'       => $batchSap,
                        'id_plant'        => $data['id_plant'],
                        'created_by'      => $data['user'],
                        'created_at'      => now(),
                    ]);

                } else {

                    // Accumulate into existing row (multi-feed scenario)
                    $idTail      = $existing[0]->id_balance_tail;
                    $idTraceTail = $existing[0]->id_trace_tail;
                    $newQty      = round($existing[0]->in_qty + $qty, 4);

                    DB::connection('eudr_ts')->table('t_balance_detail')
                        ->where('id_balance_tail', $idTail)
                        ->update([
                            'qty' => $newQty,
                            'in_qty' => $newQty,
                            'init_qty' => $newQty,
                            'updated_by' => $data['user'],
                            'updated_at' => now(),
                        ]);

                    DB::connection('eudr_ts')->table('t_trace_detail')
                        ->where('id_trace_tail', $idTraceTail)
                        ->update([
                            'in_qty' => $newQty,
                            'updated_by' => $data['user'],
                            'updated_at' => now(),
                        ]);
                }
            }

            return [
                'response'        => 1,
                'id_balance_head' => $idHead,
                'id_trace_head'   => $idTraceHead,
            ];
        });
    }

    /**
     * Scale $rows so that their 'rundownSupplier' values sum exactly to $targetTotal.
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
        $delta     = bcsub($targetTotal, $newTotal, 10);
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
