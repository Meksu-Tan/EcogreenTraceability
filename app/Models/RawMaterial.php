<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Helpers\Feed;
use App\Helpers\Rundown;

class RawMaterial extends Model
{
    protected $connection = 'eudr_ts';

    protected static $idTankSrc = "T000"; // STORAGE TANK
    protected static $movSeq = "000";
    protected static $typeMaterial = "RM";
    protected static $movType1 = "1";
    protected static $movType2 = "9";


    static function get_batchCode_bySupplier($request){
        $idSupplier = $request->input('idSupplier');

        $datSeq = DB::select('SELECT a.seq_no
                                FROM ( SELECT LPAD(SUBSTRING(a.batch_sap,7,2) + 1, 2,0) AS seq_no
                                         FROM t_balance_detail a
                                         LEFT JOIN t_balance_header b
                                           ON a.id_balance_head = b.id_balance_head
                                        WHERE a.status = 1
                                          AND SUBSTRING(a.batch_sap,1,6) = DATE_FORMAT(NOW(), "%y%m%d")
                                          AND SUBSTRING(b.trace_no,1,1) = 1
                                        ORDER BY SUBSTRING(a.batch_sap,1,8) DESC
                                        LIMIT 1) a
                                UNION ALL
                               SELECT "01" AS seq_no
                               LIMIT 1
                              ');
        $seqNo = $datSeq[0]->seq_no;

        $db = DB::select('SELECT CONCAT(DATE_FORMAT(NOW(), "%y%m%d"),?,"-",UCASE(a.batch_code)) AS batchCode
                            FROM m_supplier a
                           WHERE a.status = 1
                             AND a.id_supplier = ?
                        ', [$seqNo, $idSupplier]);

        return $db;
    }
    static function get_dtRmList($request){
        $idPlant = \App\Models\BaseModel::resolvePlant($request);
        $idTankStorage = DB::table('m_tank')
        ->where('status', 1)
        ->where('code_3', 'STORAGE')
        ->value('id_tank');
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.id_balance_head, a.id_material, a.id_tank, a.id_tank_tail, a.status,
                                 CAST(a.trace_no AS CHAR) AS trace_no, FORMAT(SUM(DISTINCT a.qty),3) AS qty, a.created_by, a.created_at,
                                 CONCAT(c.code, " :: ", c.description) AS material, FORMAT(SUM(DISTINCT a.init_qty),3) AS init_qty,
                                 CONCAT(d.description,
                                    IF(GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ", ") IS NULL,
                                        "",
                                        CONCAT(" | ", GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ", "))
                                    )
                                 ) AS tf_number, a.entry_date, b.batch_sap,
                                 GROUP_CONCAT(DISTINCT b.id_balance_tail SEPARATOR ",") AS id_balance_detail,
                                 GROUP_CONCAT(DISTINCT CONCAT(e.code, " :: ", e.description, " / ", b.batch_sap, " / Qty : ", FORMAT(b.init_qty, 3), " MT / ", IF(b.out_qty = 0, "-", "BATCH TRANSFERRED")) SEPARATOR " | ") AS supplier,
                                 IF(b.out_qty = 0, "N/A", "") AS traced, f.material_document, f.po_so, f.id_trace_head,
                                 FORMAT(bs.supplier_qty,3) AS balance_supplier
                            FROM t_balance_header a
                            LEFT JOIN t_balance_detail b
                              ON a.id_balance_head = b.id_balance_head AND b.status = 1
                            LEFT JOIN m_material c
                              ON a.id_material = c.id_material
                            LEFT JOIN m_tank d
                              ON a.id_tank = d.id_tank AND d.status = 1 AND (d.code_3 = "STORAGE" OR d.id_plant = ? OR ? = 0)
                            LEFT JOIN m_supplier e
                              ON e.id_supplier = b.id_supplier
                            LEFT JOIN (SELECT f.id_balance_head, g.material_document, g.po_so, f.id_trace_head
									     FROM t_trace_header f
									 	 LEFT JOIN t_material_document g
                                           ON f.id_trace_head = g.id_trace_head
                                        WHERE f.status = 1
										GROUP BY f.id_balance_head) f
                              ON f.id_balance_head = a.id_balance_head
                            LEFT JOIN m_tank_detail h
                              ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(h.id_tank_tail AS CHAR)))
                            LEFT JOIN (
                                SELECT id_balance_head, SUM(init_qty) AS supplier_qty
                                FROM t_balance_detail
                                WHERE status = 1
                                GROUP BY id_balance_head
                            ) bs ON bs.id_balance_head = a.id_balance_head
                           WHERE c.type = ?
                             AND (SUBSTRING(a.trace_no,1,1) = ? OR SUBSTRING(a.trace_no,1,1) = ?)
                             AND SUBSTRING(a.trace_no,8,3) = ?
                             AND a.status = 1
                             AND (a.id_plant = ? OR ? = 0)
                             AND a.id_tank = ?
                           GROUP BY a.trace_no
                           ORDER BY a.id_balance_head DESC
                           ', [$idPlant, $idPlant, self::$typeMaterial, self::$movType1, self::$movType2, self::$movSeq, $idPlant, $idPlant, $idTankStorage]);
        return $db;
    }
    static function get_dtRmListTrf($request){
        $idPlant = \App\Models\BaseModel::resolvePlant($request);
        $idTankFeed = DB::table('m_tank')
            ->where('id_plant', $idPlant)
            ->where('status', 1)
            ->where('code_3', 'FEED')
            ->value('id_tank');
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.id_balance_head, a.id_material, a.id_tank, a.id_tank_tail, a.status,
                                 aa.qty, aa.init_qty, a.created_by, a.created_at, CAST(a.trace_no AS CHAR) AS trace_nos,
                                 GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                 CONCAT(d.description,
                                    IF(GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ", ") IS NULL,
                                        "",
                                        CONCAT(" | ", GROUP_CONCAT(DISTINCT h.tf_number ORDER BY h.tf_number ASC SEPARATOR ", "))
                                    )
                                 ) AS tf_number, a.entry_date, b.batch_sap,
                                 GROUP_CONCAT(DISTINCT b.id_balance_tail SEPARATOR ",") AS id_balance_detail,
                                 GROUP_CONCAT(DISTINCT CONCAT(e.code, " :: ", e.description, " / ", b.batch_sap, " / Qty : ", FORMAT(b.init_qty, 3), " MT / ", IF(b.out_qty = 0, "-", "BATCH USED IN WIP")) SEPARATOR " | ") AS supplier,
                                 IF(b.out_qty = 0, "N/A", "") AS traced, f.material_document, f.po_so, f.id_trace_head,
                                 IFNULL(f.trace_no, CONCAT(a.trace_no, "|")) AS trace_no,
                                 FORMAT(bs.supplier_qty,3) AS balance_supplier
                            FROM t_balance_header a
                            LEFT JOIN (SELECT trace_no, FORMAT(SUM(qty),3) AS qty, FORMAT(SUM(init_qty),3) AS init_qty
                                         FROM t_balance_header
                                        WHERE `status` = 1
                                          AND (SUBSTRING(trace_no,1,1) = ? OR SUBSTRING(trace_no,1,1) = ?)
                                          AND id_tank = ?
                                        GROUP BY trace_no
                                        ) aa
                              ON a.trace_no = aa.trace_no
                            LEFT JOIN t_balance_detail b
                              ON a.id_balance_head = b.id_balance_head AND b.status = 1
                            LEFT JOIN m_material c
                              ON a.id_material = c.id_material
                            LEFT JOIN m_tank d
                              ON a.id_tank = d.id_tank AND d.status = 1 AND (d.id_plant = ? OR ? = 0)
                            LEFT JOIN m_supplier e
                              ON e.id_supplier = b.id_supplier
                            LEFT JOIN (SELECT f.id_balance_head, g.material_document, g.po_so, f.id_trace_head,
                                              GROUP_CONCAT(DISTINCT CONCAT(CAST(h.from_trace_no AS CHAR), " >>> ", CAST(f.to_trace_no AS CHAR)) SEPARATOR " | ") AS trace_no
									     FROM t_trace_header f
									 	 LEFT JOIN t_material_document g
                                           ON f.id_trace_head = g.id_trace_head
                                         LEFT JOIN t_trace_header h
                                           ON f.from_trace_no = h.to_trace_no
                                        WHERE f.status = 1
                                          AND (SUBSTRING(f.to_trace_no,1,1) = ? OR SUBSTRING(f.to_trace_no,1,1) = ?)
										GROUP BY f.id_balance_head) f
                              ON f.id_balance_head = a.id_balance_head
                            LEFT JOIN m_tank_detail h
                              ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(h.id_tank_tail AS CHAR)))
                            LEFT JOIN (
                                SELECT h.trace_no, SUM(d.init_qty) AS supplier_qty
                                FROM t_balance_header h
                                JOIN t_balance_detail d
                                    ON h.id_balance_head = d.id_balance_head
                                WHERE d.status = 1
                                GROUP BY h.trace_no
                            ) bs ON bs.trace_no = a.trace_no
                           WHERE c.type = ?
                             AND (SUBSTRING(a.trace_no,1,1) = ? OR SUBSTRING(a.trace_no,1,1) = ?)
                             AND a.id_tank = ?
                             AND a.status = 1
                           GROUP BY a.trace_no
                           ORDER BY a.id_balance_head DESC
                           ', [self::$movType1, self::$movType2, $idTankFeed, $idPlant, $idPlant,
                               self::$movType1, self::$movType2, self::$typeMaterial, self::$movType1, self::$movType2, $idTankFeed]);
        return $db;
    }

    static function deactivateRmEntry($id, $user){

        /* CHECK LOCK PERIOD */
            $entryDate = DB::select('SELECT entry_date
                                       FROM t_trace_header
                                      WHERE id_balance_head = ?
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

        /* MAIN ROUTE */
            $dat = DB::select('SELECT COUNT(a.id_trace_head) AS used
                                FROM t_trace_header a
                                WHERE a.id_balance_head = ?
                                AND a.out_qty <> 0
                                AND a.status = 1', [$id]);

            if ($dat[0]->used > 0){
                $db = [ (object)['response' => 3 ]];
                return $db;
            }

            DB::insert('INSERT INTO log_transactions
                            (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)', [ 'RM_ENTRY', 'DE-ACTIVATE', 'Id: ' . $id . ' | Status: 1 >> 0', $user ]);

            DB::update('UPDATE t_balance_detail
                        SET `status` = "0",
                            `updated_by` = ?
                        WHERE id_balance_head = ?', [$user, $id]);
            DB::update('UPDATE t_balance_header
                        SET `status` = "0",
                            `updated_by` = ?
                        WHERE id_balance_head = ?', [$user, $id]);
            $datTraceHead = DB::select('SELECT id_trace_head
                                        FROM t_trace_header
                                        WHERE `status` = 1
                                        AND id_balance_head = ?', [$id]);
            $idTraceHead = $datTraceHead[0]->id_trace_head;

            DB::update('UPDATE t_trace_header
                        SET `status` = "0",
                            `updated_by` = ?
                        WHERE id_balance_head = ?', [$user, $id]);
            DB::update('UPDATE t_trace_detail
                        SET `status` = "0",
                            `updated_by` = ?
                        WHERE id_trace_head = ?', [$user, $idTraceHead]);

        /* THROW OUTPUT */
            $db = [ (object)['response' => 1 ]];
            return $db;
    }
    static function activateRmEntry($id, $user){
        /* CHECK LOCK PERIOD */
            $entryDate = DB::select('SELECT a.entry_date
                                       FROM t_trace_header a
                                      WHERE a.id_trace_head > (
                                                SELECT b.id_trace_head
                                                FROM t_trace_header b
                                                WHERE b.id_balance_head = ?
                                                LIMIT 1',
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
        /* MAIN ROUTE */
            $dat = DB::select('SELECT COUNT(*) AS next_trace
                                FROM t_trace_header a
                                WHERE a.id_trace_head > (
                                        SELECT b.id_trace_head
                                        FROM t_trace_header b
                                        WHERE b.id_balance_head = ?
                                        LIMIT 1
                                    );', [$id]);
            if ($dat[0]->next_trace > 0){
                $db = [ (object)['response' => 4 ]];
                return $db;
            }

            DB::update('UPDATE t_balance_detail
                        SET `status` = "1",
                            `updated_by` = ?
                        WHERE id_balance_head = ?', [$user, $id]);
            DB::update('UPDATE t_balance_header
                        SET `status` = "1",
                            `updated_by` = ?
                        WHERE id_balance_head = ?', [$user, $id]);
            DB::update('UPDATE t_trace_header
                        SET `status` = "1",
                            `updated_by` = ?
                        WHERE id_balance_head = ?', [$user, $id]);
            $datTraceHead = DB::select('SELECT id_trace_head
                                        FROM t_trace_header
                                        WHERE `status` = "0"
                                        AND id_balance_head = ?', [$id]);
            $idTraceHead = $datTraceHead[0]->id_trace_head;
            DB::update('UPDATE t_trace_detail
                        SET `status` = "1",
                            `updated_by` = ?
                        WHERE id_trace_head = ?', [$user, $idTraceHead]);


        /* THROW OUTPUT */
            $db = [ (object)['response' => 1 ]];
            return $db;
    }
    static function get_rmNewEntryNumber($request){
        $idPlant = \App\Models\BaseModel::resolvePlant($request);
        $db = DB::select('SELECT a.rm_number
                            FROM (SELECT a.trace_no+1 AS rm_number
                                    FROM t_balance_header a
                                    WHERE SUBSTRING(a.trace_no,1,7) = CONCAT("1", DATE_FORMAT(CURDATE(), "%y%m%d"))
                                      AND SUBSTRING(a.trace_no,8,3) = ?
                                      AND a.status = 1 AND a.id_plant = ?
                                    ORDER BY a.id_balance_head DESC
                                    LIMIT 1 ) a
                            UNION ALL
                            SELECT CONCAT("1", DATE_FORMAT(CURDATE(), "%y%m%d"), ?, LPAD(RIGHT(?, 2), 2, "0"), "01") AS rm_number
                            LIMIT 1', [self::$movSeq, $idPlant, self::$movSeq, $idPlant]);
        return $db;
    }
    static function get_rmNewEntryNumberTrf($request){
        $idPlant = \App\Models\BaseModel::resolvePlant($request);
        $db = DB::select('SELECT CONCAT(SUBSTRING(a.rm_number,1,7), ?, SUBSTRING(a.rm_number,11,4)) + 1 AS rm_number
                            FROM (SELECT a.trace_no AS rm_number
                                    FROM t_balance_header a
                                    WHERE SUBSTRING(a.trace_no,1,7) = CONCAT("1", DATE_FORMAT(CURDATE(), "%y%m%d"))
                                      AND SUBSTRING(a.trace_no,2,9) = CONCAT(DATE_FORMAT(CURDATE(), "%y%m%d"), ?)
                                      AND a.status = 1 AND a.id_plant = ?
                                    ORDER BY a.id_balance_head DESC
                                    LIMIT 1 ) a
                            UNION ALL
                            SELECT CONCAT("1", DATE_FORMAT(CURDATE(), "%y%m%d"), ?, LPAD(RIGHT(?, 2), 2, "0"), "01") AS rm_number
                            LIMIT 1', [substr(self::$idTankSrc,1,3), "000", $idPlant, substr(self::$idTankSrc,1,3), $idPlant]);
        return $db;
    }
    static function get_cmbActiveTank(){
        $db = DB::select('SELECT a.id_tank, a.description AS tank
                            FROM m_tank a
                           WHERE a.status = 1
                             AND a.code_3 = "STORAGE"
                           ORDER BY a.code ASC');
        return $db;
    }
    static function get_cmbActiveSpecificSourceTank($request) {
                $sloc = $request->input('sloc');

                $db = DB::select('SELECT DISTINCT a.id_tank_tail, a.tf_number AS tankNo
                                                        FROM m_tank_detail a
                                                     WHERE a.status = 1
                                                         AND a.id_tank = ?
                                                     ORDER BY a.tf_number ASC', [$sloc]);

                return $db;
    }
    static function get_cmbActiveSpecificTrfTank($request) {
        $sloc = $request->input('sloc');

        $db = DB::select('SELECT a.id_tank_tail, a.tf_number AS trfTankNo
                            FROM m_tank_detail a
                           WHERE a.status = 1
                             AND a.id_tank = ?
                           ORDER BY a.tf_number ASC', [$sloc]);

        return $db;
    }
    static function get_cmbActiveTank_trf($request){
        $sloc = $request->input('sloc');
        $idPlant = $request->input('id_plant') ?? \App\Models\BaseModel::resolvePlant($request);

        $db = DB::select('SELECT a.id_tank, a.description AS tank
                            FROM m_tank a
                           WHERE a.status = 1
                             AND a.code_3 = ?
                             AND a.id_plant = ?
                           ORDER BY a.code ASC', [$sloc, $idPlant]);

        return $db;
    }
    static function get_cmbActiveMaterial(){
        $db = DB::select('SELECT a.id_material, CONCAT( UPPER(a.description), " (", a.code, " / ", a.type, " / Feed: ", a.qtf_feed, " / Rundown: ", a.qtf_rundown,  ")" ) AS material
                            FROM m_material a
                           WHERE a.status = 1
                             AND a.type = "RM"
                           ORDER BY a.code ASC');
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
    static function get_dtMaterialList($request){
        $mode = $request->input('mode');
        $number = $request->input('number');

        if ($mode == 'ADD'){
            $db = DB::select('SELECT FORMAT(a.qty,3) AS qty, a.id_material,
                                     CONCAT(c.code, " :: ", c.description) AS material,
                                     a.id_balance_temp AS idTail, a.entry_no, ? AS mode
                                FROM t_balance_temporary a
                                LEFT JOIN m_material c
                                  ON a.id_material = c.id_material
                               WHERE a.entry_no = ?
                                 AND a.status = 1', [$mode, $number]);
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
    static function get_totalQtyMaterial($request){
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
    static function get_totalStockMaterial($request){
        $idMaterial = $request->input('idMaterial');
        $idTank = $request->input('idTank');

        $db = DB::select('SELECT IFNULL(SUM(a.qty),0) AS total
                            FROM t_balance_header a
                           WHERE a.status = 1
                             AND a.id_material = ?
                             AND a.id_tank = ?', [$idMaterial, $idTank]);

        return $db;
    }
    static function post_rmEntrySupplier($user, $request){
        $flag = $request->input('flag');
        $mode = $request->input('mode');
        $rmNumber = $request->input('rmNumber');
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
                            [$rmNumber, $idSupplier, $idMaterial, $qty, $batchSap, $user]);
            $db = [ (object)['response' => $db ? 1 : 0 ]];

        } elseif ($mode == 'UPDATE'){

            $id_material = $idMaterial;

            $flag = DB::select('SELECT COUNT(a.id_balance_head) AS dat
                                  FROM t_balance_detail a
                                 WHERE a.id_balance_tail = ?
                                   AND a.status = 1', [$idTail]);

            if ($flag[0]->dat > 0){
                $dat = DB::select('SELECT id_supplier, qty
                                     FROM t_balance_detail
                                    WHERE id_balance_tail = ?', [$idTail]);
                $idSupplier_old = $dat[0]->id_supplier;
                $qty_old = $dat[0]->qty;

                /* LOGGING */
                DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'UPDATE', 'IDHEAD: ' . $idHead . ' IDTAIL: ' . $idTail . ' | ID_SUPPLIER: ' .
                                                    $idSupplier_old . ' >>> ' . $idSupplier . ' / QTY: ' . $qty_old . ' >>>' . $qty .
                                                    ' | Status: 1', $user ]);

                DB::update('UPDATE t_trace_detail
                               SET id_supplier = ?,
                                   in_qty = ?,
                                   batch_sap = ?,
                                   updated_by = ?
                             WHERE id_balance_tail = ?', [$idSupplier, $qty, $batchSap, $user, $idTail]);

                $db = DB::update('UPDATE t_balance_detail
                                     SET id_supplier = ?,
                                         qty = ?,
                                         init_qty = ?,
                                         batch_sap = ?,
                                         updated_by = ?
                                   WHERE id_balance_tail = ?', [$idSupplier, $qty, $qty, $batchSap, $user, $idTail]);
                $db = [ (object)['response' => $db ] ];

            } else {
                $flag = DB::select('SELECT COUNT(a.id_balance_head) AS dat
                                      FROM t_balance_detail a
                                     WHERE a.id_supplier = ?
                                       AND a.status = 1
                                       AND a.id_balance_head = ?', [$idSupplier, $idHead]);

                if ($flag[0]->dat > 0){
                    $dat = DB::select('SELECT id_supplier, qty, id_balance_tail, batch_sap
                                         FROM t_balance_detail
                                        WHERE id_supplier = ?
                                          AND `status` = 1', [$idSupplier]);
                    $idSupplier_old = $dat[0]->id_supplier;
                    $qty_old = $dat[0]->qty;
                    $idTail = $dat[0]->id_balance_tail;
                    $batchSap_old = $dat[0]->batch_sap;

                    /* LOGGING */
                    DB::insert('INSERT INTO log_transactions
                                       (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'UPDATE', 'IDHEAD: ' . $idHead . ' IDTAIL: ' . $idTail . ' | ID_SUPPLIER: ' .
                                                        $idSupplier_old . ' >>> ' . $idSupplier . ' / QTY: ' . $qty_old . ' >>>' . $qty .
                                                        ' / BATCH_SAP: ' . $batchSap_old . ' >>> ' . $batchSap .
                                                        ' | Status: 1', $user ]);

                    DB::update('UPDATE t_trace_detail
                                   SET in_qty = ?,
                                       batch_sap = ?,
                                       updated_by = ?
                                 WHERE id_balance_tail = ?', [$qty, $batchSap, $user, $idTail]);

                    $db = DB::update('UPDATE t_balance_detail
                                         SET qty = ?,
                                             init_qty = ?,
                                             batch_sap = ?,
                                             updated_by = ?
                                       WHERE id_supplier = ?
                                         AND id_balance_head = ?', [$qty, $qty, $batchSap, $user, $idSupplier, $idHead]);
                    $db = [ (object)['response' => 1 ]];

                } else {
                    $idTail = DB::table('t_balance_detail')->insertGetId([
                                'id_balance_head' => $idHead,
                                'id_supplier' => $idSupplier,
                                'id_material' => $id_material,
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
                        'id_material' => $id_material,
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
    static function post_rmEntryMaterial($user, $request){
        $flag = $request->input('flag');
        $mode = $request->input('mode');
        $entryNo = $request->input('entryNo');
        $idMaterial = $request->input('idMaterial');
        $qty = $request->input('qty');
        $idHead = $request->input('idHead');
        $idTail = $request->input('idTail');
        $idTank = $request->input('idTankFeed');
        $qty = floatval(str_replace(',', '', $qty));

        if ($mode == 'ADD'){
            /* CHECK FOR SAME MATERIAL */
            $dat = DB::select('SELECT COUNT(entry_no) AS flag
                                 FROM t_balance_temporary
                                WHERE id_material = ?
                                  AND entry_no = ?', [$idMaterial, $entryNo]);

            if ($dat[0]->flag > 0){
                $db = [ (object)['response' => 2 ]];
                return $db;
            };

            $db = DB::insert('INSERT INTO t_balance_temporary
                                    (entry_no, id_material, qty, id_tank, created_by)
                            VALUES (?, ?, ?, ?, ?)',
                            [$entryNo, $idMaterial, $qty, $idTank, $user]);
            $db = [ (object)['response' => $db ? 1 : 0 ]];

        } elseif ($mode == 'UPDATE'){

        }
        return $db;
    }
    static function deleteSupplier($id, $user){
        DB::delete('DELETE FROM t_balance_temporary
                     WHERE id_balance_temp = ?', [$id]);

        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function deleteMaterial($id, $user){
        DB::delete('DELETE FROM t_balance_temporary
                     WHERE id_balance_temp = ?', [$id]);

        $db = [ (object)['response' => 1 ]];
        return $db;
    }

    static function post_rmEntry($user, $request){
        $flag = $request->input('flag');
        $mode = $request->input('mode');
        $idHead = $request->input('idHead');
        $entry_no = $request->input('entry_no');
        $entry_date = $request->input('entry_date');
        $id_tank = $request->input('tank');
        $id_tank_tail = $request->input('tankNo');
        $id_tank_tail_json = json_encode($id_tank_tail);
        $qty = $request->input('qty');
        $po = $request->input('po');
        $id_material = $request->input('idMaterial');
        $qty = floatval(str_replace(',', '', $qty));
        $materialDoc = $request->input('material_doc');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        /* CHECK LOCK PERIOD */
            $lockDateTime = new \DateTime($entry_date);
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

        /* MAIN ROUTE */
            if ($mode == 'ADD'){
                $supplierRows = [];

                /* GET SUPPLIER & INSERT IN BALANCE_TAIL */
                $dat = DB::select('SELECT id_supplier, qty AS qty_tail, batch_sap
                                    FROM t_balance_temporary
                                    WHERE entry_no = ?', [$entry_no]);
                foreach ($dat as $row) {
                    if ($row->qty_tail <= 0) continue;

                    $supplierRows[] = [
                        'id_supplier'     => $row->id_supplier,
                        'batch_sap'       => $row->batch_sap,
                        'rundownSupplier' => round((float)$row->qty_tail, 4),
                    ];
                }

                if (empty($supplierRows)) {
                    return [(object)['response' => 6]];
                }

                Rundown::adjustRundownToTotal($supplierRows, $qty);

                $rundownResult = Rundown::generalRundown([
                    'user'          => $user,
                    'entry_date'    => $entry_date,
                    'from_trace_no' => null,
                    'trace_no'      => $entry_no,
                    'id_material'   => $id_material,
                    'id_tank'       => $id_tank,
                    'id_tank_tail'  => $id_tank_tail_json,
                    'in_qty'        => $qty,
                    'last_qtf'      => 0,
                    'curr_qtf'      => $qty,
                    'id_plant'      => $idPlant,
                    'supplier_rows' => $supplierRows,
                ]);

                if ($rundownResult['response'] != 1) {
                    return [(object)['response' => 3]];
                }

                $idHead      = $rundownResult['id_balance_head'];
                $idTraceHead = $rundownResult['id_trace_head'];

                /* LOGGING */
                DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'ADD', 'IDHEAD: ' . $idHead . ' | DATE: ' . $entry_date . ' / BATCH: ' .
                                                    $entry_no . ' / TANK: ' . $id_tank . ' / QTY: ' . $qty . ' / MATERIAL: ' . $id_material .
                                                    ' | Status: 1', $user ]);
                DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_TRACE_HEAD', 'ADD', 'IDTRACEHEAD: ' . $idTraceHead . 'IDHEAD: ' . $idHead . ' | DATE: ' . $entry_date . ' / TRACE: ' .
                                                    $entry_no . ' / IN_QTY: ' . $qty . ' / MATERIAL: ' . $id_material .
                                                    ' | Status: 1', $user ]);

                DB::delete('DELETE FROM t_balance_temporary
                            WHERE entry_no = ?', [$entry_no]);

                DB::insert('INSERT INTO t_material_document
                                (id_trace_head, material_document, po_so, created_by)
                            VALUES (?, ?, ?, ?)', [$idTraceHead, $materialDoc, $po, $user]);

            } elseif ($mode == 'UPDATE'){
                DB::update('UPDATE t_balance_header
                            SET id_tank = ?,
                                id_tank_tail = ?,
                                entry_date = ?,
                                qty = ?,
                                in_qty = ?,
                                init_qty = ?,
                                updated_by = ?
                            WHERE id_balance_head = ?',
                            [$id_tank, $id_tank_tail_json, $entry_date, $qty, $qty, $qty, $user, $idHead]);
                DB::update('UPDATE t_trace_header
                            SET id_sloc = ?,
                                id_tank_tail = ?,
                                entry_date = ?,
                                in_qty = ?,
                                updated_by = ?
                            WHERE id_balance_head = ?',
                            [$id_tank, $id_tank_tail_json, $entry_date, $qty, $user, $idHead]);

                $dat = DB::select('SELECT id_trace_head
                                    FROM t_trace_header
                                    WHERE id_balance_head = ?',[$idHead]);
                $idTraceHead = $dat[0]->id_trace_head;
                DB::update('UPDATE t_material_document
                            SET material_document = ?,
                                po_so = ?,
                                updated_by = ?
                            WHERE id_trace_head = ?',
                            [$materialDoc, $po, $user, $idTraceHead]);

                /* LOGGING */
                DB::insert('INSERT INTO log_transactions
                                (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'UPDATE', 'IDHEAD: ' . $idHead . ' | DATE: ' . $entry_date . ' / BATCH: ' .
                                                    $entry_no . ' / TANK: ' . $id_tank. ' / QTY' . $qty . ' / MATERIAL' . $id_material .
                                                    ' | Status: 1', $user ]);
            }

        /* THROW OUTPUT */
            $db = [ (object)['response' => 1 ]];
            return $db;
    }

    static function post_rmTrfEntry($user, $request){
        $flag = $request->input('flag');
        $mode = $request->input('mode');
        $idHead = $request->input('idHead');
        $entry_no = $request->input('entry_no');
        $curr_entryDate = $request->input('entry_date');
        $id_tankSource = $request->input('sourceTank');
        $id_tank = $request->input('trfTank');
        // $qty = $request->input('qty');
        // $out_qty = floatval(str_replace(',', '', $qty));
        $materialDoc = $request->input('material_doc');
        $id_tankSourceNo = $request->input('tankNo');
        $id_tankNo = $request->input('trfTankNo');
        $id_tankSourceNo_json = json_encode($id_tankSourceNo);
        $id_tankNo_json = json_encode($id_tankNo);
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        $srcTankRec = DB::select('SELECT code, code_3, id_plant FROM m_tank WHERE id_tank = ? AND `status` = 1 LIMIT 1', [$id_tankSource]);
        $sourceTankCode = !empty($srcTankRec) ? $srcTankRec[0]->code : null;
        $sourceTankPlant = !empty($srcTankRec) ? $srcTankRec[0]->id_plant : null;
        $tgtTankRec = DB::select('SELECT code FROM m_tank WHERE id_tank = ? AND `status` = 1 LIMIT 1', [$id_tank]);
        $targetTankCode = !empty($tgtTankRec) ? $tgtTankRec[0]->code : null;
        $isStorageTank = (isset($srcTankRec[0]) && strtoupper($srcTankRec[0]->code_3) === 'STORAGE');

        if (!$sourceTankCode || !$targetTankCode) {
            // invalid tanks
            return [ (object)['response' => 6 ] ];
        }

        /* CHECK LOCK PERIOD */
            $lockDateTime = new \DateTime($curr_entryDate);
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
        /* MAIN ROUTE */
            if ($mode == 'ADD'){
                /* GET TEMPORARY MATERIAL ENTRY */
                $datTempMaterial = DB::select('SELECT COUNT(a.entry_no) AS idData
                                                FROM t_balance_temporary a
                                                WHERE a.status = 1
                                                AND a.entry_no = ?', [$entry_no]);
                if ($datTempMaterial[0]->idData == 0){
                    if ($totalStock - $qty < 0){
                        $db = [ (object)['response' => 6 ]];
                        return $db;
                    };
                };

                $datTempMaterial = DB::select('SELECT entry_no, id_tank, id_material, qty
                                                FROM t_balance_temporary a
                                                WHERE a.status = 1
                                                AND a.entry_no = ?', [$entry_no]);
                $lenTemp = count($datTempMaterial);
                if ($lenTemp == 0){
                    $db = [ (object)['response' => 6 ]];
                    return $db;
                };

                for ($z = 0; $z < $lenTemp; $z++){
                    $id_material = $datTempMaterial[$z]->id_material;
                    $qty = $datTempMaterial[$z]->qty;
                    $out_qty = floatval(str_replace(',', '', $qty));

                    $last_qtf = 0;
                    $curr_qtf = $out_qty;

                    $datHead = DB::select('SELECT b.id_balance_head, b.qty, b.in_qty, b.out_qty, b.init_qty, b.trace_no, a.id_material
                                            FROM m_material a
                                            LEFT JOIN t_balance_header b
                                            ON a.id_material = b.id_material AND b.`status` = 1
                                            LEFT JOIN m_tank d
                                            ON b.id_tank = d.id_tank AND d.status = 1
                                            WHERE a.id_material = ?
                                            AND a.`status` = 1
                                            AND b.qty <> 0
                                            AND d.code = ?
                                            AND d.id_plant = ?
                                            ORDER BY b.id_balance_head ASC', [$id_material, self::$idTankSrc, $sourceTankPlant]);

                    $len = count($datHead);

                    /* CREATE ENTRY NO TO FEED TANK */
                    $batchTrf_id = substr($targetTankCode,1,3);
                    $batchFeed_id = "000";
                    $batch_moveType = substr($entry_no, 0, 1);
                    $batch_entryDate = substr($entry_no, 1, 6);
                    $batch_idPlant = substr($entry_no, 10, 2);
                    $batch_sequence = substr($entry_no, -2);

                    $entryTrfNo_in = $batch_moveType . $batch_entryDate . $batchTrf_id . $batch_idPlant . $batch_sequence;
                    $entryFeedNo_in = $batch_moveType . $batch_entryDate . $batchFeed_id . $batch_idPlant . $batch_sequence;

                    $feedResult = Feed::generalFeed([
                        'user'         => $user,
                        'entry_date'   => $curr_entryDate,
                        'id_material'  => $id_material,
                        'id_tank'      => $id_tankSource,
                        'id_tank_tail' => $id_tankSourceNo_json,
                        'id_plant'     => $isStorageTank ? 0 : $idPlant,
                        'qty'          => $out_qty,
                        'to_trace_no'  => $entryTrfNo_in,
                    ]);

                    foreach($feedResult['used_heads'] as $used){
                        $in_qty = $used['qty_used'];

                        $supplierRows = [];
                        foreach ($feedResult['feed_in_details'] as $d) {
                            if ($d['qty'] <= 0) continue;

                            $supplierRows[] = [
                                'id_supplier'     => $d['id_supplier'],
                                'batch_sap'       => $d['batch_sap'],
                                'rundownSupplier' => round((float)$d['qty'], 4),
                            ];
                        }

                        Rundown::adjustRundownToTotal($supplierRows, $in_qty);

                        $rundownResult = Rundown::generalRundown([
                            'user'          => $user,
                            'entry_date'    => $curr_entryDate,
                            'trace_no'      => $entryFeedNo_in,
                            'from_trace_no' => $entryTrfNo_in,
                            'id_material'   => $id_material,
                            'id_tank'       => $id_tank,
                            'id_tank_tail'  => $id_tankNo_json,
                            'id_plant'      => $idPlant,
                            'in_qty'        => $in_qty,
                            'last_qtf'      => 0,
                            'curr_qtf'      => $in_qty,
                            'supplier_rows' => $supplierRows,
                        ]);

                        DB::insert('INSERT INTO t_material_document
                                            (id_trace_head, material_document, created_by)
                                    VALUES (?, ?, ?)', [$rundownResult['id_trace_head'], $materialDoc, $user]);
                    }
                }

                DB::delete('DELETE FROM t_balance_temporary
                            WHERE entry_no = ?', [$entry_no]);

            } elseif ($mode == 'UPDATE'){

            }

        /* THROW OUTPUT */
            $db = [ (object)['response' => 1 ]];
            return $db;
    }
    static function deactivateRmEntryTrf($id, $user){
        $trace_no = $id;

        $dat = DB::select('SELECT COUNT(a.id_trace_head) AS used
                             FROM t_trace_header a
                            WHERE a.to_trace_no = ?
                              AND a.out_qty <> 0', [$trace_no]);

        if ($dat[0]->used > 0){
            $db = [ (object)['response' => 3 ]];
            return $db;
        }

        $datTraceHead = DB::select('SELECT a.id_trace_head, a.in_qty, a.id_material, a.id_balance_head
                                      FROM t_trace_header a
                                     WHERE a.`status` = 1
                                       AND a.to_trace_no = ?', [$trace_no]);
        $lenTraceHead = count($datTraceHead);

        for ($i = 0; $i < $lenTraceHead; $i++) {
            $idTraceHead = $datTraceHead[$i]->id_trace_head;    /* 670 */       /* 672 */       /* 671 */       /* 673 */
            $inQtyHead = $datTraceHead[$i]->in_qty;             /*  50 */       /* 100 */       /* 115.359 */   /* 184.641 */
            $idMaterial = $datTraceHead[$i]->id_material;       /*  26 */       /*   1 */       /*   1 */       /*   1 */
            $idBalHead = $datTraceHead[$i]->id_balance_head;    /* 345 */       /* 346 */       /* 358 */       /* 359 */

            $datTraceHead_from = DB::select('SELECT b.from_trace_no, b.out_qty, b.id_trace_head AS from_id_trace_head
                                               FROM t_trace_header a
                                               LEFT JOIN t_trace_header b
                                                 ON a.from_trace_no = b.to_trace_no AND a.in_qty = b.out_qty
                                              WHERE a.`status` = 1
                                                AND a.id_trace_head = ?
                                                AND a.id_material = ?
                                                AND a.id_balance_head = ?', [$idTraceHead, $idMaterial, $idBalHead]);   /* 671, 1 */           /* 673, 1 */
            $lenTraceHeadFrom = count($datTraceHead_from);

            for ($n = 0; $n < $lenTraceHeadFrom; $n++) {
                $fromTraceNo = $datTraceHead_from[$n]->from_trace_no;                 /* 12408160001, 12408190002 */           /* 12408160001, 12408190002 */
                $fromIdTraceHead = $datTraceHead_from[$n]->from_id_trace_head;        /*         670,         672 */           /*         670,         672 */
                $fromOutQtyHead = $datTraceHead_from[$n]->out_qty;                    /*         115.359,     184.641*/        /*         115.359,     184.641*/

                DB::update('UPDATE t_trace_header
                               SET `status` = "0",
                                   `updated_by` = ?
                             WHERE from_trace_no = ?', [$user, $fromTraceNo]);

                /* RESTORE STORAGE TANK */
                $datBalHeadFrom = DB::select('SELECT id_balance_head, qty, out_qty
                                                FROM t_balance_header
                                               WHERE `status` = 1
                                                 AND trace_no = ?', [$fromTraceNo]);    /* 12408150002 */                       /* 12408160001 */
                $idBalHeadFrom = $datBalHeadFrom[0]->id_balance_head;                   /*         337 */                       /*         344 */
                $outQtyHeadFrom = $datBalHeadFrom[0]->out_qty;                          /*         100 */                       /*         100 */
                $qtyHeadFrom = $datBalHeadFrom[0]->qty;                                 /*           0 */                       /*          50 */

                DB::update('UPDATE t_balance_header
                               SET qty = ?,
                                   out_qty = ?,
                                   updated_by = ?
                             WHERE id_balance_head = ?
                            ', [$qtyHeadFrom + $inQtyHead, $outQtyHeadFrom - $inQtyHead, $user, $idBalHeadFrom]);

                $datBalTailFrom = DB::select('SELECT a.id_balance_tail, a.qty, a.out_qty, a.id_supplier, b.in_qty, b.out_qty AS qtyTraceTail
                                                FROM t_balance_detail a
                                                LEFT JOIN t_trace_detail b
                                                  ON a.id_balance_tail = b.id_balance_tail AND b.`status` = 1
                                               WHERE a.`status` = 1
                                                 AND id_balance_head = ?
                                                 AND b.id_trace_head = ?
                                               ORDER BY id_balance_tail DESC
                                            ', [$idBalHeadFrom, $fromIdTraceHead]);   /*         337 */                       /*         344, 671 */
                $lenBalTailFrom = count($datBalTailFrom);

                for ($k = 0; $k < $lenBalTailFrom; $k++){
                    $idBalTailFrom = $datBalTailFrom[0]->id_balance_tail;   /* 605 */  /* 604 */                            /* 612 */
                    $outQtyTailFrom = $datBalTailFrom[0]->out_qty;          /*  50 */  /*  50 */                            /* 100 */
                    $qtyTailFrom = $datBalTailFrom[0]->qty;                 /*   0 */  /*   0 */                            /*  50 */
                    $idSupplierTailFrom = $datBalTailFrom[0]->id_supplier;  /*  49 */  /*  82 */                            /*   4 */
                    $qtyTraceTail = $datBalTailFrom[0]->qtyTraceTail;

                    DB::update('UPDATE t_balance_detail
                                   SET qty = ?,
                                       out_qty = ?,
                                       updated_by = ?
                                 WHERE id_balance_tail = ?
                                ', [$qtyTailFrom + $qtyTraceTail, $outQtyTailFrom - $qtyTraceTail, $user, $idBalTailFrom]);

                    DB::update('UPDATE t_trace_detail
                                   SET `status` = 0,
                                       `updated_by` = ?
                                 WHERE id_trace_head = ?
                                   AND id_balance_tail = ?', [$user, $fromIdTraceHead, $idBalTailFrom]);
                };
            }

            DB::update('UPDATE t_trace_detail
                           SET `status` = 0,
                               `updated_by` = ?
                         WHERE id_trace_head = ?', [$user, $idTraceHead]);
            DB::update('UPDATE t_balance_detail
                           SET `status` = 0,
                               `updated_by` = ?
                         WHERE id_balance_head = ?', [$user, $idBalHead]);

        }

        DB::update('UPDATE t_trace_header
                       SET `status` = "0",
                           `updated_by` = ?
                     WHERE to_trace_no = ?', [$user, $trace_no]);
        DB::update('UPDATE t_balance_header
                       SET `status` = "0",
                           `updated_by` = ?
                     WHERE trace_no = ?', [$user, $trace_no]);

        DB::insert('INSERT INTO log_transactions
                           (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)', [ 'RMTRF_ENTRY', 'DE-ACTIVATE', 'Trace No: ' . $trace_no . ' | Status: 1 >> 0', $user ]);

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
