<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Report extends Model
{
    protected $connection = 'eudr_ts';

    protected static $idTankSrc = "T00"; // STORAGE TANK
    protected static $idTankTrf = "T02"; // TRF TANK
    protected static $idTankFeed = "3"; // FEED TANK
    protected static $movSeq = "00";
    protected static $typeMaterial = "RM";
    protected static $movType1 = "1";
    protected static $movType2 = "9";
    protected static $idPlantEob1 = "1002";

    static function get_dtTsReport($request){
        $entryDate = $request->input('entryDate');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT aa.material, aa.id_material, aa.entry_date, aa.from_trace_no, aa.to_trace_no,
                                 aa.section, aa.in_qty, aa.out_qty, aa.supplier, aa.balance_supplier
                            FROM  ( SELECT CONCAT(a.description, " (", a.code, ")") AS material, a.id_material,
                                            b.entry_date, b.from_trace_no, b.to_trace_no,
                                            IF(b.in_qty = 0, SUBSTRING(a.qtf_feed,1,3), SUBSTRING(a.qtf_rundown,1,3)) AS section,
                                            FORMAT(b.in_qty,3) AS in_qty, FORMAT(b.out_qty,3) AS out_qty, b.supplier,
                                            b.balance_supplier
                                       FROM m_material a
                                       LEFT JOIN (SELECT b.id_trace_head, b.entry_date, b.id_material, b.to_trace_no, SUM(c.in_qty) AS in_qty, SUM(c.out_qty) AS out_qty,
                                                          GROUP_CONCAT(DISTINCT c.from_trace_no SEPARATOR " | ") AS from_trace_no, b.supplier, b.balance_supplier
                                                     FROM (SELECT b.id_trace_head, b.entry_date, b.id_material, b.to_trace_no,
                                                                  GROUP_CONCAT(DISTINCT CONCAT(d.description, " / ", c.batch_sap, " / Qty: ", FORMAT(c.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                                                  FORMAT(SUM(DISTINCT c.qty),3) AS balance_supplier
                                                             FROM t_trace_header b
                                                             LEFT JOIN (SELECT c.id_trace_head, c.batch_sap, c.id_supplier,
                                                                               IF(c.in_qty = 0, IF(c.out_qty = 0, 0, c.out_qty), c.in_qty) AS qty
                                                                          FROM t_trace_detail c
                                                                         WHERE c.status = 1) c
                                                               ON b.id_trace_head = c.id_trace_head
                                                             LEFT JOIN m_supplier d
                                                               ON d.id_supplier = c.id_supplier
                                                            WHERE b.status = 1
                                                              AND c.qty <> 0
                                                              AND SUBSTRING(b.to_trace_no,8,2) <> "00"
                                                              AND SUBSTRING(b.to_trace_no,1,1) <> "1"
                                                              AND SUBSTRING(b.to_trace_no,1,1) <> "6"
                                                              AND SUBSTRING(b.to_trace_no,1,1) <> "7"
                                                              AND SUBSTRING(b.to_trace_no,1,1) <> "8"
                                                              AND SUBSTRING(b.to_trace_no,1,1) <> "9"
                                                              AND b.entry_date = ?
                                                            GROUP BY b.to_trace_no) b
                                                      LEFT JOIN t_trace_header c
                                                        ON b.to_trace_no = c.to_trace_no AND c.status = 1
                                                    GROUP BY b.id_material, b.to_trace_no) b
                                        ON a.id_material = b.id_material
                                     WHERE a.status = 1
                                       AND SUBSTRING(a.qtf_rundown,1,3) <> "BLE"
                                       AND SUBSTRING(a.qtf_rundown,1,3) <> "TRA"
                                     GROUP BY a.id_material, b.to_trace_no
                                     ORDER BY a.id_rundown ASC ) aa
                           WHERE aa.section <> "-"
                           ORDER BY aa.section ASC
                          ', [$entryDate]);

        return $db;
    }
    static function get_dtTsReportRm($request){
        $entryDate = $request->input('entryDate');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.entry_date, a.id_trace_head, GROUP_CONCAT(DISTINCT a.to_trace_no SEPARATOR " | ") AS to_trace_no,
                                 FORMAT(SUM(DISTINCT a.in_qty),3) AS in_qty, "-" AS from_trace_no,
                                 CONCAT(c.description, " (", c.code, ")") AS material, FORMAT(SUM(DISTINCT a.out_qty),3) AS out_qty, "STORAGE TANK" AS sloc,
                                 GROUP_CONCAT(DISTINCT CONCAT(d.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                 FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
                            FROM t_trace_header a
                            LEFT JOIN (SELECT b.id_trace_head, b.batch_sap, b.id_supplier,
                                              IF(b.in_qty = 0, IF(b.out_qty = 0, 0, b.out_qty), b.in_qty) AS qty
                                         FROM t_trace_detail b
                                        WHERE b.status = 1) b
                              ON a.id_trace_head = b.id_trace_head
                            LEFT JOIN m_material c
                              ON a.id_material = c.id_material
                            LEFT JOIN m_supplier d
                              ON b.id_supplier = d.id_supplier
                           WHERE a.status = 1
                             AND a.from_trace_no IS NULL
                             AND (SUBSTRING(a.to_trace_no,1,1) = 1 OR SUBSTRING(a.to_trace_no,1,1) = 9)
                             AND (SUBSTRING(a.to_trace_no,8,2) = "00")
                             AND b.qty <> 0
                             AND a.entry_date = ?
                            GROUP BY c.code
                           UNION ALL
                          SELECT a.entry_date, a.id_trace_head, a.to_trace_no, a.in_qty, a.from_trace_no,
                                 a.material, a.out_qty, a.sloc, a.supplier, a.balance_supplier
                            FROM (
                                    SELECT a.entry_date, a.id_trace_head, GROUP_CONCAT(DISTINCT a.to_trace_no SEPARATOR " | ") AS to_trace_no,
                                           FORMAT(SUM(DISTINCT a.in_qty),3) AS in_qty, "-" AS from_trace_no,
                                            CONCAT(c.description, " (", c.code, ")") AS material, FORMAT(SUM(DISTINCT a.out_qty),3) AS out_qty, f.description AS sloc,
                                            GROUP_CONCAT(DISTINCT CONCAT(d.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                            FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
                                        FROM t_trace_header a
                                        LEFT JOIN (SELECT b.id_trace_head, b.batch_sap, b.id_supplier,
                                                        IF(b.in_qty = 0, IF(b.out_qty = 0, 0, b.out_qty), b.in_qty) AS qty
                                                    FROM t_trace_detail b
                                                    WHERE b.status = 1) b
                                          ON a.id_trace_head = b.id_trace_head
                                        LEFT JOIN m_material c
                                          ON a.id_material = c.id_material
                                        LEFT JOIN m_supplier d
                                          ON b.id_supplier = d.id_supplier
                                        LEFT JOIN t_balance_header e
                                          ON a.id_balance_head = e.id_balance_head
                                        LEFT JOIN m_tank f
                                          ON e.id_tank = f.id_tank
                                       WHERE a.status = 1
                                         AND a.from_trace_no IS NOT NULL
                                         AND (SUBSTRING(a.to_trace_no,1,1) = 1 OR SUBSTRING(a.to_trace_no,1,1) = 9)
                                         AND (SUBSTRING(a.to_trace_no,8,2) = "01" OR SUBSTRING(a.to_trace_no,8,2) = "02")
                                         AND b.qty <> 0
                                         AND a.entry_date = ?
                                       GROUP BY a.to_trace_no, c.code
                                       ORDER BY a.to_trace_no DESC ) a
                         ', [$entryDate, $entryDate]);

        return $db;
    }
    static function get_dtTsReportPck($request){
        $entryDate = $request->input('entryDate');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.entry_date, a.id_trace_head, a.to_trace_no, FORMAT(SUM(DISTINCT a.in_qty),3) AS in_qty,
                                 e.batch_no, e.po_no, FORMAT(SUM(DISTINCT a.out_qty),3) AS out_qty, e.from_trace_no,
                                 IF(SUBSTRING(a.to_trace_no,1,1) = 4, CONCAT(c.description, " (", c.code, ")"), CONCAT(f.description, " (", f.code, ")")) AS material,
                                 GROUP_CONCAT(DISTINCT CONCAT(d.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                 FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
                            FROM t_trace_header a
                            LEFT JOIN (SELECT b.id_trace_head, b.batch_sap, b.id_supplier,
                                              IF(b.in_qty = 0, IF(b.out_qty = 0, 0, b.out_qty), b.in_qty) AS qty
                                         FROM t_trace_detail b
                                        WHERE b.status = 1) b
                              ON a.id_trace_head = b.id_trace_head
                            LEFT JOIN m_material_pck c
                              ON a.id_material = c.id_materialpck
                            LEFT JOIN m_supplier d
                              ON b.id_supplier = d.id_supplier
                            LEFT JOIN t_warehouse_header e
                              ON a.to_trace_no = e.trace_no AND e.status = 1
                            LEFT JOIN m_material f
                              ON f.id_material = a.id_material
                           WHERE a.status = 1
                             AND (SUBSTRING(a.to_trace_no,1,1) = 4 OR SUBSTRING(a.to_trace_no,1,1) = 9)
                             AND SUBSTRING(a.to_trace_no,8,2) <> "00"
                             AND SUBSTRING(a.from_trace_no,1,1) <> 9
                             AND b.qty <> 0
                             AND a.entry_date = ?
                           GROUP BY a.to_trace_no
                         ',  [$entryDate]);

        return $db;
    }
    static function get_dtTsReportShip($request){
        $entryDate = $request->input('entryDate');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.entry_date, a.id_trace_head, a.to_trace_no, FORMAT(SUM(DISTINCT a.in_qty),3) AS in_qty, e.so_no, FORMAT(SUM(DISTINCT a.out_qty),3) AS out_qty,
                                 a.from_trace_no, IF(SUBSTRING(a.from_trace_no,1,1) = 4, CONCAT(c.description, " (", c.code, ")"), CONCAT(f.description, " (", f.code, ")")) AS material,
                                 GROUP_CONCAT(DISTINCT CONCAT(d.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                 FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
                            FROM t_trace_header a
                            LEFT JOIN (SELECT b.id_trace_head, b.batch_sap, b.id_supplier,
                                              IF(b.in_qty = 0, IF(b.out_qty = 0, 0, b.out_qty), b.in_qty) AS qty
                                         FROM t_trace_detail b
                                        WHERE b.status = 1) b
                              ON a.id_trace_head = b.id_trace_head
                            LEFT JOIN m_material_pck c
                              ON a.id_material = c.id_materialpck
                            LEFT JOIN m_supplier d
                              ON b.id_supplier = d.id_supplier
                            LEFT JOIN t_shipment_header e
                              ON a.to_trace_no = e.trace_no AND e.status = 1
                            LEFT JOIN m_material f
                              ON f.id_material = a.id_material
                           WHERE a.status = 1
                             AND (SUBSTRING(a.to_trace_no,1,1) = 5)
                             AND b.qty <> 0
                             AND a.entry_date = ?
                           GROUP BY a.to_trace_no
                         ',  [$entryDate]);

        return $db;
    }
    static function get_dtTsReportTrf($request){
        $entryDate = $request->input('entryDate');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.entry_date, a.id_trace_head, a.to_trace_no, FORMAT(SUM(DISTINCT a.in_qty),3) AS in_qty, FORMAT(SUM(DISTINCT a.out_qty),3) AS out_qty,
                                 a.from_trace_no, CONCAT(c.description, " (", c.code, ")") AS material, f.description AS sloc,
                                 GROUP_CONCAT(DISTINCT CONCAT(d.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                 FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
                            FROM t_trace_header a
                            LEFT JOIN (SELECT b.id_trace_head, b.batch_sap, b.id_supplier,
                                              IF(b.in_qty = 0, IF(b.out_qty = 0, 0, b.out_qty), b.in_qty) AS qty
                                         FROM t_trace_detail b
                                        WHERE b.status = 1) b
                              ON a.id_trace_head = b.id_trace_head
                            LEFT JOIN m_material c
                              ON a.id_material = c.id_material
                            LEFT JOIN m_supplier d
                              ON b.id_supplier = d.id_supplier
                            LEFT JOIN t_balance_header e
                              ON a.to_trace_no = e.trace_no AND e.status = 1
                            LEFT JOIN m_tank f
                              ON f.id_tank = a.id_sloc
                           WHERE a.status = 1
                             AND (SUBSTRING(a.to_trace_no,1,1) = 7 OR SUBSTRING(a.to_trace_no,1,1) = 9)
                             AND SUBSTRING(a.to_trace_no,8,2) <> "00"
                             AND b.qty <> 0
                             AND a.entry_date = ?
                           GROUP BY a.to_trace_no
                           ORDER BY a.id_trace_head DESC
                         ',  [$entryDate]);

        return $db;
    }

    /* RM TO PRD MODULE */
    static function get_dtSummaryRmPrd($request){
        $selectedYear = $request->input('selectedYear');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.id_balance_head, a.id_material, a.id_tank, a.status,
                                 CAST(a.trace_no AS CHAR) AS trace_no, FORMAT(SUM(DISTINCT a.qty),3) AS qty, a.created_by, a.created_at,
                                 CONCAT(c.code, " :: ", c.description) AS material, FORMAT(SUM(DISTINCT a.init_qty),3) AS init_qty,
                                 d.description AS tf_number, a.entry_date, b.batch_sap,
                                 GROUP_CONCAT(DISTINCT b.id_balance_tail SEPARATOR ",") AS id_balance_detail,
                                 GROUP_CONCAT(DISTINCT CONCAT(e.code, " :: ", e.description, " / ", b.batch_sap, " / Qty : ", FORMAT(b.init_qty, 3), " MT") SEPARATOR " | ") AS supplier,
                                 IF(b.out_qty = 0, "N/A", "") AS traced, f.material_document, f.po_so, f.id_trace_head,
                                 g.qty_tank, h.qty_warehouse
                            FROM t_balance_header a
                            LEFT JOIN t_balance_detail b
                              ON a.id_balance_head = b.id_balance_head AND b.status = 1
                            LEFT JOIN m_material c
                              ON a.id_material = c.id_material
                            LEFT JOIN m_tank d
                              ON a.id_tank = d.id_tank AND d.status = 1 AND d.id_plant = ?
                            LEFT JOIN m_supplier e
                              ON e.id_supplier = b.id_supplier
                            LEFT JOIN (SELECT f.id_balance_head, g.material_document, g.po_so, f.id_trace_head
									     FROM t_trace_header f
									 	 LEFT JOIN t_material_document g
                                           ON f.id_trace_head = g.id_trace_head
                                        WHERE f.status = 1
										GROUP BY f.id_balance_head) f
                              ON f.id_balance_head = a.id_balance_head
                            LEFT JOIN ( SELECT b.batch_sap AS batch_sap, FORMAT(ROUND(SUM(b.balance),3),3) AS qty_tank
                                          FROM m_tank a
                                          LEFT JOIN (SELECT b.id_tank, b.id_balance_head, bb.batch_sap, b.id_material,
                                                            SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                                       FROM t_balance_header b
                                                       LEFT JOIN t_balance_detail bb
                                                         ON b.id_balance_head = bb.id_balance_head
                                                      WHERE b.status = 1
                                                        AND bb.status = 1
                                                      GROUP BY b.id_tank, bb.id_balance_head, bb.id_material, bb.batch_sap
                                                    ) b
                                            ON a.id_tank = b.id_tank
                                         WHERE a.status = 1
                                           AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                                         GROUP BY b.batch_sap
                                 ) g
                              ON g.batch_sap = b.batch_sap
                            LEFT JOIN ( SELECT b.batch_sap AS batch_sap, FORMAT(ROUND(SUM(b.in_qty),3),3) AS qty_warehouse
                                          FROM m_warehouse a
                                          LEFT JOIN (SELECT b.id_section, b.id_whx_head, bb.batch_sap, b.id_material_fg, b.trace_no,
                                                            SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance,
                                                            b.batch_no
                                                       FROM t_warehouse_header b
                                                       LEFT JOIN t_warehouse_detail bb
                                                         ON b.id_whx_head = bb.id_whx_head
                                                      WHERE b.status = 1
                                                        AND bb.status = 1
                                                      GROUP BY b.id_section, bb.id_material_fg, bb.batch_sap
                                                    ) b
                                            ON a.id_warehouse = b.id_section
                                         WHERE a.status = 1
                                           AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                                         GROUP BY b.batch_sap
                                 ) h
                              ON h.batch_sap = b.batch_sap
                           WHERE c.type = ?
                             AND (SUBSTRING(a.trace_no,1,1) = ? OR SUBSTRING(a.trace_no,1,1) = ?)
                             AND SUBSTRING(a.trace_no,8,2) = ?
                             AND a.id_tank = 4
                             AND a.status = 1
                             AND YEAR(a.entry_date) = ?
                           GROUP BY a.trace_no
                           ORDER BY a.id_balance_head DESC
                           ', [self::$idPlantEob1, self::$typeMaterial, self::$movType1, self::$movType2, self::$movSeq, $selectedYear]);
        return $db;
    }

    static function get_dtDetailRmPrd_onTank($request){
        $batchSap = $request->input('batchSap');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT "" AS sloc, "BALANCE ON WIP" AS material, "" AS out_qty, "" AS in_qty, FORMAT(ROUND(SUM(a.balance),3),3) AS balance
                            FROM (
                                  SELECT a.description AS sloc, CONCAT("(", c.code, ") ", c.description) AS material,
                                        FORMAT(ROUND(SUM(b.in_qty),3),3) AS in_qty, FORMAT(ROUND(SUM(b.out_qty),3),3) AS out_qty,
                                        FORMAT(ROUND(SUM(b.balance),3),3) AS balance
                                    FROM m_tank a
                                    LEFT JOIN (SELECT b.id_tank, b.id_balance_head, bb.batch_sap, b.id_material,
                                                    SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                                FROM t_balance_header b
                                                LEFT JOIN t_balance_detail bb
                                                ON b.id_balance_head = bb.id_balance_head
                                                WHERE b.status = 1
                                                AND bb.status = 1
                                                AND bb.batch_sap = ?
                                                GROUP BY b.id_tank, bb.id_balance_head, bb.id_material, bb.batch_sap
                                         ) b
                                      ON a.id_tank = b.id_tank
                                    LEFT JOIN m_material c
                                      ON c.id_material = b.id_material
                                   WHERE a.status = 1
                                     AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                                   GROUP BY a.id_tank, b.id_material
                                   ORDER BY FIELD(a.description, "STORAGE TANK", "FEED TANK", "WIP TANK", "PRODUCT TANK",
                                            "EOB2", "EOB3", "EOMB", "MPR", "UFA", "ADJUSTMENT IN", "ADJUSTMENT OUT"), b.id_material ASC
                                ) a
                           UNION ALL
                          SELECT a.sloc, a.material, a.out_qty, a.in_qty, a.balance
                            FROM (
                                  SELECT a.description AS sloc, CONCAT("(", c.code, ") ", c.description) AS material,
                                        FORMAT(ROUND(SUM(b.in_qty),3),3) AS in_qty, FORMAT(ROUND(SUM(b.out_qty),3),3) AS out_qty,
                                        FORMAT(ROUND(SUM(b.balance),3),3) AS balance
                                    FROM m_tank a
                                    LEFT JOIN (SELECT b.id_tank, b.id_balance_head, bb.batch_sap, b.id_material,
                                                    SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                                FROM t_balance_header b
                                                LEFT JOIN t_balance_detail bb
                                                ON b.id_balance_head = bb.id_balance_head
                                                WHERE b.status = 1
                                                AND bb.status = 1
                                                AND bb.batch_sap = ?
                                                GROUP BY b.id_tank, bb.id_balance_head, bb.id_material, bb.batch_sap
                                         ) b
                                      ON a.id_tank = b.id_tank
                                    LEFT JOIN m_material c
                                      ON c.id_material = b.id_material
                                   WHERE a.status = 1
                                     AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                                   GROUP BY a.id_tank, b.id_material
                                   ORDER BY FIELD(a.description, "STORAGE TANK", "FEED TANK", "WIP TANK", "PRODUCT TANK",
                                            "EOB2", "EOB3", "EOMB", "MPR", "UFA", "ADJUSTMENT IN", "ADJUSTMENT OUT"), b.id_material ASC
                                ) a
                        ', [$batchSap, $batchSap]);

        return $db;
    }

    static function get_dtDetailRmPrd_onWarehouse($request){
        $batchSap = $request->input('batchSap');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT "" AS sloc, "TOTAL" AS material, FORMAT(ROUND(SUM(a.out_qty),3),3) AS out_qty, FORMAT(ROUND(SUM(a.in_qty),3),3) AS in_qty,
                                 FORMAT(ROUND(SUM(a.balance),3),3) AS balance, "" AS so_no, "" AS batch_no, "" AS shipment
                            FROM (
                                SELECT a.description AS sloc, CONCAT("(", c.code, ") ", c.description) AS material,
                                       FORMAT(ROUND(SUM(b.in_qty),3),3) AS in_qty, FORMAT(ROUND(SUM(b.out_qty),3),3) AS out_qty,
                                       FORMAT(ROUND(SUM(b.balance),3),3) AS balance, d.so_no, b.batch_no,
                                       GROUP_CONCAT(DISTINCT CONCAT(d.so_no, " / Batch : ", b.batch_no, " / Qty : ", d.qty, " MT") SEPARATOR "|") AS shipment
                                  FROM m_warehouse a
                                  LEFT JOIN (SELECT b.id_section, b.id_whx_head, bb.batch_sap, b.id_material_fg, b.trace_no,
                                                    SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance,
                                                    b.batch_no
                                               FROM t_warehouse_header b
                                               LEFT JOIN t_warehouse_detail bb
                                                 ON b.id_whx_head = bb.id_whx_head
                                              WHERE b.status = 1
                                                AND bb.status = 1
                                                AND bb.batch_sap = ?
                                              GROUP BY b.id_section, bb.id_material_fg, bb.batch_sap
                                        ) b
                                    ON a.id_warehouse = b.id_section
                                  LEFT JOIN m_material_pck c
                                    ON c.id_materialpck = b.id_material_fg
                                  LEFT JOIN (SELECT d.from_trace_no, d.so_no, ROUND(SUM(dd.qty),3) AS qty
                                               FROM t_shipment_header d
                                               LEFT JOIN t_shipment_detail dd
                                                 ON d.id_ship_head = dd.id_ship_head
                                              WHERE d.`status` = 1
                                                AND dd.`status` = 1
                                                AND dd.batch_sap = ?
                                                AND dd.qty > "0.001"
                                              GROUP BY dd.id_material_fg, dd.batch_sap
                                                ) d
                                    ON b.trace_no = d.from_trace_no
                                 WHERE a.status = 1
                                   AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                                 GROUP BY a.id_warehouse, b.id_material_fg
                                 ORDER BY b.id_material_fg ASC
                           ) a
                           UNION ALL
                          SELECT a.sloc, a.material, a.out_qty, a.in_qty, a.balance, a.so_no, a.batch_no, a.shipment
                            FROM (
                                SELECT a.description AS sloc, CONCAT("(", c.code, ") ", c.description) AS material,
                                       FORMAT(ROUND(SUM(b.in_qty),3),3) AS in_qty, FORMAT(ROUND(SUM(b.out_qty),3),3) AS out_qty,
                                       FORMAT(ROUND(SUM(b.balance),3),3) AS balance, d.so_no, b.batch_no,
                                       GROUP_CONCAT(DISTINCT CONCAT(d.so_no, " / Batch : ", b.batch_no, " / Qty : ", d.qty, " MT") SEPARATOR "|") AS shipment
                                  FROM m_warehouse a
                                  LEFT JOIN (SELECT b.id_section, b.id_whx_head, bb.batch_sap, b.id_material_fg, b.trace_no,
                                                    SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance,
                                                    b.batch_no
                                               FROM t_warehouse_header b
                                               LEFT JOIN t_warehouse_detail bb
                                                 ON b.id_whx_head = bb.id_whx_head
                                              WHERE b.status = 1
                                                AND bb.status = 1
                                                AND bb.batch_sap = ?
                                              GROUP BY b.id_section, bb.id_material_fg, bb.batch_sap
                                        ) b
                                    ON a.id_warehouse = b.id_section
                                  LEFT JOIN m_material_pck c
                                    ON c.id_materialpck = b.id_material_fg
                                  LEFT JOIN (SELECT d.from_trace_no, d.so_no, ROUND(SUM(dd.qty),3) AS qty
                                               FROM t_shipment_header d
                                               LEFT JOIN t_shipment_detail dd
                                                 ON d.id_ship_head = dd.id_ship_head
                                              WHERE d.`status` = 1
                                                AND dd.`status` = 1
                                                AND dd.batch_sap = ?
                                                AND dd.qty > "0.001"
                                              GROUP BY dd.id_material_fg, dd.batch_sap
                                                ) d
                                    ON b.trace_no = d.from_trace_no
                                 WHERE a.status = 1
                                   AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                                 GROUP BY a.id_warehouse, b.id_material_fg
                                 ORDER BY b.id_material_fg ASC
                           ) a
                        ', [$batchSap, $batchSap, $batchSap, $batchSap]);

        return $db;
    }
}
