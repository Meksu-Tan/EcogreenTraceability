<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Blending extends Model
{
    protected $connection = 'eudr_ts';

    // protected static $idPlantEob1 = "1002";

    static function get_cmbActiveMaterial(){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.id_material, CONCAT( UPPER(a.description), " (", a.code, ")" ) AS material
                            FROM m_material a
                           WHERE a.status = 1
                             AND a.id_rundown <> "-"
                           GROUP BY a.code
                           ORDER BY a.description ASC');
        return $db;
    }
    static function get_newBlendingEntryNo($request){
        $idMaterial = $request->input('id_material');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $db = DB::select('SELECT a.entryNo
                            FROM (SELECT b.trace_no + 1 AS entryNo
                                    FROM m_material a
                                    LEFT JOIN t_balance_header b
                                      ON a.id_rundown = SUBSTRING(b.trace_no, 8,2) AND b.status = 1 AND b.id_plant = ?
                                   WHERE a.id_material = ?
                                     AND SUBSTRING(b.trace_no, 1, 7) = CONCAT(8, DATE_FORMAT(CURDATE(), "%y%m%d"))
                                     AND a.status = 1
                                   ORDER BY b.id_balance_head DESC
                                   LIMIT 1) a
                           UNION ALL
                          --  8yymmdd10rundown01 (LPAD(?, 2, "0") takes first 2 digits of $idPlant)
                          SELECT CONCAT("8", DATE_FORMAT(CURDATE(), "%y%m%d"), LPAD(?, 2, "0"), IF(a.id_rundown <> "-", a.id_rundown, "00"), "01") AS entryNo
                            FROM m_material a
                           WHERE a.status = 1
                             AND a.id_material = ?
                           LIMIT 1
                           ', [$idPlant, $idMaterial, $idPlant, $idMaterial]);

        return $db;
    }
    static function get_totalStockMaterial($request){
        $idMaterial = $request->input('idMaterial');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $db = DB::select('SELECT IFNULL(SUM(c.qty),0) AS total
                            FROM m_material a
                            LEFT JOIN (SELECT b.code, b.id_material
                                         FROM m_material b
                                        WHERE b.status = 1) b
                              ON a.code = b.code
                            LEFT JOIN (SELECT c.id_material, c.qty
                                         FROM m_tank cc
                                         LEFT JOIN t_balance_header c
                                           ON c.id_tank = cc.id_tank
                                        WHERE c.status = 1
                                          AND cc.status = 1
                                          AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR SUBSTRING(c.trace_no,1,1) = 7 OR
                                               SUBSTRING(c.trace_no,1,1) = 8 OR SUBSTRING(c.trace_no,1,1) = 9)
                                          AND cc.id_plant = ?
                                        ) c
                              ON b.id_material = c.id_material
                           WHERE a.status = 1
                             AND a.id_material = ?
                         ', [$idPlant, $idMaterial]);

        return $db;
    }
    static function get_totalQtyMaterial($request){
        $mode = $request->input('mode');
        $entryNo = $request->input('entryNo');
        $idHead = $request->input('idHead');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        if ($mode == 'ADD'){
            $db = DB::select('SELECT FORMAT(SUM(a.qty),3) AS total
                                FROM t_balance_temporary a
                               WHERE a.entry_no = ?
                                 AND a.status = 1
                                 AND a.id_plant = ?', [$entryNo, $idPlant]);
        } else if ($mode == 'UPDATE'){
            $db = DB::select('SELECT FORMAT(SUM(a.qty),3) AS total
                                FROM t_balance_detail a
                               WHERE a.id_balance_head = ?
                                 AND a.status = 1
                                 AND a.id_plant = ?', [$idHead, $idPlant]);
        }
        return $db;
    }
    static function get_dtMaterialList($request){
        $mode = $request->input('mode');
        $idHead = $request->input('idHead');
        $entryNo = $request->input('entryNo');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        if ($mode == 'ADD'){
            $db = DB::select('SELECT FORMAT(a.qty,3) AS qty, a.id_material,
                                     CONCAT(c.code, " :: ", c.description) AS material,
                                     a.id_balance_temp AS idTail, a.entry_no, ? AS mode
                                FROM t_balance_temporary a
                                LEFT JOIN m_material c
                                  ON a.id_material = c.id_material
                               WHERE a.entry_no = ?
                                 AND a.status = 1
                                 AND a.id_plant = ?', [$mode, $entryNo, $idPlant]);
        } else if ($mode == 'UPDATE'){
            $db = DB::select('SELECT FORMAT(a.qty,3) AS qty, a.id_material,
                                     CONCAT(d.code, " :: ", d.description) AS material,
                                     a.id_balance_tail AS idTail, c.trace_no AS entry_no, ? AS mode
                                FROM t_balance_detail a
                                LEFT JOIN t_balance_header c
                                  ON a.id_balance_head = c.id_balance_head
                                LEFT JOIN m_material d
                                  ON a.id_material = d.id_material
                               WHERE a.id_balance_head = ?
                                 AND a.status = 1
                                 AND a.id_plant = ?
                                 AND c.id_plant = ?', [$mode, $idHead, $idPlant, $idPlant]);
        }

        return $db;
    }
    static function get_dtBlendingList($request){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $db = DB::select('SELECT a.entry_date, d.`description` AS sloc, b.material_document,
                                 CAST(a.trace_no AS CHAR) AS trace_no, FORMAT(a.qty,3) AS qty, FORMAT(a.init_qty,3) AS init_qty, a.entry_date, a.id_balance_head AS idHead,
                                 CONCAT(c.`description`, " (", c.`code`, ")") AS material,
                                 GROUP_CONCAT(DISTINCT CONCAT(f.`description`, " / ", e.batch_sap, " / Qty : ", FORMAT(e.init_qty,3), " MT / Qty : ", FORMAT(e.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                 CAST(b.from_trace_no AS CHAR) AS from_trace_no, b.id_trace_head AS idTraceHead, b.id_trace_head,
                                 b.is_last_row, b.next_process,
                                 FORMAT(ROUND(ee.init_qty,4),3) as balance_supplier
                            FROM t_balance_header a
                            LEFT JOIN (SELECT b.id_balance_head, b.id_trace_head,
                                              c.from_trace_no, d.material_document,
                                              CASE
                                                WHEN b.to_trace_no = (SELECT to_trace_no
                                                                        FROM t_trace_header
                                                                       WHERE SUBSTRING(to_trace_no, 1, 1) = 8
                                                                         AND SUBSTRING(to_trace_no, 8, 1) <> 0
                                                                         AND `status` = 1
                                                                       ORDER BY to_trace_no DESC LIMIT 1) THEN 1
                                                ELSE NULL
                                              END AS is_last_row,
                                              CASE
                                                WHEN b.to_trace_no = (SELECT from_trace_no
                                                                        FROM t_trace_header
                                                                       WHERE from_trace_no = b.to_trace_no
                                                                         AND `status` = 1
                                                                       ORDER BY from_trace_no DESC LIMIT 1) THEN 1
                                                ELSE NULL
                                              END AS next_process
                                         FROM t_trace_header b
                                         LEFT JOIN (SELECT c.to_trace_no, c.id_balance_head,
                                                           GROUP_CONCAT(CONCAT(c.from_trace_no, " :: ", cc.`description`, " (", cc.`code`, ") - Qty ", FORMAT(c.out_qty,3), " MT") SEPARATOR "|") AS from_trace_no
                                                      FROM t_trace_header c
                                                      LEFT JOIN m_material cc
                                                        ON c.id_material = cc.id_material
                                                     WHERE c.`status` = 1
                                                       AND SUBSTRING(c.to_trace_no,1,1) = 8
                                                       AND SUBSTRING(c.to_trace_no,8,1) = 0
                                                     GROUP BY c.to_trace_no ) c
                                           ON b.from_trace_no = c.to_trace_no
                                         LEFT JOIN t_material_document d
                                           ON d.id_trace_head = b.id_trace_head
                                        WHERE b.`status` = 1
                                          AND SUBSTRING(b.to_trace_no,1,1) = 8
                                          AND SUBSTRING(b.from_trace_no,1,1) = 8) b
                              ON a.id_balance_head = b.id_balance_head
                            LEFT JOIN m_material c
                              ON c.id_material = a.id_material
                            LEFT JOIN m_tank d
                              ON d.id_tank = a.id_tank AND d.id_plant = ?
                            LEFT JOIN t_balance_detail e
                              ON a.id_balance_head = e.id_balance_head
                            LEFT JOIN (SELECT ee1.trace_no, SUM(ee2.init_qty) AS init_qty
                                         FROM t_balance_header ee1
                                         LEFT JOIN t_balance_detail ee2
                                           ON ee1.id_balance_head = ee2.id_balance_head
                                        WHERE ee1.status = 1
                                        GROUP BY ee1.trace_no
                                        ) ee
                              ON a.trace_no = ee.trace_no
                            LEFT JOIN m_supplier f
                              ON e.id_supplier = f.id_supplier
                           WHERE a.`status` = 1
                             AND SUBSTRING(a.trace_no,1,1) = 8
                             AND (a.id_plant = ? OR ? = 0)
                           GROUP BY a.trace_no
                           ORDER BY a.trace_no DESC', [$idPlant, $idPlant, $idPlant]);

        return $db;
    }
    static function get_cmbActiveTank_rundown($request){
        $idMaterial = $request->input('idMaterial');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT b.id_tank, b.description AS tank
                            FROM m_material a
                            LEFT JOIN m_tank b
                              ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
                           WHERE a.status = 1
                             AND a.id_material = ?
                           GROUP BY b.id_tank', [$idPlant, $idMaterial]);
        return $db;
    }
    static function post_blendingEntryMaterial($user, $request){
        $flag = $request->input('flag');
        $mode = $request->input('mode');
        $entryNo = $request->input('entryNo');
        $idMaterialSource = $request->input('idMaterialSource');
        $qty = $request->input('qty');
        $idHead = $request->input('idHead');
        $qty = floatval(str_replace(',', '', $qty));
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        if ($mode == 'ADD'){
            /* CHECK FOR SAME MATERIAL */
            $dat = DB::select('SELECT COUNT(entry_no) AS flag
                                 FROM t_balance_temporary
                                WHERE id_material = ?
                                  AND entry_no = ?
                                  AND id_plant = ?', [$idMaterialSource, $entryNo, $idPlant]);

            if ($dat[0]->flag > 0){
                $db = [ (object)['response' => 2 ]];
                return $db;
            };

            $db = DB::insert('INSERT INTO t_balance_temporary
                                    (entry_no, id_material, qty, created_by, id_plant)
                            VALUES (?, ?, ?, ?, ?)',
                            [$entryNo, $idMaterialSource, $qty, $user, $idPlant]);
            $db = [ (object)['response' => $db ? 1 : 0 ]];

        } elseif ($mode == 'UPDATE'){

        }
        return $db;
    }
    static function blendingMaterial_destroy($id, $user){
        DB::delete('DELETE FROM t_balance_temporary
                     WHERE id_balance_temp = ?', [$id]);

        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function post_blendingEntry($user, $request){
        $flag = $request->input('flag');
        $mode = $request->input('mode');
        $idHead = $request->input('idHead');
        $entryNo = $request->input('entry_no');
        $entryDate = $request->input('entry_date');
        $idMaterial = $request->input('id_material');
        $materialDoc = $request->input('material_doc');
        $totalQty = $request->input('qty');
        $totalQty = floatval(str_replace(',', '', $totalQty));
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $insertPlant = ($idPlant === 0 ? null : $idPlant);

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        /* CHECK LOCK PERIOD */
        $lockDateTime = new \DateTime($entryDate);
        // Mengambil tahun
        $lockYear = $lockDateTime->format('Y');
        // Mengambil bulan
        $lockMonth = $lockDateTime->format('m');

        $datLock = DB::select('SELECT lock_status
                            FROM t_report_pspa_head
                            WHERE `status` = 1
                            AND YEAR(`period`) = ?
                            AND MONTH(`period`) = ?
                            UNION ALL
                        SELECT "0" AS lock_status',
                            [$lockYear, $lockMonth]);
        $lockStatus = $datLock[0]->lock_status;
        if ($lockStatus == 1){
            $db = [ (object)['response' => 99 ]];
            return $db;
        }

        /* CHECKING MATERIAL SOURCE */
        $datMaterialEntry = DB::select('SELECT COUNT(a.entry_no) AS itemCnt
                                      FROM t_balance_temporary a
                                     WHERE a.entry_no = ?', [$entryNo]);
        $itemCnt = $datMaterialEntry[0]->itemCnt;

        if ($itemCnt == 0){
            $db = [ (object)['response' => 4 ]];
            return $db;
        }

        /* CONTINUE ROUTE BLENDING */
        $datMaterial = DB::select('SELECT id_material, qty
                                 FROM t_balance_temporary
                                WHERE entry_no = ?', [$entryNo]);
        $lenDatMatl = count($datMaterial);

        /* MULTI-MATERIAL FEED FOR STOCK-OUT TO BLENDING */
        for ($z = 0; $z < $lenDatMatl; $z++){
            $idMaterialSource = $datMaterial[$z]->id_material;
            $qtySource = $datMaterial[$z]->qty;

            /* USE FEED ROUTING TO TAKE OUT MATERIAL FOR BLENDING */
            $datHead = DB::select('SELECT b.id_material, c.id_balance_head, c.qty, c.in_qty, c.out_qty, c.init_qty, c.trace_no, c.id_tank
                                 FROM m_material a
                                 LEFT JOIN (SELECT b.code, b.id_material
                                              FROM m_material b
                                             WHERE b.status = 1) b
                                   ON a.code = b.code
                                 LEFT JOIN (SELECT c.id_material, c.id_balance_head, c.qty, c.in_qty, c.out_qty, c.init_qty, c.trace_no, c.id_tank
                                              FROM m_tank cc
                                              LEFT JOIN t_balance_header c
                                                ON c.id_tank = cc.id_tank
                                             WHERE c.status = 1
                                               AND cc.status = 1
                                               AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR SUBSTRING(c.trace_no,1,1) = 7 OR
                                                    SUBSTRING(c.trace_no,1,1) = 8 OR SUBSTRING(c.trace_no,1,1) = 9)
                                               AND cc.id_plant = ?
                                               AND c.qty > 0
                                            ) c
                                   ON b.id_material = c.id_material
                                WHERE a.status = 1
                                  AND a.id_material = ?
                                  AND c.id_balance_head IS NOT NULL
                                ORDER BY c.id_balance_head ASC', [$idPlant, $idMaterialSource]);
            $lenDatHead = count($datHead);

            /* VARIABLE ADJUSTMENT */
            $out_qty = $qtySource;
            $entry_no = substr_replace($entryNo, '0', 7, 1); /* REPLACE RUNDOWN_ID TO FEED_ID */
            $curr_entryDate = $entryDate;
            $curr_qtf = $qtySource;
            $last_qtf = 0;

            /* FEEDING ALGORITHM */
            for ($i = 0; $i < $lenDatHead; $i++) {
                $idHead = $datHead[$i]->id_balance_head;
                $qty = $datHead[$i]->qty;
                $total_in_qty = $datHead[$i]->in_qty;
                $total_out_qty = $datHead[$i]->out_qty;
                $init_qty = $datHead[$i]->init_qty;
                $from_trace_no = $datHead[$i]->trace_no;
                $id_material = $datHead[$i]->id_material;
                $id_tank = $datHead[$i]->id_tank;

                $new_total_in_qty = $total_in_qty;
                $new_total_out_qty = $total_out_qty + $out_qty;

                $tail_out_qty = $out_qty;

                $balanceAfter = $qty - $out_qty;
                if ($balanceAfter < 0){
                    if ($lenDatHead == 1){
                      $db = [ (object)['response' => 3 ]];
                      break;
                    }
                    $new_balance = 0;
                    $new_total_out_qty = $init_qty;
                    $temp_out_qty = $out_qty - $qty;
                    $out_qty = $qty;
                } else {
                    $new_balance = $qty - $out_qty;
                }

                /* UPDATE INTO T_BALANCE_HEADER */
                DB::update('UPDATE t_balance_header
                           SET qty = ?,
                               in_qty = ?,
                               out_qty = ?,
                               updated_by = ?
                         WHERE id_balance_head = ?',
                         [$new_balance, $new_total_in_qty, $new_total_out_qty, $user, $idHead]);

                /* INSERT INTO T_TRACE_HEADER (FEED -> OUT) */
                $traceHeaderData = [
                    'from_trace_no'    => $from_trace_no,
                    'to_trace_no'      => $entry_no,
                    'id_balance_head'  => $idHead,
                    'id_material'      => $id_material,
                    'entry_date'       => $curr_entryDate,
                    'id_sloc'          => $id_tank,
                    'out_qty'          => $out_qty,
                    'last_qtf'         => $last_qtf,
                    'curr_qtf'         => $curr_qtf,
                    'created_by'       => $user,
                ];
                // attach plant if available (don't write 0)
                if ($insertPlant !== null) $traceHeaderData['id_plant'] = $insertPlant;

                $idTraceHead = DB::table('t_trace_header')->insertGetId($traceHeaderData);

                /* HEADER LOGGING */
                DB::insert('INSERT INTO log_transactions
                            (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'BLENDING OUT', 'IDHEAD: ' . $idHead . ' | DATE: ' . $curr_entryDate .
                                                ' / TANK: ' . $id_tank . ' / MATERIAL: ' . $id_material . ' / QTY: ' . $qty . ' >>> ' . $new_balance .
                                                ' / IN_QTY: ' . $total_in_qty . ' >>> ' . $new_total_in_qty .
                                                ' / OUT_QTY: ' . $total_out_qty . ' >>> ' . $new_total_out_qty .
                                                ' | Status: 1', $user ]);

                /* ROUTING FOR DETAIL PER SUPPLIER */
                $datTail = DB::select('SELECT id_balance_tail, id_supplier, qty, in_qty, out_qty, init_qty, batch_sap
                                     FROM t_balance_detail
                                    WHERE id_balance_head = ?
                                      AND `status` = 1
                                      AND qty > "0.0001"
                                    ORDER BY id_balance_tail ASC', [$idHead]);
                $lenTail = count($datTail);
                for ($k = 0; $k < $lenTail; $k++) {
                    $idTail = $datTail[$k]->id_balance_tail;
                    $idSupplier = $datTail[$k]->id_supplier;
                    $tail_qty = $datTail[$k]->qty;
                    $tail_total_in_qty = $datTail[$k]->in_qty;
                    $tail_total_out_qty = $datTail[$k]->out_qty;
                    $tail_init_qty = $datTail[$k]->init_qty;
                    $batch_sap = $datTail[$k]->batch_sap;

                    $new_tail_total_in_qty = $tail_total_in_qty;
                    $new_tail_total_out_qty = $tail_total_out_qty + $tail_out_qty;

                    // Rounding
                    $tail_out_qty = round($tail_out_qty, 4);
                    $tail_total_in_qty = round($tail_total_in_qty, 4);
                    $tail_total_out_qty = round($tail_total_out_qty, 4);
                    $tail_qty = round($tail_qty, 4);
                    $new_tail_total_in_qty = round($new_tail_total_in_qty, 4);
                    $new_tail_total_out_qty = round($new_tail_total_out_qty, 4);
                    $tailBalanceAfter = $tail_qty - $tail_out_qty;

                    if ($tailBalanceAfter < 0){
                        $new_tail_balance = 0;
                        $new_tail_total_out_qty = $tail_init_qty;
                        $temp_tail_out_qty = $tail_out_qty - $tail_qty;
                        $tail_out_qty = $tail_qty;
                    } else {
                        $new_tail_balance = $tail_qty - $tail_out_qty;
                    }

                    /* POPULATE NEW BALANCE DETAIL */
                    DB::update('UPDATE t_balance_detail
                               SET qty = ?,
                                   in_qty = ?,
                                   out_qty = ?,
                                   updated_by = ?
                             WHERE id_balance_tail = ?',
                             [$new_tail_balance, $new_tail_total_in_qty, $new_tail_total_out_qty, $user, $idTail]);

                    /* POPULATE TRACE DETAIL (FEED -> OUT DETAIL) */
                    $traceDetailOut = [
                        'id_trace_head'   => $idTraceHead,
                        'id_balance_tail' => $idTail,
                        'id_supplier'     => $idSupplier,
                        'id_material'     => $id_material,
                        'out_qty'         => $tail_out_qty,
                        'batch_sap'       => $batch_sap,
                        'created_by'      => $user,
                    ];
                    if ($insertPlant !== null) $traceDetailOut['id_plant'] = $insertPlant;

                    $idTraceTail = DB::table('t_trace_detail')->insertGetId($traceDetailOut);

                    /* DETAIL LOGGING */
                    DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'BLENDING OUT', ' IDTAIL: ' . $idTail .
                                                    ' / SUPPLIER: ' . $idSupplier . ' / MATERIAL: ' . $id_material .
                                                    ' / QTY: ' . $tail_qty . ' >>> ' . $new_tail_balance .
                                                    ' / IN_QTY: ' . $tail_total_in_qty . ' >>> ' . $new_tail_total_in_qty .
                                                    ' / OUT_QTY: ' . $tail_total_out_qty . ' >>> ' . $new_tail_total_out_qty .
                                                    ' | Status: 1', $user ]);

                    /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
                    if ($tailBalanceAfter >= 0){
                        break;
                    }

                    /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
                    $tail_out_qty = $temp_tail_out_qty;
                }

                /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
                if ($balanceAfter >= 0){
                    $db = [ (object)['response' => 1 ]];
                    break;
                }

                /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
                $out_qty = $temp_out_qty;
            } // end feeding algorithm
        } // end foreach materials feed

        /* USE RUNDOWN ROUTING TO TAKE IN BLENDING MATERIAL */
        /* VARIABLE ADJUSTMENT */
        $id_material = $idMaterial;
        $process_yield = 1;
        $feed_entryNo = $entry_no;
        $entry_no = $entryNo;
        $curr_qtf = $totalQty;

        /* GET FEED TRACE RELATED TO RUNDOWN */
        $batch_seq = substr($feed_entryNo, 9, 2);
        $feed_id = substr($feed_entryNo, 7, 2);

        $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, SUM(out_qty) AS out_qty, id_material
                                  FROM t_trace_header
                                 WHERE SUBSTRING(to_trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
                                   AND SUBSTRING(to_trace_no,1,1) = 8
                                   AND SUBSTRING(to_trace_no,8,2) = ?
                                   AND SUBSTRING(to_trace_no,10,2) = ?
                                   AND `status` = 1
                                   AND out_qty > "0.0001"
                                   AND (id_plant = ? OR ? = 0)
                                 ORDER BY id_trace_head DESC
                                 LIMIT 1', [$feed_id, $batch_seq, $idPlant, $idPlant]);

        if (!isset($datTraceHead[0]->id_trace_head)) {
            $db = [ (object)['response' => 6 ]];
            return $db;
        }

        $feed_idTraceHead = $datTraceHead[0]->id_trace_head;
        $from_trace_no = $datTraceHead[0]->to_trace_no;
        $feed_qty = $datTraceHead[0]->out_qty;

        $in_qty = $process_yield * $feed_qty;

        /* GET ID_TANK BASED ON MATERIAL ASSIGNMENT */
        $datTank = DB::select('SELECT b.id_tank
                             FROM m_material a
                             LEFT JOIN m_tank b
                               ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
                            WHERE a.status = 1
                              AND a.id_material = ?', [$idPlant, $id_material]);

        if (!isset($datTank[0]->id_tank)) {
            $db = [ (object)['response' => 6 ]];
            return $db;
        }

        $id_tank = $datTank[0]->id_tank;

        /* INSERT INTO T_BALANCE_HEADER (BLENDING IN) */
        $balanceHeaderData = [
            'entry_date' => $curr_entryDate,
            'trace_no'   => $entry_no,
            'id_material'=> $id_material,
            'id_tank'    => $id_tank,
            'qty'        => $in_qty,
            'in_qty'     => $in_qty,
            'init_qty'   => $in_qty,
            'created_by' => $user,
        ];
        if ($insertPlant !== null) $balanceHeaderData['id_plant'] = $insertPlant;

        $idHead = DB::table('t_balance_header')->insertGetId($balanceHeaderData);

        /* INSERT INTO T_TRACE_HEADER (BLENDING IN) */
        $traceInData = [
            'from_trace_no'    => $from_trace_no,
            'to_trace_no'      => $entry_no,
            'id_balance_head'  => $idHead,
            'id_material'      => $id_material,
            'entry_date'       => $curr_entryDate,
            'id_sloc'          => $id_tank,
            'in_qty'           => $in_qty,
            'last_qtf'         => $last_qtf,
            'curr_qtf'         => $curr_qtf,
            'created_by'       => $user,
        ];
        if ($insertPlant !== null) $traceInData['id_plant'] = $insertPlant;

        $idTraceHead = DB::table('t_trace_header')->insertGetId($traceInData);

        DB::insert('INSERT INTO t_material_document
                       (id_trace_head, material_document, created_by)
                VALUES (?, ?, ?)', [$idTraceHead, $materialDoc, $user]);

        /* HEADER LOGGING */
        DB::insert('INSERT INTO log_transactions
                       (log_module, log_type, log_description, created_by)
                VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'BLENDING IN', 'IDHEAD: ' . $idHead . ' | DATE: ' . $curr_entryDate .
                                        ' / MATERIAL: ' . $id_material . ' / QTY: ' . $in_qty .
                                        ' / IN_QTY: ' . $in_qty .
                                        ' / OUT_QTY: 0' .
                                        ' | Status: 1', $user ]);

        $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, out_qty, id_material
                                  FROM t_trace_header
                                 WHERE SUBSTRING(to_trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
                                   AND SUBSTRING(to_trace_no,1,1) = 8
                                   AND SUBSTRING(to_trace_no,8,2) = ?
                                   AND SUBSTRING(to_trace_no,10,2) = ?
                                   AND `status` = 1
                                   AND out_qty > "0.0001"
                                   AND (id_plant = ? OR ? = 0)
                                 ORDER BY id_trace_head DESC',
                                [$feed_id, $batch_seq, $idPlant, $idPlant]);
        $len = count($datTraceHead);

        for ($i = 0; $i < $len; $i++) {
            $feed_idTraceHead = $datTraceHead[$i]->id_trace_head;
            $from_trace_no = $datTraceHead[$i]->to_trace_no;
            $feed_qty = $datTraceHead[$i]->out_qty;

            /* ROUTING FOR DETAIL PER SUPPLIER */
            $datTraceTail = DB::select('SELECT id_trace_tail, id_balance_tail, id_supplier, out_qty, batch_sap
                                      FROM t_trace_detail
                                     WHERE id_trace_head = ?
                                       AND `status` = 1
                                     ORDER BY id_trace_tail ASC', [$feed_idTraceHead]);
            $lenTraceTail = count($datTraceTail);
            if ($lenTraceTail == 0){
                $db = [ (object)['response' => 6 ]];
                return $db;
            }
            for ($k = 0; $k < $lenTraceTail; $k++) {
                $idTraceTail = $datTraceTail[$k]->id_trace_tail;
                $idTail = $datTraceTail[$k]->id_balance_tail;
                $idSupplier = $datTraceTail[$k]->id_supplier;
                $feedSupplier = $datTraceTail[$k]->out_qty;
                $batchSap = $datTraceTail[$k]->batch_sap;

                $rundownSupplier = round($process_yield * $feedSupplier, 4);

                /* POPULATE TRACE DETAIL */
                $flagCheckIdSupplier = DB::select('SELECT count(id_trace_tail) AS cnt, id_trace_tail, in_qty, out_qty, id_balance_tail
                                                 FROM t_trace_detail
                                                WHERE `status` = 1
                                                  AND id_trace_head = ?
                                                  AND id_supplier = ?
                                                  AND batch_sap = ?', [$idTraceHead, $idSupplier, $batchSap]);
                $cntFlagCheckIdSupplier = $flagCheckIdSupplier[0]->cnt;
                $idTraceTail = $flagCheckIdSupplier[0]->id_trace_tail;
                $idTail = $flagCheckIdSupplier[0]->id_balance_tail;
                $inQtyTail = $flagCheckIdSupplier[0]->in_qty;
                $outQtyTail = $flagCheckIdSupplier[0]->out_qty;

                if ($cntFlagCheckIdSupplier == 0){
                    /* INSERT INTO T_BALANCE_DETAIL */
                    $balanceDetailData = [
                        'id_balance_head' => $idHead,
                        'id_supplier'     => $idSupplier,
                        'id_material'     => $id_material,
                        'qty'             => $rundownSupplier,
                        'in_qty'          => $rundownSupplier,
                        'init_qty'        => $rundownSupplier,
                        'batch_sap'       => $batchSap,
                        'created_by'      => $user,
                    ];
                    if ($insertPlant !== null) $balanceDetailData['id_plant'] = $insertPlant;

                    $idTail = DB::table('t_balance_detail')->insertGetId($balanceDetailData);

                    $traceDetailIn = [
                        'id_trace_head'   => $idTraceHead,
                        'id_balance_tail' => $idTail,
                        'id_supplier'     => $idSupplier,
                        'id_material'     => $id_material,
                        'in_qty'          => $rundownSupplier,
                        'batch_sap'       => $batchSap,
                        'created_by'      => $user,
                    ];
                    if ($insertPlant !== null) $traceDetailIn['id_plant'] = $insertPlant;

                    $idTraceTail = DB::table('t_trace_detail')->insertGetId($traceDetailIn);

                } else {
                    $newInQtyTail = $inQtyTail + $rundownSupplier;
                    DB::update('UPDATE t_balance_detail
                               SET qty = ?,
                                   in_qty = ?,
                                   init_qty = ?,
                                   updated_by = ?
                             WHERE id_balance_tail = ?', [$newInQtyTail, $newInQtyTail, $newInQtyTail, $user, $idTail]);
                    DB::update('UPDATE t_trace_detail
                               SET in_qty = ?,
                                   updated_by = ?
                             WHERE id_trace_tail = ?', [$newInQtyTail, $user, $idTraceTail]);
                }

                /* DETAIL LOGGING */
                DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'BLENDING IN', ' IDTAIL: ' . $idTail .
                                                ' / SUPPLIER: ' . $idSupplier . ' / MATERIAL: ' . $id_material .
                                                ' / QTY: ' . $rundownSupplier .
                                                ' / IN_QTY: ' . $rundownSupplier .
                                                ' / OUT_QTY: ' . $rundownSupplier .
                                                ' / INIT_QTY: ' . $rundownSupplier .
                                                ' | Status: 1', $user ]);
            }
        }

        /* DESTROY TEMPORARY DATA */
        DB::delete('DELETE FROM t_balance_temporary
                 WHERE entry_no = ?', [$entryNo]);

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function post_matlDocNumber($user, $request){
        $mode = $request->input('mode');
        $idTraceHead = $request->input('id');
        $materialDoc = $request->input('number');

        if ($mode == 'ADD'){
            $db = DB::insert('INSERT INTO t_material_document
                                     (id_trace_head, material_document, created_by)
                              VALUES (?, ?, ?)', [$idTraceHead, $materialDoc, $user]);
            $db = [ (object)['response' => $db ? 1 : 0 ]];

            /* LOGGING */
            $id = DB::select('SELECT id_matdoc FROM t_material_document ORDER BY id_matdoc DESC LIMIT 1');
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_MATERIAL_DOCUMENT', 'ADD', 'ID: ' . $id[0]->id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead .
                                                ' / DOC_NO: ' . $materialDoc .
                                                ' | Status: 1', $user ]);
        } elseif ($mode == 'UPDATE'){
            $dat = DB::select('SELECT id_matdoc, material_document
                                 FROM t_material_document
                                WHERE id_trace_head = ?', [$idTraceHead]);
            $id_matdoc = $dat[0]->id_matdoc;
            $old_materialDoc = $dat[0]->material_document;

            $db = DB::update('UPDATE t_material_document
                                 SET material_document = ?,
                                     updated_by = ?
                               WHERE id_trace_head = ?', [$materialDoc, $user, $idTraceHead]);
            $db = [ (object)['response' => $db ]];

            /* LOGGING */
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_MATERIAL_DOCUMENT', 'UPDATE', 'ID: ' . $id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead .
                                                ' / DOC_NO: ' . $old_materialDoc . ' >>> ' . $materialDoc .
                                                ' | Status: 1', $user ]);
        }
        return $db;
    }
    static function blending_destroy($id, $user){
        $idTmp          = explode("|", $id);
        $idHead         = trim($idTmp[0]);
        $idTraceHead    = trim($idTmp[1]);

        /* CHECK LOCK PERIOD */
            $entryDate = DB::select('SELECT entry_date
                                       FROM t_trace_header
                                      WHERE id_trace_head = ?
                                        AND `status` = 1',
                                    [$idTraceHead]);
            $curr_entryDate = $entryDate[0]->entry_date;

            $lockDateTime = new \DateTime($curr_entryDate);
            // Mengambil tahun
            $lockYear = $lockDateTime->format('Y');
            // Mengambil bulan
            $lockMonth = $lockDateTime->format('m');

            $datLock = DB::select(' SELECT lock_status
                                    FROM t_report_pspa_head
                                    WHERE `status` = 1
                                    AND YEAR(`period`) = ?
                                    AND MONTH(`period`) = ?
                                    UNION ALL
                                    SELECT "0" AS lock_status',
                                    [$lockYear, $lockMonth]);
            $lockStatus = $datLock[0]->lock_status;
            if ($lockStatus == 1){
                $db = [ (object)['response' => 99 ]];
                return $db;
            }

        /* CONTINUE MAIN ROUTE */
        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'BLENDING_ENTRY', 'DE-ACTIVATE', 'IdBalHead: ' . $idHead . ' | Status: 1 >> 0', $user ]);
        DB::update('UPDATE t_balance_detail
                       SET `status` = "0",
                           `updated_by` = ?
                     WHERE id_balance_head = ?', [$user, $idHead]);
        DB::update('UPDATE t_balance_header
                       SET `status` = "0",
                           `updated_by` = ?
                     WHERE id_balance_head = ?', [$user, $idHead]);

        /* GET SOURCE BLENDING AND DELETE */
            $datTraceHead = DB::select('SELECT b.id_balance_head, b.out_qty, b.id_trace_head
                                          FROM t_trace_header a
                                          LEFT JOIN t_trace_header b
                                            ON a.from_trace_no = b.to_trace_no AND b.status = 1
                                         WHERE a.id_balance_head = ?
                                           AND a.status = 1', [$idHead]);
            $lenTraceHead = count($datTraceHead);

            for ($i = 0; $i < $lenTraceHead; $i++){
                $idBalHead = $datTraceHead[$i]->id_balance_head;
                $idTracHead = $datTraceHead[$i]->id_trace_head;
                $outQtyHead = $datTraceHead[$i]->out_qty;

                /* GET SOURCE BLEND AND RESTORE STOCK */
                $datBalHeadSource = DB::select('SELECT a.qty, a.out_qty
                                                  FROM t_balance_header a
                                                 WHERE a.status = 1
                                                   AND a.id_balance_head = ?', [$idBalHead]);
                $outQtyBalHeadSource = $datBalHeadSource[0]->out_qty - $outQtyHead;
                $onhandQtyBalHeadSource = $datBalHeadSource[0]->qty + $outQtyHead;

                DB::update('UPDATE t_balance_header a
                               SET a.qty = ?,
                                   a.out_qty = ?,
                                   a.`updated_by` = ?
                             WHERE a.id_balance_head = ?', [$onhandQtyBalHeadSource, $outQtyBalHeadSource, $user, $idBalHead]);

                /* GET TRACE DETAIL */
                $datTraceTail = DB::select('SELECT a.id_balance_tail, a.out_qty, a.id_trace_tail
                                              FROM t_trace_detail a
                                             WHERE a.id_trace_head = ?
                                               AND a.status = 1', [$idTracHead]);
                $lenTraceTail = count($datTraceTail);

                for ($j = 0; $j < $lenTraceTail; $j++){
                    $idBalTail = $datTraceTail[$j]->id_balance_tail;
                    $outQtyTail = $datTraceTail[$j]->out_qty;
                    $idTracTail = $datTraceTail[$j]->id_trace_tail;

                    $datBalTailSource = DB::select('SELECT a.qty, a.out_qty
                                                      FROM t_balance_detail a
                                                     WHERE a.status = 1
                                                       AND a.id_balance_tail = ?', [$idBalTail]);
                    $outQtyBalTailSource = $datBalTailSource[0]->out_qty - $outQtyTail;
                    $onhandQtyBalTailSource = $datBalTailSource[0]->qty + $outQtyTail;

                    DB::update('UPDATE t_balance_detail a
                                   SET a.qty = ?,
                                       a.out_qty = ?,
                                       a.`updated_by` = ?
                                 WHERE a.id_balance_tail = ?', [$onhandQtyBalTailSource, $outQtyBalTailSource, $user, $idBalTail]);

                    DB::update('UPDATE t_trace_detail
                                   SET `status` = "0",
                                       `updated_by` = ?
                                 WHERE id_trace_tail = ?', [$user, $idTracTail]);
                }

                DB::update('UPDATE t_trace_header
                               SET `status` = "0",
                                   `updated_by` = ?
                             WHERE id_trace_head = ?', [$user, $idTracHead]);
            }

        DB::update('UPDATE t_trace_header
                       SET `status` = "0",
                           `updated_by` = ?
                     WHERE id_balance_head = ?', [$user, $idHead]);
        DB::update('UPDATE t_trace_detail
                       SET `status` = "0",
                           `updated_by` = ?
                     WHERE id_trace_head = ?', [$user, $idTraceHead]);

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;


    }
}  






// class Blending extends Model
// {
//     protected $connection = 'eudr_ts';

//     protected static $idPlantEob1 = "1002";

//     static function get_cmbActiveMaterial(){
//         DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
//         $db = DB::select('SELECT a.id_material, CONCAT( UPPER(a.description), " (", a.code, ")" ) AS material
//                             FROM m_material a
//                            WHERE a.status = 1
//                              AND a.id_rundown <> "-"
//                            GROUP BY a.code
//                            ORDER BY a.description ASC');
//         return $db;
//     }
//     static function get_newBlendingEntryNo($request){
//         $idMaterial = $request->input('id_material');

//         $db = DB::select('SELECT a.entryNo
//                             FROM (SELECT b.trace_no + 1 AS entryNo
//                                     FROM m_material a
//                                     LEFT JOIN t_balance_header b
//                                       ON a.id_rundown = SUBSTRING(b.trace_no, 8,2) AND b.status = 1
//                                    WHERE a.id_material = ?
//                                      AND SUBSTRING(b.trace_no, 1, 7) = CONCAT(8, DATE_FORMAT(CURDATE(), "%y%m%d"))
//                                      AND a.status = 1
//                                    ORDER BY b.id_balance_head DESC
//                                    LIMIT 1) a
//                            UNION ALL
//                           SELECT CONCAT("8", DATE_FORMAT(CURDATE(), "%y%m%d"), IF(a.id_rundown <> "-", a.id_rundown, "00"), "01") AS entryNo
//                             FROM m_material a
//                            WHERE a.status = 1
//                              AND a.id_material = ?
//                            LIMIT 1
//                            ', [$idMaterial, $idMaterial]);

//         return $db;
//     }
//     static function get_totalStockMaterial($request){
//         $idMaterial = $request->input('idMaterial');

//         $db = DB::select('SELECT IFNULL(SUM(c.qty),0) AS total
//                             FROM m_material a
//                             LEFT JOIN (SELECT b.code, b.id_material
//                                          FROM m_material b
//                                         WHERE b.status = 1) b
//                               ON a.code = b.code
//                             LEFT JOIN (SELECT c.id_material, c.qty
//                                          FROM m_tank cc
//                                          LEFT JOIN t_balance_header c
//                                            ON c.id_tank = cc.id_tank
//                                         WHERE c.status = 1
//                                           AND cc.status = 1
//                                           AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR SUBSTRING(c.trace_no,1,1) = 7 OR
//                                                SUBSTRING(c.trace_no,1,1) = 8 OR SUBSTRING(c.trace_no,1,1) = 9)
//                                           AND cc.id_plant = ?
//                                         ) c
//                               ON b.id_material = c.id_material
//                            WHERE a.status = 1
//                              AND a.id_material = ?
//                          ', [self::$idPlantEob1, $idMaterial]);

//         return $db;
//     }
//     static function get_totalQtyMaterial($request){
//         $mode = $request->input('mode');
//         $entryNo = $request->input('entryNo');
//         $idHead = $request->input('idHead');

//         if ($mode == 'ADD'){
//             $db = DB::select('SELECT FORMAT(SUM(a.qty),3) AS total
//                                 FROM t_balance_temporary a
//                                WHERE a.entry_no = ?
//                                  AND a.status = 1', [$entryNo]);
//         } else if ($mode == 'UPDATE'){
//             $db = DB::select('SELECT FORMAT(SUM(a.qty),3) AS total
//                                 FROM t_balance_detail a
//                                WHERE a.id_balance_head = ?
//                                  AND a.status = 1', [$idHead]);
//         }
//         return $db;
//     }
//     static function get_dtMaterialList($request){
//         $mode = $request->input('mode');
//         $idHead = $request->input('idHead');
//         $entryNo = $request->input('entryNo');

//         if ($mode == 'ADD'){
//             $db = DB::select('SELECT FORMAT(a.qty,3) AS qty, a.id_material,
//                                      CONCAT(c.code, " :: ", c.description) AS material,
//                                      a.id_balance_temp AS idTail, a.entry_no, ? AS mode
//                                 FROM t_balance_temporary a
//                                 LEFT JOIN m_material c
//                                   ON a.id_material = c.id_material
//                                WHERE a.entry_no = ?
//                                  AND a.status = 1', [$mode, $entryNo]);
//         } else if ($mode == 'UPDATE'){
//             $db = DB::select('SELECT FORMAT(a.qty,3) AS qty, a.id_material,
//                                      CONCAT(d.code, " :: ", d.description) AS material,
//                                      a.id_balance_tail AS idTail, c.trace_no AS entry_no, ? AS mode
//                                 FROM t_balance_detail a
//                                 LEFT JOIN t_balance_header c
//                                   ON a.id_balance_head = c.id_balance_head
//                                 LEFT JOIN m_material d
//                                   ON a.id_material = d.id_material
//                                WHERE a.id_balance_head = ?
//                                  AND a.status = 1', [$mode, $idHead]);
//         }

//         return $db;
//     }
//     static function get_dtBlendingList(){
//         DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
//         $db = DB::select('SELECT a.entry_date, d.`description` AS sloc, b.material_document,
//                                  CAST(a.trace_no AS CHAR) AS trace_no, FORMAT(a.qty,3) AS qty, FORMAT(a.init_qty,3) AS init_qty, a.entry_date, a.id_balance_head AS idHead,
//                                  CONCAT(c.`description`, " (", c.`code`, ")") AS material,
//                                  GROUP_CONCAT(DISTINCT CONCAT(f.`description`, " / ", e.batch_sap, " / Qty : ", FORMAT(e.init_qty,3), " MT / Qty : ", FORMAT(e.qty,3), " MT") SEPARATOR " | ") AS supplier,
//                                  CAST(b.from_trace_no AS CHAR) AS from_trace_no, b.id_trace_head AS idTraceHead, b.id_trace_head,
//                                  b.is_last_row, b.next_process,
//                                  FORMAT(ROUND(ee.init_qty,4),3) as balance_supplier
//                             FROM t_balance_header a
//                             LEFT JOIN (SELECT b.id_balance_head, b.id_trace_head,
//                                               c.from_trace_no, d.material_document,
//                                               CASE
//                                                 WHEN b.to_trace_no = (SELECT to_trace_no
//                                                                         FROM t_trace_header
//                                                                        WHERE SUBSTRING(to_trace_no, 1, 1) = 8
//                                                                          AND SUBSTRING(to_trace_no, 8, 1) <> 0
//                                                                          AND `status` = 1
//                                                                        ORDER BY to_trace_no DESC LIMIT 1) THEN 1
//                                                 ELSE NULL
//                                               END AS is_last_row,
//                                               CASE
//                                                 WHEN b.to_trace_no = (SELECT from_trace_no
//                                                                         FROM t_trace_header
//                                                                        WHERE from_trace_no = b.to_trace_no
//                                                                          AND `status` = 1
//                                                                        ORDER BY from_trace_no DESC LIMIT 1) THEN 1
//                                                 ELSE NULL
//                                               END AS next_process
//                                          FROM t_trace_header b
//                                          LEFT JOIN (SELECT c.to_trace_no, c.id_balance_head,
//                                                            GROUP_CONCAT(CONCAT(c.from_trace_no, " :: ", cc.`description`, " (", cc.`code`, ") - Qty ", FORMAT(c.out_qty,3), " MT") SEPARATOR "|") AS from_trace_no
//                                                       FROM t_trace_header c
//                                                       LEFT JOIN m_material cc
//                                                         ON c.id_material = cc.id_material
//                                                      WHERE c.`status` = 1
//                                                        AND SUBSTRING(c.to_trace_no,1,1) = 8
//                                                        AND SUBSTRING(c.to_trace_no,8,1) = 0
//                                                      GROUP BY c.to_trace_no ) c
//                                            ON b.from_trace_no = c.to_trace_no
//                                          LEFT JOIN t_material_document d
//                                            ON d.id_trace_head = b.id_trace_head
//                                         WHERE b.`status` = 1
//                                           AND SUBSTRING(b.to_trace_no,1,1) = 8
//                                           AND SUBSTRING(b.from_trace_no,1,1) = 8) b
//                               ON a.id_balance_head = b.id_balance_head
//                             LEFT JOIN m_material c
//                               ON c.id_material = a.id_material
//                             LEFT JOIN m_tank d
//                               ON d.id_tank = a.id_tank AND d.id_plant = ?
//                             LEFT JOIN t_balance_detail e
//                               ON a.id_balance_head = e.id_balance_head
//                             LEFT JOIN (SELECT ee1.trace_no, SUM(ee2.init_qty) AS init_qty
//                                          FROM t_balance_header ee1
//                                          LEFT JOIN t_balance_detail ee2
//                                            ON ee1.id_balance_head = ee2.id_balance_head
//                                         WHERE ee1.status = 1
//                                         GROUP BY ee1.trace_no
//                                         ) ee
//                               ON a.trace_no = ee.trace_no
//                             LEFT JOIN m_supplier f
//                               ON e.id_supplier = f.id_supplier
//                            WHERE a.`status` = 1
//                              AND SUBSTRING(a.trace_no,1,1) = 8
//                            GROUP BY a.trace_no
//                            ORDER BY a.trace_no DESC', [self::$idPlantEob1]);

//         return $db;
//     }
//     static function get_cmbActiveTank_rundown($request){
//         $idMaterial = $request->input('idMaterial');

//         DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
//         $db = DB::select('SELECT b.id_tank, b.description AS tank
//                             FROM m_material a
//                             LEFT JOIN m_tank b
//                               ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
//                            WHERE a.status = 1
//                              AND a.id_material = ?
//                            GROUP BY b.id_tank', [self::$idPlantEob1, $idMaterial]);
//         return $db;
//     }
//     static function post_blendingEntryMaterial($user, $request){
//         $flag = $request->input('flag');
//         $mode = $request->input('mode');
//         $entryNo = $request->input('entryNo');
//         $idMaterialSource = $request->input('idMaterialSource');
//         $qty = $request->input('qty');
//         $idHead = $request->input('idHead');
//         $qty = floatval(str_replace(',', '', $qty));

//         if ($mode == 'ADD'){
//             /* CHECK FOR SAME MATERIAL */
//             $dat = DB::select('SELECT COUNT(entry_no) AS flag
//                                  FROM t_balance_temporary
//                                 WHERE id_material = ?
//                                   AND entry_no = ?', [$idMaterialSource, $entryNo]);

//             if ($dat[0]->flag > 0){
//                 $db = [ (object)['response' => 2 ]];
//                 return $db;
//             };

//             $db = DB::insert('INSERT INTO t_balance_temporary
//                                     (entry_no, id_material, qty, created_by)
//                             VALUES (?, ?, ?, ?)',
//                             [$entryNo, $idMaterialSource, $qty, $user]);
//             $db = [ (object)['response' => $db ? 1 : 0 ]];

//         } elseif ($mode == 'UPDATE'){

//         }
//         return $db;
//     }
//     static function blendingMaterial_destroy($id, $user){
//         DB::delete('DELETE FROM t_balance_temporary
//                      WHERE id_balance_temp = ?', [$id]);

//         $db = [ (object)['response' => 1 ]];
//         return $db;
//     }
//     static function post_blendingEntry($user, $request){
//         $flag = $request->input('flag');
//         $mode = $request->input('mode');
//         $idHead = $request->input('idHead');
//         $entryNo = $request->input('entry_no');
//         $entryDate = $request->input('entry_date');
//         $idMaterial = $request->input('id_material');
//         $materialDoc = $request->input('material_doc');
//         $totalQty = $request->input('qty');
//         $totalQty = floatval(str_replace(',', '', $totalQty));

//         DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

//         /* CHECK LOCK PERIOD */
//             $lockDateTime = new \DateTime($entryDate);
//             // Mengambil tahun
//             $lockYear = $lockDateTime->format('Y');
//             // Mengambil bulan
//             $lockMonth = $lockDateTime->format('m');

//             $datLock = DB::select('SELECT lock_status
//                                     FROM t_report_pspa_head
//                                     WHERE `status` = 1
//                                     AND YEAR(`period`) = ?
//                                     AND MONTH(`period`) = ?
//                                     UNION ALL
//                                 SELECT "0" AS lock_status',
//                                     [$lockYear, $lockMonth]);
//             $lockStatus = $datLock[0]->lock_status;
//             if ($lockStatus == 1){
//                 $db = [ (object)['response' => 99 ]];
//                 return $db;
//             }

//         /* CHECKING MATERIAL SOURCE */
//             $datMaterialEntry = DB::select('SELECT COUNT(a.entry_no) AS itemCnt
//                                               FROM t_balance_temporary a
//                                              WHERE a.entry_no = ?', [$entryNo]);
//             $itemCnt = $datMaterialEntry[0]->itemCnt;

//             if ($itemCnt == 0){
//                 $db = [ (object)['response' => 4 ]];
//                 return $db;
//             }

//         /* CONTINUE ROUTE BLENDING */
//             $datMaterial = DB::select('SELECT id_material, qty
//                                          FROM t_balance_temporary
//                                         WHERE entry_no = ?', [$entryNo]);
//             $lenDatMatl = count($datMaterial);

//             /* MULTI-MATERIAL FEED FOR STOCK-OUT TO BLENDING */
//             for ($z = 0; $z < $lenDatMatl; $z++){
//                 $idMaterialSource = $datMaterial[$z]->id_material;
//                 $qtySource = $datMaterial[$z]->qty;

//                 /* USE FEED ROUTING TO TAKE OUT MATERIAL FOR BLENDING */
//                     $datHead = DB::select('SELECT b.id_material, c.id_balance_head, c.qty, c.in_qty, c.out_qty, c.init_qty, c.trace_no, c.id_tank
//                                              FROM m_material a
//                                              LEFT JOIN (SELECT b.code, b.id_material
//                                                           FROM m_material b
//                                                          WHERE b.status = 1) b
//                                                ON a.code = b.code
//                                              LEFT JOIN (SELECT c.id_material, c.id_balance_head, c.qty, c.in_qty, c.out_qty, c.init_qty, c.trace_no, c.id_tank
//                                                           FROM m_tank cc
//                                                           LEFT JOIN t_balance_header c
//                                                             ON c.id_tank = cc.id_tank
//                                                          WHERE c.status = 1
//                                                            AND cc.status = 1
//                                                            AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR SUBSTRING(c.trace_no,1,1) = 7 OR
//                                                                 SUBSTRING(c.trace_no,1,1) = 8 OR SUBSTRING(c.trace_no,1,1) = 9)
//                                                            AND cc.id_plant = ?
//                                                            AND c.qty > 0
//                                                         ) c
//                                                ON b.id_material = c.id_material
//                                             WHERE a.status = 1
//                                               AND a.id_material = ?
//                                               AND c.id_balance_head IS NOT NULL
//                                             ORDER BY c.id_balance_head ASC', [self::$idPlantEob1, $idMaterialSource]);
//                     $lenDatHead = count($datHead);

//                     /* VARIABLE ADJUSTMENT */
//                         $out_qty = $qtySource;
//                         $entry_no = substr_replace($entryNo, '0', 7, 1); /* REPLACE RUNDOWN_ID TO FEED_ID */
//                         $curr_entryDate = $entryDate;
//                         $curr_qtf = $qtySource;
//                         $last_qtf = 0;

//                     /* FEEDING ALGORITHM */
//                         for ($i = 0; $i < $lenDatHead; $i++) {
//                             $idHead = $datHead[$i]->id_balance_head;
//                             $qty = $datHead[$i]->qty;
//                             $total_in_qty = $datHead[$i]->in_qty;
//                             $total_out_qty = $datHead[$i]->out_qty;
//                             $init_qty = $datHead[$i]->init_qty;
//                             $from_trace_no = $datHead[$i]->trace_no;
//                             $id_material = $datHead[$i]->id_material;
//                             $id_tank = $datHead[$i]->id_tank;

//                             $new_total_in_qty = $total_in_qty;
//                             $new_total_out_qty = $total_out_qty + $out_qty;

//                             $tail_out_qty = $out_qty;

//                             $balanceAfter = $qty - $out_qty;
//                             if ($balanceAfter < 0){
//                                 if ($lenDatHead == 1){
//                                     $db = [ (object)['response' => 3 ]];
//                                     break;
//                                 }
//                                 $new_balance = 0;
//                                 $new_total_out_qty = $init_qty;
//                                 $temp_out_qty = $out_qty - $qty;
//                                 $out_qty = $qty;
//                             } else {
//                                 $new_balance = $qty - $out_qty;
//                             }

//                             /* UPDATE INTO T_BALANCE_HEADER */
//                                 DB::update('UPDATE t_balance_header
//                                                SET qty = ?,
//                                                    in_qty = ?,
//                                                    out_qty = ?,
//                                                    updated_by = ?
//                                              WHERE id_balance_head = ?',
//                                              [$new_balance, $new_total_in_qty, $new_total_out_qty, $user, $idHead]);

//                             /* INSERT INTO T_TRACE_HEADER */
//                                 $idTraceHead = DB::table('t_trace_header')->insertGetId([
//                                         'from_trace_no' => $from_trace_no,
//                                         'to_trace_no' => $entry_no,
//                                         'id_balance_head' => $idHead,
//                                         'id_material' => $id_material,
//                                         'entry_date' => $curr_entryDate,
//                                         'id_sloc' => $id_tank,
//                                         'out_qty' => $out_qty,
//                                         'last_qtf' => $last_qtf,
//                                         'curr_qtf' => $curr_qtf,
//                                         'created_by' => $user,
//                                 ]);

//                             /* HEADER LOGGING */
//                                 DB::insert('INSERT INTO log_transactions
//                                                 (log_module, log_type, log_description, created_by)
//                                             VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'BLENDING OUT', 'IDHEAD: ' . $idHead . ' | DATE: ' . $curr_entryDate .
//                                                                     ' / TANK: ' . $id_tank . ' / MATERIAL: ' . $id_material . ' / QTY: ' . $qty . ' >>> ' . $new_balance .
//                                                                     ' / IN_QTY: ' . $total_in_qty . ' >>> ' . $new_total_in_qty .
//                                                                     ' / OUT_QTY: ' . $total_out_qty . ' >>> ' . $new_total_out_qty .
//                                                                     ' | Status: 1', $user ]);

//                             /* ROUTING FOR DETAIL PER SUPPLIER */
//                                 /* GET ID_BALANCE_DETAIL */
//                                     $datTail = DB::select('SELECT id_balance_tail, id_supplier, qty, in_qty, out_qty, init_qty, batch_sap
//                                                              FROM t_balance_detail
//                                                             WHERE id_balance_head = ?
//                                                               AND `status` = 1
//                                                               AND qty > "0.0001"
//                                                             ORDER BY id_balance_tail ASC', [$idHead]);
//                                     $lenTail = count($datTail);
//                                     for ($k = 0; $k < $lenTail; $k++) {
//                                         $idTail = $datTail[$k]->id_balance_tail;
//                                         $idSupplier = $datTail[$k]->id_supplier;
//                                         $tail_qty = $datTail[$k]->qty;
//                                         $tail_total_in_qty = $datTail[$k]->in_qty;
//                                         $tail_total_out_qty = $datTail[$k]->out_qty;
//                                         $tail_init_qty = $datTail[$k]->init_qty;
//                                         $batch_sap = $datTail[$k]->batch_sap;

//                                         $new_tail_total_in_qty = $tail_total_in_qty;
//                                         $new_tail_total_out_qty = $tail_total_out_qty + $tail_out_qty;

//                                         // Rounding
//                                         $tail_out_qty = round($tail_out_qty, 4);
//                                         $tail_total_in_qty = round($tail_total_in_qty, 4);
//                                         $tail_total_out_qty = round($tail_total_out_qty, 4);
//                                         $tail_qty = round($tail_qty, 4);
//                                         $new_tail_total_in_qty = round($new_tail_total_in_qty, 4);
//                                         $new_tail_total_out_qty = round($new_tail_total_out_qty, 4);
//                                         $tailBalanceAfter = $tail_qty - $tail_out_qty;

//                                         if ($tailBalanceAfter < 0){
//                                             $new_tail_balance = 0;
//                                             $new_tail_total_out_qty = $tail_init_qty;
//                                             $temp_tail_out_qty = $tail_out_qty - $tail_qty;
//                                             $tail_out_qty = $tail_qty;
//                                         } else {
//                                             $new_tail_balance = $tail_qty - $tail_out_qty;
//                                         }

//                                         /* POPULATE NEW BALANCE DETAIL */
//                                             DB::update('UPDATE t_balance_detail
//                                                            SET qty = ?,
//                                                                in_qty = ?,
//                                                                out_qty = ?,
//                                                                updated_by = ?
//                                                          WHERE id_balance_tail = ?',
//                                                          [$new_tail_balance, $new_tail_total_in_qty, $new_tail_total_out_qty, $user, $idTail]);

//                                         /* POPULATE TRACE DETAIL */
//                                             $idTraceTail = DB::table('t_trace_detail')->insertGetId([
//                                                                     'id_trace_head' => $idTraceHead,
//                                                                     'id_balance_tail' => $idTail,
//                                                                     'id_supplier' => $idSupplier,
//                                                                     'id_material' => $id_material,
//                                                                     'out_qty' => $tail_out_qty,
//                                                                     'batch_sap' => $batch_sap,
//                                                                     'created_by' => $user,
//                                                             ]);

//                                         /* DETAIL LOGGING */
//                                             DB::insert('INSERT INTO log_transactions
//                                                             (log_module, log_type, log_description, created_by)
//                                                         VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'BLENDING OUT', ' IDTAIL: ' . $idTail .
//                                                                                 ' / SUPPLIER: ' . $idSupplier . ' / MATERIAL: ' . $id_material .
//                                                                                 ' / QTY: ' . $tail_qty . ' >>> ' . $new_tail_balance .
//                                                                                 ' / IN_QTY: ' . $tail_total_in_qty . ' >>> ' . $new_tail_total_in_qty .
//                                                                                 ' / OUT_QTY: ' . $tail_total_out_qty . ' >>> ' . $new_tail_total_out_qty .
//                                                                                 ' | Status: 1', $user ]);

//                                         /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
//                                         if ($tailBalanceAfter >= 0){
//                                             break;
//                                         }

//                                         /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
//                                             $tail_out_qty = $temp_tail_out_qty;

//                                     }

//                             /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
//                                 if ($balanceAfter >= 0){
//                                     $db = [ (object)['response' => 1 ]];
//                                     break;
//                                 }

//                             /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
//                                 $out_qty = $temp_out_qty;

//                         }
//             }

//             /* USE RUNDOWN ROUTING TO TAKE IN BLENDING MATERIAL */
//                 /* VARIABLE ADJUSTMENT */
//                     $id_material = $idMaterial;
//                     $process_yield = 1;
//                     $feed_entryNo = $entry_no;
//                     $entry_no = $entryNo;
//                     $curr_qtf = $totalQty;

//                 /* GET FEED TRACE RELATED TO RUNDOWN */
//                     $batch_seq = substr($feed_entryNo, 9, 2);
//                     $feed_id = substr($feed_entryNo, 7, 2);

//                     $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, SUM(out_qty) AS out_qty, id_material
//                                                   FROM t_trace_header
//                                                  WHERE SUBSTRING(to_trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
//                                                    AND SUBSTRING(to_trace_no,1,1) = 8
//                                                    AND SUBSTRING(to_trace_no,8,2) = ?
//                                                    AND SUBSTRING(to_trace_no,10,2) = ?
//                                                    AND `status` = 1
//                                                    AND out_qty > "0.0001"
//                                                  ORDER BY id_trace_head DESC
//                                                  LIMIT 1', [$feed_id, $batch_seq]);

//                     $feed_idTraceHead = $datTraceHead[0]->id_trace_head;
//                     $from_trace_no = $datTraceHead[0]->to_trace_no;
//                     $feed_qty = $datTraceHead[0]->out_qty;

//                     $in_qty = $process_yield * $feed_qty;

//                 /* GET ID_TANK BASED ON MATERIAL ASSIGNMENT */
//                     $datTank = DB::select('SELECT b.id_tank
//                                              FROM m_material a
//                                              LEFT JOIN m_tank b
//                                                ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
//                                             WHERE a.status = 1
//                                               AND a.id_material = ?', [self::$idPlantEob1, $id_material]);
//                     $id_tank = $datTank[0]->id_tank;

//                 /* INSERT INTO T_BALANCE_HEADER */
//                     $idHead = DB::table('t_balance_header')->insertGetId([
//                         'entry_date' => $curr_entryDate,
//                         'trace_no' => $entry_no,
//                         'id_material' => $id_material,
//                         'id_tank' => $id_tank,
//                         'qty' => $in_qty,
//                         'in_qty' => $in_qty,
//                         'init_qty' => $in_qty,
//                         'created_by' => $user,
//                     ]);
//                 /* INSERT INTO T_TRACE_HEADER */
//                     $idTraceHead = DB::table('t_trace_header')->insertGetId([
//                         'from_trace_no' => $from_trace_no,
//                         'to_trace_no' => $entry_no,
//                         'id_balance_head' => $idHead,
//                         'id_material' => $id_material,
//                         'entry_date' => $curr_entryDate,
//                         'id_sloc' => $id_tank,
//                         'in_qty' => $in_qty,
//                         'last_qtf' => $last_qtf,
//                         'curr_qtf' => $curr_qtf,
//                         'created_by' => $user,
//                     ]);

//                     DB::insert('INSERT INTO t_material_document
//                                        (id_trace_head, material_document, created_by)
//                                 VALUES (?, ?, ?)', [$idTraceHead, $materialDoc, $user]);
//                 /* HEADER LOGGING */
//                     DB::insert('INSERT INTO log_transactions
//                                        (log_module, log_type, log_description, created_by)
//                                 VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'BLENDING IN', 'IDHEAD: ' . $idHead . ' | DATE: ' . $curr_entryDate .
//                                                         ' / MATERIAL: ' . $id_material . ' / QTY: ' . $in_qty .
//                                                         ' / IN_QTY: ' . $in_qty .
//                                                         ' / OUT_QTY: 0' .
//                                                         ' | Status: 1', $user ]);

//                     $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, out_qty, id_material
//                                                   FROM t_trace_header
//                                                  WHERE SUBSTRING(to_trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
//                                                    AND SUBSTRING(to_trace_no,1,1) = 8
//                                                    AND SUBSTRING(to_trace_no,8,2) = ?
//                                                    AND SUBSTRING(to_trace_no,10,2) = ?
//                                                    AND `status` = 1
//                                                    AND out_qty > "0.0001"
//                                                  ORDER BY id_trace_head DESC',
//                                                 [$feed_id, $batch_seq]);
//                     $len = count($datTraceHead);

//                     for ($i = 0; $i < $len; $i++) {
//                         $feed_idTraceHead = $datTraceHead[$i]->id_trace_head;
//                         $from_trace_no = $datTraceHead[$i]->to_trace_no;
//                         $feed_qty = $datTraceHead[$i]->out_qty;

//                         /* ROUTING FOR DETAIL PER SUPPLIER */
//                             /* GET FEED ID_TRACE_DETAIL */
//                                 $datTraceTail = DB::select('SELECT id_trace_tail, id_balance_tail, id_supplier, out_qty, batch_sap
//                                                               FROM t_trace_detail
//                                                              WHERE id_trace_head = ?
//                                                                AND `status` = 1
//                                                              ORDER BY id_trace_tail ASC', [$feed_idTraceHead]);
//                                 $lenTraceTail = count($datTraceTail);
//                                 if ($lenTraceTail == 0){
//                                     $db = [ (object)['response' => 6 ]];
//                                     return $db;
//                                 }
//                                 for ($k = 0; $k < $lenTraceTail; $k++) {
//                                     $idTraceTail = $datTraceTail[$k]->id_trace_tail;
//                                     $idTail = $datTraceTail[$k]->id_balance_tail;
//                                     $idSupplier = $datTraceTail[$k]->id_supplier;
//                                     $feedSupplier = $datTraceTail[$k]->out_qty;
//                                     $batchSap = $datTraceTail[$k]->batch_sap;

//                                     $rundownSupplier = round($process_yield * $feedSupplier, 4);

//                                     /* POPULATE TRACE DETAIL */
//                                         $flagCheckIdSupplier = DB::select('SELECT count(id_trace_tail) AS cnt, id_trace_tail, in_qty, out_qty, id_balance_tail
//                                                                              FROM t_trace_detail
//                                                                             WHERE `status` = 1
//                                                                               AND id_trace_head = ?
//                                                                               AND id_supplier = ?
//                                                                               AND batch_sap = ?', [$idTraceHead, $idSupplier, $batchSap]);
//                                         $cntFlagCheckIdSupplier = $flagCheckIdSupplier[0]->cnt;
//                                         $idTraceTail = $flagCheckIdSupplier[0]->id_trace_tail;
//                                         $idTail = $flagCheckIdSupplier[0]->id_balance_tail;
//                                         $inQtyTail = $flagCheckIdSupplier[0]->in_qty;
//                                         $outQtyTail = $flagCheckIdSupplier[0]->out_qty;

//                                         if ($cntFlagCheckIdSupplier == 0){
//                                             /* INSERT INTO T_BALANCE_DETAIL */
//                                             $idTail = DB::table('t_balance_detail')->insertGetId([
//                                                 'id_balance_head' => $idHead,
//                                                 'id_supplier' => $idSupplier,
//                                                 'id_material' => $id_material,
//                                                 'qty' => $rundownSupplier,
//                                                 'in_qty' => $rundownSupplier,
//                                                 'init_qty' => $rundownSupplier,
//                                                 'batch_sap' => $batchSap,
//                                                 'created_by' => $user,
//                                             ]);
//                                             $idTraceTail = DB::table('t_trace_detail')->insertGetId([
//                                                 'id_trace_head' => $idTraceHead,
//                                                 'id_balance_tail' => $idTail,
//                                                 'id_supplier' => $idSupplier,
//                                                 'id_material' => $id_material,
//                                                 'in_qty' => $rundownSupplier,
//                                                 'batch_sap' => $batchSap,
//                                                 'created_by' => $user,
//                                             ]);

//                                         } else {
//                                             $newInQtyTail = $inQtyTail + $rundownSupplier;
//                                             DB::update('UPDATE t_balance_detail
//                                                            SET qty = ?,
//                                                                in_qty = ?,
//                                                                init_qty = ?,
//                                                                updated_by = ?
//                                                          WHERE id_balance_tail = ?', [$newInQtyTail, $newInQtyTail, $newInQtyTail, $user, $idTail]);
//                                             DB::update('UPDATE t_trace_detail
//                                                            SET in_qty = ?,
//                                                                updated_by = ?
//                                                          WHERE id_trace_tail = ?', [$newInQtyTail, $user, $idTraceTail]);
//                                         }

//                                     /* DETAIL LOGGING */
//                                         DB::insert('INSERT INTO log_transactions
//                                                            (log_module, log_type, log_description, created_by)
//                                                     VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'BLENDING IN', ' IDTAIL: ' . $idTail .
//                                                                             ' / SUPPLIER: ' . $idSupplier . ' / MATERIAL: ' . $id_material .
//                                                                             ' / QTY: ' . $rundownSupplier .
//                                                                             ' / IN_QTY: ' . $rundownSupplier .
//                                                                             ' / OUT_QTY: ' . $rundownSupplier .
//                                                                             ' / INIT_QTY: ' . $rundownSupplier .
//                                                                             ' | Status: 1', $user ]);

//                                 }

//             }

//             /* DESTROY TEMPORARY DATA */
//                 DB::delete('DELETE FROM t_balance_temporary
//                              WHERE entry_no = ?', [$entryNo]);

//         /* THROW OUTPUT */
//             $db = [ (object)['response' => 1 ]];
//             return $db;
//     }
//     static function post_matlDocNumber($user, $request){
//         $mode = $request->input('mode');
//         $idTraceHead = $request->input('id');
//         $materialDoc = $request->input('number');

//         if ($mode == 'ADD'){
//             $db = DB::insert('INSERT INTO t_material_document
//                                      (id_trace_head, material_document, created_by)
//                               VALUES (?, ?, ?)', [$idTraceHead, $materialDoc, $user]);
//             $db = [ (object)['response' => $db ? 1 : 0 ]];

//             /* LOGGING */
//             $id = DB::select('SELECT id_matdoc FROM t_material_document ORDER BY id_matdoc DESC LIMIT 1');
//             DB::insert('INSERT INTO log_transactions
//                                (log_module, log_type, log_description, created_by)
//                         VALUES (?, ?, ?, ?)', [ 'T_MATERIAL_DOCUMENT', 'ADD', 'ID: ' . $id[0]->id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead .
//                                                 ' / DOC_NO: ' . $materialDoc .
//                                                 ' | Status: 1', $user ]);
//         } elseif ($mode == 'UPDATE'){
//             $dat = DB::select('SELECT id_matdoc, material_document
//                                  FROM t_material_document
//                                 WHERE id_trace_head = ?', [$idTraceHead]);
//             $id_matdoc = $dat[0]->id_matdoc;
//             $old_materialDoc = $dat[0]->material_document;

//             $db = DB::update('UPDATE t_material_document
//                                  SET material_document = ?,
//                                      updated_by = ?
//                                WHERE id_trace_head = ?', [$materialDoc, $user, $idTraceHead]);
//             $db = [ (object)['response' => $db ]];

//             /* LOGGING */
//             DB::insert('INSERT INTO log_transactions
//                                (log_module, log_type, log_description, created_by)
//                         VALUES (?, ?, ?, ?)', [ 'T_MATERIAL_DOCUMENT', 'UPDATE', 'ID: ' . $id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead .
//                                                 ' / DOC_NO: ' . $old_materialDoc . ' >>> ' . $materialDoc .
//                                                 ' | Status: 1', $user ]);
//         }
//         return $db;
//     }
//     static function blending_destroy($id, $user){
//         $idTmp          = explode("|", $id);
//         $idHead         = trim($idTmp[0]);
//         $idTraceHead    = trim($idTmp[1]);

//         /* CHECK LOCK PERIOD */
//             $entryDate = DB::select('SELECT entry_date
//                                        FROM t_trace_header
//                                       WHERE id_trace_head = ?
//                                         AND `status` = 1',
//                                     [$idTraceHead]);
//             $curr_entryDate = $entryDate[0]->entry_date;

//             $lockDateTime = new \DateTime($curr_entryDate);
//             // Mengambil tahun
//             $lockYear = $lockDateTime->format('Y');
//             // Mengambil bulan
//             $lockMonth = $lockDateTime->format('m');

//             $datLock = DB::select(' SELECT lock_status
//                                     FROM t_report_pspa_head
//                                     WHERE `status` = 1
//                                     AND YEAR(`period`) = ?
//                                     AND MONTH(`period`) = ?
//                                     UNION ALL
//                                     SELECT "0" AS lock_status',
//                                     [$lockYear, $lockMonth]);
//             $lockStatus = $datLock[0]->lock_status;
//             if ($lockStatus == 1){
//                 $db = [ (object)['response' => 99 ]];
//                 return $db;
//             }

//         /* CONTINUE MAIN ROUTE */
//         DB::insert('INSERT INTO log_transactions
//                            (log_module, log_type, log_description, created_by)
//                     VALUES (?, ?, ?, ?)', [ 'BLENDING_ENTRY', 'DE-ACTIVATE', 'IdBalHead: ' . $idHead . ' | Status: 1 >> 0', $user ]);
//         DB::update('UPDATE t_balance_detail
//                        SET `status` = "0",
//                            `updated_by` = ?
//                      WHERE id_balance_head = ?', [$user, $idHead]);
//         DB::update('UPDATE t_balance_header
//                        SET `status` = "0",
//                            `updated_by` = ?
//                      WHERE id_balance_head = ?', [$user, $idHead]);

//         /* GET SOURCE BLENDING AND DELETE */
//             $datTraceHead = DB::select('SELECT b.id_balance_head, b.out_qty, b.id_trace_head
//                                           FROM t_trace_header a
//                                           LEFT JOIN t_trace_header b
//                                             ON a.from_trace_no = b.to_trace_no AND b.status = 1
//                                          WHERE a.id_balance_head = ?
//                                            AND a.status = 1', [$idHead]);
//             $lenTraceHead = count($datTraceHead);

//             for ($i = 0; $i < $lenTraceHead; $i++){
//                 $idBalHead = $datTraceHead[$i]->id_balance_head;
//                 $idTracHead = $datTraceHead[$i]->id_trace_head;
//                 $outQtyHead = $datTraceHead[$i]->out_qty;

//                 /* GET SOURCE BLEND AND RESTORE STOCK */
//                 $datBalHeadSource = DB::select('SELECT a.qty, a.out_qty
//                                                   FROM t_balance_header a
//                                                  WHERE a.status = 1
//                                                    AND a.id_balance_head = ?', [$idBalHead]);
//                 $outQtyBalHeadSource = $datBalHeadSource[0]->out_qty - $outQtyHead;
//                 $onhandQtyBalHeadSource = $datBalHeadSource[0]->qty + $outQtyHead;

//                 DB::update('UPDATE t_balance_header a
//                                SET a.qty = ?,
//                                    a.out_qty = ?,
//                                    a.`updated_by` = ?
//                              WHERE a.id_balance_head = ?', [$onhandQtyBalHeadSource, $outQtyBalHeadSource, $user, $idBalHead]);

//                 /* GET TRACE DETAIL */
//                 $datTraceTail = DB::select('SELECT a.id_balance_tail, a.out_qty, a.id_trace_tail
//                                               FROM t_trace_detail a
//                                              WHERE a.id_trace_head = ?
//                                                AND a.status = 1', [$idTracHead]);
//                 $lenTraceTail = count($datTraceTail);

//                 for ($j = 0; $j < $lenTraceTail; $j++){
//                     $idBalTail = $datTraceTail[$j]->id_balance_tail;
//                     $outQtyTail = $datTraceTail[$j]->out_qty;
//                     $idTracTail = $datTraceTail[$j]->id_trace_tail;

//                     $datBalTailSource = DB::select('SELECT a.qty, a.out_qty
//                                                       FROM t_balance_detail a
//                                                      WHERE a.status = 1
//                                                        AND a.id_balance_tail = ?', [$idBalTail]);
//                     $outQtyBalTailSource = $datBalTailSource[0]->out_qty - $outQtyTail;
//                     $onhandQtyBalTailSource = $datBalTailSource[0]->qty + $outQtyTail;

//                     DB::update('UPDATE t_balance_detail a
//                                    SET a.qty = ?,
//                                        a.out_qty = ?,
//                                        a.`updated_by` = ?
//                                  WHERE a.id_balance_tail = ?', [$onhandQtyBalTailSource, $outQtyBalTailSource, $user, $idBalTail]);

//                     DB::update('UPDATE t_trace_detail
//                                    SET `status` = "0",
//                                        `updated_by` = ?
//                                  WHERE id_trace_tail = ?', [$user, $idTracTail]);
//                 }

//                 DB::update('UPDATE t_trace_header
//                                SET `status` = "0",
//                                    `updated_by` = ?
//                              WHERE id_trace_head = ?', [$user, $idTracHead]);
//             }

//         DB::update('UPDATE t_trace_header
//                        SET `status` = "0",
//                            `updated_by` = ?
//                      WHERE id_balance_head = ?', [$user, $idHead]);
//         DB::update('UPDATE t_trace_detail
//                        SET `status` = "0",
//                            `updated_by` = ?
//                      WHERE id_trace_head = ?', [$user, $idTraceHead]);

//         /* THROW OUTPUT */
//         $db = [ (object)['response' => 1 ]];
//         return $db;


//     }
// }
