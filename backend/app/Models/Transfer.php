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

        $db = DB::select('SELECT ROUND(SUM(c.in_qty) - SUM(c.out_qty), 3) AS total
                            FROM m_material a
                            LEFT JOIN (SELECT b.code, b.id_material
                                         FROM m_material b
                                        WHERE b.status = 1) b
                              ON a.code = b.code
                            LEFT JOIN (SELECT c.id_material, c.in_qty, c.out_qty
                                         FROM t_trace_header c
                                        WHERE c.status = 1
                                          AND c.id_sloc = ?
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
        $db = DB::select('SELECT a.id_plant,
                                 COALESCE(p.code_2, p.code_3, a.id_plant) AS plant_code,
                                 COALESCE(p.description, p.code_2, a.id_plant) AS plant_name,
                                 a.entry_date, b.material_document, a.id_tank AS to_id_tank, a.id_tank_tail, t_to.description AS to_sloc_name, GROUP_CONCAT(DISTINCT h_to.tf_number ORDER BY h_to.tf_number) AS to_tf_number,
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
                            LEFT JOIN m_plant p
                              ON p.code_3 = a.id_plant
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

    static function post_transferEntry($user, $entryNo, $entryDate, $idMaterial, $materialDoc, $trfQty, $trfSource, $trfDestination, $trfSourceTail, $trfDestinationTail){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $srcPlant = DB::table('m_tank')->where('id_tank', $trfSource)->value('id_plant');
        $destPlant = DB::table('m_tank')->where('id_tank', $trfDestination)->value('id_plant');

        $srcTailJson = json_encode($trfSourceTail);
        $destTailJson = json_encode($trfDestinationTail);

        /* PRE-FLIGHT: VALIDATE SOURCE HAS SUPPLIER DETAIL BEFORE WRITING ANYTHING
         *
         * If ANY balance_head in the source tank has qty > 0 but NO t_balance_detail
         * rows, the transfer would cascade the "no supplier" problem to the destination.
         * Block the transfer early with a clear error (response 6) so the operator can
         * run the orphan diagnostic and repair before retrying.
         */
            $orphanHeads = DB::select(
                'SELECT bh.id_balance_head, bh.trace_no, bh.qty
                   FROM t_balance_header bh
                   LEFT JOIN t_balance_detail bd
                     ON bh.id_balance_head = bd.id_balance_head
                    AND bd.status = 1
                    AND bd.qty > "0.0001"
                  WHERE bh.status = 1
                    AND bh.qty > "0.0001"
                    AND bh.id_material = ?
                    AND bh.id_tank = ?
                    AND bh.id_plant = ?
                    AND bd.id_balance_tail IS NULL',
                [$idMaterial, $trfSource, $srcPlant]
            );

            if (count($orphanHeads) > 0) {
                $headIds = implode(', ', array_column($orphanHeads, 'id_balance_head'));
                \Log::warning('Transfer blocked: orphan balance heads found', [
                    'id_material'    => $idMaterial,
                    'id_tank_source' => $trfSource,
                    'orphan_heads'   => $headIds,
                ]);
                return [(object)['response' => 6]]; // 6 = orphan supplier data
            }

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
                    /* VARIABLE ADJUSTMENT */
                    if (substr($entryNo,7,3) == '000'){
                        $entry_no = substr_replace($entryNo, '1', 9, 1); /* REPLACE RUNDOWN_ID TO FEED_ID FOR RAW MATERIAL */
                    } else {
                        $entry_no = substr_replace($entryNo, '0', 8, 1); /* REPLACE RUNDOWN_ID TO FEED_ID FOR WIP*/
                    }
                    $curr_entryDate = $entryDate;

                    /* Wrap Feed + Rundown in one atomic transaction.
                     * If Feed succeeds but Rundown fails (or if supplier rows
                     * turn out to be empty despite the pre-flight check above),
                     * the whole transfer is rolled back — no partial deduction.
                     */
                    $transferResult = DB::transaction(function () use (
                        $trfQty, $idMaterial, $trfSource, $srcTailJson, $srcPlant,
                        $entry_no, $entryDate, $entryNo, $trfDestination, $destTailJson,
                        $destPlant, $user, $materialDoc
                    ) {
                        $feedResult = Feed::generalFeed([
                            'qty'          => $trfQty,
                            'id_material'  => $idMaterial,
                            'id_tank'      => $trfSource,
                            'id_tank_tail' => $srcTailJson,
                            'id_plant'     => $srcPlant,
                            'to_trace_no'  => $entry_no,
                            'entry_date'   => $entryDate,
                            'user'         => $user,
                        ]);

                        // Feed::generalFeed() now throws RuntimeException if any
                        // balance_head has no supplier detail — transaction auto-rollback.

                        // Aggregate supplier proportions from the feed's trace_detail rows.
                        $supplierRows = DB::select(
                            'SELECT id_supplier, batch_sap, SUM(out_qty) AS rundownSupplier
                               FROM t_trace_detail
                              WHERE status = 1
                                AND id_trace_head IN (
                                    SELECT id_trace_head
                                      FROM t_trace_header
                                     WHERE status = 1
                                       AND to_trace_no = ?
                                )
                              GROUP BY id_supplier, batch_sap',
                            [$entry_no]
                        );

                        // Guard: supplier rows must not be empty here.
                        // Pre-flight above should have caught this, but double-check
                        // inside the transaction to be certain.
                        if (empty($supplierRows)) {
                            throw new \RuntimeException(
                                'Transfer::post_transferEntry — Feed completed but ' .
                                'no t_trace_detail rows found for entry_no=' . $entry_no .
                                '. Supplier chain is broken. Transfer rolled back.'
                            );
                        }

                        $supplierRowsFormatted = array_map(fn($r) => [
                            'id_supplier'     => $r->id_supplier,
                            'batch_sap'       => $r->batch_sap,
                            'rundownSupplier' => $r->rundownSupplier,
                        ], $supplierRows);

                        // Actual qty deducted may differ from requested if some heads
                        // were partially consumed — use the real deducted qty.
                        $actualQty = round($feedResult['total_out'], 4);

                        if ($actualQty <= 0) {
                            throw new \RuntimeException(
                                'Transfer::post_transferEntry — Feed returned total_out=0 ' .
                                'for entry_no=' . $entry_no . '. Transfer rolled back.'
                            );
                        }

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

                        return $rundownResult;
                    });

                    if (!isset($transferResult['response']) || $transferResult['response'] != 1) {
                        return [(object)['response' => 3]];
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
        if (count($idTmp) === 2) {
            $idHead         = trim($idTmp[0]);
            $idTraceHead    = trim($idTmp[1]);
        } else {
            $idTraceHead    = trim($id);
            $traceRecord    = DB::selectOne('SELECT id_balance_head FROM t_trace_header WHERE id_trace_head = ? AND status = 1', [$idTraceHead]);
            if ($traceRecord) {
                $idHead = $traceRecord->id_balance_head;
            } else {
                return [(object)['response' => 98]];
            }
        }

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
