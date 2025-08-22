<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Shipment extends Model
{
    protected $connection = 'eudr_ts';

    static function get_activeFgProduct(){
        // $db = DB::select('SELECT CONCAT(UPPER(a.description), " (", a.code, ")") AS material,
        //                          CONCAT("WIP|", a.id_material) AS id_material
        //                     FROM m_material a
        //                    WHERE a.type = "PRD"
        //                      AND a.status = 1
        //                    UNION ALL
        //                   SELECT CONCAT(UPPER(a.description), " (", a.code, ")") AS material,
        //                          CONCAT("PCK|", a.id_materialpck) AS id_material
        //                     FROM m_material_pck a
        //                    WHERE a.status = 1
        //                    ORDER BY material ASC');
        $db = DB::select('SELECT CONCAT(UPPER(a.description), " (", a.code, ")") AS material,
                                 CONCAT("PCK|", a.id_materialpck) AS id_material
                            FROM m_material_pck a
                           WHERE a.status = 1
                           ORDER BY material ASC');

        return $db;
    }
    static function get_activeBatchProduct($request){
        $idMaterialPck = $request->input('idMaterial');
        $parts = explode('|', $idMaterialPck);
        $type = $parts[0];
        $idMaterial = $parts[1];

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $db = DB::select('SELECT a.batch_no, CONCAT(a.batch_no, " | Qty : ", FORMAT(b.qty,3)) AS `description`
                            FROM t_warehouse_header a
                            LEFT JOIN (SELECT b.id_material_fg, b.batch_no, SUM(b.qty) AS qty
                                         FROM t_warehouse_header b
                                        WHERE b.status = 1
                                        GROUP BY b.id_material_fg, b.batch_no) b
                              ON a.batch_no = b.batch_no AND a.id_material_fg = b.id_material_fg
                           WHERE a.id_material_fg = ?
                             AND a.`status` = 1
                             AND a.qty > "0.000001"
                           GROUP BY a.batch_no', [$idMaterial]);

        return $db;
    }
    static function get_wipMaterialByFgProduct($request){
        $dat = $request->input('idMaterial');
        $parts = explode('|', $dat);
        $type = $parts[0]; // "WIP"
        $idMaterial = $parts[1]; // "16"

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        if ($type == 'WIP'){
            $db = DB::select('SELECT FORMAT(IFNULL(SUM(a.qty),0), 3) AS balance,
                                     IFNULL(CONCAT(b.description, " (", b.code, ") || Balance : ", FORMAT(IFNULL(SUM(a.qty),0),3), " MT" ), CONCAT(b.description, " (", b.code, ") || Balance : 0.000 MT" )) AS wip_material
                                FROM t_balance_header a
                                LEFT JOIN m_material b
                                  ON a.id_material = b.id_material
                               WHERE a.id_material = ?
                                 AND a.status = 1
                             ', [$idMaterial]);

        } elseif ($type == 'PCK'){
            $db = DB::select('SELECT FORMAT(IFNULL(SUM(a.qty),0), 3) AS balance,
                                     IFNULL(CONCAT(b.description, " (", b.code, ") || Balance : ", FORMAT(IFNULL(SUM(a.qty),0),3), " MT" ), CONCAT(b.description, " (", b.code, ") || Balance : 0.000 MT" )) AS wip_material
                                FROM t_warehouse_header a
                                LEFT JOIN m_material_pck b
                                  ON a.id_material_fg = b.id_materialpck
                               WHERE a.id_material_fg = ?
                                 AND a.status = 1
                             ', [$idMaterial]);

        }

        return $db;
    }
    static function get_dtShipEntry(){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.id_ship_head, a.entry_date, CONCAT(CAST(dd.from_trace_no AS CHAR), " >>> ", CAST(dd.trace_no AS CHAR) ) AS fromto_trace_no,
                                 a.so_no, a.id_material_fg, FORMAT(ROUND(f.qty,3), 3) AS qty , a.status, a.created_by, a.created_at, a.updated_by, a.updated_at,
                                 IF(SUBSTRING(a.from_trace_no,1,1) < 3, g.`description`, c.`description`) AS material,
                                 f.id_trace_head, f.id_balance_head, a.trace_no, a.from_trace_no, f.batch_no,
                                 GROUP_CONCAT(DISTINCT CONCAT(d.description, " / ", d.batch_sap, " / Qty: ", FORMAT(d.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                 FORMAT(ROUND(dd.qty,3),3) AS balance_supplier, a.doc_url,
                                 CASE
                                    WHEN a.trace_no = (SELECT to_trace_no
                                                         FROM t_trace_header
                                                        WHERE SUBSTRING(to_trace_no, 1, 1) = 5
                                                          AND `status` = 1
                                                        ORDER BY id_trace_head DESC LIMIT 1) THEN 1
                                    ELSE NULL
                                 END AS is_last_row,
                                 CASE
                                    WHEN a.trace_no = (SELECT from_trace_no
                                                         FROM t_trace_header
                                                        WHERE SUBSTRING(from_trace_no, 8, 2) = "01"
                                                          AND SUBSTRING(from_trace_no, 1, 1) = 4
                                                          AND `status` = 1
                                                        ORDER BY from_trace_no DESC LIMIT 1) THEN 1
                                    ELSE NULL
                                 END AS next_process
                            FROM t_shipment_header a
                            LEFT JOIN m_material_pck c
                              ON a.id_material_fg = c.id_materialpck
                            LEFT JOIN (SELECT dd.trace_no, e.description, d.batch_sap, SUM(ROUND(d.qty,4)) AS qty
                                         FROM t_shipment_header dd
                                         LEFT JOIN t_shipment_detail d
                                           ON dd.id_ship_head = d.id_ship_head
                                         LEFT JOIN m_supplier e
                                           ON e.id_supplier = d.id_supplier
                                        WHERE d.status = 1
                                          AND dd.status = 1
                                        GROUP BY dd.trace_no, d.batch_sap
                                      ) d
                              ON a.trace_no = d.trace_no
                            LEFT JOIN (SELECT dd.trace_no, SUM(ROUND(ee.qty,4)) AS qty, GROUP_CONCAT(DISTINCT CAST(dd.from_trace_no AS CHAR) SEPARATOR " + ") AS from_trace_no
                                         FROM t_shipment_header dd
                                         LEFT JOIN t_shipment_detail ee
                                           ON dd.id_ship_head = ee.id_ship_head
                                        WHERE dd.status = 1
                                        GROUP BY dd.trace_no
                                      ) dd
                              ON a.trace_no = dd.trace_no
                            LEFT JOIN (SELECT f.to_trace_no, f.id_trace_head, f.id_balance_head, ff.batch_no,
                                              SUM(ROUND(f.out_qty,4)) AS qty
                                         FROM t_trace_header f
                                         LEFT JOIN t_warehouse_header ff
                                           ON f.id_balance_head = ff.id_whx_head AND ff.status = 1
                                        WHERE f.status = 1
                                        GROUP BY f.to_trace_no
                                        ) f
                              ON f.to_trace_no = a.trace_no
                            LEFT JOIN m_material g
                              ON g.id_material = a.id_material_fg
                           WHERE a.status = 1
                           GROUP BY a.trace_no
                           ORDER BY a.entry_date DESC, id_ship_head DESC');

        return $db;
    }
    static function post_cancelShip($user, $request){
        $traceNo = $request->input('traceNo');
        $idShipHead = $request->input('idShipHead');
        $idTraceHead = $request->input('idTraceHead');
        $fromTraceNo = $request->input('fromTraceNo');

        preg_match('/\d/', $fromTraceNo, $matches);
        $origin = $matches[0];

        /* UPDATE BALANCE FROM WIP | WAREHOUSE */
            $datTraceHead = DB::select('SELECT from_trace_no, id_balance_head, out_qty, id_trace_head
                                          FROM t_trace_header
                                         WHERE to_trace_no = ?
                                           AND `status` = 1', [$traceNo]);

            if (count($datTraceHead) == 0){
                $db = [ (object)['response' => 4 ]];
                return $db;
            }

            $lenTraceHead = count($datTraceHead);
            for ($k = 0; $k < $lenTraceHead; $k++){
                $idTraceHead = $datTraceHead[$k]->id_trace_head;
                $idHead = $datTraceHead[$k]->id_balance_head;
                $outQtyShip = $datTraceHead[$k]->out_qty;
                $fromTraceNo = $datTraceHead[$k]->from_trace_no;

                $datTraceTail = DB::select('SELECT id_balance_tail, out_qty
                                              FROM t_trace_detail
                                             WHERE id_trace_head = ?
                                               AND `status` = 1', [$idTraceHead]);
                $lenTraceTail = count($datTraceTail);

                if ($origin == 4){
                    /* GET EXISTING QTY IN WAREHOUSE */
                        $datWhxHead = DB::select('SELECT qty, out_qty
                                                    FROM t_warehouse_header
                                                   WHERE id_whx_head = ?
                                                     AND `status` = 1', [$idHead]);
                        $whxBalQty = $datWhxHead[0]->qty;
                        $whxOutQty = $datWhxHead[0]->out_qty;

                        $whxBalQty_new = $whxBalQty + $outQtyShip;
                        $whxOutQty_new = $whxOutQty - $outQtyShip;

                    /* UPDATE WAREHOUSE BALANCE */
                        DB::update('UPDATE t_warehouse_header
                                       SET qty = ?,
                                           out_qty = ?,
                                           updated_by = ?
                                     WHERE id_whx_head = ?
                                       AND `status` = 1', [$whxBalQty_new, $whxOutQty_new, $user, $idHead]);

                    /* UPDATE TO STATUS = 0 */
                        DB::update('UPDATE t_trace_header
                                       SET `status` = 0,
                                           updated_by = ?
                                     WHERE id_trace_head = ?', [$user, $idTraceHead]);

                        DB::update('UPDATE t_shipment_header
                                       SET `status` = 0,
                                           updated_by = ?
                                     WHERE from_trace_no = ?
                                       AND trace_no = ?
                                       AND qty = ?', [$user, $fromTraceNo, $traceNo, $outQtyShip]);
                        $idShipHead = DB::select('SELECT id_ship_head
                                                    FROM t_shipment_header
                                                   WHERE from_trace_no = ?
                                                     AND trace_no = ?
                                                     AND qty = ?', [$fromTraceNo, $traceNo, $outQtyShip]);
                        DB::update('UPDATE t_shipment_detail
                                       SET `status` = 0,
                                           updated_by = ?
                                     WHERE id_ship_head = ?', [$user, $idShipHead[0]->id_ship_head]);

                    /* LOGGING */
                        DB::insert('INSERT INTO log_transactions
                                           (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)', [ 'T_WAREHOUSE_HEAD', 'UPDATE', 'IDHEAD: ' . $idHead .
                                                            ' | QTY: ' . $whxBalQty . ' >>> ' . $whxBalQty_new .
                                                            ' / OUT_QTY: ' . $whxOutQty . ' >>> ' . $whxOutQty_new .
                                                            ' | Status: 1', $user ]);

                    /* TRACE DETAIL */
                        for ($i = 0; $i < $lenTraceTail; $i++){
                            /* CALCULATE WAREHOUSE DETAIL */
                                $idTail = $datTraceTail[$i]->id_balance_tail;
                                $outQtyShipTail = $datTraceTail[$i]->out_qty;

                                $datWhxTail = DB::select('SELECT qty, out_qty
                                                            FROM t_warehouse_detail
                                                           WHERE id_whx_tail = ?', [$idTail]);
                                $whxBalQtyTail = $datWhxTail[0]->qty;
                                $whxOutQtyTail = $datWhxTail[0]->out_qty;

                                $whxBalQtyTail_new = $whxBalQtyTail + $outQtyShipTail;
                                $whxOutQtyTail_new = $whxOutQtyTail - $outQtyShipTail;

                            /* UPDATE WAREHOUSE DETAIL */
                                DB::update('UPDATE t_warehouse_detail
                                               SET qty = ?,
                                                   out_qty = ?,
                                                   updated_by = ?
                                             WHERE id_whx_tail = ?', [$whxBalQtyTail_new, $whxOutQtyTail_new, $user, $idTail]);
                            /* UPDATE STATUS */
                                DB::update('UPDATE t_trace_detail
                                               SET `status` = 0,
                                                   updated_by = ?
                                             WHERE id_trace_tail = ?', [$user, $idTail]);
                            /* LOGGING */
                                DB::insert('INSERT INTO log_transactions
                                                   (log_module, log_type, log_description, created_by)
                                            VALUES (?, ?, ?, ?)', [ 'T_WAREHOUSE_DETAIL', 'UPDATE', 'IDTAIL: ' . $idTail .
                                                                    ' | QTY: ' . $whxBalQtyTail . ' >>> ' . $whxBalQtyTail_new .
                                                                    ' / OUT_QTY: ' . $whxOutQtyTail . ' >>> ' . $whxOutQtyTail_new .
                                                                    ' | Status: 1', $user ]);
                            }

                } elseif ($origin == 1){
                    /* GET EXISTING QTY IN WIP */
                        $datWipHead = DB::select('SELECT qty, out_qty
                                                    FROM t_balance_header
                                                   WHERE id_balance_head = ?
                                                     AND `status` = 1', [$idHead]);
                        $wipBalQty = $datWipHead[0]->qty;
                        $wipOutQty = $datWipHead[0]->out_qty;

                        $wipBalQty_new = $wipBalQty + $outQtyShip;
                        $wipOutQty_new = $wipOutQty - $outQtyShip;

                    /* UPDATE WIP BALANCE */
                        DB::update('UPDATE t_balance_header
                                       SET qty = ?,
                                           out_qty = ?,
                                           updated_by = ?
                                     WHERE id_balance_head = ?
                                       AND `status` = 1', [$wipBalQty_new, $wipOutQty_new, $user, $idHead]);

                    /* UPDATE TO STATUS = 0 */
                        DB::update('UPDATE t_trace_header
                                       SET `status` = 0,
                                           updated_by = ?
                                     WHERE id_trace_head = ?', [$user, $idTraceHead]);

                        DB::update('UPDATE t_shipment_header
                                       SET `status` = 0,
                                           updated_by = ?
                                     WHERE from_trace_no = ?
                                       AND trace_no = ?
                                       AND qty = ?', [$user, $fromTraceNo, $traceNo, $outQtyShip]);
                        $idShipHead = DB::select('SELECT id_ship_head
                                                    FROM t_shipment_header
                                                   WHERE from_trace_no = ?
                                                     AND trace_no = ?
                                                     AND qty = ?', [$fromTraceNo, $traceNo, $outQtyShip]);
                        DB::update('UPDATE t_shipment_detail
                                       SET `status` = 0,
                                           updated_by = ?
                                     WHERE id_ship_head = ?', [$user, $idShipHead[0]->id_ship_head]);

                    /* LOGGING */
                        DB::insert('INSERT INTO log_transactions
                                        (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'UPDATE', 'IDHEAD: ' . $idHead .
                                                            ' | QTY: ' . $wipBalQty . ' >>> ' . $wipBalQty_new .
                                                            ' / OUT_QTY: ' . $wipOutQty . ' >>> ' . $wipOutQty_new .
                                                            ' | Status: 1', $user ]);

                    /* TRACE DETAIL */
                        for ($i = 0; $i < $lenTraceTail; $i++){
                            /* CALCULATE WIP DETAIL */
                                $idTail = $datTraceTail[$i]->id_balance_tail;
                                $outQtyShipTail = $datTraceTail[$i]->out_qty;

                                $datWhxTail = DB::select('SELECT qty, out_qty
                                                            FROM t_balance_detail
                                                           WHERE id_balance_tail = ?', [$idTail]);
                                $whxBalQtyTail = $datWhxTail[0]->qty;
                                $whxOutQtyTail = $datWhxTail[0]->out_qty;

                                $whxBalQtyTail_new = $whxBalQtyTail + $outQtyShipTail;
                                $whxOutQtyTail_new = $whxOutQtyTail - $outQtyShipTail;
                            /* UPDATE WIP DETAIL */
                                DB::update('UPDATE t_balance_detail
                                               SET qty = ?,
                                                   out_qty = ?,
                                                   updated_by = ?
                                             WHERE id_balance_tail = ?', [$whxBalQtyTail_new, $whxOutQtyTail_new, $user, $idTail]);
                            /* UPDATE STATUS */
                                DB::update('UPDATE t_trace_detail
                                               SET `status` = 0,
                                                   updated_by = ?
                                             WHERE id_trace_tail = ?', [$user, $idTail]);
                            /* LOGGING */
                                DB::insert('INSERT INTO log_transactions
                                                (log_module, log_type, log_description, created_by)
                                            VALUES (?, ?, ?, ?)', [ 'T_BALANCE_DETAIL', 'UPDATE', 'IDTAIL: ' . $idTail .
                                                                    ' | QTY: ' . $whxBalQtyTail . ' >>> ' . $whxBalQtyTail_new .
                                                                    ' / OUT_QTY: ' . $whxOutQtyTail . ' >>> ' . $whxOutQtyTail_new .
                                                                    ' | Status: 1', $user ]);
                        }
                }
            }

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function post_entryShip($user, $request){
        $id = $request->input('id');
        $entryDate = $request->input('entryDate');
        $idMaterialPck = $request->input('fgProduct');
        $soNo = $request->input('soNo');
        $out_qty = $request->input('qty');
        $batchNo = $request->input('batch_no');
        $fileName = $request->input('filename');

        $parts = explode('|', $idMaterialPck);
        $type = $parts[0]; // "WIP"
        $idMaterial = $parts[1]; // "16"

        /* DO ROUTING SHIPMENT ENTRY */
            if ($type == 'WIP'){


            } elseif ($type == 'PCK'){
                $shID = '01';

                /* CREATE SHIPMENT BATCH NUMBER */
                $datPckBatch = DB::select('SELECT a.pck_batch
                                             FROM (SELECT a.to_trace_no + 1 AS pck_batch
                                                     FROM t_trace_header a
                                                    WHERE SUBSTRING(a.to_trace_no,1,9) = CONCAT(5, DATE_FORMAT(CURDATE(), "%y%m%d"), ?)
                                                      AND a.status = 1
                                                    ORDER BY a.id_trace_head DESC
                                                    LIMIT 1 ) a
                                             UNION ALL
                                            SELECT CONCAT(5, DATE_FORMAT(CURDATE(), "%y%m%d"), ? , "01") AS pck_batch
                                             LIMIT 1', [$shID, $shID]);
                $traceNo = $datPckBatch[0]->pck_batch;

                /* FIND BALANCE STOCK */
                $datHead = DB::select('SELECT a.id_whx_head, a.qty, a.out_qty, a.trace_no, a.init_qty, a.id_section
                                         FROM t_warehouse_header a
                                        WHERE a.status = 1
                                          AND a.qty > "0.0001"
                                          AND a.id_material_fg = ?
                                          AND a.batch_no = ?
                                        ORDER BY a.id_whx_head ASC', [$idMaterial, $batchNo]);
                $lenHead = count($datHead);

                if ($lenHead == 0){
                    $db = [ (object)['response' => 3 ]];
                    return $db;
                }

                for ($i=0; $i < $lenHead; $i++){
                    $idHead = $datHead[$i]->id_whx_head;
                    $from_trace_no = $datHead[$i]->trace_no;
                    $qty = $datHead[$i]->qty;
                    $total_out_qty = $datHead[$i]->out_qty;
                    $init_qty = $datHead[$i]->init_qty;
                    $idWarehouse = $datHead[$i]->id_section;

                    $new_total_out_qty = $total_out_qty + $out_qty;

                    $qtyWhTail = $out_qty;

                    $balanceAfter = $qty - $out_qty;
                    if ($balanceAfter < 0){
                        $new_balance = 0;
                        $new_total_out_qty = $init_qty;
                        $leftOver_qtyWh = $out_qty - $qty;
                        $out_qty = $qty;
                    } else {
                        $new_balance = $qty - $out_qty;
                    };

                    /* GET WAREHOUSE DETAIL 2025-03-01 */
                        $datTail = DB::select('SELECT a.id_whx_tail, a.id_supplier, a.batch_sap, a.qty, a.out_qty, a.init_qty
                                                 FROM t_warehouse_detail a
                                                WHERE a.status = 1
                                                  AND a.id_whx_head = ?
                                                ORDER BY a.id_whx_tail ASC', [$idHead]);
                        $lenTail = count($datTail);

                        if ($lenTail == 0){
                            $db = [ (object)['response' => 3 ]];
                            return $db;
                        }

                    /* UPDATE INTO T_WAREHOUSE_HEADER */
                        DB::update('UPDATE t_warehouse_header
                                       SET qty = ?,
                                           out_qty = ?,
                                           updated_by = ?
                                     WHERE id_whx_head = ?',
                                     [$new_balance, $new_total_out_qty, $user, $idHead]);

                    /* INSERT TRACE HEADER FEED */
                        $idTraceHead = DB::table('t_trace_header')->insertGetId([
                                'from_trace_no' => $from_trace_no,
                                'to_trace_no' => $traceNo,
                                'id_balance_head' => $idHead,
                                'id_material' => $idMaterial,
                                'entry_date' => $entryDate,
                                'id_sloc' => $idWarehouse,
                                'out_qty' => $out_qty,
                                'curr_qtf' => $out_qty,
                                'created_by' => $user,
                            ]);

                    /* INSERT SHIPMENT HEADER */
                        $idShipHead = DB::table('t_shipment_header')->insertGetId([
                                'entry_date' => $entryDate,
                                'from_trace_no' => $from_trace_no,
                                'trace_no' => $traceNo,
                                'so_no' => $soNo,
                                'id_material_fg' => $idMaterial,
                                'qty' => $out_qty,
                                'doc_url' => $fileName,
                                'created_by' => $user,
                            ]);

                    /* HEADER LOGGING */
                        DB::insert('INSERT INTO log_transactions
                                           (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)', [ 'T_TRACE_HEAD', 'ADD SHIP', 'IDTRACEHEAD: ' . $idTraceHead . 'IDHEAD: ' . $idHead . ' | DATE: ' . $entryDate .
                                                            ' / FROM_TRACE: ' . $from_trace_no . ' / TO_TRACE: ' . $traceNo . ' / OUT_QTY: ' . $out_qty .
                                                            ' / MATERIAL: ' . $idMaterial . ' / LAST_QTF: 0 / CURR_QTF: ' . $out_qty .
                                                            ' | Status: 1', $user ]);
                        DB::insert('INSERT INTO log_transactions
                                           (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)', [ 'T_WH_HEAD', 'ADD SHIP', 'IDSHIPHEAD: ' . $idShipHead . 'IDTRACEHEAD: ' . $idTraceHead . ' | DATE: ' . $entryDate .
                                                            ' / FROM_TRACE: ' . $from_trace_no . ' / TO_TRACE: ' . $traceNo . ' / IN_QTY: ' . $out_qty .
                                                            ' / MATERIAL: ' . $idMaterial .
                                                            ' | Status: 1', $user ]);

                    /* GET WAREHOUSE DETAIL */
                        for ($k=0; $k < $lenTail; $k++){
                            $idTail = $datTail[$k]->id_whx_tail;
                            $idSupplier = $datTail[$k]->id_supplier;
                            $batchSap = $datTail[$k]->batch_sap;
                            $qtyTail = $datTail[$k]->qty;
                            $outQtyTail = $datTail[$k]->out_qty;
                            $initQtyTail = $datTail[$k]->init_qty;

                            $new_tail_total_out_qty = $outQtyTail + $qtyWhTail;

                            $tailBalanceAfter = $qtyTail - $qtyWhTail;
                            if ($tailBalanceAfter < 0){
                                $leftOver_qtyWhTail = $qtyWhTail - $qtyTail;

                                $new_tail_balance = 0;
                                $new_tail_total_out_qty = $initQtyTail;
                                $qtyWhTail = $qtyTail;
                            } else {
                                $new_tail_balance = $qtyTail - $qtyWhTail;
                            }

                            /* POPULATE NEW WAREHOUSE DETAIL */
                                DB::update('UPDATE t_warehouse_detail
                                               SET qty = ?,
                                                   out_qty = ?,
                                                   updated_by = ?
                                             WHERE id_whx_tail = ?',
                                             [$new_tail_balance, $new_tail_total_out_qty, $user, $idTail]);
                            /* POPULATE TRACE DETAIL FEED */
                                $idTraceTail = DB::table('t_trace_detail')->insertGetId([
                                        'id_trace_head' => $idTraceHead,
                                        'id_balance_tail' => $idTail,
                                        'id_supplier' => $idSupplier,
                                        'id_material' => $idMaterial,
                                        'out_qty' => $qtyWhTail,
                                        'batch_sap' => $batchSap,
                                        'created_by' => $user,
                                    ]);
                            /* INSERT SHIPMENT DETAIL */
                                $idShipTail = DB::table('t_shipment_detail')->insertGetId([
                                        'id_ship_head' => $idShipHead,
                                        'id_material_fg' => $idMaterial,
                                        'id_supplier' => $idSupplier,
                                        'batch_sap' => $batchSap,
                                        'qty' => $qtyWhTail,
                                        'created_by' => $user,
                                    ]);

                            /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
                                if ($tailBalanceAfter >= 0){
                                    break;
                                }

                            /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
                                $qtyWhTail = $leftOver_qtyWhTail;
                        }

                    /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
                        if ($balanceAfter >= 0){
                            $db = [ (object)['response' => 1 ]];
                            break;
                        }

                    /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
                        $out_qty = $leftOver_qtyWh;
                };
            }

        /* RETURN */
        $db = [ (object)['response' => 1 ]];
        return $db;

    }
    static function post_shipEntry_soNo($user, $request){
        $mode = $request->input('mode');
        $id = $request->input('id');
        $soNo = $request->input('soNo');

        /* UPDATE DATA */
        DB::update('UPDATE t_shipment_header
                       SET so_no = ?,
                           updated_by = ?
                     WHERE id_ship_head = ?', [$soNo, $user, $id]);

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function get_shipmentBatchPackaging($request){
        $batchNo = $request->input('batchNo');

        $db = DB::select('SELECT a.entry_date, a.tf_number, a.batch_no, a.spec, a.production_order,
                                 a.lot_qty, a.qty, a.product, b.id_process, c.id_packing, d.id_pallet,
                                 CONCAT(b.id_process, " , ", b.code, " , ", b.description) AS process,
                                 CONCAT(c.code, " , ", c.description) AS packing,
                                 CONCAT(d.code, " , ", d.description) AS pallet,
                                 e.url_link AS label_link, f.url_link AS splabel_link,
                                 g.url_link AS csmark_link, a.id_special_label, a.id_customer_mark,
                                 CONCAT(a.id_tank, ",", a.tf_number) AS id_tank, a.csmark_isCheck, a.splabel_isCheck,
                                 CONCAT(a.id_product, ",", a.product) AS id_product, a.long_text,
                                 a.approved_by, a.approved_at,
                                 a.created_by, a.id_prdexecution, a.created_at,
                                 a.status, e.id_label, h.id_customer, CONCAT(h.code, " , ", h.description) AS customer,
                                 CONCAT(e.description) AS label, CONCAT(f.description) AS splabel,
                                 CONCAT(g.description) AS csmark, a.updated_by, UPPER(a.uom) AS uom,
                                 a.updated_at AS updated_at, a.finished_by, a.finished_at,
                                 a.p_label_link, a.p_splabel_link, a.p_csmark_link, a.tank_data, a.started_at, a.started_by
                            FROM oee_756.t_prd_execution a
                            LEFT JOIN oee_756.m_process b
                              ON a.id_process = b.id_process
                            LEFT JOIN oee_756.m_packing c
                              ON a.id_packing = c.id_packing
                            LEFT JOIN oee_756.m_pallet d
                              ON a.id_pallet = d.id_pallet
                            LEFT JOIN oee_756.m_label e
                              ON a.id_label = e.id_label
                            LEFT JOIN oee_756.m_special_label f
                              ON a.id_special_label = f.id_label
                            LEFT JOIN oee_756.m_customer_mark g
                              ON a.id_customer_mark = g.id_label
                            LEFT JOIN oee_756.m_customer h
                              ON a.id_customer = h.id_customer
                           WHERE a.batch_no = ?
                           ORDER BY a.id_prdexecution DESC
                           LIMIT 1', [$batchNo]);

        return $db;
    }

    static function get_label($request){
        $label = $request->input('label');

        $db = DB::select('SELECT a.url_link
                            FROM oee_756.m_label a
                           WHERE a.status = "1"
                             AND a.id_label = ?', [$label]);
        return $db;
    }
    static function get_splabel($request){
        $label = $request->input('label');

        $db = DB::select('SELECT a.url_link
                            FROM oee_756.m_special_label a
                           WHERE a.status = "1"
                             AND a.id_label = ?', [$label]);
        return $db;
    }
    static function get_csmark($request){
        $label = $request->input('label');

        $db = DB::select('SELECT a.url_link
                            FROM oee_756.m_customer_mark a
                           WHERE a.status = "1"
                             AND a.id_label = ?', [$label]);
        return $db;
    }
    static function get_dtPreparationRecord($request){
        $batchNo = $request->input('batchNo');

        $db = DB::select('SELECT a.id_prepentry, a.id_prdexecution, a.batch_no, a.type,
                                 a.description, a.created_by, a.created_at, a.updated_at, a.status
                            FROM oee_756.t_prep_entry a
                           WHERE a.batch_no = ?
                           ORDER BY a.type ASC, a.created_at ASC', [$batchNo]);
        return $db;
    }
}
