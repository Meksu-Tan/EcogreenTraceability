<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Transfer;

class Adjustment extends Model
{
    protected $connection = 'eudr_ts';

    static function get_adjNewEntryNumber($entryDate=null){
        if ($entryDate == null){
            $db = DB::select('SELECT a.adj_number
                                FROM (SELECT a.trace_no+1 AS adj_number
                                        FROM t_balance_header a
                                       WHERE SUBSTRING(a.trace_no,1,7) = CONCAT("9", DATE_FORMAT(CURDATE(), "%y%m%d"))
                                         AND a.status = 1
                                       ORDER BY a.id_balance_head DESC
                                       LIMIT 1 ) a
                               UNION ALL
                              SELECT CONCAT("9", DATE_FORMAT(CURDATE(), "%y%m%d"),"0001") AS adj_number
                               LIMIT 1');
        } else {
            $db = DB::select('SELECT a.adj_number
                                FROM (
                                    SELECT a.trace_no+1 AS adj_number
                                    FROM t_balance_header a
                                    WHERE SUBSTRING(a.trace_no, 1, 7) = CONCAT("9", DATE_FORMAT(LAST_DAY(?), "%y%m%d") )
                                    AND a.status = 1
                                    ORDER BY a.id_balance_head DESC
                                    LIMIT 1
                                ) a
                              UNION ALL
                             SELECT CONCAT("9", DATE_FORMAT(LAST_DAY(?),"%y%m%d"), "0001") AS adj_number
                              LIMIT 1;', [$entryDate, $entryDate]);
        }
        return $db;
    }
    static function get_adjNewEntryNumberWhx(){
        $db = DB::select('SELECT a.adj_number
                            FROM (SELECT a.trace_no+1 AS adj_number
                                    FROM t_warehouse_header a
                                   WHERE SUBSTRING(a.trace_no,1,7) = CONCAT("6", DATE_FORMAT(CURDATE(), "%y%m%d"))
                                     AND a.status = 1
                                   ORDER BY a.id_whx_head DESC
                                   LIMIT 1 ) a
                            UNION ALL
                            SELECT CONCAT("6", DATE_FORMAT(CURDATE(), "%y%m%d"),"0001") AS adj_number
                            LIMIT 1');
        return $db;
    }
    static function get_dtSupplierList($request){
        $mode = $request->input('mode');
        $number = $request->input('number');

        if ($mode == 'ADD'){
            $db = DB::select('SELECT FORMAT(a.qty,3) AS qty, a.id_supplier, c.code AS material,
                                     CONCAT(b.code, " :: ", b.description) AS supplier,
                                     a.id_balance_temp AS idTail, a.entry_no, ? AS mode,
                                     a.batch_sap
                                FROM t_balance_temporary a
                                LEFT JOIN m_supplier b
                                  ON a.id_supplier = b.id_supplier
                                LEFT JOIN m_material c
                                  ON a.id_material = c.id_material
                               WHERE a.entry_no = ?
                                 AND a.status = 1', [$mode, $number]);
        } else if ($mode == 'UPDATE'){
            $db = DB::select('SELECT FORMAT(a.qty,3) AS qty, a.id_supplier, d.code AS material,
                                     CONCAT(b.code, " :: ", b.description) AS supplier,
                                     a.id_balance_tail AS idTail, c.trace_no AS entry_no, ? AS mode,
                                     a.batch_sap
                                FROM t_balance_detail a
                                LEFT JOIN m_supplier b
                                  ON a.id_supplier = b.id_supplier
                                LEFT JOIN t_balance_header c
                                  ON a.id_balance_head = c.id_balance_head
                                LEFT JOIN m_material d
                                  ON a.id_material = d.id_material
                               WHERE a.id_balance_head = ?
                                 AND a.status = 1', [$mode, $number]);
        }

        return $db;
    }
    static function get_totalQtySupplier($request){
        $mode = $request->input('mode');
        $number = $request->input('number');

        if ($mode == 'ADD'){
            $db = DB::select('SELECT FORMAT(SUM(a.qty),3) AS total
                                FROM t_balance_temporary a
                               WHERE a.entry_no = ?
                                 AND a.status = 1', [$number]);
        } else if ($mode == 'UPDATE'){
            $db = DB::select('SELECT FORMAT(SUM(a.qty),3) AS total
                                FROM t_balance_detail a
                               WHERE a.id_balance_head = ?
                                 AND a.status = 1', [$number]);
        }

        return $db;
    }
    static function get_activeSupplier_bySelect2($request){
        $supplier = $request->input('supplier');

        $db = DB::select('SELECT CONCAT(a.code, " :: ", a.description) AS supplier, a.id_supplier
                              FROM m_supplier a
                             WHERE a.status = "1"
                               AND a.description LIKE CONCAT("%",?,"%")
                             ORDER BY a.description ASC', [$supplier]);

        return $db;
    }
    static function get_dtAdjustment($request){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $db = DB::select('SELECT a.entry_date, CAST(a.adjust_no AS CHAR) AS adjust_no, CONCAT(b.code, " :: ", b.description) AS material,
                                 CAST(c.trace_no AS CHAR) AS trace_no, CONCAT("Qty: ", a.before_adjust, " >>> ", a.after_adjust, " MT") AS adjustment, a.id_adjust_head,
                                 GROUP_CONCAT(DISTINCT CONCAT(e.description, " / ", d.batch_sap, " / Qty: ", FORMAT(d.before_adjust,3), " >>> ", FORMAT(d.after_adjust,3), " MT") SEPARATOR " | ") AS supplier,
                                 a.created_by, a.created_at, a.`status`, a.after_adjust, g.description AS sloc,
                                 IF(a.after_adjust <> c.qty, 0, 1) AS adjust_flag, f.id_matdoc, f.material_document, f.id_trace_head
                            FROM t_adjustment_header a
                            LEFT JOIN m_material b
                              ON a.id_material = b.id_material
                            LEFT JOIN t_balance_header c
                              ON a.id_balance_head = c.id_balance_head AND c.`status` = 1
                            LEFT JOIN t_adjustment_detail d
                              ON a.id_adjust_head = d.id_adjust_head
                            LEFT JOIN m_supplier e
                              ON e.id_supplier = d.id_supplier
                            LEFT JOIN (SELECT f.to_trace_no, ff.id_matdoc, ff.material_document, f.id_trace_head
                                         FROM t_trace_header f
                                         LEFT JOIN t_material_document ff
                                           ON f.id_trace_head = ff.id_trace_head AND ff.status = 1
                                        WHERE f.status = 1
                                        ) f
                              ON a.adjust_no = f.to_trace_no
                            LEFT JOIN m_tank g
                              ON g.id_tank = a.id_tank
                           WHERE a.`status` = 1
                             AND SUBSTRING(a.adjust_no, 1, 1) = 9
                           GROUP BY a.adjust_no
                           ORDER BY a.entry_date DESC');

        return $db;
    }
    static function get_dtAdjustmentWhx($request){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $db = DB::select('SELECT a.entry_date, CAST(a.adjust_no AS CHAR) AS adjust_no, CONCAT(b.code, " :: ", b.description) AS material, c.batch_no, c.po_no,
                                 CAST(c.trace_no AS CHAR) AS trace_no, CONCAT("Qty: ", a.before_adjust, " >>> ", a.after_adjust, " MT") AS adjustment, a.id_adjust_head,
                                 GROUP_CONCAT(DISTINCT CONCAT(e.description, " / ", d.batch_sap, " / Qty: ", FORMAT(d.before_adjust,3), " >>> ", FORMAT(d.after_adjust,3), " MT") SEPARATOR " | ") AS supplier,
                                 a.created_by, a.created_at, a.`status`, a.after_adjust, g.description AS sloc,
                                 IF(a.after_adjust <> c.qty, 0, 1) AS adjust_flag, f.id_matdoc, f.material_document, f.id_trace_head
                            FROM t_adjustment_header a
                            LEFT JOIN m_material_pck b
                              ON a.id_material = b.id_materialpck
                            LEFT JOIN t_warehouse_header c
                              ON a.id_balance_head = c.id_whx_head AND c.`status` = 1
                            LEFT JOIN t_adjustment_detail d
                              ON a.id_adjust_head = d.id_adjust_head
                            LEFT JOIN m_supplier e
                              ON e.id_supplier = d.id_supplier
                            LEFT JOIN (SELECT f.to_trace_no, ff.id_matdoc, ff.material_document, f.id_trace_head
                                         FROM t_trace_header f
                                         LEFT JOIN t_material_document ff
                                           ON f.id_trace_head = ff.id_trace_head AND ff.status = 1
                                        WHERE f.status = 1
                                        ) f
                              ON a.adjust_no = f.to_trace_no
                            LEFT JOIN m_warehouse g
                              ON g.id_warehouse = a.id_tank
                           WHERE a.`status` = 1
                             AND SUBSTRING(a.adjust_no, 1, 1) = 6
                           GROUP BY a.adjust_no
                           ORDER BY a.entry_date DESC');

        return $db;
    }
    static function get_cmbActiveMaterial(){
        $db = DB::select('SELECT a.id_material, CONCAT( UPPER(a.description), " (", a.code, " / ", a.type, " / Feed: ", a.qtf_feed, " / Rundown: ", a.qtf_rundown,  ")" ) AS material
                            FROM m_material a
                           WHERE a.status = 1
                             AND a.`type` <> "FG"
                           ORDER BY a.description ASC');

        return $db;
    }
    static function get_cmbActiveMaterialWhx(){
        $db = DB::select('SELECT a.id_materialpck, CONCAT( UPPER(a.description), " (", a.code, ")" ) AS material
                            FROM m_material_pck a
                           WHERE a.status = 1
                           ORDER BY a.description ASC');

        return $db;
    }
    static function get_cmbActiveTank(){
        $db = DB::select('SELECT a.id_tank, a.description AS tank
                            FROM m_tank a
                           WHERE a.status = 1
                           ORDER BY a.description ASC');

        return $db;
    }
    static function get_cmbActiveWhx(){
        $db = DB::select('SELECT a.id_warehouse AS id_tank, CONCAT(a.id_batch, " - ", a.description) AS tank
                            FROM m_warehouse a
                           WHERE a.status = 1
                           ORDER BY a.id_batch, a.description ASC');

        return $db;
    }

    static function post_storeAdjustment($user, $id_material, $adjustQty, $entryDate, $id_tank){

        /* CREATE ADJUSTMENT NUMBER */
            $batch_moveType = 9;
            $batch_entryDate = substr(str_replace("-", "", $entryDate),2);
            $datMatl = DB::select('SELECT a.id_rundown
                                     FROM m_material a
                                    WHERE a.id_material = ?', [$id_material]);
            $batch_id = $datMatl[0]->id_rundown;
            $batchMapping = $batch_moveType . $batch_entryDate . $batch_id;

        /* SEARCH FOR EXISTING BATCH SEQUENCE */
            DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
            $datBatch = DB::select('SELECT a.adjust_no, COUNT(a.adjust_no) AS flag
                                      FROM (SELECT a.adjust_no
                                              FROM t_adjustment_header a
                                             WHERE SUBSTRING(a.adjust_no,1,9) = ?
                                             ORDER BY a.adjust_no DESC
                                             LIMIT 1) a', [$batchMapping]);
            $flagBatch = $datBatch[0]->flag;
            if ($flagBatch > 0) {
                $batchNo = $datBatch[0]->adjust_no + 1;
            } else {
                $batchNo = $batchMapping . '01';
            }

        /* GET EXISTING BALANCE */
            $datBal = DB::select('SELECT a.qty, a.trace_no, a.id_balance_head, a.in_qty, a.out_qty,
                                         a.from_trace_no, a.entry_date, a.id_trace_head
                                    FROM m_material b
                                    LEFT JOIN (SELECT c.code, a.qty, a.trace_no, a.id_balance_head, a.in_qty, a.out_qty,
                                                      b.from_trace_no, a.entry_date, b.id_trace_head
                                                 FROM m_material c
                                                 LEFT JOIN t_balance_header a
                                                   ON c.id_material = a.id_material
                                                 LEFT JOIN t_trace_header b
                                                   ON a.id_balance_head = b.id_balance_head AND b.status = 1 AND b.out_qty = 0
                                                WHERE a.status = 1
                                                  AND a.id_tank = ? ) a
                                      ON b.code = a.code
                                   WHERE b.id_material = ?
                                     AND b.status = 1
                                   ORDER BY a.entry_date DESC, a.id_balance_head DESC
                                   LIMIT 1
                                   ', [$id_tank, $id_material]);

        /* GET TOTAL BALANCE */
            DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
            $datTotalBal = DB::select('SELECT SUM(a.qty) AS total_qty
                                         FROM m_material b
                                         LEFT JOIN (SELECT a.code, b.qty
                                                      FROM m_material a
                                                      LEFT JOIN t_balance_header b
                                                        ON a.id_material = b.id_material AND b.status = 1
                                                     WHERE a.status = 1
                                                       AND b.id_tank = ? ) a
                                           ON b.code = a.code
                                        WHERE b.id_material = ?
                                          AND b.status = 1
                                        LIMIT 1
                                       ', [$id_tank, $id_material]);

            $len = count($datBal);
            if ($len == 0){
                $db = [ (object)['response' => 4 ]];
                return $db;
            }

            $totalBal = $datTotalBal[0]->total_qty;
            if ($totalBal == 0){
                $db = [ (object)['response' => 5 ]];
                return $db;
            }

            $traceNo = $datBal[0]->trace_no;
            $idHead = $datBal[0]->id_balance_head;
            $inBalQty = $datBal[0]->in_qty;
            $outBalQty = $datBal[0]->out_qty;
            $headBalQty = $datBal[0]->qty;
            $idTraceHead = $datBal[0]->id_trace_head;

        /* COMPARING IN HEADER */
            $diffQty = $totalBal - $adjustQty;
            $adjustType = null;

            if ($diffQty > 0){
                /* CHECK BALANCE LAST TRANSACTION COMPARE TO ADJUST QTY */
                    $diffQtyLastTrans = $headBalQty - $diffQty;

                    if ($diffQtyLastTrans < 0){
                        return [ (object)['response' => 9 ]];
                    }

                /* OUT ADJUSTMENT */
                    $beforeAdjust = $headBalQty;

                    $inQty = $inBalQty - $diffQty;
                    $afterAdjust = $beforeAdjust - $diffQty;
                    $adjustType = "OUT";

            } elseif ($diffQty < 0){
                /* IN ADJUSTMENT */
                    $beforeAdjust = $headBalQty;

                    $inQty = $inBalQty + (-1 * $diffQty);
                    $afterAdjust = $beforeAdjust + (-1 * $diffQty);
                    $adjustType = "IN";

            } elseif ($diffQty == 0){
                return [ (object)['response' => 10 ]];
            }

        /* CHECKING PREVIOUS HEADER TRANSACTION FOR ADJUSTMENT */
            /* GET BALANCE */
                $prevTraceBalance = DB::select('SELECT a.qty, a.in_qty, a.out_qty, bb.id_trace_head,
                                                       bb.in_qty AS traceInQty, bb.out_qty AS traceOutQty,
                                                       a.id_balance_head
                                                  FROM t_trace_header b
                                                  LEFT JOIN t_trace_header bb
                                                    ON b.from_trace_no = bb.to_trace_no
                                                  LEFT JOIN t_balance_header a
                                                    ON a.id_balance_head = bb.id_balance_head AND b.status = 1
                                                 WHERE b.to_trace_no = ?
                                                   AND a.status = 1
                                                   AND b.id_sloc = ?
                                                ', [$traceNo, $id_tank]);
                if(empty($prevTraceBalance)){
                    return [ (object)['response' => 11 ]];
                };

                $prevBalIdHead = $prevTraceBalance[0]->id_balance_head;
                $prevBalQty = $prevTraceBalance[0]->qty;
                $prevBalInQty = $prevTraceBalance[0]->in_qty;
                $prevBalOutQty = $prevTraceBalance[0]->out_qty;
                $prevTraceIdHead = $prevTraceBalance[0]->id_trace_head;
                $prevTraceInQty = $prevTraceBalance[0]->traceInQty;
                $prevTraceOutQty = $prevTraceBalance[0]->traceOutQty;


            /* CHECK IF PREVIOUS BALANCE STILL HAVE QTY */
                if ($adjustType == "IN"){
                    $diffPrevBalanceQty = $prevBalQty - $prevTraceOutQty + (-1 * $diffQty);

                    if ($diffPrevBalanceQty < 0){
                        return [ (object)['response' => 9 ]];
                    }

                    $new_prevTraceOutQty = $prevTraceOutQty + (-1 * $diffQty);

                } elseif ($adjustType == "OUT"){

                    $new_prevTraceOutQty = $prevTraceOutQty - $diffQty;
                };

                $new_prevBalOutQty = $prevBalOutQty - $prevTraceOutQty + $new_prevTraceOutQty;
                $new_prevBalQty = $prevBalQty + $prevTraceOutQty - $new_prevTraceOutQty;

                if ($new_prevBalQty < 0){
                    return [ (object)['response' => 9 ]];
                }

        /* INSERT INTO ADJUSTMENT HEADER */
            $idAdjustHead = DB::table('t_adjustment_header')->insertGetId([
                'entry_date' => $entryDate,
                'adjust_no' => $batchNo,
                'id_balance_head' => $idHead,
                'id_material' => $id_material,
                'id_tank' => $id_tank,
                'in_qty' => $inQty,
                'out_qty' => 0,
                'before_adjust' => $beforeAdjust,
                'after_adjust' => $afterAdjust,
                'created_by' => $user
            ]);
        /* UPDATE BALANCE HEADER */
            DB::update('UPDATE t_balance_header
                           SET qty = ?,
                               in_qty = ?,
                               updated_by = ?
                         WHERE id_balance_head = ?',
                        [$afterAdjust, $inQty, $user, $idHead]);
        /* UPDATE INTO TRACE HEADER */
            DB::update('UPDATE t_trace_header
                           SET in_qty = ?,
                               updated_by = ?
                         WHERE id_trace_head = ?',
                        [$inQty, $user, $idTraceHead]);

        /* UPDATE PREVIOUS BALANCE HEADER */
            DB::update('UPDATE t_balance_header
                           SET qty = ?,
                               out_qty = ?,
                               updated_by = ?
                         WHERE id_balance_head = ?',
                        [$new_prevBalQty, $new_prevBalOutQty, $user, $prevBalIdHead]);
        /* UPDATE PREVIOUS TRACE HEADER */
            DB::update('UPDATE t_trace_header
                           SET out_qty = ?,
                               updated_by = ?
                         WHERE id_trace_head = ?',
                        [$new_prevTraceOutQty, $user, $prevTraceIdHead]);


        /* HEADER LOGGING */
            DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'ADJUST BALANCE', 'IDHEAD: ' . $idHead . ' | DATE: ' . $entryDate .
                                                ' / MATERIAL: ' . $id_material . ' / QTY: ' . $beforeAdjust . ' >>> ' . $afterAdjust .
                                                ' / IN_QTY: ' . $inBalQty . ' >>> ' . $inQty  .
                                                ' | Status: 1', $user ]);

        /* GET SUPPLIER DETAIL */
            $datDet = DB::select('SELECT a.id_supplier, a.batch_sap, a.qty, a.in_qty, a.out_qty, a.init_qty,
                                         a.id_balance_tail, b.id_trace_tail
                                    FROM t_trace_detail b
                                    LEFT JOIN t_balance_detail a
                                      ON a.id_balance_tail = b.id_balance_tail
                                   WHERE b.id_trace_head = ?
                                     AND a.status = 1
                                     AND b.status = 1
                                     AND b.out_qty = 0
                                     AND a.qty > "0.0001"', [$idTraceHead]);
            $lenDet = count($datDet);

            $balQty = [];
            $balInitQty = [];
            $balInQty = [];
            $balOutQty = [];
            $idSupplier = [];
            $idTail = [];
            $batchSap = [];
            $compositionRate = [];
            $newBalQty = [];

            $idTraceTail = [];

            for ($i = 0; $i < $lenDet; $i++) {
                if ($datDet[$i]->init_qty > "0.0009"){
                    $idSupplier[] = $datDet[$i]->id_supplier;
                    $batchSap[] = $datDet[$i]->batch_sap;
                    $balQty[] = $datDet[$i]->qty;
                    $balInitQty[] = $datDet[$i]->init_qty;
                    $balInQty[] = $datDet[$i]->in_qty;
                    $balOutQty[] = $datDet[$i]->out_qty;
                    $idTail[] = $datDet[$i]->id_balance_tail;
                    $idTraceTail[] = $datDet[$i]->id_trace_tail;
                }
            }

            /* RE-COUNT LENGTH */
            $lenDet = count($balQty);

            $totalQty = array_sum($balQty);
            foreach ($balQty as $qty) {
                $compositionRate[] = ($totalQty > 0) ? ($qty / $totalQty) : 0;
            }
            for ($i = 0; $i < $lenDet; $i++) {
                $diffDetQty[] = $diffQty * $compositionRate[$i];
            }


            for ($i = 0; $i < $lenDet; $i++){
                if ($diffDetQty[$i] >= 0){
                    /* OUT ADJUSTMENT */
                        $beforeAdjustDet = $balQty[$i];

                        $inDetQty = $balInQty[$i] - $diffDetQty[$i];
                        $afterAdjustDet = $beforeAdjustDet - $diffDetQty[$i];

                } elseif ($diffDetQty[$i] < 0){
                    /* IN ADJUSTMENT */
                        $beforeAdjustDet = $balQty[$i];

                        $inDetQty = $balInQty[$i] + (-1 * $diffDetQty[$i]);
                        $afterAdjustDet = $beforeAdjustDet + (-1 * $diffDetQty[$i]);
                }

                /* INSERT INTO ADJUSTMENT DETAIL */
                    $idAdjustTail = DB::table('t_adjustment_detail')->insertGetId([
                                    'id_adjust_head' => $idAdjustHead,
                                    'id_balance_tail' => $idTail[$i],
                                    'id_supplier' => $idSupplier[$i],
                                    'id_material' => $id_material,
                                    'batch_sap' => $batchSap[$i],
                                    'in_qty' => $inDetQty,
                                    'out_qty' => 0,
                                    'before_adjust' => $beforeAdjustDet,
                                    'after_adjust' => $afterAdjustDet,
                                    'created_by' => $user
                                ]);
                /* UPDATE BALANCE DETAIL */
                    DB::update('UPDATE t_balance_detail
                                   SET qty = ?,
                                       in_qty = ?,
                                       updated_by = ?
                                 WHERE id_balance_tail = ?',
                                [$afterAdjustDet, $inDetQty , $user, $idTail[$i]]);
                /* UPDATE INTO TRACE DETAIL */
                    DB::update('UPDATE t_trace_detail
                                   SET in_qty = ?,
                                       updated_by = ?
                                 WHERE id_balance_tail = ?',
                                [$inDetQty , $user, $idTail[$i]]);

                /* DETAIL LOGGING */
                    DB::insert('INSERT INTO log_transactions
                                       (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'ADJUST BALANCE', ' IDTAIL: ' . $idTail[$i] .
                                                        ' / SUPPLIER: ' . $idSupplier[$i] . ' / MATERIAL: ' . $id_material .
                                                        ' / QTY: ' . $balQty[$i] . ' >>> ' . $afterAdjustDet .
                                                        ' / IN_QTY: ' . $balInQty[$i] . ' >>> ' . $inDetQty .
                                                        ' | Status: 1', $user ]);

            }
        /* GET PREVIOUS SUPPLIER DETAIL */
            $datDet = DB::select('SELECT a.id_supplier, a.batch_sap, a.qty, a.in_qty,
                                         a.out_qty, a.init_qty,
                                         a.id_balance_tail, b.id_trace_tail,
                                         b.in_qty AS traceInQty, b.out_qty AS traceOutQty
                                    FROM t_trace_detail b
                                    LEFT JOIN t_balance_detail a
                                      ON a.id_balance_tail = b.id_balance_tail AND b.status = 1
                                   WHERE b.id_trace_head = ?
                                     AND a.status = 1', [$prevTraceIdHead]);

            $lenDet = count($datDet);

            $balQty = [];
            $balInitQty = [];
            $balInQty = [];
            $balOutQty = [];
            $idSupplier = [];
            $idTail = [];
            $batchSap = [];
            $compositionRate = [];
            $newBalQty = [];

            $idTraceTail = [];
            $traceOutQty = [];

            for ($i = 0; $i < $lenDet; $i++) {
                if ($datDet[$i]->init_qty > "0.0009"){
                    $idSupplier[] = $datDet[$i]->id_supplier;
                    $batchSap[] = $datDet[$i]->batch_sap;
                    $balQty[] = $datDet[$i]->qty;
                    $balInitQty[] = $datDet[$i]->init_qty;
                    $balInQty[] = $datDet[$i]->in_qty;
                    $balOutQty[] = $datDet[$i]->out_qty;
                    $idTail[] = $datDet[$i]->id_balance_tail;
                    $idTraceTail[] = $datDet[$i]->id_trace_tail;
                    $traceOutQty[] = $datDet[$i]->traceOutQty;
                }
            }

            /* RE-COUNT LENGTH */
            $lenDet = count($balQty);

            $totalQty = array_sum($balQty);
            foreach ($balQty as $qty) {
                $compositionRate[] = ($totalQty > 0) ? ($qty / $totalQty) : 0;
            }
            for ($i = 0; $i < $lenDet; $i++) {
                $diffDetQty[] = $diffQty * $compositionRate[$i];
            }
            for ($i = 0; $i < $lenDet; $i++){
                if ($diffDetQty[$i] > 0){
                    /* OUT ADJUSTMENT */
                    $new_prevTraceOutQty = $traceOutQty[$i] - $diffDetQty[$i] ;

                } elseif ($diffDetQty[$i] < 0){
                    /* IN ADJUSTMENT */
                    $new_prevTraceOutQty = $traceOutQty[$i] + (-1 * $diffDetQty[$i]);
                }
                $new_prevDetBalOutQty = $balOutQty[$i] - $traceOutQty[$i] + $new_prevTraceOutQty;
                $new_prevDetBalQty = $balQty[$i] + $traceOutQty[$i] - $new_prevTraceOutQty;



                /* UPDATE BALANCE DETAIL */
                    DB::update('UPDATE t_balance_detail
                                   SET qty = ?,
                                       out_qty = ?,
                                       updated_by = ?
                                 WHERE id_balance_tail = ?',
                                [$new_prevDetBalQty, $new_prevDetBalOutQty, $user, $idTail[$i]]);
                /* UPDATE INTO TRACE DETAIL */
                    DB::update('UPDATE t_trace_detail
                                   SET out_qty = ?,
                                       updated_by = ?
                                 WHERE id_trace_tail = ?',
                                [$new_prevTraceOutQty, $user, $idTraceTail[$i]]);
            }

        /* THROW OUTPUT */
            $db = [ (object)['response' => 1 ]];
            return $db;
    }
    static function post_storeAdjustmentWhx($user, $request){
        // $mode = $request->input('mode');
        // $id = $request->input('id');
        // $id_material = $request->input('id_material');
        // $adjustQty = $request->input('qty');
        // $entryDate = $request->input('entryDate');
        // $id_tank = $request->input('idTank');

        // /* CREATE ADJUSTMENT NUMBER */
        //     $batch_moveType = 6;
        //     $batch_entryDate = substr(str_replace("-", "", $entryDate),2);
        //     $batch_id = sprintf("%02d", $id_tank);
        //     $batchMapping = $batch_moveType . $batch_entryDate . $batch_id;

        //     /* SEARCH FOR EXISTING BATCH SEQUENCE */
        //     DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        //     $datBatch = DB::select('SELECT a.adjust_no, COUNT(a.adjust_no) AS flag
        //                               FROM (SELECT a.adjust_no
        //                                       FROM t_adjustment_header a
        //                                      WHERE SUBSTRING(a.adjust_no,1,9) = ?
        //                                      ORDER BY a.adjust_no DESC
        //                                      LIMIT 1) a', [$batchMapping]);
        //     $flagBatch = $datBatch[0]->flag;
        //     if ($flagBatch > 0) {
        //         $batchNo = $datBatch[0]->adjust_no + 1;
        //     } else {
        //         $batchNo = $batchMapping . '01';
        //     }

        // /* GET EXISTING BALANCE */

        //     $datBal = DB::select('SELECT a.qty, a.trace_no, a.id_whx_head, a.in_qty, a.out_qty, a.id_material_feed
        //                             FROM t_warehouse_header a
        //                            WHERE a.id_material_fg = ?
        //                              AND a.status = 1
        //                              AND a.id_section = ?
        //                            ORDER BY a.entry_date DESC
        //                            LIMIT 1
        //                            ', [$id_material, $id_tank]);
        //     $len = count($datBal);
        //     if ($len == 0){
        //         $db = [ (object)['response' => 4 ]];
        //         return $db;
        //     } else {
        //         $idMaterialFeed = $datBal[0]->id_material_feed;
        //         $fromTraceNo = $datBal[0]->trace_no;
        //         $idHead = $datBal[0]->id_whx_head;
        //         $inBalQty = $datBal[0]->in_qty;
        //         $outBalQty = $datBal[0]->out_qty;
        //         $headBalQty = $datBal[0]->qty;

        //         if ($headBalQty == 0){
        //             $db = [ (object)['response' => 5 ]];
        //             return $db;
        //         }
        //     }

        // /* COMPARING IN HEADER */
        //     $diffQty = $headBalQty - $adjustQty;

        //     if ($diffQty > 0){
        //         /* OUT ADJUSTMENT */
        //             $beforeAdjust = $headBalQty;
        //             $afterAdjust = $adjustQty;
        //             $inQty = 0;
        //             $outQty = $diffQty;
        //     } elseif ($diffQty < 0){
        //         /* IN ADJUSTMENT */
        //             $beforeAdjust = $headBalQty;
        //             $afterAdjust = $adjustQty;
        //             $inQty = -1 * $diffQty;
        //             $outQty = 0;
        //     } else {
        //         $db = [ (object)['response' => 7 ]];
        //         return $db;
        //     }

        // /* INSERT/UPDATE BALANCE HEADER */
        //     if ($len == 0){
        //         $idHead = DB::table('t_warehouse_header')->insertGetId([
        //             'id_material_feed' => $idMaterialFeed,
        //             'from_trace_no' => $fromTraceNo,
        //             'trace_no' => $batchNo,
        //             'id_material_fg' => $id_material,
        //             'id_tank' => $id_tank,
        //             'entry_date' => $entryDate,
        //             'qty' => $afterAdjust,
        //             'in_qty' => $inQty,
        //             'out_qty' => $outQty,
        //             'init_qty' => $afterAdjust,
        //             'created_by' => $user,
        //         ]);

        //     } else {
        //         DB::update('UPDATE t_warehouse_header
        //                        SET qty = ?,
        //                            in_qty = ?,
        //                            out_qty = ?,
        //                            updated_by = ?
        //                      WHERE id_whx_head = ?',
        //                     [$afterAdjust, $inQty + $inBalQty , $outQty + $outBalQty, $user, $idHead]);
        //     }

        // /* INSERT INTO ADJUSTMENT HEADER */
        //     $idAdjustHead = DB::table('t_adjustment_header')->insertGetId([
        //         'entry_date' => $entryDate,
        //         'adjust_no' => $batchNo,
        //         'id_balance_head' => $idHead,
        //         'id_material' => $id_material,
        //         'id_tank' => $id_tank,
        //         'in_qty' => $inQty,
        //         'out_qty' => $outQty,
        //         'before_adjust' => $beforeAdjust,
        //         'after_adjust' => $afterAdjust,
        //         'created_by' => $user
        //     ]);

        // /* INSERT INTO TRACE HEADER */
        //     $idTraceHead = DB::table('t_trace_header')->insertGetId([
        //         'from_trace_no' => $fromTraceNo,
        //         'to_trace_no' => $batchNo,
        //         'id_balance_head' => $idHead,
        //         'id_material' => $id_material,
        //         'entry_date' => $entryDate,
        //         'id_sloc' => $id_tank,
        //         'in_qty' => $inQty,
        //         'out_qty' => $outQty,
        //         'curr_qtf' => $inQty + $outQty,
        //         'created_by' => $user
        //     ]);

        // /* HEADER LOGGING */
        //     DB::insert('INSERT INTO log_transactions
        //                         (log_module, log_type, log_description, created_by)
        //                 VALUES (?, ?, ?, ?)', [ 'T_WHX_HEAD', 'ADJUST BALANCE', 'IDHEAD: ' . $idHead . ' | DATE: ' . $entryDate .
        //                                         ' / MATERIAL: ' . $id_material . ' / QTY: ' . $beforeAdjust . ' >>> ' . $afterAdjust .
        //                                         ' / IN_QTY: ' . $inBalQty . ' >>> ' . $inQty + $inBalQty .
        //                                         ' / OUT_QTY: ' . $outBalQty . ' >>> ' . $outQty + $outBalQty .
        //                                         ' | Status: 1', $user ]);

        // /* GET SUPPLIER DETAIL */
        //     $datDet = DB::select('SELECT a.id_supplier, a.batch_sap, a.qty, a.in_qty, a.out_qty, a.init_qty, a.id_whx_tail
        //                             FROM t_warehouse_detail a
        //                            WHERE a.id_whx_head = ?
        //                              AND a.status = 1', [$idHead]);
        //     $lenDet = count($datDet);

        //     $balQty = [];
        //     $balInitQty = [];
        //     $balInQty = [];
        //     $balOutQty = [];
        //     $idSupplier = [];
        //     $idTail = [];
        //     $batchSap = [];
        //     $compositionRate = [];
        //     $newBalQty = [];

        //     for ($i = 0; $i < $lenDet; $i++) {
        //         if ($datDet[$i]->init_qty <> 0){
        //             $idSupplier[] = $datDet[$i]->id_supplier;
        //             $batchSap[] = $datDet[$i]->batch_sap;
        //             $balQty[] = $datDet[$i]->qty;
        //             $balInitQty[] = $datDet[$i]->init_qty;
        //             $balInQty[] = $datDet[$i]->in_qty;
        //             $balOutQty[] = $datDet[$i]->out_qty;
        //             $idTail[] = $datDet[$i]->id_balance_tail;
        //         }
        //     }

        //     /* RE-COUNT LENGTH */
        //     $lenDet = count($balQty);

        //     $totalQty = array_sum($balQty);
        //     foreach ($balQty as $qty) {
        //         $compositionRate[] = ($totalQty > 0) ? ($qty / $totalQty) : 0;
        //     }
        //     for ($i = 0; $i < $lenDet; $i++) {
        //         $newBalQty[] = $adjustQty * $compositionRate[$i];
        //     }

        //     for ($i = 0; $i < $lenDet; $i++){
        //         $diffDetQty = $balQty[$i] - $newBalQty[$i];

        //         if ($diffDetQty > 0){
        //             /* OUT ADJUSTMENT */
        //                 $beforeAdjustDet = $balQty[$i];
        //                 $afterAdjustDet = $newBalQty[$i];
        //                 $inDetQty = 0;
        //                 $outDetQty = $diffDetQty;
        //         } elseif ($diffDetQty < 0){
        //             /* IN ADJUSTMENT */
        //                 $beforeAdjustDet = $balQty[$i];
        //                 $afterAdjustDet = $newBalQty[$i];
        //                 $inDetQty = -1 * $diffDetQty;
        //                 $outDetQty = 0;
        //         }

        //         /* UPDATE BALANCE DETAIL */
        //             DB::update('UPDATE t_warehouse_detail
        //                        SET qty = ?,
        //                            in_qty = ?,
        //                            out_qty = ?,
        //                            updated_by = ?
        //                      WHERE id_whx_tail = ?',
        //                     [$afterAdjustDet, $balInQty[$i] + $inDetQty , $balOutQty[$i] + $outDetQty, $user, $idTail[$i]]);
        //         /* INSERT INTO ADJUSTMENT HEADER */
        //             $idAdjustTail = DB::table('t_adjustment_detail')->insertGetId([
        //                             'id_adjust_head' => $idAdjustHead,
        //                             'id_balance_tail' => $idTail[$i],
        //                             'id_supplier' => $idSupplier[$i],
        //                             'id_material' => $id_material,
        //                             'batch_sap' => $batchSap[$i],
        //                             'in_qty' => $inDetQty,
        //                             'out_qty' => $outDetQty,
        //                             'before_adjust' => $beforeAdjustDet,
        //                             'after_adjust' => $afterAdjustDet,
        //                             'created_by' => $user
        //                         ]);
        //         /* INSERT INTO TRACE HEADER */
        //             $idTraceTail = DB::table('t_trace_detail')->insertGetId([
        //                             'id_trace_head' => $idTraceHead,
        //                             'id_balance_tail' => $idTail[$i],
        //                             'id_supplier' => $idSupplier[$i],
        //                             'id_material' => $id_material,
        //                             'batch_sap' => $batchSap[$i],
        //                             'in_qty' => $inDetQty,
        //                             'out_qty' => $outDetQty,
        //                             'created_by' => $user
        //                         ]);
        //         /* DETAIL LOGGING */
        //             DB::insert('INSERT INTO log_transactions
        //                                (log_module, log_type, log_description, created_by)
        //                         VALUES (?, ?, ?, ?)', [ 'T_WHX_TAIL', 'ADJUST BALANCE', ' IDTAIL: ' . $idTail[$i] .
        //                                                 ' / SUPPLIER: ' . $idSupplier[$i] . ' / MATERIAL: ' . $id_material .
        //                                                 ' / QTY: ' . $balQty[$i] . ' >>> ' . $afterAdjustDet .
        //                                                 ' / IN_QTY: ' . $balInQty[$i] . ' >>> ' . $balInQty[$i] + $inDetQty .
        //                                                 ' / OUT_QTY: ' . $balOutQty[$i] . ' >>> ' . $balOutQty[$i] + $outDetQty .
        //                                                 ' | Status: 1', $user ]);

        //     }

        /* THROW OUTPUT */
            $db = [ (object)['response' => 1 ]];
            return $db;
    }

    static function post_destroyAdjustment($id, $user){
        /* CHECK LOCK PERIOD */
            $entryDate = DB::select('SELECT entry_date
                                       FROM t_adjustment_header
                                      WHERE id_adjust_head = ?
                                        AND `status` = 1',
                                    [$id]);
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

        /* GET ADJUSTMENT HEADER DATA */
            $dat = DB::select('SELECT a.before_adjust, a.id_balance_head, a.in_qty, a.out_qty, a.adjust_no, b.id_trace_head
                                FROM t_adjustment_header a
                                LEFT JOIN t_trace_header b
                                ON b.to_trace_no = a.adjust_no AND b.status = 1
                                WHERE a.id_adjust_head = ?
                                AND a.`status` = 1', [$id]);
            $idHead = $dat[0]->id_balance_head;
            $beforeAdjust = $dat[0]->before_adjust;
            $adjustInQty = $dat[0]->in_qty;
            $adjustOutQty = $dat[0]->out_qty;
            $traceNo = $dat[0]->adjust_no;
            $idTraceHead = $dat[0]->id_trace_head;
            if ($idTraceHead == null){
                $db = [ (object)['response' => 3 ]];
                return $db;
            }

        /* GET BALANCE HEADER DATA */
            $datHead = DB::select('SELECT qty, in_qty, out_qty
                                     FROM t_balance_header
                                    WHERE id_balance_head = ?
                                      AND `status` = 1', [$idHead]);

        /* UPDATE BALANCE HEADER */
            $newBalQty = $beforeAdjust;
            $newBalInQty = $datHead[0]->in_qty - $adjustInQty;
            $newBalOutQty = $datHead[0]->out_qty - $adjustOutQty;

        /* NEW ROUTE TO DEACTIVATE STORAGE INIT */
            $flagStoreInitDat = DB::select('SELECT IFNULL(a.from_trace_no, 1) AS `init`
                                              FROM t_trace_header a
                                             WHERE a.id_trace_head = ?
                                               AND a.status = 1', [$idTraceHead]);
            $flagStoreInit = $flagStoreInitDat[0]->init;

            if ($flagStoreInit == 1){
                DB::update('UPDATE t_balance_header
                               SET qty = ?,
                                   in_qty = ?,
                                   out_qty = ?,
                                   `status` = 0
                             WHERE id_balance_head = ?
                               AND `status` = 1', [$newBalQty, $newBalInQty, $newBalOutQty, $idHead]);
            } else {
                DB::update('UPDATE t_balance_header
                               SET qty = ?,
                                   in_qty = ?,
                                   out_qty = ?
                             WHERE id_balance_head = ?
                               AND `status` = 1', [$newBalQty, $newBalInQty, $newBalOutQty, $idHead]);
            }

        /* DEACTIVATE TRACE HEADER */
        DB::update('UPDATE t_trace_header
                       SET `status` = 0,
                           `updated_by` = ?
                     WHERE to_trace_no = ?
                       AND `status` = 1', [$user, $traceNo]);
        /* DEACTIVATE ADJUSTMENT HEADER */
        DB::update('UPDATE t_adjustment_header
                       SET `status` = 0,
                           `updated_by` = ?
                     WHERE id_adjust_head = ?
                       AND `status` = 1', [$user, $id]);

        /* GET ADJUSTMENT DETAIL DATA */
        $datAdjustTail = DB::select('SELECT before_adjust, id_balance_tail, in_qty, out_qty, id_adjust_tail
                                       FROM t_adjustment_detail
                                      WHERE id_adjust_head = ?
                                        AND `status` = 1', [$id]);
        $lenAdjustTail = count($datAdjustTail);

        for ($i=0; $i < $lenAdjustTail; $i++){
            $idTail = $datAdjustTail[$i]->id_balance_tail;
            $idAdjustTail = $datAdjustTail[$i]->id_adjust_tail;
            $beforeAdjustTail = $datAdjustTail[$i]->before_adjust;
            $adjustInQtyTail = $datAdjustTail[$i]->in_qty;
            $adjustOutQtyTail = $datAdjustTail[$i]->out_qty;

            /* GET BALANCE DETAIL */
            $datBalTail = DB::select('SELECT in_qty, out_qty
                                        FROM t_balance_detail
                                       WHERE id_balance_tail = ?
                                         AND `status` = 1', [$idTail]);
            $balInQty = $datBalTail[0]->in_qty;
            $balOutQty = $datBalTail[0]->out_qty;

            DB::update('UPDATE t_adjustment_detail
                           SET `status` = 0,
                               `updated_by` = ?
                         WHERE id_adjust_tail = ?
                           AND `status` = 1', [$user, $idAdjustTail]);

            if ($flagStoreInit == 1){
                DB::update('UPDATE t_balance_detail
                               SET qty = ?,
                                   in_qty = ?,
                                   out_qty = ?,
                                   `status` = 0
                             WHERE id_balance_tail = ?', [$beforeAdjustTail, $balInQty-$adjustInQtyTail, $balOutQty-$adjustOutQtyTail, $idTail]);
            } else {
                DB::update('UPDATE t_balance_detail
                               SET qty = ?,
                                   in_qty = ?,
                                   out_qty = ?
                             WHERE id_balance_tail = ?', [$beforeAdjustTail, $balInQty-$adjustInQtyTail, $balOutQty-$adjustOutQtyTail, $idTail]);
            }

        }
        DB::update('UPDATE t_trace_detail
                       SET `status` = 0,
                           `updated_by` = ?
                     WHERE id_trace_head = ?
                       AND `status` = 1', [$user, $idTraceHead]);

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function post_destroyAdjustmentWhx($id, $user){
        /* GET ADJUSTMENT HEADER DATA */
        $dat = DB::select('SELECT a.before_adjust, a.id_balance_head, a.in_qty, a.out_qty, a.adjust_no, b.id_trace_head
                             FROM t_adjustment_header a
                             LEFT JOIN t_trace_header b
                               ON b.to_trace_no = a.adjust_no AND b.status = 1
                            WHERE a.id_adjust_head = ?
                              AND a.`status` = 1', [$id]);
        $idHead = $dat[0]->id_balance_head;
        $beforeAdjust = $dat[0]->before_adjust;
        $adjustInQty = $dat[0]->in_qty;
        $adjustOutQty = $dat[0]->out_qty;
        $traceNo = $dat[0]->adjust_no;
        $idTraceHead = $dat[0]->id_trace_head;

        /* GET BALANCE HEADER DATA */
        $datHead = DB::select('SELECT qty, in_qty, out_qty
                                 FROM t_warehouse_header
                                WHERE id_whx_head = ?
                                  AND `status` = 1', [$idHead]);

        /* UPDATE BALANCE HEADER */
        $newBalQty = $beforeAdjust;
        $newBalInQty = $datHead[0]->in_qty - $adjustInQty;
        $newBalOutQty = $datHead[0]->out_qty - $adjustOutQty;

        /* NEW ROUTE TO DEACTIVATE STORAGE INIT */
            $flagStoreInitDat = DB::select('SELECT IFNULL(a.from_trace_no, 1) AS `init`
                                              FROM t_trace_header a
                                             WHERE a.id_trace_head = ?
                                               AND a.status = 1', [$idTraceHead]);
            $flagStoreInit = $flagStoreInitDat[0]->init;
            if ($flagStoreInit == 1){
                DB::update('UPDATE t_warehouse_header
                               SET qty = ?,
                                   in_qty = ?,
                                   out_qty = ?,
                                   `status` = 0
                             WHERE id_whx_head = ?
                               AND `status` = 1', [$newBalQty, $newBalInQty, $newBalOutQty, $idHead]);
            } else {
                DB::update('UPDATE t_warehouse_header
                               SET qty = ?,
                                   in_qty = ?,
                                   out_qty = ?
                             WHERE id_whx_head = ?
                               AND `status` = 1', [$newBalQty, $newBalInQty, $newBalOutQty, $idHead]);
            }

        /* DEACTIVATE TRACE HEADER */
        DB::update('UPDATE t_trace_header
                       SET `status` = 0,
                           `updated_by` = ?
                     WHERE to_trace_no = ?
                       AND `status` = 1', [$user, $traceNo]);
        /* DEACTIVATE ADJUSTMENT HEADER */
        DB::update('UPDATE t_adjustment_header
                       SET `status` = 0,
                           `updated_by` = ?
                     WHERE id_adjust_head = ?
                       AND `status` = 1', [$user, $id]);

        /* GET ADJUSTMENT DETAIL DATA */
        $datAdjustTail = DB::select('SELECT before_adjust, id_balance_tail, in_qty, out_qty, id_adjust_tail
                                       FROM t_adjustment_detail
                                      WHERE id_adjust_head = ?
                                        AND `status` = 1', [$id]);
        $lenAdjustTail = count($datAdjustTail);

        for ($i=0; $i < $lenAdjustTail; $i++){
            $idTail = $datAdjustTail[$i]->id_balance_tail;
            $idAdjustTail = $datAdjustTail[$i]->id_adjust_tail;
            $beforeAdjustTail = $datAdjustTail[$i]->before_adjust;
            $adjustInQtyTail = $datAdjustTail[$i]->in_qty;
            $adjustOutQtyTail = $datAdjustTail[$i]->out_qty;

            /* GET WAREHOUSE DETAIL */
            $datBalTail = DB::select('SELECT in_qty, out_qty
                                        FROM t_warehouse_detail
                                       WHERE id_whx_tail = ?
                                         AND `status` = 1', [$idTail]);
            $balInQty = $datBalTail[0]->in_qty;
            $balOutQty = $datBalTail[0]->out_qty;

            DB::update('UPDATE t_adjustment_detail
                           SET `status` = 0,
                               `updated_by` = ?
                         WHERE id_adjust_tail = ?
                           AND `status` = 1', [$user, $idAdjustTail]);

            if ($flagStoreInit == 1){
                DB::update('UPDATE t_warehouse_detail
                               SET qty = ?,
                                   in_qty = ?,
                                   out_qty = ?,
                                   `status` = 0
                             WHERE id_whx_tail = ?', [$beforeAdjustTail, $balInQty-$adjustInQtyTail, $balOutQty-$adjustOutQtyTail, $idTail]);
            } else {
                DB::update('UPDATE t_warehouse_detail
                               SET qty = ?,
                                   in_qty = ?,
                                   out_qty = ?
                             WHERE id_whx_tail = ?', [$beforeAdjustTail, $balInQty-$adjustInQtyTail, $balOutQty-$adjustOutQtyTail, $idTail]);
            }

        }
        DB::update('UPDATE t_trace_detail
                       SET `status` = 0,
                           `updated_by` = ?
                     WHERE id_trace_head = ?
                       AND `status` = 1', [$user, $idTraceHead]);

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }

    static function post_adjEntrySupplier($user, $request){
        $flag = $request->input('flag');
        $mode = $request->input('mode');
        $adjNumber = $request->input('adjNumber');
        $idSupplier = $request->input('idSupplier');
        $qty = $request->input('qty');
        $idHead = $request->input('idHead');
        $idTail = $request->input('idTail');
        $batchSap = $request->input('batchSap');
        $idMaterial = $request->input('idMaterial');
        $qty = floatval(str_replace(',', '', $qty));

        if ($mode == 'ADD'){
            $db = DB::insert('INSERT INTO t_balance_temporary
                                    (entry_no, id_supplier, id_material, qty, batch_sap, created_by)
                            VALUES (?, ?, ?, ?, ?, ?)',
                            [$adjNumber, $idSupplier, $idMaterial, $qty, $batchSap, $user]);
            $db = [ (object)['response' => $db ? 1 : 0 ]];

        } elseif ($mode == 'UPDATE'){

            $id_material = $idMaterial;

            $flag = DB::select('SELECT COUNT(a.id_balance_head) AS dat
                                  FROM t_balance_detail a
                                 WHERE a.id_balance_tail = ?
                                   AND a.status = 1', [$idTail]);

            if ($flag[0]->dat > 0){
                $dat = DB::select('SELECT id_supplier, qty, id_material
                                     FROM t_balance_detail
                                    WHERE id_balance_tail = ?', [$idTail]);
                $idSupplier_old = $dat[0]->id_supplier;
                $qty_old = $dat[0]->qty;
                $idMaterial_old = $dat[0]->id_material;

                /* LOGGING */
                DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'UPDATE', 'IDHEAD: ' . $idHead . ' IDTAIL: ' . $idTail . ' | ID_SUPPLIER: ' .
                                                    $idSupplier_old . ' >>> ' . $idSupplier . ' / QTY: ' . $qty_old . ' >>>' . $qty .
                                                    $idMaterial_old . ' >>> ' . $idMaterial .
                                                    ' | Status: 1', $user ]);

                DB::update('UPDATE t_trace_detail
                               SET id_supplier = ?,
                                   id_material = ?,
                                   in_qty = ?,
                                   batch_sap = ?,
                                   updated_by = ?
                             WHERE id_balance_tail = ?', [$idSupplier, $idMaterial, $qty, $batchSap, $user, $idTail]);

                $db = DB::update('UPDATE t_balance_detail
                                     SET id_supplier = ?,
                                         id_material = ?,
                                         qty = ?,
                                         init_qty = ?,
                                         batch_sap = ?,
                                         updated_by = ?
                                   WHERE id_balance_tail = ?', [$idSupplier, $idMaterial, $qty, $qty, $batchSap, $user, $idTail]);
                $db = [ (object)['response' => $db ] ];

            } else {
                $flag = DB::select('SELECT COUNT(a.id_balance_head) AS dat
                                      FROM t_balance_detail a
                                     WHERE a.id_supplier = ?
                                       AND a.status = 1
                                       AND a.id_balance_head = ?', [$idSupplier, $idHead]);

                if ($flag[0]->dat > 0){
                    $dat = DB::select('SELECT id_supplier, qty, id_balance_tail, batch_sap, id_material
                                         FROM t_balance_detail
                                        WHERE id_supplier = ?
                                          AND `status` = 1', [$idSupplier]);
                    $idSupplier_old = $dat[0]->id_supplier;
                    $qty_old = $dat[0]->qty;
                    $idTail = $dat[0]->id_balance_tail;
                    $batchSap_old = $dat[0]->batch_sap;
                    $idMaterial_old = $dat[0]->id_material;

                    /* LOGGING */
                    DB::insert('INSERT INTO log_transactions
                                       (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'UPDATE', 'IDHEAD: ' . $idHead . ' IDTAIL: ' . $idTail . ' | ID_SUPPLIER: ' .
                                                        $idSupplier_old . ' >>> ' . $idSupplier . ' / QTY: ' . $qty_old . ' >>>' . $qty .
                                                        ' / BATCH_SAP: ' . $batchSap_old . ' >>> ' . $batchSap .
                                                        ' / ID_MATERIAL: ' . $idMaterial_old . ' >>> ' . $idMaterial .
                                                        ' | Status: 1', $user ]);

                    DB::update('UPDATE t_trace_detail
                                   SET id_material = ?,
                                       in_qty = ?,
                                       batch_sap = ?,
                                       updated_by = ?
                                 WHERE id_balance_tail = ?', [$idMaterial, $qty, $batchSap, $user, $idTail]);

                    $db = DB::update('UPDATE t_balance_detail
                                         SET id_material = ?,
                                             qty = ?,
                                             init_qty = ?,
                                             batch_sap = ?,
                                             updated_by = ?
                                       WHERE id_supplier = ?
                                         AND id_balance_head = ?', [$idMaterial, $qty, $qty, $batchSap, $user, $idSupplier, $idHead]);
                    $db = [ (object)['response' => 1 ]];

                } else {
                    $idTail = DB::table('t_balance_detail')->insertGetId([
                                'id_balance_head' => $idHead,
                                'id_supplier' => $idSupplier,
                                'id_material' => $idMaterial,
                                'qty' => $qty,
                                'init_qty' => $qty,
                                'batch_sap' => $batchSap,
                                'created_by' => $user,
                                'updated_by' => $user,
                    ]);

                    $dat = DB::select('SELECT id_trace_head
                                         FROM t_trace_header
                                        WHERE id_balance_head = ?', [$idHead]);
                    $idTraceHead = $dat[0]->id_trace_head;

                    $idTraceTail = DB::table('t_trace_detail')->insertGetId([
                        'id_trace_head' => $idTraceHead,
                        'id_balance_tail' => $idTail,
                        'id_supplier' => $idSupplier,
                        'id_material' => $idMaterial,
                        'in_qty' => $qty,
                        'batch_sap' => $batchSap,
                        'created_by' => $user,
                        'updated_by' => $user,
                    ]);
                    $db = [ (object)['response' => 1 ]];

                    /* LOGGING */
                    DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'UPDATE', 'IDHEAD: ' . $idHead . ' IDTAIL: ' . $idTail .
                                                        ' | ID_SUPPLIER: ' . $idSupplier . ' / QTY: ' . $qty . ' / BATCH_SAP: ' . $batchSap .
                                                        ' | Status: 1', $user ]);
                }
            }

            $dat = DB::select('SELECT SUM(a.init_qty) AS qty
                                 FROM t_balance_detail a
                                WHERE a.id_balance_head = ?
                                  AND a.status = 1', [$idHead]);
            $new_total_qty = $dat[0]->qty;
            DB::update('UPDATE t_balance_header
                           SET init_qty = ?,
                               qty = ?,
                               updated_by = ?
                         WHERE id_balance_head = ?', [$new_total_qty, $new_total_qty, $user, $idHead]);
            DB::update('UPDATE t_trace_header
                           SET in_qty = ?,
                               updated_by = ?
                         WHERE id_balance_head = ?', [$new_total_qty, $user, $idHead]);
        }

        return $db;
    }
    static function deleteSupplier($id, $user){
        DB::delete('DELETE FROM t_balance_temporary
                     WHERE id_balance_temp = ?', [$id]);

        $db = [ (object)['response' => 1 ]];
        return $db;
    }

    static function post_adjustmentInit($user, $mode, $idHead, $entry_no, $entry_date, $id_tank, $qty,
                                        $id_material, $materialDoc){

        /* CEK SUPPLIER ENTRY */
            $dat = DB::select('SELECT id_supplier, qty AS qty_tail, batch_sap
                                 FROM t_balance_temporary
                                WHERE entry_no = ?', [$entry_no]);
            if (count($dat) == 0){
                $db = [ (object)['response' => 6 ]];
                return $db;
            }

        /* UPDATE BATCH NUMBER FOR FEED ID */
            $batch_moveType = substr($entry_no, 0, 1);
            $batch_entryDate = substr($entry_no, 1, 6);
            $batch_sequence = substr($entry_no, -2);
            $datMatl = DB::select('SELECT a.id_rundown, a.type
                                     FROM m_material a
                                    WHERE a.id_material = ?', [$id_material]);
            $batch_id = $datMatl[0]->id_rundown;
            $type = $datMatl[0]->type;

            if ($type == "RM"){
                $new_entry_no = $batch_moveType . $batch_entryDate . $batch_id . $batch_sequence;
            } else {
                $new_entry_no = $batch_moveType . $batch_entryDate . $batch_id . $batch_sequence;
            }

        if ($mode == 'ADD'){
            $idHead = DB::table('t_balance_header')->insertGetId([
                            'trace_no' => $new_entry_no,
                            'id_material' => $id_material,
                            'id_tank' => $id_tank,
                            'entry_date' => $entry_date,
                            'qty' => $qty,
                            'in_qty' => $qty,
                            'init_qty' => $qty,
                            'created_by' => $user,
                        ]);
            $idTraceHead = DB::table('t_trace_header')->insertGetId([
                                'to_trace_no' => $new_entry_no,
                                'id_balance_head' => $idHead,
                                'id_material' => $id_material,
                                'entry_date' => $entry_date,
                                'id_sloc' => $id_tank,
                                'in_qty' => $qty,
                                'created_by' => $user,
                        ]);

            /* INSERT INTO ADJUSTMENT HEADER */
                $idAdjustHead = DB::table('t_adjustment_header')->insertGetId([
                    'entry_date' => $entry_date,
                    'adjust_no' => $new_entry_no,
                    'id_balance_head' => $idHead,
                    'id_material' => $id_material,
                    'id_tank' => $id_tank,
                    'in_qty' => $qty,
                    'before_adjust' => 0,
                    'after_adjust' => $qty,
                    'created_by' => $user
                ]);

            /* LOGGING */
                DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_ADJUST_HEAD', 'ADD ADJUST', 'IDADJUSTHEAD: ' . $idAdjustHead . ' | IDHEAD: ' . $idHead . ' | DATE: ' . $entry_date .
                                                ' / MATERIAL: ' . $id_material . ' / IN_QTY: ' . $qty  .  ' / OUT_QTY: 0' .
                                                ' / BEFORE_ADJUST: 0 / AFTER_ADJUST: ' . $qty .
                                                ' | Status: 1', $user ]);

            /* GET SUPPLIER & INSERT IN BALANCE_TAIL */
                $dat = DB::select('SELECT id_supplier, qty AS qty_tail, batch_sap
                                    FROM t_balance_temporary
                                    WHERE entry_no = ?', [$entry_no]);
                foreach ($dat as $record) {
                    $id_supplier = $record->id_supplier;
                    $qty_tail = $record->qty_tail;
                    $batchSap = $record->batch_sap;
                    $idTail = DB::table('t_balance_detail')->insertGetId([
                                        'id_balance_head' => $idHead,
                                        'id_supplier' => $id_supplier,
                                        'id_material' => $id_material,
                                        'qty' => $qty_tail,
                                        'in_qty' => $qty_tail,
                                        'init_qty' => $qty_tail,
                                        'batch_sap' => $batchSap,
                                        'created_by' => $user
                                    ]);
                    $idTraceTail = DB::table('t_trace_detail')->insertGetId([
                                        'id_trace_head' => $idTraceHead,
                                        'id_balance_tail' => $idTail,
                                        'id_supplier' => $id_supplier,
                                        'id_material' => $id_material,
                                        'batch_sap' => $batchSap,
                                        'in_qty' => $qty_tail,
                                        'created_by' => $user
                                    ]);
                    $idAdjustTail = DB::table('t_adjustment_detail')->insertGetId([
                                        'id_adjust_head' => $idAdjustHead,
                                        'id_balance_tail' => $idTail,
                                        'id_supplier' => $id_supplier,
                                        'id_material' => $id_material,
                                        'batch_sap' => $batchSap,
                                        'in_qty' => $qty_tail,
                                        'out_qty' => 0,
                                        'before_adjust' => 0,
                                        'after_adjust' => $qty_tail,
                                        'created_by' => $user
                                    ]);

                    /* LOGGING */
                    DB::insert('INSERT INTO log_transactions
                                    (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'ADD', 'IDHEAD: ' . $idHead . ' IDTAIl: ' . $idTail . ' | DATE: ' . $entry_date . ' / BATCH: ' .
                                                        $entry_no . ' >>> ' . $new_entry_no . ' / TANK: ' . $id_tank . ' / SUPPLIER: ' . $id_supplier . ' / QTY_TAIL: ' . $qty_tail . ' / BATCH_SAP: ' . $batchSap .
                                                        ' | Status: 1', $user ]);
                }

                DB::delete('DELETE FROM t_balance_temporary
                            WHERE entry_no = ?', [$entry_no]);

                DB::insert('INSERT INTO t_material_document
                                (id_trace_head, material_document, created_by)
                            VALUES (?, ?, ?)', [$idTraceHead, $materialDoc, $user]);

        }
        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function post_adjustmentInitWhx($user, $request){
        $flag = $request->input('flag');
        $mode = $request->input('mode');
        $idHead = $request->input('idHead');
        $entry_no = $request->input('entry_no');
        $entry_date = $request->input('entry_date');
        $id_tank = $request->input('tank');
        $qty = $request->input('qty');
        $id_material = $request->input('idMaterial');
        $qty = floatval(str_replace(',', '', $qty));
        $materialDoc = $request->input('material_doc');
        $id_tank = $request->input('tank');
        $po_no = $request->input('po_no');
        $batch_no = $request->input('batch_no');

        /* CEK SUPPLIER ENTRY */
            $dat = DB::select('SELECT id_supplier, qty AS qty_tail, batch_sap
                                 FROM t_balance_temporary
                                WHERE entry_no = ?', [$entry_no]);
            if ($dat == 0){
                $db = [ (object)['response' => 6 ]];
                return $db;
            }

        /* UPDATE BATCH NUMBER FOR FEED ID */
            $batch_moveType = substr($entry_no, 0, 1);
            $batch_entryDate = substr($entry_no, 1, 6);
            $batch_sequence = substr($entry_no, -2);

            $batch_id = sprintf("%02d", $id_tank);

            $new_entry_no = $batch_moveType . $batch_entryDate . $batch_id . $batch_sequence;

        if ($mode == 'ADD'){
            $idHead = DB::table('t_warehouse_header')->insertGetId([
                            'trace_no' => $new_entry_no,
                            'id_material_fg' => $id_material,
                            'id_section' => $batch_id,
                            'entry_date' => $entry_date,
                            'batch_no' => $batch_no,
                            'po_no' => $po_no,
                            'qty' => $qty,
                            'in_qty' => $qty,
                            'init_qty' => $qty,
                            'created_by' => $user,
                        ]);
            $idTraceHead = DB::table('t_trace_header')->insertGetId([
                            'to_trace_no' => $new_entry_no,
                            'id_balance_head' => $idHead,
                            'id_material' => $id_material,
                            'entry_date' => $entry_date,
                            'id_sloc' => $id_tank,
                            'in_qty' => $qty,
                            'created_by' => $user,
                        ]);

            /* INSERT INTO ADJUSTMENT HEADER */
            $idAdjustHead = DB::table('t_adjustment_header')->insertGetId([
                'entry_date' => $entry_date,
                'adjust_no' => $new_entry_no,
                'id_balance_head' => $idHead,
                'id_material' => $id_material,
                'id_tank' => $id_tank,
                'in_qty' => $qty,
                'before_adjust' => 0,
                'after_adjust' => $qty,
                'created_by' => $user
            ]);

            /* LOGGING */
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_WHX_HEAD', 'ADD', 'IDHEAD: ' . $idHead . ' | DATE: ' . $entry_date . ' / BATCH: ' .
                                                $entry_no . ' >>> ' . $new_entry_no . ' / TANK: ' . $id_tank . ' / QTY: ' . $qty . ' / MATERIAL: ' . $id_material .
                                                ' | Status: 1', $user ]);

            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_ADJUST_HEAD', 'ADD ADJUST', 'IDADJUSTHEAD: ' . $idAdjustHead . ' | IDHEAD: ' . $idHead . ' | DATE: ' . $entry_date .
                                                ' / MATERIAL: ' . $id_material . ' / IN_QTY: ' . $qty  .  ' / OUT_QTY: 0' .
                                                ' / BEFORE_ADJUST: 0 / AFTER_ADJUST: ' . $qty .
                                                ' | Status: 1', $user ]);

            /* GET SUPPLIER & INSERT IN BALANCE_TAIL */
            $dat = DB::select('SELECT id_supplier, qty AS qty_tail, batch_sap
                                 FROM t_balance_temporary
                                WHERE entry_no = ?', [$entry_no]);

            foreach ($dat as $record) {
                $id_supplier = $record->id_supplier;
                $qty_tail = $record->qty_tail;
                $batchSap = $record->batch_sap;
                $idTail = DB::table('t_warehouse_detail')->insertGetId([
                                    'id_whx_head' => $idHead,
                                    'id_supplier' => $id_supplier,
                                    'id_material_fg' => $id_material,
                                    'qty' => $qty_tail,
                                    'in_qty' => $qty_tail,
                                    'init_qty' => $qty_tail,
                                    'batch_sap' => $batchSap,
                                    'created_by' => $user
                                ]);
                $idTraceTail = DB::table('t_trace_detail')->insertGetId([
                                    'id_trace_head' => $idTraceHead,
                                    'id_balance_tail' => $idTail,
                                    'id_supplier' => $id_supplier,
                                    'id_material' => $id_material,
                                    'batch_sap' => $batchSap,
                                    'in_qty' => $qty_tail,
                                    'created_by' => $user
                                ]);
                $idAdjustTail = DB::table('t_adjustment_detail')->insertGetId([
                                    'id_adjust_head' => $idAdjustHead,
                                    'id_balance_tail' => $idTail,
                                    'id_supplier' => $id_supplier,
                                    'id_material' => $id_material,
                                    'batch_sap' => $batchSap,
                                    'in_qty' => $qty_tail,
                                    'out_qty' => 0,
                                    'before_adjust' => 0,
                                    'after_adjust' => $qty_tail,
                                    'created_by' => $user
                                ]);

                /* LOGGING */
                DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_WHX_TAIL', 'ADD', 'IDHEAD: ' . $idHead . ' IDTAIl: ' . $idTail . ' | DATE: ' . $entry_date . ' / BATCH: ' .
                                                    $entry_no . ' >>> ' . $new_entry_no . ' / TANK: ' . $id_tank . ' / SUPPLIER: ' . $id_supplier . ' / QTY_TAIL: ' . $qty_tail . ' / BATCH_SAP: ' . $batchSap .
                                                    ' | Status: 1', $user ]);
            }

            DB::delete('DELETE FROM t_balance_temporary
                        WHERE entry_no = ?', [$entry_no]);

            DB::insert('INSERT INTO t_material_document
                               (id_trace_head, material_document, created_by)
                        VALUES (?, ?, ?)', [$idTraceHead, $materialDoc, $user]);
        }

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }

    static function get_adjustmentPeriodHeader_dt(){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $db = DB::select('SELECT a.id_report_head, a.`period`, a.batch_sap, a.adjust_status,
                                 a.`status`, a.created_at, a.updated_at, a.lock_status,
                                 IF(COUNT(b.id_report_tail) > 0, 1, 0) AS uploaded_file
                            FROM t_report_pspa_head a
                            LEFT JOIN t_report_pspa_tail b
                              ON a.id_report_head = b.id_report_head
                           WHERE a.`status` = 1
                           GROUP BY a.id_report_head
                           ORDER BY a.`period` DESC');
        return $db;
    }
    static function post_destroyAdjustmentPeriod($id, $user){
        DB::update('UPDATE t_report_pspa_head
                       SET `status` = 0,
                           `updated_by` = ?
                     WHERE id_report_head = ?
                       AND `status` = 1', [$user, $id]);

        DB::update('UPDATE t_report_pspa_tail
                       SET `status` = 0,
                           `updated_by` = ?
                     WHERE id_report_head = ?
                       AND `status` = 1', [$user, $id]);

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function post_adjPeriodHeader($user, $request){
        $flag = $request->input('flag');
        $mode = $request->input('mode');
        $id = $request->input('id');
        $period = $request->input('period');
        $batch = $request->input('batch');

        if ($mode == 'ADD'){
            /* CHECK SAME ACTIVE PERIOD */
            $flag = DB::select('SELECT COUNT(`period`)
                                  FROM t_report_pspa_head
                                 WHERE `status` = 1
                                   AND `period` = ?
                                ', [$period]);
            if ($flag == 1){
                $db = [ (object)['response' => 2 ]];
                return $db;
            }

            $idHead = DB::table('t_report_pspa_head')->insertGetId([
                'period' => $period,
                'batch_sap' => $batch,
                'created_by' => $user,
            ]);

            /* LOGGING */
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_PSPA_HEAD', 'ADD', 'ID: ' . $idHead . ' | PERIOD: ' . $period . ' / BATCH: ' . $batch .
                                                ' | Status: 1', $user ]);

        } elseif ($mode == 'UPDATE'){

            $dat = DB::select('SELECT batch_sap
                                 FROM t_report_pspa_head
                                WHERE id_report_head = ?',[$id]);
            $old_batch = $dat[0]->batch_sap;

            DB::update('UPDATE t_report_pspa_head
                           SET batch_sap = ?,
                               updated_by = ?
                         WHERE id_report_head = ?',
                         [$batch, $user, $id]);

            /* LOGGING */
            DB::insert('INSERT INTO log_transactions
                               (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'T_PSPA_HEAD', 'UPDATE', 'ID: ' . $id . ' | PERIOD: ' . $period . ' / BATCH: ' . $old_batch . ' >>> ' . $batch .
                                                ' | Status: 1', $user ]);
        }

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }

    static function get_adjPeriodHeader_integrityCheck($tfNumber, $materialCode){
        $flag_idSloc = DB::select('SELECT COUNT(id_tank) AS `data`
                                     FROM m_tank_detail
                                    WHERE `status` = 1
                                      AND tf_number = ?', [$tfNumber]);
        $flag_idMaterial = DB::select(' SELECT COUNT(id_material) AS `data`
                                        FROM m_material
                                        WHERE `status` = 1
                                        AND code_noneudr = ?', [$materialCode]);

        /* THROW OUTPUT */
        $db = [ (object)['response' => 0, 'feature' => 0 ]];

        if ($flag_idSloc[0]->data > 0){
            if ($flag_idMaterial[0]->data > 0){
                $db = [ (object)['response' => 1 ]];
            } else {
                $db = [ (object)['response' => 0, 'feature' => 1 ]];
            }
        } else {
            $db = [ (object)['response' => 0, 'feature' => 2 ]];
        }

        return $db;
    }
    static function post_adjPeriodHeader_delete($user, $request){
        $idReportHead = $request->input('id');

        /* SET TO NON-ACTIVE EXISTING DATA */
            DB::update('UPDATE t_report_pspa_tail
                           SET `status` = 0,
                               updated_by = ?
                         WHERE id_report_head = ?
                           AND `status` = 1', [$user, $idReportHead]);

        /* THROW OUTPUT */
            $db = [ (object)['response' => 1]];
            return $db;

    }
    static function post_adjPeriodHeader_uploadExcel($user, $request, $plantSource, $tfNumber, $materialCode, $materialDescription,
                                                     $capacity, $sounding, $temperature, $volume, $density, $qty){
        $idReportHead = $request->input('id');

        $idSloc = DB::select('SELECT id_tank AS `data`
                                FROM m_tank_detail
                               WHERE `status` = 1
                                 AND tf_number = ?', [$tfNumber]);
        $idMaterial = DB::select('SELECT id_material AS `data`
                                    FROM m_material
                                   WHERE `status` = 1
                                     AND code_noneudr = ?', [$materialCode]);

        /* INSERT NEW DATA */
            if (!is_numeric($capacity)) {
                $capacity = 0;
            }
            if (!is_numeric($sounding)) {
                $sounding = 0;
            }
            if (!is_numeric($temperature)) {
                $temperature = 0;
            }
            if (!is_numeric($volume)) {
                $volume = 0;
            }
            if (!is_numeric($density)) {
                $density = 0;
            }
            if (!is_numeric($qty)) {
                $qty = 0;
            }

            DB::insert('INSERT INTO t_report_pspa_tail
                               (id_report_head, id_material, id_sloc, plant, tank, material_code, `description`,
                                capacity, sounding, temperature, volume, density, qty, created_by)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [$idReportHead, $idMaterial[0]->data, $idSloc[0]->data, $plantSource, $tfNumber,
                        $materialCode, $materialDescription, $capacity, $sounding, $temperature, $volume, $density, $qty, $user]);


        /* THROW OUTPUT */
            $db = [ (object)['response' => 1]];
            return $db;
    }

    static function get_adjPeriodView_dt($request){
        $idHead = $request->input('idHead');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.plant, a.tank, a.sloc, a.material, FORMAT(a.qty,3) AS qty, a.id_material,
                                 FORMAT(a.qty_data,3) AS qty_data, FORMAT(a.qty_data - a.qty,3) AS total,
                                 a.adj_type, a.adjust_number, a.adjust_status, a.created_at, a.updated_at,
                                 a.id_report_head, a.qty AS qty_pspa, a.id_sloc, a.qty_data AS qty_onhand,
                                 a.populated_at
                            FROM (SELECT a.plant, a.tank, b.`description` AS sloc, a.id_material,
                                         CONCAT(a.`description`, " (", a.material_code, ")") AS material,
                                         SUM(a.qty) AS qty, a.qty_data AS qty_data, a.adj_type,
                                         a.adjust_status, a.created_at, a.updated_at, a.adjust_number,
                                         a.id_report_head, a.id_sloc, a.populated_at
                                    FROM t_report_pspa_tail a
                                    LEFT JOIN m_tank b
                                      ON a.id_sloc = b.id_tank
                                   WHERE a.`status` = 1
                                     AND a.id_report_head = ?
                                   GROUP BY a.material_code, a.id_sloc) a', [$idHead]);

        return $db;
    }
    static function post_adjPeriodView_onHand($user, $request, $idMaterial, $plant, $idSloc, $qty){
        $idHead = $request->input('idHead');
        $idPlant = '1002';

        /* GET END OF PERIOD */
            $datHead = DB::select('SELECT `period`
                                    FROM t_report_pspa_head
                                    WHERE `status` = 1
                                    AND id_report_head = ?
                                ', [$idHead]);
            $closingDate = $datHead[0]->period;

        /* GET ON-HAND IN CLOSING DATE */
            $totalOnHand = DB::select('SELECT IFNULL(SUM(d.`balance`),0) AS `qty`
                                         FROM m_material z
                                         LEFT JOIN (SELECT a.code, a.id_material
                                                      FROM m_material a
                                                     WHERE a.status = 1) a
                                           ON z.code = a.code
                                         LEFT JOIN (SELECT bb.id_material, IFNULL(SUM(bb.`balance`),0) AS balance
                                                      FROM m_tank bbb
                                                      LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, IFNULL(b.balance,0) AS balance
                                                                   FROM t_balance_header bb
                                                                   LEFT JOIN (SELECT b.id_balance_head, b.id_material, b.id_trace_head,
                                                                                     SUM(b.in_qty) - SUM(b.out_qty) AS `balance`
                                                                                FROM t_trace_header b
                                                                               WHERE b.`status` = 1
                                                                                 AND b.entry_date <= ?
                                                                               GROUP BY b.id_balance_head, b.id_material
                                                                            ) b
                                                                    ON b.id_balance_head = bb.id_balance_head AND b.id_material = bb.id_material
                                                                 WHERE bb.status = 1
                                                                   AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 2 OR
                                                                                                          SUBSTRING(bb.trace_no,1,1) = 7 OR
                                                                        SUBSTRING(bb.trace_no,1,1) = 8 OR SUBSTRING(bb.trace_no,1,1) = 9)
                                                               ) bb
                                                      ON bbb.id_tank = bb.id_tank
                                                   WHERE bbb.id_plant = ?
                                                     AND bbb.id_tank = ?
                                                   GROUP BY bb.id_material
                                                 ) d
                                              ON a.id_material = d.id_material
                                           WHERE SUBSTRING(z.qtf_rundown,1,3) <> "BLE"
                                             AND SUBSTRING(z.qtf_rundown,1,3) <> "TRA"
                                             AND z.id_material = ?
                                        ', [$closingDate, $idPlant, $idSloc, $idMaterial]);
            $qtyData = $totalOnHand[0]->qty;
            $dateTime = new \DateTime();
            $dateTime->setTimezone(new \DateTimeZone('Asia/Jakarta'));
            $populatedAt = $dateTime->format('Y-m-d H:i:s');

            $reconQty = $qtyData - $qty;
            if ($reconQty > 0){
                $adjType = 'OUT';
            } elseif ($reconQty < 0){
                $adjType = 'IN';
            } else {
                $adjType = '-';
            }

        /* UPDATE TO T_REPORT_PSPA_TAIL */
            /* GET ADJUST_STATUS */
            $datAdjust = DB::select('SELECT a.adjust_status
                                       FROM t_report_pspa_tail a
                                      WHERE a.status = 1
                                        AND a.id_material = ?
                                        AND a.id_sloc = ?
                                        AND a.id_report_head = ?
                                    ', [$idMaterial, $idSloc, $idHead]);
            $adjustStatus = $datAdjust[0]->adjust_status;

            if ($adjustStatus == 0){
                DB::update('UPDATE t_report_pspa_tail a
                               SET a.qty_data = ?,
                                   a.populated_at = ?,
                                   a.adj_type = ?,
                                   a.updated_by = ?
                             WHERE a.status = 1
                               AND a.id_material = ?
                               AND a.id_sloc = ?
                               AND a.id_report_head = ?
                            ', [$qtyData, $populatedAt, $adjType, $user, $idMaterial, $idSloc, $idHead]);
            }

        /* THROW OUTPUT */
            $db = [ (object)['response' => 1]];
            return $db;
    }
    static function post_adjPeriodView_adjustment($user, $request, $idMaterial, $plant, $idSloc, $qty, $qtyOnhand, $adjType, $adjStatus){
        $idHead = $request->input('idHead');
        $idPlant = '1002';
        $materialDoc = "Period Adj";

        /* CHECK IF ON-HAND HAS DONE */
            $datOnHand = DB::select('   SELECT COUNT(a.id_report_head) AS flag
                                        FROM t_report_pspa_tail a
                                        WHERE a.status = 1
                                        AND a.populated_at IS NULL
                                        AND a.id_report_head = ?', [$idHead]);
            if ($datOnHand[0]->flag > 0){
                $db = [ (object)['response' => 8]];
                return $db;
            }
        /* RETURN UN-PROCESS ROUTE ADJUSTMENT */
            if ($adjStatus == 1){
                $db = [ (object)['response' => 1]];
                return $db;
            }
            if ($adjType == "-"){
                DB::update('UPDATE t_report_pspa_tail
                            SET adjust_number = NULL,
                                adjust_status = 1,
                                updated_by = ?
                            WHERE id_material = ?
                            AND id_report_head = ?
                            AND id_sloc = ?
                            AND `status` = 1',
                            [$user, $idMaterial, $idHead, $idSloc]);

                $db = [ (object)['response' => 1]];
                return $db;
            }

        /* GET ENTRY DATE AS ADJUSTMENT DATE */
            $datHead = DB::select('SELECT DATE_FORMAT(`period`, "%y%m%d") AS `period`
                                     FROM t_report_pspa_head
                                    WHERE `status` = 1
                                      AND id_report_head = ?
                                    ', [$idHead]);
            $entryDate = $datHead[0]->period;

        /* GET TRANSFER NUMBER */
            $batchNo = DB::select('SELECT a.entryNo
                                     FROM (SELECT CONCAT(7,?,a.id_rundown, LPAD(SUBSTRING(b.trace_no,10,2) + 1,2,0)) AS entryNo
                                             FROM m_material a
                                             LEFT JOIN t_balance_header b
                                               ON SUBSTRING(a.id_rundown,2,1) = SUBSTRING(b.trace_no, 9,1) AND b.status = 1
                                            WHERE a.id_material = ?
                                              AND SUBSTRING(b.trace_no, 1, 1) = 7
                                              AND SUBSTRING(b.trace_no, 2, 6) = ?
                                              AND a.status = 1
                                    ORDER BY SUBSTRING(b.trace_no,10,2) DESC
                                    LIMIT 1) a
                                    UNION ALL
                                   SELECT CONCAT("7", ?, IF(a.id_rundown <> "-", a.id_rundown, "00"), "01") AS entryNo
                                  FROM m_material a
                                 WHERE a.status = 1
                                   AND a.id_material = ?
                                 LIMIT 1
                                ', [$entryDate, $idMaterial, $entryDate, $entryDate, $idMaterial]);
            $entryNo = $batchNo[0]->entryNo;

        /* CALCULATE REQUIRED ADJUSTMENT FOR TRANFER */
            if ($adjType == "IN"){
                $trfQty = round($qty - $qtyOnhand, 3);
                $trfSource = 11;
                $trfDestination = $idSloc;

                $datAdjustNo = static::get_adjNewEntryNumber($entryDate);
                $adjNo = $datAdjustNo[0]->adj_number;
                $out = static::initSupplier_periodAdjustment($adjNo, $trfQty, $idMaterial, $user);
                if ($out[0]->response == 1){
                    static::post_adjustmentInit($user, 'ADD', $idHead, $adjNo, $entryDate, $trfSource, $trfQty, $idMaterial, null);

                    /* DO TRANSFER ADJUSTMENT */
                    $out = Transfer::post_transferEntry($user, $entryNo, $entryDate, $idMaterial, $materialDoc, $trfQty, $trfSource, $trfDestination);
                    $adjustNumber = $adjNo . ' >>> ' . $entryNo;
                }
            } elseif ($adjType == "OUT"){
                $trfQty = round($qtyOnhand - $qty, 3);
                $trfSource = $idSloc;
                $trfDestination = 10;

                /* DO TRANSFER ADJUSTMENT */
                $out = Transfer::post_transferEntry($user, $entryNo, $entryDate, $idMaterial, $materialDoc, $trfQty, $trfSource, $trfDestination);
                $adjustNumber = $entryNo;
            }

        /* UPDATE ADJ NUMBER TO DATA */
            if ($out[0]->response == 1){
                DB::update('UPDATE t_report_pspa_tail
                            SET adjust_number = ?,
                                adjust_status = 1,
                                updated_by = ?
                            WHERE id_material = ?
                            AND id_report_head = ?
                            AND id_sloc = ?
                            AND `status` = 1',
                            [$adjustNumber, $user, $idMaterial, $idHead, $idSloc]);
            } else {
                DB::update('UPDATE t_report_pspa_tail
                            SET adjust_number = "Manual Stock Init",
                                adjust_status = 2,
                                updated_by = ?
                            WHERE id_material = ?
                            AND id_report_head = ?
                            AND id_sloc = ?
                            AND `status` = 1',
                            [$user, $idMaterial, $idHead, $idSloc]);
            }

        /* UPDATE ADS STATUS IN HEADER */
            $adjData = DB::select(' SELECT COUNT(a.adjust_status) AS flag
                                    FROM t_report_pspa_tail a
                                    WHERE a.status = 1
                                    AND a.id_report_head = ?
                                    AND (a.adjust_status = 0 OR a.adjust_status = 2)
                                ', [$idHead]);
            if ($adjData[0]->flag == 0){
                DB::update('UPDATE t_report_pspa_head
                            SET adjust_status = 1
                            WHERE status = 1
                            AND id_report_head = ?', [$idHead]);
            }

        /* THROW OUTPUT */
            $db = [ (object)['response' => 1]];
            return $db;
    }
    static function initSupplier_periodAdjustment($entry_no, $trfQty, $idMaterial, $user){
        /* GET LAST BATCH SUPPLIER COMPOSITION */
            $datSupplier = DB::select(' SELECT a.id_balance_tail, a.id_balance_head, a.id_supplier, a.batch_sap, a.qty
                                        FROM t_balance_detail a
                                        WHERE a.id_balance_head = ( SELECT a.id_balance_head
                                                                    FROM t_balance_header a
                                                                    WHERE a.`status` = 1
                                                                    AND a.id_material = ?
                                                                    ORDER BY a.id_balance_head DESC
                                                                    LIMIT 1 )
                                        ', [$idMaterial]);
            $lenDatSupplier = count($datSupplier);

            if ($lenDatSupplier > 0){
                // Step 1: Calculate total qty
                    $totalQty = 0;
                    for ($i = 0; $i < $lenDatSupplier; $i++) {
                        $totalQty += $datSupplier[$i]->qty;
                    }
                // Step 2: Calculate % composition for each supplier
                    $supplierComposition = [];
                    for ($i = 0; $i < $lenDatSupplier; $i++) {
                        $id_balance_tail = $datSupplier[$i]->id_balance_tail;
                        $qty = $datSupplier[$i]->qty;
                        $idSupplier = $datSupplier[$i]->id_supplier;
                        $batchSap = $datSupplier[$i]->batch_sap;

                        // Calculate composition
                        if ($totalQty == 0){
                            $supplierCompQty = $trfQty / $lenDatSupplier;
                        } else {
                            $supplierCompQty = $trfQty * ($qty / $totalQty);
                        }
                        // Store the result
                        if (!isset($supplierComposition[$id_balance_tail])) {
                            $supplierComposition[$id_balance_tail] = 0;
                        }
                        $supplierComposition[$id_balance_tail] += $supplierCompQty;

                        $db = DB::insert('  INSERT INTO t_balance_temporary
                                                (entry_no, id_supplier, id_material, qty, batch_sap, created_by)
                                            VALUES (?, ?, ?, ?, ?, ?)',
                                            [$entry_no, $idSupplier, $idMaterial, $supplierCompQty, $batchSap, $user]);
                        $db = [ (object)['response' => $db ? 1 : 0 ]];
                    }
            } else {
                $db = [ (object)['response' => 2]];
            }

        /* RETURN FUNCTION */
        return $db;
    }

    static function get_adjustStatus($request){
        $idHead = $request->input('idHead');

        $db = DB::select(' SELECT COUNT(a.adjust_status) AS flag
                            FROM t_report_pspa_tail a
                            WHERE a.status = 1
                            AND a.id_report_head = ?
                            AND a.adjust_status = 1
                        ', [$idHead]);
        return $db;
    }
    static function post_adjPeriodHeader_lock($user, $request){
        $idHead = $request->input('idHead');
        $lockStatus = $request->input('lockStatus');

        DB::update('UPDATE t_report_pspa_head
                       SET lock_status = ?
                     WHERE id_report_head = ?',
                     [$lockStatus, $idHead]);

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1]];
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
}
