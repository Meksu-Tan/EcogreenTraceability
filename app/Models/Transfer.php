<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Helpers\Feed;
use App\Helpers\Rundown;

class Transfer extends Model
{
    protected $connection = 'eudr_ts';

    // protected static $idPlantEob1 = "1002";


    static function get_cmbActiveMaterial(){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.id_material, CONCAT( UPPER(a.description), " (", a.code, " - ", a.type, ")" ) AS material
                            FROM m_material a
                           WHERE a.status = 1
                             AND a.id_rundown <> "-"
                           GROUP BY a.code
                           ORDER BY a.description ASC');
        return $db;
    }
    static function get_newTransferEntryNo($request){
        $idMaterial = $request->input('id_material');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $getLatestTransfer = DB::select('SELECT LPAD(a.seq_no + 1,2,0) AS seq_no
                                           FROM (SELECT SUBSTRING(a.to_trace_no, 13,2) AS seq_no
                                                   FROM t_trace_header a
                                                  WHERE SUBSTRING(a.to_trace_no, 1, 1) = 7
                                                    AND SUBSTRING(a.to_trace_no, 2, 6) = DATE_FORMAT(CURDATE(), "%y%m%d")
                                                    AND a.status = 1 AND a.id_plant = ?
                                                  ORDER BY SUBSTRING(a.to_trace_no,13,2) DESC
                                                  LIMIT 1) a
                                          UNION ALL
                                         SELECT "01" AS seq_no
                                          LIMIT 1', [$idPlant]);
        $seq_no = $getLatestTransfer[0]->seq_no;

        $db = DB::select('SELECT CONCAT("7", DATE_FORMAT(CURDATE(), "%y%m%d"), IF(a.id_rundown <> "-", a.id_rundown, "000"), LPAD(RIGHT(?, 2), 2, "0"), ?) AS entryNo
                            FROM m_material a
                           WHERE a.status = 1
                             AND a.id_material = ?
                           LIMIT 1
                         ', [$idPlant, $seq_no, $idMaterial]);

        return $db;
    }
    // Get Main Tanks
    static function get_cmbActiveTank_rundown($request){
        $idMaterial = $request->input('idMaterial');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        if ($idMaterial == null){
            $db = DB::select('SELECT b.id_tank, b.description AS tank
                                FROM m_tank b
                               WHERE b.status = 1
                                 AND b.id_plant <> ?
                               GROUP BY b.id_tank
                               ORDER BY b.description ASC', [$idPlant]);
        } else {
            $db = DB::select('SELECT b.id_tank, b.description AS tank
                                FROM m_material a
                                LEFT JOIN m_tank b
                                  ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
                               WHERE a.status = 1
                                 AND a.id_material = ?
                               GROUP BY b.id_tank', [$idPlant, $idMaterial]);
        }
        return $db;
    }
    // Get Sub Tanks
    static function get_cmbActiveSpecificTank_rundown($request){
        $sloc = $request->input('sloc');

        $db = DB::select('SELECT a.id_tank_tail, a.tf_number AS tankNo
                            FROM m_tank_detail a
                          WHERE a.status = 1
                            AND a.id_tank = ?
                          ORDER BY a.tf_number ASC', [$sloc]);

        return $db;
    }
    static function get_totalStockMaterial($request){
        $idMaterial = $request->input('idMaterial');
        $idTank = $request->input('idTank');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $db = DB::select('SELECT SUM(c.qty) AS total
                            FROM m_material a
                            LEFT JOIN (SELECT b.code, b.id_material
                                         FROM m_material b
                                        WHERE b.status = 1) b
                              ON a.code = b.code
                            LEFT JOIN (SELECT c.id_material, c.qty
                                         FROM t_balance_header c
                                        WHERE c.status = 1
                                          AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR SUBSTRING(c.trace_no,1,1) = 7 OR
                                               SUBSTRING(c.trace_no,1,1) = 8 OR SUBSTRING(c.trace_no,1,1) = 9)
                                          AND c.id_tank = ?
                                        ) c
                              ON b.id_material = c.id_material
                           WHERE a.status = 1
                             AND a.id_material = ?
                         ', [$idTank, $idMaterial]);

        return $db;
    }
    static function get_dtTransferList($request){
        $idPlant = \App\Models\BaseModel::resolvePlant($request);
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.entry_date, b.material_document, a.id_tank AS to_id_tank, a.id_tank_tail, t_to.description AS to_sloc_name, GROUP_CONCAT(DISTINCT h_to.tf_number ORDER BY h_to.tf_number) AS to_tf_number,
                                 th_from.id_balance_head AS fromIdHead, th_from.id_sloc AS from_id_tank, t_from.description AS from_sloc_name, GROUP_CONCAT(DISTINCT h_from.tf_number ORDER BY h_from.tf_number) AS from_tf_number,
                                 GROUP_CONCAT(DISTINCT h_from.id_tank_tail ORDER BY h_from.id_tank_tail) AS from_id_tank_tail, GROUP_CONCAT(DISTINCT h_to.id_tank_tail ORDER BY h_to.id_tank_tail) AS to_id_tank_tail,
                                 CAST(a.trace_no AS CHAR) AS trace_no, FORMAT(ROUND(a.qty,3),3) AS qty, FORMAT(ROUND(a.init_qty,3),3) AS init_qty, a.entry_date, a.id_balance_head AS idHead,
                                 CONCAT(c.`description`, " (", c.`code`, ")") AS material, FORMAT(ROUND(a.in_qty,3),3) AS in_qty, FORMAT(ROUND(a.out_qty,3),3) AS out_qty,
                                 GROUP_CONCAT(DISTINCT CONCAT(f.`description`, " / ", e.batch_sap, " / Qty : ", ROUND(e.init_qty,3), " MT / Qty : ", ROUND(e.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                 b.id_trace_head AS idTraceHead, b.id_trace_head, b.is_last_row, b.next_process,
                                 CONCAT(COALESCE(b.from_sloc, ""),
                                   IF(
                                      GROUP_CONCAT(DISTINCT h_from.tf_number ORDER BY h_from.tf_number SEPARATOR ", ") IS NULL,
                                      "",
                                      CONCAT(" - [", GROUP_CONCAT(DISTINCT h_from.tf_number ORDER BY h_from.tf_number SEPARATOR ", "), "]")
                                   ),
                                  " >>> ",
                                  COALESCE(t_to.description, ""),
                                  IF(
                                      GROUP_CONCAT(DISTINCT h_to.tf_number ORDER BY h_to.tf_number SEPARATOR ", ") IS NULL,
                                      "",
                                      CONCAT(" - [", GROUP_CONCAT(DISTINCT h_to.tf_number ORDER BY h_to.tf_number SEPARATOR ", "), "]")
                                  )
                                 ) AS sloc,
                                 IF(ABS(bs.init_qty - a.init_qty) > 0.005, FORMAT(bs.init_qty,3), FORMAT(a.init_qty,3)) as balance_supplier,
                                 IF(ABS(bs.qty - a.qty) > 0.005, FORMAT(bs.qty,3), FORMAT(a.qty,3)) as qty_supplier
                            FROM t_balance_header a
                            LEFT JOIN (SELECT b.id_balance_head, b.id_trace_head, b.from_trace_no,
                                              c.from_sloc, d.material_document,
                                              CASE
                                                WHEN b.to_trace_no = (SELECT to_trace_no
                                                                        FROM t_trace_header
                                                                       WHERE SUBSTRING(to_trace_no, 1, 1) = 7
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
                                         LEFT JOIN (SELECT c.to_trace_no, c.id_tank_tail, d.description AS from_sloc
                                                      FROM t_trace_header c
                                                      LEFT JOIN t_balance_header cc
                                                        ON c.id_balance_head = cc.id_balance_head
                                                      LEFT JOIN m_tank d
                                                        ON cc.id_tank = d.id_tank
                                                     WHERE c.`status` = 1
                                                       AND SUBSTRING(c.to_trace_no,1,1) = 7
                                                     GROUP BY c.to_trace_no ) c
                                           ON b.from_trace_no = c.to_trace_no
                                         LEFT JOIN t_material_document d
                                           ON d.id_trace_head = b.id_trace_head
                                        WHERE b.`status` = 1
                                          AND SUBSTRING(b.to_trace_no,1,1) = 7
                                          AND SUBSTRING(b.from_trace_no,1,1) = 7
                                        GROUP BY b.id_balance_head) b
                              ON a.id_balance_head = b.id_balance_head
                            LEFT JOIN m_material c
                              ON c.id_material = a.id_material
                            LEFT JOIN t_trace_header th_from
                              ON th_from.to_trace_no = b.from_trace_no
                            LEFT JOIN m_tank t_to
                              ON t_to.id_tank = a.id_tank
                            LEFT JOIN m_tank t_from
                              ON t_from.id_tank = th_from.id_sloc
                            LEFT JOIN (SELECT e.id_balance_head, e.id_supplier, ROUND(SUM(e.init_qty),3) AS init_qty, ROUND(SUM(e.qty),3) AS qty, e.batch_sap
                                         FROM t_balance_detail e
                                        WHERE e.`status` = 1
                                          AND e.init_qty > "0.0001"
                                        GROUP BY e.id_balance_head, e.batch_sap) e
                              ON a.id_balance_head = e.id_balance_head
                            LEFT JOIN (SELECT h.trace_no, ROUND(SUM(d.init_qty),3) AS init_qty, ROUND(SUM(d.qty),3) AS qty
                                        FROM t_balance_header h
                                        JOIN t_balance_detail d
                                            ON d.id_balance_head = h.id_balance_head
                                        WHERE d.status = 1
                                            AND d.init_qty > 0.0001
                                        GROUP BY h.trace_no
                            ) bs ON bs.trace_no = a.trace_no
                            LEFT JOIN m_supplier f
                              ON e.id_supplier = f.id_supplier
                            LEFT JOIN m_tank_detail h_to
                              ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(h_to.id_tank_tail AS CHAR)))
                            LEFT JOIN m_tank_detail h_from
                              ON JSON_CONTAINS(th_from.id_tank_tail, JSON_QUOTE(CAST(h_from.id_tank_tail AS CHAR)))
                           WHERE a.`status` = 1
                             AND SUBSTRING(a.trace_no,1,1) = 7
                             AND (a.id_plant = ? OR ? = 0)
                           GROUP BY a.trace_no
                           ORDER BY b.id_trace_head DESC', [$idPlant, $idPlant]);

        return $db;
    }
    static function get_lockStatus($entryDate){
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
            } else {
                $db = [ (object)['response' => 0 ]];
            }
            return $db;
    }
    static function get_updateSupplierMaterial($request){
        $idMaterial = $request->input('idMaterial');
        $idTank = $request->input('idTank');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $datSeq = DB::select('SELECT a.seq_no
                                FROM ( SELECT LPAD(SUBSTRING(a.batch_sap,7,2) + 1, 2,0) AS seq_no
                                         FROM t_balance_detail a
                                         LEFT JOIN t_balance_header b
                                           ON a.id_balance_head = b.id_balance_head
                                        WHERE a.status = 1
                                          AND SUBSTRING(a.batch_sap,1,6) = DATE_FORMAT(NOW(), "%y%m%d")
                                          AND SUBSTRING(b.trace_no,1,1) = 7
                                        ORDER BY SUBSTRING(a.batch_sap,1,8) DESC
                                        LIMIT 1) a
                                UNION ALL
                               SELECT "01" AS seq_no
                                LIMIT 1
                              ');
        $seqNo = $datSeq[0]->seq_no;

        $db = DB::select('SELECT CONCAT(DATE_FORMAT(NOW(), "%y%m%d"), ?, b.code_4, UCASE(a.code_matl_supplier)) AS supplierCode,
                                 c.id_supplier AS idSupplier
                            FROM (SELECT a.code_matl_supplier
                                    FROM m_material a
                                   WHERE a.status = 1
                                     AND a.id_material = ?) a,
                                 (SELECT b.code_4
                                    FROM m_tank b
                                   WHERE b.status = 1
                                     AND b.id_tank = ?) b,
                                 (SELECT c.id_supplier
                                    FROM m_supplier c
                                   WHERE c.status = 1
                                     AND c.type = ?
                                   UNION ALL
                                  SELECT 0 AS id_supplier
                                   LIMIT 1) c',
                             [$seqNo, $idMaterial, $idTank, $idTank]);

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
    // static function post_transferEntry($user, $entryNo, $entryDate, $idMaterial, $materialDoc, $trfQty, $trfSource, $trfDestination, $trfSourceTail, $trfDestinationTail){
    //     DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

    //     $srcPlant = DB::table('m_tank')->where('id_tank', $trfSource)->value('id_plant');
    //     $destPlant = DB::table('m_tank')->where('id_tank', $trfDestination)->value('id_plant');

    //     $srcTailJson = json_encode($trfSourceTail);
    //     $destTailJson = json_encode($trfDestinationTail);

    //     /* CHECKING TOTAL STOCK */
    //         $datHead = DB::select('SELECT IFNULL(SUM(c.qty),0) AS qty
    //                                  FROM m_material a
    //                                  LEFT JOIN (SELECT b.code, b.id_material
    //                                               FROM m_material b) b
    //                                    ON a.code = b.code
    //                                  LEFT JOIN t_balance_header c
    //                                    ON b.id_material = c.id_material AND c.status = 1 AND c.id_tank = ?
    //                                 WHERE a.id_material = ?
    //                                   AND a.`status` = 1
    //                                   AND c.qty > "0.0001"
    //                                   AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR
    //                                        SUBSTRING(c.trace_no,1,1) = 7 OR SUBSTRING(c.trace_no,1,1) = 8 OR
    //                                        SUBSTRING(c.trace_no,1,1) = 9)
    //                                ', [$trfSource, $idMaterial]);

    //         $total_reserve = $datHead[0]->qty;
    //         if (round($total_reserve - $trfQty, 4) < 0){
    //             $db = [ (object)['response' => 4 ]];
    //             return $db;
    //         }
    //     /* CHECKING TRACE NUMBER */
    //         do {
    //             $datTrace = DB::select('SELECT COUNT(to_trace_no) AS double_trace
    //                                       FROM t_trace_header
    //                                      WHERE `status` = 1
    //                                        AND to_trace_no = ?', [$entryNo]);
    //             $flag = $datTrace[0]->double_trace;
    //             if ($flag > 0) {
    //                 $entryNo++;
    //             }
    //         } while ($flag > 0);

    //     /* USE FEED ROUTING TO TAKE OUT MATERIAL FOR TRANSFER */
    //                 $datHead = DB::select('SELECT c.id_balance_head, c.qty, c.in_qty, c.out_qty, c.init_qty, c.trace_no, b.id_material, c.id_tank
    //                                          FROM m_material a
    //                                          LEFT JOIN (SELECT b.code, b.id_material
    //                                                       FROM m_material b) b
    //                                            ON a.code = b.code
    //                                          LEFT JOIN t_balance_header c
    //                                            ON b.id_material = c.id_material AND c.`status` = 1 AND c.id_tank = ?
    //                                         WHERE a.id_material = ?
    //                                           AND a.`status` = 1
    //                                           AND c.qty > "0.0001"
    //                                           AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR
    //                                                SUBSTRING(c.trace_no,1,1) = 7 OR SUBSTRING(c.trace_no,1,1) = 8 OR
    //                                                SUBSTRING(c.trace_no,1,1) = 9)
    //                                         ORDER BY c.id_balance_head ASC', [$trfSource, $idMaterial]);
    //                 $lenDatHead = count($datHead);
    //                 if ($lenDatHead == 0){
    //                     $db = [ (object)['response' => 3 ]];
    //                     return $db;
    //                 }

    //                 /* VARIABLE ADJUSTMENT */
    //                 $out_qty = $trfQty;
    //                 if (substr($entryNo,7,3) == '000'){
    //                     $entry_no = substr_replace($entryNo, '1', 9, 1); /* REPLACE RUNDOWN_ID TO FEED_ID FOR RAW MATERIAL */
    //                 } else {
    //                     $entry_no = substr_replace($entryNo, '0', 8, 1); /* REPLACE RUNDOWN_ID TO FEED_ID FOR WIP*/
    //                 }
    //                 $curr_entryDate = $entryDate;
    //                 $curr_qtf = $trfQty;
    //                 $last_qtf = 0;

    //                 /* FEEDING ALGORITHM */
    //                 for ($i = 0; $i < $lenDatHead; $i++) {
    //                     $idHead = $datHead[$i]->id_balance_head;
    //                     $qty = $datHead[$i]->qty;
    //                     $total_in_qty = $datHead[$i]->in_qty;
    //                     $total_out_qty = $datHead[$i]->out_qty;
    //                     $init_qty = $datHead[$i]->init_qty;
    //                     $from_trace_no = $datHead[$i]->trace_no;
    //                     $id_material = $datHead[$i]->id_material;
    //                     $id_tank = $datHead[$i]->id_tank;

    //                     $new_total_in_qty = $total_in_qty;
    //                     $new_total_out_qty = $total_out_qty + $out_qty;

    //                     $tail_out_qty = $out_qty;

    //                     $balanceAfter = $qty - $out_qty;

    //                     if ($balanceAfter < 0){
    //                         if ($lenDatHead == 1){
    //                             $db = [ (object)['response' => 3 ]];
    //                             return $db;
    //                         }
    //                         $new_balance = 0;
    //                         $new_total_out_qty = $init_qty;
    //                         $temp_out_qty = $out_qty - $qty;
    //                         $out_qty = $qty;
    //                     } else {
    //                         $new_balance = $qty - $out_qty;
    //                     }

    //                     /* GET ID_BALANCE_DETAIL 2025-01-03 */
    //                         $datTail = DB::select('SELECT a.id_balance_tail, a.id_supplier, a.qty, a.in_qty, a.out_qty, a.init_qty, a.batch_sap
    //                                                  FROM t_balance_detail a
    //                                                  JOIN m_supplier b ON a.id_supplier = b.id_supplier
    //                                                 WHERE id_balance_head = ?
    //                                                   AND a.`status` = 1
    //                                                   AND qty > "0.0001"
    //                                                   AND b.`status` = 1
    //                                                 ORDER BY a.id_balance_tail ASC', [$idHead]);
    //                         $lenTail = count($datTail);

    //                         if ($lenTail == 0){
    //                             $db = [ (object)['response' => 3 ]];
    //                             return $db;
    //                         }

    //                     /* UPDATE INTO T_BALANCE_HEADER */
    //                         DB::update('UPDATE t_balance_header
    //                                        SET qty = ?,
    //                                            in_qty = ?,
    //                                            out_qty = ?,
    //                                            updated_by = ?
    //                                      WHERE id_balance_head = ?',
    //                                      [$new_balance, $new_total_in_qty, $new_total_out_qty, $user, $idHead]);

    //                     /* INSERT INTO T_TRACE_HEADER */
    //                         $idTraceHead = DB::table('t_trace_header')->insertGetId([
    //                                 'from_trace_no' => $from_trace_no,
    //                                 'to_trace_no' => $entry_no,
    //                                 'id_balance_head' => $idHead,
    //                                 'id_material' => $id_material,
    //                                 'entry_date' => $curr_entryDate,
    //                                 'id_sloc' => $id_tank,
    //                                 'id_tank_tail' => $srcTailJson,
    //                                 'out_qty' => $out_qty,
    //                                 'last_qtf' => $last_qtf,
    //                                 'curr_qtf' => $curr_qtf,
    //                                 'created_by' => $user,
    //                                 'id_plant' => $srcPlant,
    //                         ]);

    //                     /* HEADER LOGGING */
    //                         DB::insert('INSERT INTO log_transactions
    //                                         (log_module, log_type, log_description, created_by)
    //                                     VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'TRF OUT', 'IDHEAD: ' . $idHead . ' | DATE: ' . $curr_entryDate .
    //                                                             ' / TANK: ' . $id_tank . ' / MATERIAL: ' . $id_material . ' / QTY: ' . $qty . ' >>> ' . $new_balance .
    //                                                             ' / IN_QTY: ' . $total_in_qty . ' >>> ' . $new_total_in_qty .
    //                                                             ' / OUT_QTY: ' . $total_out_qty . ' >>> ' . $new_total_out_qty .
    //                                                             ' | Status: 1', $user ]);

    //                     /* ROUTING FOR DETAIL PER SUPPLIER */
    //                             for ($k = 0; $k < $lenTail; $k++) {
    //                                 $idTail = $datTail[$k]->id_balance_tail;
    //                                 $idSupplier = $datTail[$k]->id_supplier;
    //                                 $tail_qty = $datTail[$k]->qty;
    //                                 $tail_total_in_qty = $datTail[$k]->in_qty;
    //                                 $tail_total_out_qty = $datTail[$k]->out_qty;
    //                                 $tail_init_qty = $datTail[$k]->init_qty;
    //                                 $batch_sap = $datTail[$k]->batch_sap;

    //                                 $new_tail_total_in_qty = $tail_total_in_qty;
    //                                 $new_tail_total_out_qty = $tail_total_out_qty + $tail_out_qty;

    //                                 $tailBalanceAfter = $tail_qty - $tail_out_qty;
    //                                 if ($tailBalanceAfter < 0){
    //                                     $new_tail_balance = 0;
    //                                     $new_tail_total_out_qty = $tail_init_qty;
    //                                     $temp_tail_out_qty = $tail_out_qty - $tail_qty;
    //                                     $tail_out_qty = $tail_qty;
    //                                 } else {
    //                                     $new_tail_balance = $tail_qty - $tail_out_qty;
    //                                 }

    //                                 $tail_out_qty = round($tail_out_qty, 4);
    //                                 $tail_total_in_qty = round($tail_total_in_qty, 4);
    //                                 $tail_total_out_qty = round($tail_total_out_qty, 4);
    //                                 $tail_qty = round($tail_qty, 4);
    //                                 $new_tail_balance = round($new_tail_balance, 4);
    //                                 $new_tail_total_in_qty = round($new_tail_total_in_qty, 4);
    //                                 $new_tail_total_out_qty = round($new_tail_total_out_qty, 4);

    //                                 /* POPULATE NEW BALANCE DETAIL */
    //                                     DB::update('UPDATE t_balance_detail
    //                                                    SET qty = ?,
    //                                                        in_qty = ?,
    //                                                        out_qty = ?,
    //                                                        updated_by = ?
    //                                                  WHERE id_balance_tail = ?',
    //                                                  [$new_tail_balance, $new_tail_total_in_qty, $new_tail_total_out_qty, $user, $idTail]);

    //                                 /* POPULATE TRACE DETAIL */
    //                                     $idTraceTail = DB::table('t_trace_detail')->insertGetId([
    //                                                             'id_trace_head' => $idTraceHead,
    //                                                             'id_balance_tail' => $idTail,
    //                                                             'id_supplier' => $idSupplier,
    //                                                             'id_material' => $id_material,
    //                                                             'id_sloc'   => $id_tank,
    //                                                             'id_tank_tail' => $srcTailJson,
    //                                                             'out_qty' => $tail_out_qty,
    //                                                             'batch_sap' => $batch_sap,
    //                                                             'created_by' => $user,
    //                                                             'id_plant' => $srcPlant,
    //                                                     ]);

    //                                 /* DETAIL LOGGING */
    //                                     DB::insert('INSERT INTO log_transactions
    //                                                     (log_module, log_type, log_description, created_by)
    //                                                 VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'TRF OUT', ' IDTAIL: ' . $idTail .
    //                                                                         ' / SUPPLIER: ' . $idSupplier . ' / MATERIAL: ' . $id_material .
    //                                                                         ' / QTY: ' . $tail_qty . ' >>> ' . $new_tail_balance .
    //                                                                         ' / IN_QTY: ' . $tail_total_in_qty . ' >>> ' . $new_tail_total_in_qty .
    //                                                                         ' / OUT_QTY: ' . $tail_total_out_qty . ' >>> ' . $new_tail_total_out_qty .
    //                                                                         ' | Status: 1', $user ]);

    //                                 /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
    //                                     if ($tailBalanceAfter >= 0){
    //                                         break;
    //                                     }
    //                                 /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
    //                                     $tail_out_qty = $temp_tail_out_qty;

    //                             }

    //                     /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
    //                         if ($balanceAfter >= 0){
    //                             $db = [ (object)['response' => 1 ]];
    //                             break;
    //                         }

    //                     /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
    //                         $out_qty = $temp_out_qty;

    //                 }

    //             /* USE RUNDOWN ROUTING TO TAKE IN TRANSFER MATERIAL */
    //                 /* VARIABLE ADJUSTMENT */
    //                     $id_material = $idMaterial;
    //                     $process_yield = 1;
    //                     $feed_entryNo = $entry_no;
    //                     $entry_no = $entryNo;
    //                     $curr_qtf = $trfQty;

    //                 /* GET FEED TRACE RELATED TO RUNDOWN */
    //                     $batch_seq = substr($feed_entryNo, 12, 2);
    //                     $feed_id = substr($feed_entryNo, 7, 3);
    //                     $batch_date = substr($feed_entryNo, 1, 6);

    //                     $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, SUM(out_qty) AS out_qty, id_material
    //                                                   FROM t_trace_header
    //                                                  WHERE SUBSTRING(to_trace_no,2,6) = ?
    //                                                    AND SUBSTRING(to_trace_no,1,1) = 7
    //                                                    AND SUBSTRING(to_trace_no,8,3) = ?
    //                                                    AND SUBSTRING(to_trace_no,13,2) = ?
    //                                                    AND `status` = 1
    //                                                    AND out_qty > "0.0001"
    //                                                  ORDER BY id_trace_head DESC
    //                                                  LIMIT 1', [$batch_date, $feed_id, $batch_seq]);

    //                     $feed_idTraceHead = $datTraceHead[0]->id_trace_head;
    //                     $from_trace_no = $datTraceHead[0]->to_trace_no;
    //                     $feed_qty = $datTraceHead[0]->out_qty;

    //                     $in_qty = $process_yield * $feed_qty;

    //                 /* ASSIGN ID_TANK BASED ON TRANSFER DESTINATION */
    //                     $id_tank = $trfDestination;

    //                 /* TRACE DETAIL 2025-01-03 */
    //                     $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, out_qty, id_material
    //                                                   FROM t_trace_header
    //                                                  WHERE SUBSTRING(to_trace_no,2,6) = ?
    //                                                    AND SUBSTRING(to_trace_no,1,1) = 7
    //                                                    AND SUBSTRING(to_trace_no,8,3) = ?
    //                                                    AND SUBSTRING(to_trace_no,13,2) = ?
    //                                                    AND `status` = 1
    //                                                    AND out_qty > "0.0001"
    //                                                  ORDER BY id_trace_head DESC',
    //                                                 [$batch_date, $feed_id, $batch_seq]);
    //                     $len = count($datTraceHead);

    //                     if ($len == 0){
    //                         $db = [ (object)['response' => 3 ]];
    //                         return $db;
    //                     }

    //                 /* INSERT INTO T_BALANCE_HEADER */
    //                     $idHead = DB::table('t_balance_header')->insertGetId([
    //                         'entry_date' => $curr_entryDate,
    //                         'trace_no' => $entry_no,
    //                         'id_material' => $id_material,
    //                         'id_tank' => $id_tank,
    //                         'id_tank_tail' => $destTailJson,
    //                         'qty' => $in_qty,
    //                         'in_qty' => $in_qty,
    //                         'init_qty' => $in_qty,
    //                         'created_by' => $user,
    //                         'id_plant' => $destPlant,
    //                     ]);
    //                 /* INSERT INTO T_TRACE_HEADER */
    //                     $idTraceHead = DB::table('t_trace_header')->insertGetId([
    //                         'from_trace_no' => $from_trace_no,
    //                         'to_trace_no' => $entry_no,
    //                         'id_balance_head' => $idHead,
    //                         'id_material' => $id_material,
    //                         'entry_date' => $curr_entryDate,
    //                         'id_sloc' => $id_tank,
    //                         'id_tank_tail' => $destTailJson,
    //                         'in_qty' => $in_qty,
    //                         'last_qtf' => $last_qtf,
    //                         'curr_qtf' => $curr_qtf,
    //                         'created_by' => $user,
    //                         'id_plant' => $destPlant,
    //                     ]);

    //                     DB::insert('INSERT INTO t_material_document
    //                                     (id_trace_head, material_document, created_by)
    //                                 VALUES (?, ?, ?)', [$idTraceHead, $materialDoc, $user]);

    //                 /* HEADER LOGGING */
    //                     DB::insert('INSERT INTO log_transactions
    //                                     (log_module, log_type, log_description, created_by)
    //                                 VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'BLENDING IN', 'IDHEAD: ' . $idHead . ' | DATE: ' . $curr_entryDate .
    //                                                         ' / MATERIAL: ' . $id_material . ' / QTY: ' . $in_qty .
    //                                                         ' / IN_QTY: ' . $in_qty .
    //                                                         ' / OUT_QTY: 0' .
    //                                                         ' | Status: 1', $user ]);
    //                 /* TRACE DETAIL */
    //                     for ($i = 0; $i < $len; $i++) {
    //                         $feed_idTraceHead = $datTraceHead[$i]->id_trace_head;
    //                         $from_trace_no = $datTraceHead[$i]->to_trace_no;
    //                         $feed_qty = $datTraceHead[$i]->out_qty;

    //                         /* ROUTING FOR DETAIL PER SUPPLIER */
    //                             /* GET FEED ID_TRACE_DETAIL */
    //                                 $datTraceTail = DB::select('SELECT a.id_trace_tail, a.id_balance_tail, a.id_supplier, a.out_qty, a.batch_sap
    //                                                               FROM t_trace_detail a
    //                                                               JOIN m_supplier b ON a.id_supplier = b.id_supplier
    //                                                              WHERE id_trace_head = ?
    //                                                                AND a.`status` = 1
    //                                                                and b.`status` = 1
    //                                                              ORDER BY a.id_trace_tail ASC', [$feed_idTraceHead]);
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
    //                                                 'id_tank' => $id_tank,
    //                                                 'id_tank_tail' => $destTailJson,
    //                                                 'qty' => $rundownSupplier,
    //                                                 'in_qty' => $rundownSupplier,
    //                                                 'init_qty' => $rundownSupplier,
    //                                                 'batch_sap' => $batchSap,
    //                                                 'created_by' => $user,
    //                                                 'id_plant' => $destPlant,
    //                                             ]);
    //                                             $idTraceTail = DB::table('t_trace_detail')->insertGetId([
    //                                                 'id_trace_head' => $idTraceHead,
    //                                                 'id_balance_tail' => $idTail,
    //                                                 'id_supplier' => $idSupplier,
    //                                                 'id_material' => $id_material,
    //                                                 'id_sloc' => $id_tank,
    //                                                 'id_tank_tail' => $destTailJson,
    //                                                 'in_qty' => $rundownSupplier,
    //                                                 'batch_sap' => $batchSap,
    //                                                 'created_by' => $user,
    //                                                 'id_plant' => $destPlant,
    //                                             ]);

    //                                         } else {
    //                                             $newInQtyTail = $inQtyTail + $rundownSupplier;
    //                                             $newInQtyTail = round($newInQtyTail, 4); 
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
    //                                                         (log_module, log_type, log_description, created_by)
    //                                                     VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'BLENDING IN', ' IDTAIL: ' . $idTail .
    //                                                                             ' / SUPPLIER: ' . $idSupplier . ' / MATERIAL: ' . $id_material .
    //                                                                             ' / QTY: ' . $rundownSupplier .
    //                                                                             ' / IN_QTY: ' . $rundownSupplier .
    //                                                                             ' / OUT_QTY: ' . $rundownSupplier .
    //                                                                             ' / INIT_QTY: ' . $rundownSupplier .
    //                                                                             ' | Status: 1', $user ]);

    //                                 }

    //                     }

    //     /* THROW OUTPUT */
    //     $db = [ (object)['response' => 1 ]];
    //     return $db;

    // }
    static function post_transferEntry($user, $entryNo, $entryDate, $idMaterial, $materialDoc, $trfQty, $trfSource, $trfDestination, $trfSourceTail, $trfDestinationTail){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $srcPlant = DB::table('m_tank')->where('id_tank', $trfSource)->value('id_plant');
        $destPlant = DB::table('m_tank')->where('id_tank', $trfDestination)->value('id_plant');

        $srcTailJson = json_encode($trfSourceTail);
        $destTailJson = json_encode($trfDestinationTail);

        /* CHECKING TOTAL STOCK */
            $datHead = DB::select('SELECT IFNULL(SUM(c.qty),0) AS qty
                                     FROM m_material a
                                     LEFT JOIN (SELECT b.code, b.id_material
                                                  FROM m_material b) b
                                       ON a.code = b.code
                                     LEFT JOIN t_balance_header c
                                       ON b.id_material = c.id_material AND c.status = 1 AND c.id_tank = ?
                                    WHERE a.id_material = ?
                                      AND a.`status` = 1
                                      AND c.qty > "0.0001"
                                      AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR
                                           SUBSTRING(c.trace_no,1,1) = 7 OR SUBSTRING(c.trace_no,1,1) = 8 OR
                                           SUBSTRING(c.trace_no,1,1) = 9)
                                   ', [$trfSource, $idMaterial]);

            $total_reserve = $datHead[0]->qty;
            if (round($total_reserve - $trfQty, 4) < 0){
                $db = [ (object)['response' => 4 ]];
                return $db;
            }
        /* CHECKING TRACE NUMBER */
            do {
                $datTrace = DB::select('SELECT COUNT(to_trace_no) AS double_trace
                                          FROM t_trace_header
                                         WHERE `status` = 1
                                           AND to_trace_no = ?', [$entryNo]);
                $flag = $datTrace[0]->double_trace;
                if ($flag > 0) {
                    $entryNo++;
                }
            } while ($flag > 0);

        /* USE FEED ROUTING TO TAKE OUT MATERIAL FOR TRANSFER */
                    // $datHead = DB::select('SELECT c.id_balance_head, c.qty, c.in_qty, c.out_qty, c.init_qty, c.trace_no, b.id_material, c.id_tank
                    //                          FROM m_material a
                    //                          LEFT JOIN (SELECT b.code, b.id_material
                    //                                       FROM m_material b) b
                    //                            ON a.code = b.code
                    //                          LEFT JOIN t_balance_header c
                    //                            ON b.id_material = c.id_material AND c.`status` = 1 AND c.id_tank = ?
                    //                         WHERE a.id_material = ?
                    //                           AND a.`status` = 1
                    //                           AND c.qty > "0.0001"
                    //                           AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR
                    //                                SUBSTRING(c.trace_no,1,1) = 7 OR SUBSTRING(c.trace_no,1,1) = 8 OR
                    //                                SUBSTRING(c.trace_no,1,1) = 9)
                    //                         ORDER BY c.id_balance_head ASC', [$trfSource, $idMaterial]);
                    // $lenDatHead = count($datHead);
                    // if ($lenDatHead == 0){
                    //     $db = [ (object)['response' => 3 ]];
                    //     return $db;
                    // }

                    /* VARIABLE ADJUSTMENT */
                    if (substr($entryNo,7,3) == '000'){
                        $entry_no = substr_replace($entryNo, '1', 9, 1); /* REPLACE RUNDOWN_ID TO FEED_ID FOR RAW MATERIAL */
                    } else {
                        $entry_no = substr_replace($entryNo, '0', 8, 1); /* REPLACE RUNDOWN_ID TO FEED_ID FOR WIP*/
                    }
                    $curr_entryDate = $entryDate;

                    $feedResult = Feed::generalFeed([
                        'qty'           => $trfQty,
                        'id_material'   => $idMaterial,
                        'id_tank'       => $trfSource,
                        'id_tank_tail'  => $srcTailJson,
                        'id_plant'      => $srcPlant,
                        'to_trace_no'   => $entry_no,
                        'entry_date'    => $entryDate,
                        'allow_partial' => true,
                        'require_supplier' => false,
                        // 'trace_prefixes' => [1,2,7,8,9],
                        'user'          => $user,
                    ]);

                    if ($feedResult['response'] != 1) {
                        return [ (object)['response' => 3] ];
                    }

                    $supplierRows = DB::select('SELECT id_supplier, batch_sap, SUM(out_qty) AS rundownSupplier
                                                FROM t_trace_detail
                                                WHERE status = 1
                                                    AND id_trace_head IN (
                                                        SELECT id_trace_head
                                                        FROM t_trace_header
                                                        WHERE status = 1
                                                            AND to_trace_no = ?
                                                    )
                                                GROUP BY id_supplier, batch_sap', [$entry_no]);
                        
                    $supplierRowsFormatted = array_map(function ($r) {
                        return [
                            'id_supplier'       => $r->id_supplier,
                            'batch_sap'         => $r->batch_sap,
                            'rundownSupplier'   => $r->rundownSupplier,
                        ];
                    }, $supplierRows);

                    $datTraceHead = DB::select('SELECT SUM(out_qty) AS out_qty
                                                FROM t_trace_header
                                                WHERE status = 1
                                                    AND to_trace_no = ?', [$entry_no]);
                        
                    $actualQty = round($datTraceHead[0]->out_qty ?? 0, 4);

                    $rundownResult = Rundown::generalRundown([
                        'user'          => $user,
                        'entry_date'    => $entryDate,
                        'trace_no'      => $entryNo,
                        'from_trace_no' => $entry_no,
                        'id_material'   => $idMaterial,
                        'id_tank'       => $trfDestination,
                        'id_tank_tail'  => $destTailJson,
                        'id_plant'      => $destPlant,
                        'in_qty'        => $actualQty,
                        'last_qtf'      => 0,
                        'curr_qtf'      => $actualQty,
                        'supplier_rows' => $supplierRowsFormatted,
                    ]);

                    if ($rundownResult['response'] != 1) {
                        return [ (object)['response' => 3] ];
                    }

                /* USE RUNDOWN ROUTING TO TAKE IN TRANSFER MATERIAL */
                    /* VARIABLE ADJUSTMENT */
                        // $entry_no = $entryNo;

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;

    }
    static function transfer_destroy($id, $user){
        $idTmp          = explode("|", $id);
        $idHead         = trim($idTmp[0]);
        $idTraceHead    = trim($idTmp[1]);

        try {
            DB::beginTransaction();

            /* CHECK LOCK PERIOD */
                $entryDate = DB::select('SELECT entry_date
                                        FROM t_trace_header
                                        WHERE id_trace_head = ?
                                            AND `status` = 1',
                                        [$idTraceHead]);
                if (count($entryDate) == 0) {
                    DB::rollBack();
                    return [(object)['response' => 98]];
                }
                $curr_entryDate = $entryDate[0]->entry_date;

                $lockDateTime = new \DateTime($curr_entryDate);
                // Mengambil tahun
                $lockYear = $lockDateTime->format('Y');
                // Mengambil bulan
                $lockMonth = $lockDateTime->format('m');
                // Check Lock Status
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
                    DB::rollBack();
                    $db = [ (object)['response' => 99 ]];
                    return $db;
                }

            $counter = 0;
            $maxIterations = 100;
            do {
                /* MAIN ROUTE */
                    DB::insert('INSERT INTO log_transactions
                                    (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)', [ 'TRANSFER_ENTRY', 'DE-ACTIVATE', 'IdBalHead: ' . $idHead . ' | Status: 1 >> 0', $user ]);
                    DB::update('UPDATE t_balance_detail
                                SET `status` = "0",
                                    `updated_by` = ?
                                WHERE id_balance_head = ?', [$user, $idHead]);
                    DB::update('UPDATE t_balance_header
                                SET `status` = "0",
                                    `updated_by` = ?
                                WHERE id_balance_head = ?', [$user, $idHead]);

                /* GET SOURCE BLENDING AND DELETE */
                    $datTraceHead = DB::select('SELECT b.id_balance_head, b.out_qty, b.id_trace_head, a.id_material, a.in_qty,
                                                       DATE_FORMAT(a.`created_at`, "%Y-%m-%d %H:%i") AS created_at
                                                FROM t_trace_header a
                                                LEFT JOIN t_trace_header b
                                                    ON a.from_trace_no = b.to_trace_no AND b.status = 1
                                                WHERE a.id_balance_head = ?
                                                AND a.status = 1', [$idHead]);
                    $lenTraceHead = count($datTraceHead);
                    $createdAt = $datTraceHead[0]->created_at;
                    $idMaterial = $datTraceHead[0]->id_material;
                    $inQty = $datTraceHead[0]->in_qty;

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
                                     WHERE a.id_balance_head = ?
                                ', [$onhandQtyBalHeadSource, $outQtyBalHeadSource, $user, $idBalHead]);

                        /* DEACTIVATED ADJUSTMENT */
                        $datAdjustHead = DB::select('SELECT id_adjust_head
                                                    FROM t_adjustment_header
                                                    WHERE id_balance_head = ?
                                                        AND `status` = 1',
                                                    [$idBalHead]);
                        $lenAdjustHead = count($datAdjustHead);
                        if ($lenAdjustHead > 0){
                            $idAdjustHead = $datAdjustHead[0]->id_adjust_head;
                            DB::update('UPDATE t_adjustment_header a
                                        SET a.status = 0,
                                            a.`updated_by` = ?
                                        WHERE a.id_adjust_head = ?
                                    ', [$user, $idAdjustHead]);
                            DB::update('UPDATE t_adjustment_detail a
                                        SET a.status = 0,
                                            a.`updated_by` = ?
                                        WHERE a.id_adjust_head = ?
                                    ', [$user, $idAdjustHead]);
                        }

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

                /* DESTROYING AUTO ADJUSTMENT IN */
                    $datAdjustIn = DB::select('SELECT id_balance_head, id_trace_head
                                                FROM t_trace_header
                                                WHERE `status` = 1
                                                AND `from_trace_no` IS NULL
                                                AND SUBSTRING(`to_trace_no`,1,1) = 9
                                                AND DATE_FORMAT(`created_at`, "%Y-%m-%d %H:%i") = ?
                                                AND `id_material` = ?
                                                AND `in_qty` = ?
                                            ', [$createdAt, $idMaterial, $inQty]);
                    $lenAdjustIn = count($datAdjustIn);

                    if ($lenAdjustIn > 0){
                        $idBalHead = $datAdjustIn[0]->id_balance_head;
                        $idTraceHead = $datAdjustIn[0]->id_trace_head;

                        DB::update('UPDATE t_trace_header
                                    SET `status` = 0,
                                        `updated_by` = ?
                                    WHERE `status` = 1
                                    AND `id_trace_head` = ?
                                    ', [$user, $idTraceHead]);
                        DB::update('UPDATE t_trace_detail
                                    SET `status` = 0,
                                        `updated_by` = ?
                                    WHERE `status` = 1
                                    AND `id_trace_head` = ?
                                    ', [$user, $idTraceHead]);
                        DB::update('UPDATE t_balance_header
                                    SET `status` = 0,
                                        `updated_by` = ?
                                    WHERE `status` = 1
                                    AND `id_balance_head` = ?
                                    ', [$user, $idBalHead]);
                        DB::update('UPDATE t_balance_detail
                                    SET `status` = 0,
                                        `updated_by` = ?
                                    WHERE `status` = 1
                                    AND `id_balance_head` = ?
                                    ', [$user, $idBalHead]);
                    };

                /* DESTROYING AUTO TRF TO ADJUSTMENT OUT */
                $datAdjustOut = DB::select('SELECT id_balance_head, id_trace_head
                                            FROM t_trace_header
                                            WHERE `status` = 1
                                            AND SUBSTRING(`to_trace_no`,1,1) = 7
                                            AND DATE_FORMAT(`created_at`, "%Y-%m-%d %H:%i") = ?
                                            AND `id_material` = ?
                                            AND `in_qty` = ?
                                            ', [$createdAt, $idMaterial, $inQty]);
                $lenAdjustOut = count($datAdjustOut);

                if ($lenAdjustOut > 0){
                    $idHead = $datAdjustOut[0]->id_balance_head;
                    $idTraceHead = $datAdjustOut[0]->id_trace_head;
                } else {
                    break;
                };

                if (++$counter >= $maxIterations) {
                    throw new \Exception("Infinite loop detected in transfer_destroy");
                }
            } while ($lenAdjustOut > 0);

            DB::commit();

            /* THROW OUTPUT */
            $db = [ (object)['response' => 1 ]];
            return $db;

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan transaksi jika ada error
            return [(object)['response' => 0, 'error' => $e->getMessage()]];
        }
    }
    static function post_adjEntrySupplier($user, $adjNumber, $idSupplier, $idMaterial, $qty, $batchSap, $request){

        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $db = DB::insert('INSERT INTO t_balance_temporary
                                (entry_no, id_supplier, id_material, qty, batch_sap, id_plant, created_by)
                          VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [$adjNumber, $idSupplier, $idMaterial, $qty, $batchSap, $idPlant, $user]);
        $db = [ (object)['response' => $db ? 1 : 0 ]];

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
