<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Helpers\Feed;
use App\Helpers\Rundown;

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
                                      ON a.id_rundown = SUBSTRING(b.trace_no, 8,3) AND b.status = 1
                                   WHERE a.id_material = ?
                                     AND SUBSTRING(b.trace_no, 1, 7) = CONCAT(8, DATE_FORMAT(CURDATE(), "%y%m%d"))
                                     AND a.status = 1
                                   ORDER BY b.id_balance_head DESC
                                   LIMIT 1) a
                           UNION ALL
                          --  8yymmddrundown0201 (LPAD(RIGHT(?, 2), 2, "0") takes last 2 digits of $idPlant - eob1 = 02)
                          SELECT CONCAT("8", DATE_FORMAT(CURDATE(), "%y%m%d"), IF(a.id_rundown <> "-", a.id_rundown, "000"), LPAD(RIGHT(?, 2), 2, "0"), "01") AS entryNo
                            FROM m_material a
                           WHERE a.status = 1
                             AND a.id_material = ?
                           LIMIT 1
                           ', [$idMaterial, $idPlant, $idMaterial]);

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

        $db = DB::select('SELECT a.entry_date, b.material_document, a.id_tank, a.id_tank_tail,
                                 CAST(a.trace_no AS CHAR) AS trace_no, FORMAT(a.qty,3) AS qty, FORMAT(a.init_qty,3) AS init_qty, a.entry_date, a.id_balance_head AS idHead,
                                 CONCAT(c.`description`, " (", c.`code`, ")") AS material,
                                 GROUP_CONCAT(DISTINCT CONCAT(f.`description`, " / ", e.batch_sap, " / Qty : ", FORMAT(e.init_qty,3), " MT / Qty : ", FORMAT(e.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                 CAST(b.from_trace_no AS CHAR) AS from_trace_no, b.id_trace_head AS idTraceHead, b.id_trace_head,
                                 b.is_last_row, b.next_process,
                                 CONCAT(d.description, 
                                    IF(GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ", ") IS NULL, 
                                        "", 
                                        CONCAT(" | ", GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ", "))
                                    )
                                 ) AS sloc,
                                 FORMAT(ROUND(ee.init_qty,4),3) as balance_supplier
                            FROM t_balance_header a
                            LEFT JOIN (SELECT b.id_balance_head, b.id_trace_head,
                                              c.from_trace_no, d.material_document,
                                              CASE
                                                WHEN b.to_trace_no = (SELECT to_trace_no
                                                                        FROM t_trace_header
                                                                       WHERE SUBSTRING(to_trace_no, 1, 1) = 8
                                                                         AND SUBSTRING(to_trace_no, 9, 1) <> 0
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
                                                       AND SUBSTRING(c.to_trace_no,9,1) = 0
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
                            LEFT JOIN m_tank_detail h
                              ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(h.id_tank_tail AS CHAR)))
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
    static function get_cmbActiveSpecificTank_rundown($request){
        $sloc = $request->input('sloc');

        $db = DB::select('SELECT a.id_tank_tail, a.tf_number AS tankNo
                            FROM m_tank_detail a
                           WHERE a.status = 1
                             AND a.id_tank = ?
                           ORDER BY a.tf_number ASC', [$sloc]);
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
        $idTank = $request->input('idTank');

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
                                    (entry_no, id_material, qty, created_by, id_tank, id_plant)
                            VALUES (?, ?, ?, ?, ?, ?)',
                            [$entryNo, $idMaterialSource, $qty, $user, $idTank, $idPlant]);
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
    // static function post_blendingEntry($user, $request){
    //     $flag = $request->input('flag');
    //     $mode = $request->input('mode');
    //     $idHead = $request->input('idHead');
    //     $entryNo = $request->input('entry_no');
    //     $entryDate = $request->input('entry_date');
    //     $idMaterial = $request->input('id_material');
    //     $materialDoc = $request->input('material_doc');
    //     $totalQty = $request->input('qty');
    //     $totalQty = floatval(str_replace(',', '', $totalQty));
    //     $idPlant = \App\Models\BaseModel::resolvePlant($request);
    //     $id_tank_tail = $request->input('tankNo');
    //     $id_tank_tail_json = json_encode($id_tank_tail);

    //     $insertPlant = ($idPlant === 0 ? null : $idPlant);

    //     DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

    //     /* CHECK LOCK PERIOD */
    //     $lockDateTime = new \DateTime($entryDate);
    //     // Mengambil tahun
    //     $lockYear = $lockDateTime->format('Y');
    //     // Mengambil bulan
    //     $lockMonth = $lockDateTime->format('m');

    //     $datLock = DB::select('SELECT lock_status
    //                         FROM t_report_pspa_head
    //                         WHERE `status` = 1
    //                         AND YEAR(`period`) = ?
    //                         AND MONTH(`period`) = ?
    //                         UNION ALL
    //                     SELECT "0" AS lock_status',
    //                         [$lockYear, $lockMonth]);
    //     $lockStatus = $datLock[0]->lock_status;
    //     if ($lockStatus == 1){
    //         $db = [ (object)['response' => 99 ]];
    //         return $db;
    //     }

    //     /* CHECKING MATERIAL SOURCE */
    //     $datMaterialEntry = DB::select('SELECT COUNT(a.entry_no) AS itemCnt
    //                                   FROM t_balance_temporary a
    //                                  WHERE a.entry_no = ?', [$entryNo]);
    //     $itemCnt = $datMaterialEntry[0]->itemCnt;

    //     if ($itemCnt == 0){
    //         $db = [ (object)['response' => 4 ]];
    //         return $db;
    //     }

    //     /* CONTINUE ROUTE BLENDING */
    //     $datMaterial = DB::select('SELECT id_material, qty, id_tank
    //                              FROM t_balance_temporary
    //                             WHERE entry_no = ?', [$entryNo]);
    //     $lenDatMatl = count($datMaterial);

    //     /* MULTI-MATERIAL FEED FOR STOCK-OUT TO BLENDING */
    //     for ($z = 0; $z < $lenDatMatl; $z++){
    //         $idMaterialSource = $datMaterial[$z]->id_material;
    //         $qtySource = $datMaterial[$z]->qty;

    //         /* USE FEED ROUTING TO TAKE OUT MATERIAL FOR BLENDING */
    //         $datHead = DB::select('SELECT b.id_material, c.id_balance_head, c.qty, c.in_qty, c.out_qty, c.init_qty, c.trace_no, c.id_tank
    //                              FROM m_material a
    //                              LEFT JOIN (SELECT b.code, b.id_material
    //                                           FROM m_material b
    //                                          WHERE b.status = 1) b
    //                                ON a.code = b.code
    //                              LEFT JOIN (SELECT c.id_material, c.id_balance_head, c.qty, c.in_qty, c.out_qty, c.init_qty, c.trace_no, c.id_tank
    //                                           FROM m_tank cc
    //                                           LEFT JOIN t_balance_header c
    //                                             ON c.id_tank = cc.id_tank
    //                                          WHERE c.status = 1
    //                                            AND cc.status = 1
    //                                            AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR SUBSTRING(c.trace_no,1,1) = 7 OR
    //                                                 SUBSTRING(c.trace_no,1,1) = 8 OR SUBSTRING(c.trace_no,1,1) = 9)
    //                                            AND cc.id_plant = ?
    //                                            AND c.qty > 0
    //                                         ) c
    //                                ON b.id_material = c.id_material
    //                             WHERE a.status = 1
    //                               AND a.id_material = ?
    //                               AND c.id_balance_head IS NOT NULL
    //                             ORDER BY c.id_balance_head ASC', [$idPlant, $idMaterialSource]);
    //         $lenDatHead = count($datHead);

    //         /* VARIABLE ADJUSTMENT */
    //         $out_qty = $qtySource;
    //         $entry_no = substr_replace($entryNo, '0', 8, 1); /* REPLACE RUNDOWN_ID TO FEED_ID */
    //         $curr_entryDate = $entryDate;
    //         $curr_qtf = $qtySource;
    //         $last_qtf = 0;

    //         /* FEEDING ALGORITHM */
    //         for ($i = 0; $i < $lenDatHead; $i++) {
    //             $idHead = $datHead[$i]->id_balance_head;
    //             $qty = $datHead[$i]->qty;
    //             $total_in_qty = $datHead[$i]->in_qty;
    //             $total_out_qty = $datHead[$i]->out_qty;
    //             $init_qty = $datHead[$i]->init_qty;
    //             $from_trace_no = $datHead[$i]->trace_no;
    //             $id_material = $datHead[$i]->id_material;
    //             $id_tank = $datHead[$i]->id_tank;

    //             $new_total_in_qty = $total_in_qty;
    //             $new_total_out_qty = $total_out_qty + $out_qty;

    //             $tail_out_qty = $out_qty;

    //             $balanceAfter = $qty - $out_qty;
    //             if ($balanceAfter < 0){
    //                 if ($lenDatHead == 1){
    //                   $db = [ (object)['response' => 3 ]];
    //                   break;
    //                 }
    //                 $new_balance = 0;
    //                 $new_total_out_qty = $init_qty;
    //                 $temp_out_qty = $out_qty - $qty;
    //                 $out_qty = $qty;
    //             } else {
    //                 $new_balance = $qty - $out_qty;
    //             }

    //             /* UPDATE INTO T_BALANCE_HEADER */
    //             DB::update('UPDATE t_balance_header
    //                        SET qty = ?,
    //                            in_qty = ?,
    //                            out_qty = ?,
    //                            updated_by = ?
    //                      WHERE id_balance_head = ?',
    //                      [$new_balance, $new_total_in_qty, $new_total_out_qty, $user, $idHead]);

    //             /* INSERT INTO T_TRACE_HEADER (FEED -> OUT) */
    //             $traceHeaderData = [
    //                 'from_trace_no'    => $from_trace_no,
    //                 'to_trace_no'      => $entry_no,
    //                 'id_balance_head'  => $idHead,
    //                 'id_material'      => $id_material,
    //                 'entry_date'       => $curr_entryDate,
    //                 'id_sloc'          => $id_tank,
    //                 'id_tank_tail'     => $id_tank_tail_json,
    //                 'out_qty'          => $out_qty,
    //                 'last_qtf'         => $last_qtf,
    //                 'curr_qtf'         => $curr_qtf,
    //                 'created_by'       => $user,
    //             ];
    //             if ($insertPlant !== null) $traceHeaderData['id_plant'] = $insertPlant;

    //             $idTraceHead = DB::table('t_trace_header')->insertGetId($traceHeaderData);

    //             /* HEADER LOGGING */
    //             DB::insert('INSERT INTO log_transactions
    //                         (log_module, log_type, log_description, created_by)
    //                     VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'BLENDING OUT', 'IDHEAD: ' . $idHead . ' | DATE: ' . $curr_entryDate .
    //                                             ' / TANK: ' . $id_tank . ' / MATERIAL: ' . $id_material . ' / QTY: ' . $qty . ' >>> ' . $new_balance .
    //                                             ' / IN_QTY: ' . $total_in_qty . ' >>> ' . $new_total_in_qty .
    //                                             ' / OUT_QTY: ' . $total_out_qty . ' >>> ' . $new_total_out_qty .
    //                                             ' | Status: 1', $user ]);

    //             /* ROUTING FOR DETAIL PER SUPPLIER */
    //             $datTail = DB::select('SELECT id_balance_tail, id_supplier, qty, in_qty, out_qty, init_qty, batch_sap
    //                                  FROM t_balance_detail
    //                                 WHERE id_balance_head = ?
    //                                   AND `status` = 1
    //                                   AND qty > "0.0001"
    //                                 ORDER BY id_balance_tail ASC', [$idHead]);
    //             $lenTail = count($datTail);
    //             for ($k = 0; $k < $lenTail; $k++) {
    //                 $idTail = $datTail[$k]->id_balance_tail;
    //                 $idSupplier = $datTail[$k]->id_supplier;
    //                 $tail_qty = $datTail[$k]->qty;
    //                 $tail_total_in_qty = $datTail[$k]->in_qty;
    //                 $tail_total_out_qty = $datTail[$k]->out_qty;
    //                 $tail_init_qty = $datTail[$k]->init_qty;
    //                 $batch_sap = $datTail[$k]->batch_sap;

    //                 $new_tail_total_in_qty = $tail_total_in_qty;
    //                 $new_tail_total_out_qty = $tail_total_out_qty + $tail_out_qty;

    //                 $tailBalanceAfter = $tail_qty - $tail_out_qty;

    //                 if ($tailBalanceAfter < 0){
    //                     $new_tail_balance = 0;
    //                     $new_tail_total_out_qty = $tail_init_qty;
    //                     $temp_tail_out_qty = $tail_out_qty - $tail_qty;
    //                     $tail_out_qty = $tail_qty;
    //                 } else {
    //                     $new_tail_balance = $tail_qty - $tail_out_qty;
    //                 }

    //                 $tail_out_qty = round($tail_out_qty, 4);
    //                 $tail_total_in_qty = round($tail_total_in_qty, 4);
    //                 $tail_total_out_qty = round($tail_total_out_qty, 4);
    //                 $tail_qty = round($tail_qty, 4);
    //                 $new_tail_balance = round($new_tail_balance, 4);
    //                 $new_tail_total_in_qty = round($new_tail_total_in_qty, 4);
    //                 $new_tail_total_out_qty = round($new_tail_total_out_qty, 4);

    //                 /* POPULATE NEW BALANCE DETAIL */
    //                 DB::update('UPDATE t_balance_detail
    //                            SET qty = ?,
    //                                in_qty = ?,
    //                                out_qty = ?,
    //                                updated_by = ?
    //                          WHERE id_balance_tail = ?',
    //                          [$new_tail_balance, $new_tail_total_in_qty, $new_tail_total_out_qty, $user, $idTail]);

    //                 /* POPULATE TRACE DETAIL (FEED -> OUT DETAIL) */
    //                 $traceDetailOut = [
    //                     'id_trace_head'   => $idTraceHead,
    //                     'id_balance_tail' => $idTail,
    //                     'id_supplier'     => $idSupplier,
    //                     'id_material'     => $id_material,
    //                     'id_sloc'         => $id_tank,
    //                     'id_tank_tail'    => $id_tank_tail_json,
    //                     'out_qty'         => $tail_out_qty,
    //                     'batch_sap'       => $batch_sap,
    //                     'created_by'      => $user,
    //                 ];
    //                 if ($insertPlant !== null) $traceDetailOut['id_plant'] = $insertPlant;

    //                 $idTraceTail = DB::table('t_trace_detail')->insertGetId($traceDetailOut);

    //                 /* DETAIL LOGGING */
    //                 DB::insert('INSERT INTO log_transactions
    //                             (log_module, log_type, log_description, created_by)
    //                         VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'BLENDING OUT', ' IDTAIL: ' . $idTail .
    //                                                 ' / SUPPLIER: ' . $idSupplier . ' / MATERIAL: ' . $id_material .
    //                                                 ' / QTY: ' . $tail_qty . ' >>> ' . $new_tail_balance .
    //                                                 ' / IN_QTY: ' . $tail_total_in_qty . ' >>> ' . $new_tail_total_in_qty .
    //                                                 ' / OUT_QTY: ' . $tail_total_out_qty . ' >>> ' . $new_tail_total_out_qty .
    //                                                 ' | Status: 1', $user ]);

    //                 /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
    //                 if ($tailBalanceAfter >= 0){
    //                     break;
    //                 }

    //                 /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
    //                 $tail_out_qty = $temp_tail_out_qty;
    //             }

    //             /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
    //             if ($balanceAfter >= 0){
    //                 $db = [ (object)['response' => 1 ]];
    //                 break;
    //             }

    //             /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
    //             $out_qty = $temp_out_qty;
    //         } // end feeding algorithm
    //     } // end foreach materials feed

    //     /* USE RUNDOWN ROUTING TO TAKE IN BLENDING MATERIAL */
    //     /* VARIABLE ADJUSTMENT */
    //     $id_material = $idMaterial;
    //     $process_yield = 1;
    //     $feed_entryNo = $entry_no;
    //     $entry_no = $entryNo;
    //     $curr_qtf = $totalQty;

    //     /* GET FEED TRACE RELATED TO RUNDOWN */
    //     $batch_seq = substr($feed_entryNo, 12, 2);
    //     $feed_id = substr($feed_entryNo, 7, 3);

    //     $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, SUM(out_qty) AS out_qty, id_material
    //                               FROM t_trace_header
    //                              WHERE SUBSTRING(to_trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
    //                                AND SUBSTRING(to_trace_no,1,1) = 8
    //                                AND SUBSTRING(to_trace_no,8,3) = ?
    //                                AND SUBSTRING(to_trace_no,13,2) = ?
    //                                AND `status` = 1
    //                                AND out_qty > "0.0001"
    //                                AND (id_plant = ? OR ? = 0)
    //                              ORDER BY id_trace_head DESC
    //                              LIMIT 1', [$feed_id, $batch_seq, $idPlant, $idPlant]);

    //     if (!isset($datTraceHead[0]->id_trace_head)) {
    //         $db = [ (object)['response' => 6 ]];
    //         return $db;
    //     }

    //     $feed_idTraceHead = $datTraceHead[0]->id_trace_head;
    //     $from_trace_no = $datTraceHead[0]->to_trace_no;
    //     $feed_qty = $datTraceHead[0]->out_qty;

    //     $in_qty = $process_yield * $feed_qty;

    //     /* GET ID_TANK BASED ON MATERIAL ASSIGNMENT */
    //     $datTank = DB::select('SELECT b.id_tank
    //                          FROM m_material a
    //                          LEFT JOIN m_tank b
    //                            ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
    //                         WHERE a.status = 1
    //                           AND a.id_material = ?', [$idPlant, $id_material]);

    //     if (!isset($datTank[0]->id_tank)) {
    //         $db = [ (object)['response' => 6 ]];
    //         return $db;
    //     }

    //     $id_tank = $datTank[0]->id_tank;

    //     /* INSERT INTO T_BALANCE_HEADER (BLENDING IN) */
    //     $balanceHeaderData = [
    //         'entry_date' => $curr_entryDate,
    //         'trace_no'   => $entry_no,
    //         'id_material'=> $id_material,
    //         'id_tank'    => $id_tank,
    //         'id_tank_tail' => $id_tank_tail_json,
    //         'qty'        => $in_qty,
    //         'in_qty'     => $in_qty,
    //         'init_qty'   => $in_qty,
    //         'created_by' => $user,
    //     ];
    //     if ($insertPlant !== null) $balanceHeaderData['id_plant'] = $insertPlant;

    //     $idHead = DB::table('t_balance_header')->insertGetId($balanceHeaderData);

    //     /* INSERT INTO T_TRACE_HEADER (BLENDING IN) */
    //     $traceInData = [
    //         'from_trace_no'    => $from_trace_no,
    //         'to_trace_no'      => $entry_no,
    //         'id_balance_head'  => $idHead,
    //         'id_material'      => $id_material,
    //         'entry_date'       => $curr_entryDate,
    //         'id_sloc'          => $id_tank,
    //         'id_tank_tail'     => $id_tank_tail_json,
    //         'in_qty'           => $in_qty,
    //         'last_qtf'         => $last_qtf,
    //         'curr_qtf'         => $curr_qtf,
    //         'created_by'       => $user,
    //     ];
    //     if ($insertPlant !== null) $traceInData['id_plant'] = $insertPlant;

    //     $idTraceHead = DB::table('t_trace_header')->insertGetId($traceInData);

    //     DB::insert('INSERT INTO t_material_document
    //                    (id_trace_head, material_document, created_by)
    //             VALUES (?, ?, ?)', [$idTraceHead, $materialDoc, $user]);

    //     /* HEADER LOGGING */
    //     DB::insert('INSERT INTO log_transactions
    //                    (log_module, log_type, log_description, created_by)
    //             VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'BLENDING IN', 'IDHEAD: ' . $idHead . ' | DATE: ' . $curr_entryDate .
    //                                     ' / MATERIAL: ' . $id_material . ' / QTY: ' . $in_qty .
    //                                     ' / IN_QTY: ' . $in_qty .
    //                                     ' / OUT_QTY: 0' .
    //                                     ' | Status: 1', $user ]);

    //     $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, out_qty, id_material
    //                               FROM t_trace_header
    //                              WHERE SUBSTRING(to_trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
    //                                AND SUBSTRING(to_trace_no,1,1) = 8
    //                                AND SUBSTRING(to_trace_no,8,3) = ?
    //                                AND SUBSTRING(to_trace_no,13,2) = ?
    //                                AND `status` = 1
    //                                AND out_qty > "0.0001"
    //                                AND (id_plant = ? OR ? = 0)
    //                              ORDER BY id_trace_head DESC',
    //                             [$feed_id, $batch_seq, $idPlant, $idPlant]);
    //     $len = count($datTraceHead);

    //     for ($i = 0; $i < $len; $i++) {
    //         $feed_idTraceHead = $datTraceHead[$i]->id_trace_head;
    //         $from_trace_no = $datTraceHead[$i]->to_trace_no;
    //         $feed_qty = $datTraceHead[$i]->out_qty;

    //         /* ROUTING FOR DETAIL PER SUPPLIER */
    //         $datTraceTail = DB::select('SELECT id_trace_tail, id_balance_tail, id_supplier, out_qty, batch_sap
    //                                   FROM t_trace_detail
    //                                  WHERE id_trace_head = ?
    //                                    AND `status` = 1
    //                                  ORDER BY id_trace_tail ASC', [$feed_idTraceHead]);
    //         $lenTraceTail = count($datTraceTail);
    //         if ($lenTraceTail == 0){
    //             $db = [ (object)['response' => 6 ]];
    //             return $db;
    //         }
    //         for ($k = 0; $k < $lenTraceTail; $k++) {
    //             $idTraceTail = $datTraceTail[$k]->id_trace_tail;
    //             $idTail = $datTraceTail[$k]->id_balance_tail;
    //             $idSupplier = $datTraceTail[$k]->id_supplier;
    //             $feedSupplier = $datTraceTail[$k]->out_qty;
    //             $batchSap = $datTraceTail[$k]->batch_sap;

    //             $rundownSupplier = round($process_yield * $feedSupplier, 4);

    //             /* POPULATE TRACE DETAIL */
    //             $flagCheckIdSupplier = DB::select('SELECT count(id_trace_tail) AS cnt, id_trace_tail, in_qty, out_qty, id_balance_tail
    //                                              FROM t_trace_detail
    //                                             WHERE `status` = 1
    //                                               AND id_trace_head = ?
    //                                               AND id_supplier = ?
    //                                               AND batch_sap = ?', [$idTraceHead, $idSupplier, $batchSap]);
    //             $cntFlagCheckIdSupplier = $flagCheckIdSupplier[0]->cnt;
    //             $idTraceTail = $flagCheckIdSupplier[0]->id_trace_tail;
    //             $idTail = $flagCheckIdSupplier[0]->id_balance_tail;
    //             $inQtyTail = $flagCheckIdSupplier[0]->in_qty;
    //             $outQtyTail = $flagCheckIdSupplier[0]->out_qty;

    //             if ($cntFlagCheckIdSupplier == 0){
    //                 /* INSERT INTO T_BALANCE_DETAIL */
    //                 $balanceDetailData = [
    //                     'id_balance_head' => $idHead,
    //                     'id_supplier'     => $idSupplier,
    //                     'id_material'     => $id_material,
    //                     'id_tank'         => $id_tank,
    //                     'id_tank_tail'    => $id_tank_tail_json,
    //                     'qty'             => $rundownSupplier,
    //                     'in_qty'          => $rundownSupplier,
    //                     'init_qty'        => $rundownSupplier,
    //                     'batch_sap'       => $batchSap,
    //                     'created_by'      => $user,
    //                 ];
    //                 if ($insertPlant !== null) $balanceDetailData['id_plant'] = $insertPlant;

    //                 $idTail = DB::table('t_balance_detail')->insertGetId($balanceDetailData);

    //                 $traceDetailIn = [
    //                     'id_trace_head'   => $idTraceHead,
    //                     'id_balance_tail' => $idTail,
    //                     'id_supplier'     => $idSupplier,
    //                     'id_material'     => $id_material,
    //                     'id_sloc'         => $id_tank,
    //                     'id_tank_tail'    => $id_tank_tail_json,
    //                     'in_qty'          => $rundownSupplier,
    //                     'batch_sap'       => $batchSap,
    //                     'created_by'      => $user,
    //                 ];
    //                 if ($insertPlant !== null) $traceDetailIn['id_plant'] = $insertPlant;

    //                 $idTraceTail = DB::table('t_trace_detail')->insertGetId($traceDetailIn);

    //             } else {
    //                 $newInQtyTail = $inQtyTail + $rundownSupplier;
    //                 $newInQtyTail = round($newInQtyTail, 4); 
    //                 DB::update('UPDATE t_balance_detail
    //                            SET qty = ?,
    //                                in_qty = ?,
    //                                init_qty = ?,
    //                                updated_by = ?
    //                          WHERE id_balance_tail = ?', [$newInQtyTail, $newInQtyTail, $newInQtyTail, $user, $idTail]);
    //                 DB::update('UPDATE t_trace_detail
    //                            SET in_qty = ?,
    //                                updated_by = ?
    //                          WHERE id_trace_tail = ?', [$newInQtyTail, $user, $idTraceTail]);
    //             }

    //             /* DETAIL LOGGING */
    //             DB::insert('INSERT INTO log_transactions
    //                            (log_module, log_type, log_description, created_by)
    //                     VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'BLENDING IN', ' IDTAIL: ' . $idTail .
    //                                             ' / SUPPLIER: ' . $idSupplier . ' / MATERIAL: ' . $id_material .
    //                                             ' / QTY: ' . $rundownSupplier .
    //                                             ' / IN_QTY: ' . $rundownSupplier .
    //                                             ' / OUT_QTY: ' . $rundownSupplier .
    //                                             ' / INIT_QTY: ' . $rundownSupplier .
    //                                             ' | Status: 1', $user ]);
    //         }
    //     }

    //     // Fetch all supplier quantities
    //     $details = DB::select('SELECT d.id_balance_tail, d.id_supplier, t.id_trace_tail, d.qty, d.in_qty, d.init_qty
    //                            FROM t_balance_detail d
    //                            LEFT JOIN t_trace_detail t 
    //                            ON t.id_balance_tail = d.id_balance_tail
    //                            AND t.id_trace_head = ?
    //                            AND t.status = 1
    //                            WHERE d.id_balance_head = ?
    //                            ORDER BY d.id_balance_tail ASC', [$idTraceHead, $idHead]);

    //     if (!empty($details)) {
    //         // Convert to a nested array
    //         $dataPerHead = [array_map(function ($d) {
    //             return ['qty' => $d->qty];
    //         }, $details)];

    //         // Adjust supplier quantities proportionally so total matches header
    //         adjustQtyToTotal($dataPerHead, $in_qty);

    //         // Update DB with adjusted values
    //         foreach ($details as $i => $d) {
    //             $newQty = $dataPerHead[0][$i]['qty'];
    //             DB::update('UPDATE t_balance_detail
    //                         SET qty = ?, in_qty = ?, init_qty = ?
    //                         WHERE id_balance_tail = ?', [$newQty, $newQty, $newQty, $d->id_balance_tail]);

    //             if(!empty($d->id_trace_tail)) {
    //                 DB::update('UPDATE t_trace_detail
    //                             SET in_qty = ?
    //                             WHERE id_trace_tail = ?', [$newQty, $d->id_trace_tail]);
    //             }
    //         }
    //     }

    //     /* DESTROY TEMPORARY DATA */
    //     DB::delete('DELETE FROM t_balance_temporary
    //              WHERE entry_no = ?', [$entryNo]);

    //     /* THROW OUTPUT */
    //     $db = [ (object)['response' => 1 ]];
    //     return $db;
    // }
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
        $id_tank_tail = $request->input('tankNo');
        $id_tank_tail_json = json_encode($id_tank_tail);

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
        $datMaterial = DB::select('SELECT id_material, qty, id_tank
                                 FROM t_balance_temporary
                                WHERE entry_no = ?', [$entryNo]);
        $lenDatMatl = count($datMaterial);

        $feed_entry_no = substr_replace($entryNo, '0', 8, 1); /* REPLACE RUNDOWN_ID TO FEED_ID */
        $curr_entryDate = $entryDate;
        $last_qtf = 0;

        foreach ($datMaterial as $row) {
            $qtySource = floatval(str_replace(',', '', $row->qty));
            if ($qtySource <= 0) continue;
      
            $feedResult = Feed::generalFeed([
                'user'         => $user,
                'entry_date'   => $entryDate,
                'id_material'  => $row->id_material,
                'id_tank'      => $row->id_tank,
                'id_tank_tail' => $id_tank_tail_json,
                'id_plant'     => $idPlant,
                'qty'          => $qtySource,
                'allow_partial' => true,
                'trace_prefixes' => [1,2,7,8,9],
                'to_trace_no'  => $feed_entry_no,
            ]);
      
            if ($feedResult['response'] != 1) {
                return [ (object)['response' => $feedResult['response']] ];
            }
        }
        
        $batch_seq = substr($feed_entry_no, 12, 2);
        $feed_id   = substr($feed_entry_no, 7, 3);

        $checkTrace = DB::select('SELECT to_trace_no, id_trace_head, SUM(out_qty) AS out_qty, id_material
                                  FROM t_trace_header
                                 WHERE SUBSTRING(to_trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
                                   AND SUBSTRING(to_trace_no,1,1) = 8
                                   AND SUBSTRING(to_trace_no,8,3) = ?
                                   AND SUBSTRING(to_trace_no,13,2) = ?
                                   AND `status` = 1
                                   AND out_qty > "0.0001"
                                   AND (id_plant = ? OR ? = 0)
                                 ORDER BY id_trace_head DESC 
                                 LIMIT 1', [$feed_id, $batch_seq, $idPlant, $idPlant]);

        if (!isset($checkTrace[0]->id_trace_head)) {
            $datTraceHead = [];
        } else {
            $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, out_qty, id_material
                                      FROM t_trace_header
                                    WHERE SUBSTRING(to_trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
                                       AND SUBSTRING(to_trace_no,1,1) = 8
                                       AND SUBSTRING(to_trace_no,8,3) = ?
                                       AND SUBSTRING(to_trace_no,13,2) = ?
                                       AND `status` = 1
                                       AND out_qty > "0.0001"
                                       AND (id_plant = ? OR ? = 0)
                                    ORDER BY id_trace_head DESC',
                                   [$feed_id, $batch_seq, $idPlant, $idPlant]);
        }

        $totalFeedQty = array_sum(array_column($datTraceHead, 'out_qty'));
        $in_qty = round($totalFeedQty, 4);

        /* USE RUNDOWN ROUTING TO TAKE IN BLENDING MATERIAL */
        /* VARIABLE ADJUSTMENT */
        $id_material = $idMaterial;
        $process_yield = 1;

        $supplierRows = [];

        foreach ($datTraceHead as $th) {
            $tails = DB::select('SELECT id_supplier, batch_sap, out_qty
                                FROM t_trace_detail
                              WHERE id_trace_head = ?
                                AND status = 1', [$th->id_trace_head]);

            if (empty($tails)) {
                continue;
            }

            foreach ($tails as $t) {
                $key = $t->id_supplier.'|'.$t->batch_sap;
                if (!isset($supplierRows[$key])) {
                    $supplierRows[$key] = [
                        'id_supplier' => $t->id_supplier,
                        'batch_sap'   => $t->batch_sap,
                        'rundownSupplier' => 0
                    ];
                }
                $supplierRows[$key]['rundownSupplier'] += round($t->out_qty, 4);
            }
        }

        if (empty($supplierRows)) {
            return [ (object)['response' => 6] ];
        }

        $supplierRows = array_values($supplierRows);
        Rundown::adjustRundownToTotal($supplierRows, $in_qty);

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

        $from_trace_no = $datTraceHead[0]->to_trace_no;

        $rundownResult = Rundown::generalRundown([
            'user'          => $user,
            'entry_date'    => $entryDate,
            'trace_no'      => $entryNo,
            'from_trace_no' => $from_trace_no,
            'id_material'   => $idMaterial,
            'id_tank'       => $id_tank,
            'id_tank_tail'  => $id_tank_tail_json,
            'id_plant'      => $idPlant,
            'in_qty'        => $in_qty,
            'last_qtf'      => 0,
            'curr_qtf'      => $totalQty,
            'supplier_rows' => $supplierRows,
        ]);
      
        if ($rundownResult['response'] != 1) {
            return [ (object)['response' => 3] ];
        }

        DB::insert('INSERT INTO t_material_document
                       (id_trace_head, material_document, created_by)
                 VALUES (?, ?, ?)', [$rundownResult['id_trace_head'], $materialDoc, $user]);

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
    static function post_updateEntrySubTank($user, $request){
      $idHead   = $request->input('idHead');
      $tails  = $request->input('idTankTail');
      $idPlant = \App\Models\BaseModel::resolvePlant($request);
  
      if (!is_array($tails)) {
          return [(object)['response' => 0, 'message' => 'INVALID SUBTANK DATA']];
      }
  
      $jsonTails = json_encode(array_values(array_unique($tails)));
  
      // Fetch existing header
      $row = DB::selectOne('SELECT id_tank_tail, trace_no 
                            FROM t_balance_header 
                            WHERE id_balance_head = ? AND status = 1', [$idHead]);
  
      // Update header
      DB::update('UPDATE t_balance_header
                  SET id_tank_tail = ?, updated_by = ?
                  WHERE id_balance_head = ?',
                  [$jsonTails, $user, $idHead]);
  
      // Update trace header
      DB::update('UPDATE t_trace_header
                  SET id_tank_tail = ?, updated_by = ?
                  WHERE id_balance_head = ?',
                  [$jsonTails, $user, $idHead]);
  
      // Update ALL balance details
      DB::update('UPDATE t_balance_detail
                  SET id_tank_tail = ?, updated_by = ?
                  WHERE id_balance_head = ?',
                  [$jsonTails, $user, $idHead]);
  
      // Update ALL trace details
      DB::update('UPDATE t_trace_detail
                  SET id_tank_tail = ?, updated_by = ?
                  WHERE id_trace_head IN (
                      SELECT id_trace_head 
                      FROM t_trace_header 
                      WHERE id_balance_head = ?
                  )', [$jsonTails, $user, $idHead]);
  
      // Log change
      DB::insert(
          'INSERT INTO log_transactions
              (log_module, log_type, log_description, created_by)
          VALUES (?, ?, ?, ?)',
          [
              'T_BALANCE_HEAD', 'UPDATE_SUBTANK',
              'IDHEAD: '.$idHead.' | TRACE: '.$row->trace_no.
              ' | SUBTANKS: '.implode(',', $tails),
              $user
          ]
      );
  
      return [(object)['response' => 1]];
  }

}  

function adjustQtyToTotal(&$dataPerHead, $targetTotal) {
  // Step 1: Calculate the initial total
  $total = '0';
  foreach ($dataPerHead as $head) {
      foreach ($head as $item) {
          $total = bcadd($total, (string)$item['qty'], 10);
      }
  }

  if (bccomp($total, '0', 10) == 0) {
      return; // No need to adjust if the total is 0
  }

  // Step 2: Calculate factor
  $factor = bcdiv((string)$targetTotal, $total, 10);

  // Step 3: Multiply everything and save the delta
  $newTotal = '0';
  $lastHeadKey = array_key_last($dataPerHead);
  $lastItemKey = array_key_last($dataPerHead[$lastHeadKey]);

  foreach ($dataPerHead as $headKey => &$headItems) {
      foreach ($headItems as $itemKey => &$item) {
          $item['qty'] = round(bcmul((string)$item['qty'], $factor, 10), 4);
          $newTotal = bcadd($newTotal, (string)$item['qty'], 10);
      }
  }

  // Step 4: Adjust the difference to the last item
  $delta = round((float)bcsub((string)$targetTotal, $newTotal, 10), 4);
  $dataPerHead[$lastHeadKey][$lastItemKey]['qty'] += $delta;
}
