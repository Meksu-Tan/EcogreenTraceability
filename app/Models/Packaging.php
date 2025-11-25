<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Packaging extends Model
{
    protected $connection = 'eudr_ts';
    protected static $movType1 = "4";

    static function get_activeFgProduct(){
        $db = DB::select('SELECT a.id_materialpck, UPPER(CONCAT(a.description, " (", a.code, ")")) AS material
                            FROM m_material_pck a
                           WHERE a.status = 1
                           ORDER BY a.description ASC');

        return $db;
    }
    static function get_wipMaterialByFgProduct($request){
        $idMaterialPck = $request->input('idMaterialPck');
        $idTank = $request->input('tank');
        $idPlant = DB::table('m_tank')
          ->where('id_tank', $idTank)
          ->value('id_plant');

        if (!$idPlant) {
          $idPlant = \App\Models\BaseModel::resolvePlant($request);
        }

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT IFNULL(CONCAT(a.description, " (", a.code, ") || Balance : ", IFNULL(a.balance,0), " MT" ), CONCAT(a.description, " (", a.code, ") || Balance : 0.0 MT" )) AS wip_material,
                                 IFNULL(a.balance,0) AS balance, a.id_rundown
                            FROM (
                                SELECT b.description, b.code, IFNULL(FORMAT(SUM(c.balance),3),3) AS balance, b.id_rundown
                                  FROM (
                                        SELECT c.id_material, c.id_tank, c.code, c.description, c.id_rundown
                                          FROM m_material_pck a
                                          LEFT JOIN (SELECT b.id_material, b.code, b.description, b.id_rundown
                                                       FROM m_material b
                                                      WHERE b.`status` = 1
                                                    ) b
                                            ON a.id_material = b.id_material
                                          LEFT JOIN (SELECT b.code, b.id_material, b.description, b.id_rundown,
                                                            c.id_tank
                                                       FROM m_material b
                                                       LEFT JOIN m_tank c
                                                         ON b.type = c.code_2 AND c.status = 1
                                                      WHERE b.`status` = 1) c
                                            ON b.code = c.code
                                         WHERE a.id_materialpck = ?
                                        ) b
                                  LEFT JOIN (
                                        SELECT c.id_tank, c.id_material, SUM(c.qty) AS balance
                                          FROM t_balance_header c
                                         WHERE c.`status` = 1 and c.id_plant = ?
                                         GROUP BY c.id_material, c.id_tank
                                        ) c
                                    ON b.id_material = c.id_material AND b.id_tank = c.id_tank
                                 GROUP BY b.code
                            ) a
                            ', [$idMaterialPck, $idPlant]);

        return $db;
    }
    static function get_dtPckEntry(){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.id_whx_head, a.entry_date, CONCAT(CAST(g.from_trace_no AS CHAR) , " >>> ", CAST(a.trace_no AS CHAR) ) AS fromto_trace_no,
                                 a.id_material_feed, a.id_material_fg, a.batch_no, f.id_trace_head, f.id_balance_head,
                                 FORMAT(g.init_qty,3) AS init_qty, FORMAT(g.qty,3) AS balance, a.status, a.created_by, a.created_at, a.updated_by, a.updated_at,
                                 UPPER(b.description) AS feed, UPPER(c.description) AS fg, a.trace_no, a.po_no, g.code AS whx, a.id_section,
                                 GROUP_CONCAT(DISTINCT CONCAT(d.description, " / ", d.batch_sap, " / Init: ", FORMAT(d.init_qty,3), " MT / Balance: ", FORMAT(d.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                 FORMAT(SUM(DISTINCT dd.init_qty),3) AS balance_supplier,
                                 CASE
                                    WHEN a.trace_no = (SELECT to_trace_no
                                                         FROM t_trace_header
                                                        WHERE SUBSTRING(to_trace_no, 8, 3) <> "000"
                                                          AND SUBSTRING(to_trace_no, 1, 1) = 4
                                                          AND `status` = 1
                                                        ORDER BY id_trace_head DESC LIMIT 1) THEN 1
                                    ELSE NULL
                                 END AS is_last_row,
                                 CASE
                                    WHEN a.trace_no = (SELECT from_trace_no
                                                         FROM t_trace_header
                                                        WHERE from_trace_no = a.trace_no
                                                          AND `status` = 1
                                                        LIMIT 1) THEN 1
                                    ELSE NULL
                                 END AS next_process
                            FROM t_warehouse_header a
                            LEFT JOIN m_material b
                              ON a.id_material_feed = b.id_material
                            LEFT JOIN m_material_pck c
                              ON a.id_material_fg = c.id_materialpck
                            LEFT JOIN (SELECT dd.trace_no, e.description, d.batch_sap, SUM(d.init_qty) AS init_qty, SUM(d.qty) AS qty
                                         FROM t_warehouse_header dd
                                         LEFT JOIN t_warehouse_detail d
                                           ON dd.id_whx_head = d.id_whx_head
                                         LEFT JOIN m_supplier e
                                           ON e.id_supplier = d.id_supplier
                                        WHERE d.status = 1
                                          AND dd.status = 1
                                        GROUP BY dd.trace_no, d.batch_sap
                                      ) d
                              ON a.trace_no = d.trace_no
                            LEFT JOIN (SELECT dd.trace_no, SUM(d.init_qty) AS init_qty, SUM(d.qty) AS qty
                                         FROM t_warehouse_header dd
                                         LEFT JOIN t_warehouse_detail d
                                           ON dd.id_whx_head = d.id_whx_head
                                        WHERE d.status = 1
                                          AND dd.status = 1
                                        GROUP BY dd.trace_no
                                      ) dd
                              ON a.trace_no = dd.trace_no
                            LEFT JOIN t_trace_header f
                              ON f.to_trace_no = a.trace_no AND f.status = 1
                            LEFT JOIN (SELECT g.id_whx_head, SUM(g.init_qty) AS init_qty, SUM(g.qty) AS qty,
                                              GROUP_CONCAT(DISTINCT g.from_trace_no SEPARATOR " ") AS from_trace_no
                                         FROM t_warehouse_header g
                                        WHERE g.status = 1
                                        GROUP BY g.trace_no) g
                              ON a.id_whx_head = g.id_whx_head
                            LEFT JOIN m_warehouse g
                              ON g.id_warehouse = a.id_section
                           WHERE a.`status` = 1
                             AND g.from_trace_no IS NOT NULL
                           GROUP BY a.trace_no
                           ORDER BY a.entry_date DESC');


        return $db;
    }
    static function get_cmbActiveTank_pck($request){
        $rundownID = $request->input('rundownID');

        $datType = DB::select('SELECT a.type
                                 FROM m_material a
                                WHERE a.status = 1
                                  AND a.id_rundown = ?', [$rundownID]);
        $sloc = $datType[0]->type;

        if (!$sloc) return [];

        $allowedPlants = ['1002', '1007'];

        $item = match ($sloc) {
          'RM'  => 'FEED TANK',
          'PRD' => 'PRODUCT TANK',
          'WIP' => 'WIP TANK',
          default => null,
        };
  
        if (!$item) return [];
  
        $placeholders = implode(',', array_fill(0, count($allowedPlants), '?'));
  
        $query = "
            SELECT a.id_tank, a.description AS tank
            FROM m_tank a
            WHERE a.status = 1
              AND a.description LIKE ?
              AND a.id_plant IN ($placeholders)
            ORDER BY a.code ASC
          ";
  
        $params = array_merge(['%' . $item], $allowedPlants);
        return DB::select($query, $params);
    }
    static function get_cmbActiveWarehouse_pck($request){
        $batchNo_tmp = $request->input('batchNo');

        // Periksa apakah string mengandung tanda '-'
        if (strpos($batchNo_tmp, '-') !== false) {
            // Jika ada '-', pisahkan string dan trim setiap bagian
            $parts = array_map('trim', explode('-', $batchNo_tmp));
            // Ambil dua elemen pertama dan gabungkan kembali
            $batchNo = $parts[0];
        } else {
            // Jika tidak ada '-', ambil dua string pertama secara langsung
            $batchNo = substr($batchNo_tmp, 0, 2);
        }

        $db = DB::select('SELECT a.id_warehouse, a.description AS warehouse
                            FROM m_warehouse a
                           WHERE a.status = 1
                             AND a.id_batch = ?', [$batchNo]);
        return $db;
    }

    static function post_cancelPck($user, $request){
        $traceNo = $request->input('traceNo');
        //$idWhxHead = $request->input('idWhxHead');
        //$idTraceHead = $request->input('idTraceHead');

        /* CHECK LOCK PERIOD */
            $entryDate = DB::select('SELECT entry_date
                                       FROM t_trace_header
                                      WHERE to_trace_no = ?
                                        AND `status` = 1',
                                    [$traceNo]);
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

        /* GET ALL DATA BASED ON TRACE NO */
            $datPack = DB::select('SELECT a.id_trace_head, a.in_qty
                                     FROM t_trace_header a
                                    WHERE a.status = 1
                                      AND a.to_trace_no = ?', [$traceNo]);

            $lenPack = count($datPack);
            for ($z = 0; $z < $lenPack; $z++){
                $idTraceHead = $datPack[$z]->id_trace_head;
                $inQtyWhx = $datPack[$z]->in_qty;

                $datWhx = DB::select('SELECT a.id_whx_head
                                        FROM t_warehouse_header a
                                       WHERE a.status = 1
                                         AND a.trace_no = ?
                                         AND a.init_qty = ?', [$traceNo, $inQtyWhx]);
                $idWhxHead = $datWhx[0]->id_whx_head;

                /* BALANCE UPDATE QTY */
                    $datTraceHeadFeed = DB::select('SELECT b.id_trace_head, b.id_balance_head
                                                      FROM t_trace_header a
                                                      LEFT JOIN t_trace_header b
                                                        ON a.from_trace_no = b.to_trace_no AND b.status = 1 AND a.in_qty = b.out_qty
                                                     WHERE a.id_trace_head = ?
                                                       AND a.status = 1', [$idTraceHead]);
                    $idBalHead = $datTraceHeadFeed[0]->id_balance_head;

                    $datWhxHead = DB::select('SELECT init_qty
                                                FROM t_warehouse_header
                                               WHERE id_whx_head = ?
                                                 AND `status` = 1', [$idWhxHead]);
                    $whxQty = $datWhxHead[0]->init_qty;
                    $datBalHead = DB::select('SELECT qty, out_qty
                                                FROM t_balance_header
                                               WHERE id_balance_head = ?
                                                 AND `status` = 1', [$idBalHead]);
                    $balQty = $datBalHead[0]->qty;
                    $balOutQty = $datBalHead[0]->out_qty;

                    $newBalQty = $balQty + $whxQty;
                    $newBalOutQty = $balOutQty - $whxQty;

                /* UPDATE BALANCE HEADER */
                    DB::update('UPDATE t_balance_header
                                   SET qty = ?,
                                       out_qty = ?,
                                       updated_by = ?
                                 WHERE id_balance_head = ?
                                   AND `status` = 1', [$newBalQty, $newBalOutQty, $user, $idBalHead]);

                /* LOGGING */
                    DB::insert('INSERT INTO log_transactions
                                       (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'UPDATE', 'IDHEAD: ' . $idBalHead .
                                                        ' | QTY: ' . $balQty . ' >>> ' . $newBalQty .
                                                        ' / OUT_QTY: ' . $balOutQty . ' >>> ' . $newBalOutQty .
                                                        ' | Status: 1', $user ]);
                /* RETURN TAIL */
                    $datWhxTail = DB::select('SELECT a.id_whx_tail, a.init_qty, b.from_trace_no, d.id_balance_tail, d.qty, d.out_qty
                                                FROM t_warehouse_detail a
                                                LEFT JOIN t_warehouse_header b
                                                  ON a.id_whx_head = b.id_whx_head AND b.`status` = 1
                                                LEFT JOIN t_balance_header c
                                                  ON c.trace_no = b.from_trace_no AND c.`status` = 1
                                                LEFT JOIN t_balance_detail d
                                                  ON c.id_balance_head = d.id_balance_head AND d.`status` = 1 AND a.batch_sap = d.batch_sap
                                               WHERE a.id_whx_head = ?
                                                 AND a.`status` = 1', [$idWhxHead]);
                    $lenWhxTail = count($datWhxTail);

                    for ($i = 0; $i < $lenWhxTail; $i++){
                        $idWhxTail = $datWhxTail[$i]->id_whx_tail;
                        $whxQtyTail = $datWhxTail[$i]->init_qty;
                        $idBalTail = $datWhxTail[$i]->id_balance_tail;
                        $balQtyTail = $datWhxTail[$i]->qty;
                        $balOutQtyTail = $datWhxTail[$i]->out_qty;

                        $newBalQtyTail = $balQtyTail + $whxQtyTail;
                        $newBalOutQtyTail = $balOutQtyTail - $whxQtyTail;
                        
                        //echo('#' . $idTraceHead . '#' . $idWhxHead . ':' . $idWhxTail . ':' . $balOutQtyTail . ':' . $whxQtyTail . ':' . $idBalTail);
                        if ($newBalOutQtyTail < 0){
                            break;
                        }
                        DB::update('UPDATE t_balance_detail
                                       SET qty = ?,
                                           out_qty = ?,
                                           updated_by = ?
                                     WHERE id_balance_tail = ?
                                       AND `status` = 1', [$newBalQtyTail, $newBalOutQtyTail, $user, $idBalTail]);
                        /* LOGGING */
                        DB::insert('INSERT INTO log_transactions
                                           (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'UPDATE', 'IDTAIL: ' . $idBalTail .
                                                            ' | QTY: ' . $balQtyTail . ' >>> ' . $newBalQtyTail .
                                                            ' / OUT_QTY: ' . $balOutQtyTail . ' >>> ' . $newBalOutQtyTail .
                                                            ' | Status: 1', $user ]);

                    }

                /* WAREHOUSE UPDATE TO STATUS = 0 */
                    DB::update('UPDATE t_warehouse_header
                                   SET `status` = 0,
                                       updated_by = ?
                                 WHERE id_whx_head = ?', [$user, $idWhxHead]);
                    DB::update('UPDATE t_warehouse_detail
                                   SET `status` = 0,
                                       updated_by = ?
                                 WHERE id_whx_head = ?', [$user, $idWhxHead]);

                /* TRACE UPDATE TO STATUS = 0 */
                    DB::update('UPDATE t_trace_header
                                   SET `status` = 0,
                                       updated_by = ?
                                 WHERE id_trace_head = ?', [$user, $idTraceHead]);
                    DB::update('UPDATE t_trace_detail
                                   SET `status` = 0,
                                       updated_by = ?
                                 WHERE id_trace_head = ?', [$user, $idTraceHead]);

                    DB::update('UPDATE t_trace_header
                                   SET `status` = 0,
                                       updated_by = ?
                                 WHERE id_trace_head = ?', [$user, $datTraceHeadFeed[0]->id_trace_head]);
                    DB::update('UPDATE t_trace_detail
                                   SET `status` = 0,
                                       updated_by = ?
                                 WHERE id_trace_head = ?', [$user, $datTraceHeadFeed[0]->id_trace_head]);
            }

        /* THROW OUTPUT */
            $db = [ (object)['response' => 1 ]];
            return $db;
    }
    static function post_entryPck($user, $request){
        $id = $request->input('id');
        $entryDate = $request->input('entryDate');
        $idMaterialPck = $request->input('fgProduct');
        $batchNo = $request->input('batchNo');
        $qtyPck = $request->input('qty');
        $poNo = $request->input('poNo');
        $idTank = $request->input('tank');
        $idWarehouse = $request->input('warehouse');
        $idPlant = DB::table('m_tank')
          ->where('id_tank', $idTank)
          ->value('id_plant')
          ?? \App\Models\BaseModel::resolvePlant($request);

        $whID = str_pad($idWarehouse, 3, "0", STR_PAD_LEFT);;
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
        /* CREATE BATCH NUMBER */
            $datPckBatch = DB::select('SELECT a.pck_batch
                                         FROM (SELECT CONCAT(?, DATE_FORMAT(CURDATE(), "%y%m%d"), ?, LPAD(SUBSTRING(a.to_trace_no,13,2) + 1,2,0)) AS pck_batch
                                                 FROM t_trace_header a
                                                WHERE SUBSTRING(a.to_trace_no,1,7) = CONCAT(?, DATE_FORMAT(CURDATE(), "%y%m%d"))
                                                  AND a.status = 1
                                                ORDER BY a.id_trace_head DESC
                                                LIMIT 1 ) a
                                        UNION ALL
                                       SELECT CONCAT(?, DATE_FORMAT(CURDATE(), "%y%m%d"), ? , LPAD(RIGHT(?, 2), 2, "0"), "01") AS pck_batch
                                        LIMIT 1', [self::$movType1, $whID, self::$movType1, self::$movType1, $whID, $idPlant]);
            $traceNoWhx = $datPckBatch[0]->pck_batch;
            // Problem with Trace Loop
            $traceNoTrf = substr_replace($traceNoWhx, '000', 7, 3); /* REPLACE WHX_ID WITH TRANSFER_ID */

        /* GET PRODUCT */
            $datMaterial = DB::select('SELECT a.id_material, b.code
                                         FROM m_material_pck a
                                         LEFT JOIN m_material b
                                           ON a.id_material = b.id_material
                                        WHERE a.id_materialpck = ?', [$idMaterialPck]);
            $idMaterialFeed = $datMaterial[0]->id_material;
            $codeMaterial = $datMaterial[0]->code;

        /* CEK BALANCE QTY */
            $datBalQty = DB::select('SELECT SUM(b.qty) AS total
                                       FROM m_material a
                                       LEFT JOIN t_balance_header b
                                         ON b.id_material = a.id_material
                                      WHERE a.code = ?
                                        AND a.status = 1
                                        AND b.status = 1
                                        AND b.qty > "0.0001"
                                        AND b.id_tank = ?
                                        AND b.id_plant = ?
                                      ORDER BY b.id_balance_head ASC', [$codeMaterial, $idTank, $idPlant]);
            $leftOver = $datBalQty[0]->total - $qtyPck;

            if ($leftOver < 0){
                $db = [ (object)['response' => 4 ]];
                return $db;
            }

        /* FIND BALANCE BATCH */
            $datBalHead = DB::select('SELECT b.id_balance_head, b.qty, b.out_qty, b.trace_no, b.init_qty
                                        FROM m_material a
                                        LEFT JOIN t_balance_header b
                                          ON b.id_material = a.id_material
                                       WHERE a.code = ?
                                         AND a.status = 1
                                         AND b.status = 1
                                         AND b.qty > "0.0001"
                                         AND b.id_tank = ?
                                         AND b.id_plant = ?
                                       ORDER BY b.id_balance_head ASC', [$codeMaterial, $idTank, $idPlant]);

            $lenBalHead = count($datBalHead);
            $qtyWh = $qtyPck;

            if ($lenBalHead == 0){
                $db = [ (object)['response' => 3 ]];
                return $db;
            }

            for ($i=0; $i < $lenBalHead; $i++){
                $idHead = $datBalHead[$i]->id_balance_head;
                $from_trace_no = $datBalHead[$i]->trace_no;
                $qtyHead = $datBalHead[$i]->qty;
                $outQtyHead = $datBalHead[$i]->out_qty;
                $init_qty = $datBalHead[$i]->init_qty;

                $new_total_out_qty = $outQtyHead + $qtyWh;
                $balanceAfter = $qtyHead - $qtyWh;

                if ($balanceAfter < 0){
                    $new_balance = 0;
                    $leftOver_qtyWh = $qtyWh - $qtyHead;

                    $new_total_out_qty = $init_qty;
                    $qtyWh = $qtyHead;

                } else {
                    $new_balance = $qtyHead - $qtyWh;

                };

                /* GET BALANCE DETAIL CHECKER */
                    $datBalTail = DB::select('SELECT a.id_balance_tail, a.id_supplier, a.batch_sap, a.qty, a.out_qty, a.init_qty
                                                FROM t_balance_detail a
                                               WHERE a.status = 1
                                                 AND a.qty > "0.0001"
                                                 AND a.id_balance_head = ?
                                               ORDER BY a.id_balance_tail ASC', [$idHead]);
                    $lenBalTail = count($datBalTail);

                    if ($lenBalTail == 0){
                        $db = [ (object)['response' => 3 ]];
                        return $db;
                    }

                /* UPDATE INTO T_BALANCE_HEADER */
                    DB::update('UPDATE t_balance_header
                                   SET qty = ?,
                                       out_qty = ?,
                                       updated_by = ?
                                 WHERE id_balance_head = ?',
                                 [$new_balance, $new_total_out_qty, $user, $idHead]);

                /* INSERT TRACE HEADER FEED */
                    $idTraceHead = DB::table('t_trace_header')->insertGetId([
                            'from_trace_no' => $from_trace_no,
                            'to_trace_no' => $traceNoTrf,
                            'id_balance_head' => $idHead,
                            'id_material' => $idMaterialFeed,
                            'entry_date' => $entryDate,
                            'id_sloc' => $idTank,
                            'out_qty' => $qtyWh,
                            'curr_qtf' => $qtyPck,
                            'id_plant' => $idPlant,
                            'created_by' => $user,
                        ]);

                /* INSERT WAREHOUSE HEADER */
                    $idWhxHead = DB::table('t_warehouse_header')->insertGetId([
                            'entry_date' => $entryDate,
                            'from_trace_no' => $from_trace_no,
                            'trace_no' => $traceNoWhx,
                            'id_material_feed' => $idMaterialFeed,
                            'id_material_fg' => $idMaterialPck,
                            'id_section' => $idWarehouse,
                            'batch_no' => $batchNo,
                            'po_no' => $poNo,
                            'qty' => $qtyWh,
                            'in_qty' => $qtyWh,
                            'init_qty' => $qtyWh,
                            'id_plant' => $idPlant,
                            'created_by' => $user
                        ]);

                /* INSERT TRACE HEADER RUNDOWN */
                    $idTraceHeadRundown = DB::table('t_trace_header')->insertGetId([
                        'from_trace_no' => $traceNoTrf,
                        'to_trace_no' => $traceNoWhx,
                        'id_balance_head' => $idWhxHead,
                        'id_material' => $idMaterialPck,
                        'entry_date' => $entryDate,
                        'id_sloc' => $idWarehouse,
                        'in_qty' => $qtyWh,
                        'curr_qtf' => $qtyWh,
                        'id_plant' => $idPlant,
                        'created_by' => $user,
                    ]);

                /* HEADER LOGGING */
                    DB::insert('INSERT INTO log_transactions
                                       (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)', [ 'T_TRACE_HEAD', 'ADD PCK', 'IDTRACEHEAD: ' . $idTraceHead . 'IDHEAD: ' . $idHead . ' | DATE: ' . $entryDate .
                                                        ' / FROM_TRACE: ' . $from_trace_no . ' / TO_TRACE: ' . $traceNoWhx . ' / OUT_QTY: ' . $qtyWh . ' / MATERIAL: ' . $idMaterialFeed .
                                                        ' / LAST_QTF: 0 / CURR_QTF: ' . $qtyWh .
                                                        ' | Status: 1', $user ]);

                /* GET BALANCE DETAIL */
                    $qtyWhTail = $qtyWh;

                    for ($k=0; $k < $lenBalTail; $k++){
                        $idTail = $datBalTail[$k]->id_balance_tail;
                        $idSupplier = $datBalTail[$k]->id_supplier;
                        $batchSap = $datBalTail[$k]->batch_sap;
                        $qtyTail = $datBalTail[$k]->qty;
                        $outQtyTail = $datBalTail[$k]->out_qty;
                        $initQtyTail = $datBalTail[$k]->init_qty;

                        $new_tail_total_out_qty = $outQtyTail + $qtyWhTail;

                        $tailBalanceAfter = $qtyTail - $qtyWhTail;

                        if ($tailBalanceAfter < 0){
                            $new_tail_balance = 0;
                            $new_tail_total_out_qty = $initQtyTail;

                            $leftOver_qtyWhTail = $qtyWhTail - $qtyTail;
                            $qtyWhTail = $qtyTail;
                        } else {

                            $new_tail_balance = $qtyTail - $qtyWhTail;
                        }

                        $outQtyTail = round($outQtyTail, 4);
                        $initQtyTail = round($initQtyTail, 4);
                        $qtyTail = round($qtyTail, 4);
                        $new_tail_total_out_qty = round($new_tail_total_out_qty, 4);
                        $new_tail_balance = round($new_tail_balance, 4);

                        /* POPULATE NEW BALANCE DETAIL */
                            DB::update('UPDATE t_balance_detail
                                           SET qty = ?,
                                               out_qty = ?,
                                               updated_by = ?
                                         WHERE id_balance_tail = ?',
                                         [$new_tail_balance, $new_tail_total_out_qty, $user, $idTail]);

                        /* POPULATE TRACE DETAIL FEED */
                            $idTraceTail = DB::table('t_trace_detail')->insertGetId([
                                    'id_trace_head' => $idTraceHead,
                                    'id_balance_tail' => $idTail,
                                    'id_supplier' => $idSupplier,
                                    'id_material' => $idMaterialFeed,
                                    'out_qty' => $qtyWhTail,
                                    'batch_sap' => $batchSap,
                                    'id_sloc' => $idTank,
                                    'id_plant' => $idPlant,
                                    'created_by' => $user,
                            ]);
                        /* INSERT WAREHOUSE DETAIL */
                            $idWhxTail = DB::table('t_warehouse_detail')->insertGetId([
                                'id_whx_head' => $idWhxHead,
                                'id_material_feed' => $idMaterialFeed,
                                'id_material_fg' => $idMaterialPck,
                                'id_supplier' => $idSupplier,
                                'batch_sap' => $batchSap,
                                'qty' => $qtyWhTail,
                                'in_qty' => $qtyWhTail,
                                'init_qty' => $qtyWhTail,
                                'id_plant' => $idPlant,
                                'created_by' => $user
                            ]);
                        /* POPULATE TRACE DETAIL RUNDOWN TO WAREHOUSE */
                            $idTraceTailRundown = DB::table('t_trace_detail')->insertGetId([
                                    'id_trace_head' => $idTraceHeadRundown,
                                    'id_balance_tail' => $idWhxTail,
                                    'id_supplier' => $idSupplier,
                                    'id_material' => $idMaterialPck,
                                    'in_qty' => $qtyWhTail,
                                    'batch_sap' => $batchSap,
                                    'id_sloc' => $idWarehouse,
                                    'id_plant' => $idPlant,
                                    'created_by' => $user,
                            ]);
                        /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
                            if ($tailBalanceAfter >= 0){
                                break;
                            };
                        /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
                            $qtyWhTail = $leftOver_qtyWhTail;
                    }

                    //  ADJUST SUPPLIER FEED QTY TOTAL
                    $details = DB::select('SELECT d.id_balance_tail, t.id_trace_tail, d.qty, d.out_qty, d.init_qty
                                            FROM t_balance_detail d
                                            JOIN t_trace_detail t ON t.id_balance_tail = d.id_balance_tail
                                            WHERE d.id_balance_head = ?
                                            ORDER BY d.id_balance_tail ASC', [$idHead]);

                    if (!empty($details)) {
                        // Prepare array for adjustQtyToTotal
                        $dataPerHead = [array_map(function ($d) {
                            return ['qty' => (string)$d->out_qty];
                        }, $details)];

                        // Total outgoing qty must match the packaging qtyWh (the consumed qty)
                        $targetTotal = $qtyWh;

                        adjustQtyToTotal($dataPerHead, $targetTotal);

                        // Write adjusted values back
                        foreach ($details as $i => $d) {
                            $newQty = $dataPerHead[0][$i]['qty'];

                          // Update new proportional OUT for supplier batch
                          DB::update('UPDATE t_balance_detail
                                      SET out_qty = ?
                                      WHERE id_balance_tail = ?', [$newQty, $d->id_balance_tail]);

                          // Update trace detail
                          DB::update('UPDATE t_trace_detail
                                      SET out_qty = ?
                                      WHERE id_trace_tail = ?', [$newQty, $d->id_trace_tail]);
                        }
                    }

                /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
                    if ($balanceAfter >= 0){
                        break;
                    }

                /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
                    $qtyWh = $leftOver_qtyWh;
            };
            $db = [ (object)['response' => 1 ]];
            return $db;
    }
    static function post_pckEntry_poNo($user, $request){
        $mode = $request->input('mode');
        $id = $request->input('id');
        $poNo = $request->input('poNo');

        /* UPDATE DATA */
        DB::update('UPDATE t_warehouse_header
                       SET po_no = ?,
                           updated_by = ?
                     WHERE id_whx_head = ?', [$poNo, $user, $id]);

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function post_pckEntry_batchNo($user, $request){
        $mode = $request->input('mode');
        $id = $request->input('id');
        $batchNo = $request->input('batchNo');
        $idSection = $request->input('warehouse');

        if ($mode == 'UPDATE'){
            DB::update('UPDATE t_warehouse_header
                           SET batch_no = ?,
                               id_section = ?,
                               updated_by = ?
                         WHERE id_whx_head = ?', [$batchNo, $idSection, $user, $id]);
        }

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;

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
