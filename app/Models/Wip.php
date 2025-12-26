<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Wip extends Model
{
    protected $connection = 'eudr_ts';

    protected static $movType1 = "2";
    protected static $movType2 = "3";
    protected static $movType3 = "7";
    protected static $movType4 = "8";
    protected static $movType5 = "9";
    protected static $idPlantEob1 = "1002";

    static function get_dtBalance($request, $rundownId){
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT aa.id_balance_head, aa.id_material, aa.id_tank, aa.status,
                                 aa.trace_no, aa.qty, aa.created_by, aa.created_at,
                                 aa.material, aa.init_qty, aa.tf_number AS sloc, aa.entry_date,
                                 aa.id_balance_detail, aa.supplier, aa.traced, aa.material_document,
                                 aa.balance_supplier
                            FROM (   SELECT e.id_balance_head, e.id_material, e.id_tank, e.status,
                                            e.trace_no, e.qty, e.created_by, e.created_at, e.init_qty,
                                            e.material, e.tf_number, e.entry_date,
                                            e.id_balance_detail, e.supplier,
                                            e.traced, e.material_document, e.balance_supplier
                                       FROM m_material c
                                       LEFT JOIN (SELECT d.code, d.id_material
                                                    FROM m_material d
                                                   WHERE d.status = 1) d
                                         ON c.code = d.code
                                       LEFT JOIN (SELECT a.id_balance_head, a.id_material, a.id_tank, a.status,
                                                         a.trace_no, aa.qty, a.created_by, a.created_at, aa.init_qty,
                                                         GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                                         d.description AS tf_number, a.entry_date,
                                                         GROUP_CONCAT(DISTINCT b.id_balance_tail SEPARATOR ",") AS id_balance_detail,
                                                         GROUP_CONCAT(DISTINCT CONCAT(e.description, " / ", b.batch_sap, " / Qty : ", FORMAT(b.init_qty,3), " MT / Qty : ", FORMAT(b.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                                         FORMAT(SUM(b.init_qty),3) AS balance_supplier,
                                                         IFNULL(f.to_trace_no, "N/A") AS traced, f.material_document
                                                    FROM m_tank d
                                                    LEFT JOIN (SELECT a.id_tank, a.id_balance_head, a.id_material, a.status, a.trace_no,
                                                                      a.created_by, a.created_at, a.entry_date
                                                                 FROM t_balance_header a
                                                                WHERE a.status = 1
                                                                  AND (SUBSTRING(a.trace_no,1,1) = 1 OR SUBSTRING(a.trace_no,1,1) = 2 OR SUBSTRING(a.trace_no,1,1) = 7 OR
                                                                       SUBSTRING(a.trace_no,1,1) = 8 OR SUBSTRING(a.trace_no,1,1) = 9)
                                                               ) a
                                                      ON a.id_tank = d.id_tank
                                                    LEFT JOIN (SELECT id_balance_head, FORMAT(SUM(qty),3) AS qty, FORMAT(SUM(init_qty),3) AS init_qty
                                                                 FROM t_balance_header
                                                                WHERE `status` = 1
                                                                  AND (SUBSTRING(trace_no,1,1) = 1 OR SUBSTRING(trace_no,1,1) = 2 OR SUBSTRING(trace_no,1,1) = 7 OR
                                                                       SUBSTRING(trace_no,1,1) = 8 OR SUBSTRING(trace_no,1,1) = 9)
                                                                GROUP BY trace_no
                                                                ) aa
                                                      ON a.id_balance_head = aa.id_balance_head
                                                    LEFT JOIN t_balance_detail b
                                                      ON a.id_balance_head = b.id_balance_head AND b.init_qty > "0.0001"
                                                    LEFT JOIN m_material c
                                                      ON a.id_material = c.id_material
                                                    LEFT JOIN m_supplier e
                                                      ON e.id_supplier = b.id_supplier
                                                    LEFT JOIN (SELECT f.id_balance_head, g.material_document, f.to_trace_no
                                                                 FROM t_trace_header f
                                                                 LEFT JOIN t_material_document g
                                                                   ON f.id_trace_head = g.id_trace_head
                                                                WHERE f.status = 1
                                                                  AND (SUBSTRING(f.to_trace_no,1,1) = 1 OR SUBSTRING(f.to_trace_no,1,1) = 2 OR SUBSTRING(f.to_trace_no,1,1) = 7 OR
                                                                       SUBSTRING(f.to_trace_no,1,1) = 8 OR SUBSTRING(f.to_trace_no,1,1) = 9)
                                                                GROUP BY f.id_balance_head) f
                                                      ON f.id_balance_head = a.id_balance_head
                                                   WHERE d.id_plant = ?
                                                     AND d.code_3 <> "STORAGE"
                                                   GROUP BY a.trace_no
                                                ) e
                                           ON d.id_material = e.id_material
                                        WHERE c.status = 1
                                          AND c.id_rundown = ?
                                ) aa
                            ORDER BY entry_date DESC
                           ', [$idPlant, $rundownId]);
        return $db;
    }
    static function get_dtRundown($request, $rundownId){
        $mode = $request->input('mode');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        if ($mode == 'LATEST'){
            $db = DB::select('SELECT a.id_trace_head, a.entry_date, a.to_trace_no AS rundown_trace_no, a.id_balance_head, a.id_material, a.id_sloc, a.id_tank_tail,
                                     FORMAT(ROUND(h.in_qty,3),3) AS in_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                     CONCAT(c.code, " :: ", c.description) AS material, g.material_document,
                                     FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf, b.batch_sap,
                                     GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.in_qty,3), " MT") SEPARATOR " | ") AS supplier,
                                     FORMAT(ROUND(bs.supplier_qty,3),3) AS balance_supplier,
                                     CONCAT(i.description, 
                                        IF(GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", ") IS NULL, 
                                            "", 
                                            CONCAT(" | ", GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", "))
                                        )
                                    ) AS sloc
                                FROM t_trace_header a
                                LEFT JOIN t_trace_detail b
                                  ON a.id_trace_head = b.id_trace_head
                                LEFT JOIN m_material c
                                  ON a.id_material = c.id_material
                                LEFT JOIN m_supplier e
                                  ON e.id_supplier = b.id_supplier
                                LEFT JOIN t_material_document g
                                  ON a.id_trace_head = g.id_trace_head
                                LEFT JOIN (SELECT a.to_trace_no, SUM(a.in_qty) AS in_qty
                                        	 FROM t_trace_header a
                               			    WHERE a.`status` = 1
                                        	GROUP BY a.to_trace_no
                                        ) h
                                  ON a.to_trace_no = h.to_trace_no
                                LEFT JOIN m_tank i
                                    ON a.id_sloc = i.id_tank
                                LEFT JOIN m_tank_detail j
                                    ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(j.id_tank_tail AS CHAR)))
                                LEFT JOIN (
                                    SELECT id_trace_head, SUM(in_qty) AS supplier_qty
                                    FROM t_trace_detail
                                    WHERE in_qty > 0
                                    GROUP BY id_trace_head
                                ) bs ON bs.id_trace_head = a.id_trace_head
                               WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                 AND a.in_qty > 0
                                 AND b.in_qty > 0
                                 AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                 AND a.status = 1
                                 AND a.id_plant = ?
                               GROUP BY a.to_trace_no
                               ORDER BY a.to_trace_no DESC
                               LIMIT 1', [$rundownId, self::$movType1, $idPlant]);
        } elseif ($mode == 'LOG'){
                $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material,
                                        FORMAT(ROUND(h.in_qty,3),3) AS in_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                        CONCAT(c.code, " :: ", c.description) AS material, g.material_document,
                                        FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf, b.batch_sap,
                                        GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.in_qty,3), " MT") SEPARATOR " | ") AS supplier,
                                        FORMAT(ROUND(SUM(b.in_qty),3),3) AS balance_supplier,
                                        CASE
                                            WHEN a.to_trace_no = (SELECT to_trace_no
                                                                    FROM t_trace_header
                                                                WHERE (SUBSTRING(to_trace_no, 1, 1) = ? OR SUBSTRING(to_trace_no, 1, 1) = ?)
                                                                    AND SUBSTRING(to_trace_no, 8, 3) = ?
                                                                    AND `status` = 1
                                                                    AND id_plant = ?
                                                                ORDER BY to_trace_no DESC LIMIT 1) THEN 1
                                            ELSE NULL
                                        END AS is_last_row,
                                        CASE
                                            WHEN a.to_trace_no = (SELECT from_trace_no
                                                                    FROM t_trace_header
                                                                WHERE from_trace_no = a.to_trace_no
                                                                    AND `status` = 1
                                                                    AND id_plant = ?
                                                                ORDER BY from_trace_no DESC LIMIT 1) THEN 1
                                            ELSE NULL
                                        END AS next_process
                                    FROM t_trace_header a
                                    LEFT JOIN t_trace_detail b
                                    ON a.id_trace_head = b.id_trace_head
                                    LEFT JOIN m_material c
                                    ON a.id_material = c.id_material
                                    LEFT JOIN m_supplier e
                                    ON e.id_supplier = b.id_supplier
                                    LEFT JOIN t_material_document g
                                    ON a.id_trace_head = g.id_trace_head
                                   LEFT JOIN (SELECT a.to_trace_no, SUM(a.in_qty) AS in_qty
                                        	 FROM t_trace_header a
                               			    WHERE a.`status` = 1
                                        	GROUP BY a.to_trace_no
                                        ) h
                                  ON a.to_trace_no = h.to_trace_no
                                WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                    AND a.in_qty > 0
                                    AND b.in_qty > 0
                                    AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                    AND a.status = 1
                                    AND a.id_plant = ?
                                GROUP BY a.to_trace_no
                                ORDER BY a.to_trace_no DESC
                                ', [self::$movType1, self::$movType2, $rundownId,
                                    $idPlant, $idPlant, $rundownId, self::$movType1, $idPlant]);

        }
        return $db;
    }
    static function get_dtFeed($request, $feedID){
        $mode = $request->input('mode');
        $feedId = substr($feedID, 0, 3);
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        if (strlen($feedID) >= 6) {
            $idMatlSign = substr($feedID, 4, 2);

            if ($feedId == '009'){
                if ($idMatlSign == '01'){
                    $idMaterial = '12';
                } elseif ($idMatlSign == '02'){
                    $idMaterial = '25';
                } elseif ($idMatlSign == '03'){
                    $idMaterial1 = '18';
                    $idMaterial2 = '22';
                } elseif ($idMatlSign == '04'){
                    $idMaterial = '14';
                }

                if ($idMatlSign == '03'){
                    if ($mode == 'LATEST'){
                        $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material, g.material_document, a.id_sloc, a.id_tank_tail,
                                                FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                                GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                                b.batch_sap, FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                                                GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                                                IF(ABS(ROUND(bs.supplier_qty,3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(bs.supplier_qty,3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                                                CONCAT(i.description, 
                                                    IF(GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", ") IS NULL, 
                                                        "", 
                                                        CONCAT(" | ", GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", "))
                                                    )
                                                ) AS sloc
                                            FROM t_trace_header a
                                            LEFT JOIN t_trace_detail b
                                            ON a.id_trace_head = b.id_trace_head
                                            LEFT JOIN m_material c
                                            ON a.id_material = c.id_material
                                            LEFT JOIN m_supplier e
                                            ON e.id_supplier = b.id_supplier
                                            LEFT JOIN t_material_document g
                                            ON a.id_trace_head = g.id_trace_head
                                            LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                                            FROM t_trace_header a
                                                            WHERE a.`status` = 1 and a.id_plant = ?
                                                            GROUP BY a.to_trace_no
                                                        ) h
                                            ON a.to_trace_no = h.to_trace_no
                                            LEFT JOIN m_tank i
                                            ON a.id_sloc = i.id_tank
                                            LEFT JOIN m_tank_detail j
                                            ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(j.id_tank_tail AS CHAR)))
                                            LEFT JOIN (
                                                SELECT h.to_trace_no, SUM(d.out_qty) AS supplier_qty
                                                FROM t_trace_header h
                                                JOIN t_trace_detail d
                                                    ON h.id_trace_head = d.id_trace_head
                                                WHERE d.out_qty > 0
                                                    AND h.status = 1
                                                GROUP BY h.to_trace_no
                                            ) bs ON bs.to_trace_no = a.to_trace_no
                                        WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                            AND a.out_qty > 0
                                            AND b.out_qty > 0
                                            AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                            AND (a.id_material = ? OR a.id_material = ?)
                                            AND a.status = 1
                                            AND a.id_plant = ?
                                        GROUP BY a.to_trace_no
                                        ORDER BY a.to_trace_no DESC
                                        LIMIT 1', [$idPlant, $feedId, self::$movType2, $idMaterial1, $idMaterial2, $idPlant]);

                    } elseif ($mode == 'LOG'){
                        $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material,
                                                FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                                GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                                FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                                                g.material_document, b.batch_sap,
                                                GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                                                IF(ABS(ROUND(SUM(b.out_qty),3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(SUM(b.out_qty),3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                                                CASE
                                                    WHEN a.to_trace_no = (SELECT to_trace_no
                                                                            FROM t_trace_header
                                                                        WHERE SUBSTRING(to_trace_no, 8, 3) = ?
                                                                            AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                                            AND (a.id_material = ? OR a.id_material = ?)
                                                                            AND `status` = 1
                                                                            AND id_plant = ?
                                                                        ORDER BY to_trace_no DESC LIMIT 1) THEN 1
                                                    ELSE NULL
                                                END AS is_last_row,
                                                CASE
                                                    WHEN a.to_trace_no = (SELECT from_trace_no
                                                                            FROM t_trace_header
                                                                        WHERE from_trace_no = a.to_trace_no
                                                                            AND (a.id_material = ? OR a.id_material = ?)
                                                                            AND `status` = 1
                                                                            AND id_plant = ?
                                                                        ORDER BY from_trace_no DESC LIMIT 1) THEN 1
                                                    ELSE NULL
                                                END AS next_process
                                            FROM t_trace_header a
                                            LEFT JOIN t_trace_detail b
                                            ON a.id_trace_head = b.id_trace_head
                                            LEFT JOIN m_material c
                                            ON a.id_material = c.id_material
                                            LEFT JOIN m_supplier e
                                            ON e.id_supplier = b.id_supplier
                                            LEFT JOIN t_material_document g
                                            ON a.id_trace_head = g.id_trace_head
                                            LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                                            FROM t_trace_header a
                                                            WHERE a.`status` = 1 AND a.id_plant = ?
                                                            GROUP BY a.to_trace_no
                                                            ) h
                                            ON a.to_trace_no = h.to_trace_no
                                        WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                            AND a.out_qty > 0
                                            AND b.out_qty > 0
                                            AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                            AND (a.id_material = ? OR a.id_material = ?)
                                            AND a.status = 1
                                            AND a.id_plant = ?
                                        GROUP BY a.to_trace_no
                                        ORDER BY a.id_trace_head DESC
                                        ', [$feedId, self::$movType2, $idMaterial1, $idMaterial2, $idPlant,
                                            $idMaterial1, $idMaterial2, $idPlant,$idPlant,
                                            $feedId, self::$movType2, $idMaterial1, $idMaterial2, $idPlant]);

                    }
                } else {
                    if ($mode == 'LATEST'){
                        //dd($feedId . ' ' . $idMaterial . ' ' . self::$movType2);
                        $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material, g.material_document, a.id_sloc, a.id_tank_tail,
                                                FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                                GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                                b.batch_sap, FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                                                GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.out_qty,3), " MT") SEPARATOR " | ") AS supplier,
                                                IF(ABS(ROUND(bs.supplier_qty,3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(bs.supplier_qty,3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                                                CONCAT(i.description, 
                                                    IF(GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", ") IS NULL, 
                                                        "", 
                                                        CONCAT(" | ", GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", "))
                                                    )
                                                ) AS sloc
                                            FROM t_trace_header a
                                            LEFT JOIN t_trace_detail b
                                            ON a.id_trace_head = b.id_trace_head
                                            LEFT JOIN m_material c
                                            ON a.id_material = c.id_material
                                            LEFT JOIN m_supplier e
                                            ON e.id_supplier = b.id_supplier
                                            LEFT JOIN t_material_document g
                                            ON a.id_trace_head = g.id_trace_head
                                            LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                                            FROM t_trace_header a
                                                            WHERE a.`status` = 1 AND a.id_plant = ?
                                                            GROUP BY a.to_trace_no
                                                            ) h
                                            ON a.to_trace_no = h.to_trace_no
                                            LEFT JOIN m_tank i
                                            ON a.id_sloc = i.id_tank
                                            LEFT JOIN m_tank_detail j
                                            ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(j.id_tank_tail AS CHAR)))
                                            LEFT JOIN (
                                                SELECT h.to_trace_no, SUM(d.out_qty) AS supplier_qty
                                                FROM t_trace_header h
                                                JOIN t_trace_detail d
                                                    ON h.id_trace_head = d.id_trace_head
                                                WHERE d.out_qty > 0
                                                    AND h.status = 1
                                                GROUP BY h.to_trace_no
                                            ) bs ON bs.to_trace_no = a.to_trace_no
                                        WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                            AND a.out_qty > 0
                                            AND b.out_qty > 0
                                            AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                            AND a.id_material = ?
                                            AND a.status = 1
                                            AND a.id_plant = ?
                                        GROUP BY a.to_trace_no
                                        ORDER BY a.to_trace_no DESC
                                        LIMIT 1', [$idPlant, $feedId, self::$movType2, $idMaterial, $idPlant]);

                    } elseif ($mode == 'LOG'){
                        $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material,
                                                FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                                GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                                FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                                                g.material_document, b.batch_sap,
                                                GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                                                IF(ABS(ROUND(SUM(b.out_qty),3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(SUM(b.out_qty),3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                                                CASE
                                                    WHEN a.to_trace_no = (SELECT to_trace_no
                                                                            FROM t_trace_header
                                                                        WHERE SUBSTRING(to_trace_no, 8, 3) = ?
                                                                            AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                                            AND a.id_material = ?
                                                                            AND `status` = 1
                                                                            AND id_plant = ?
                                                                        ORDER BY to_trace_no DESC LIMIT 1) THEN 1
                                                    ELSE NULL
                                                END AS is_last_row,
                                                CASE
                                                    WHEN a.to_trace_no = (SELECT from_trace_no
                                                                            FROM t_trace_header
                                                                        WHERE SUBSTRING(from_trace_no, 1, 1) = ?
                                                                            AND SUBSTRING(from_trace_no, 10, 1) = ?
                                                                            AND a.id_material = ?
                                                                            AND `status` = 1
                                                                            AND id_plant = ?
                                                                        ORDER BY from_trace_no DESC LIMIT 1) THEN 1
                                                    ELSE NULL
                                                END AS next_process
                                            FROM t_trace_header a
                                            LEFT JOIN t_trace_detail b
                                            ON a.id_trace_head = b.id_trace_head
                                            LEFT JOIN m_material c
                                            ON a.id_material = c.id_material
                                            LEFT JOIN m_supplier e
                                            ON e.id_supplier = b.id_supplier
                                            LEFT JOIN t_material_document g
                                            ON a.id_trace_head = g.id_trace_head
                                            LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                                            FROM t_trace_header a
                                                            WHERE a.`status` = 1 AND a.id_plant = ?
                                                            GROUP BY a.to_trace_no
                                                            ) h
                                            ON a.to_trace_no = h.to_trace_no
                                        WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                            AND a.out_qty > 0
                                            AND b.out_qty > 0
                                            AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                            AND a.id_material = ?
                                            AND a.status = 1
                                            AND a.id_plant = ?
                                        GROUP BY a.to_trace_no
                                        ORDER BY a.id_trace_head DESC
                                        ', [$feedId, self::$movType2, $idMaterial, $idPlant,
                                            self::$movType2, substr($feedId, 1, 1), $idMaterial, $idPlant, $idPlant,
                                            $feedId, self::$movType2, $idMaterial, $idPlant]);
                    }
                }

            } elseif ($feedId == '006'){
                if ($idMatlSign == '01'){
                    $idMaterial1 = '6';
                    $idMaterial2 = '31';
                } elseif ($idMatlSign == '02'){
                    $idMaterial = '66';
                }

                if ($idMatlSign == '01'){
                    if ($mode == 'LATEST'){
                        $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material, g.material_document, a.id_sloc, a.id_tank_tail,
                                                FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                                GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                                b.batch_sap, FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                                                GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                                                IF(ABS(ROUND(bs.supplier_qty,3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(bs.supplier_qty,3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                                                CONCAT(i.description, 
                                                    IF(GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", ") IS NULL, 
                                                        "", 
                                                        CONCAT(" | ", GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", "))
                                                    )
                                                ) AS sloc
                                            FROM t_trace_header a
                                            LEFT JOIN t_trace_detail b
                                            ON a.id_trace_head = b.id_trace_head
                                            LEFT JOIN m_material c
                                            ON a.id_material = c.id_material
                                            LEFT JOIN m_supplier e
                                            ON e.id_supplier = b.id_supplier
                                            LEFT JOIN t_material_document g
                                            ON a.id_trace_head = g.id_trace_head
                                            LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                                            FROM t_trace_header a
                                                            WHERE a.`status` = 1 AND a.id_plant = ?
                                                            GROUP BY a.to_trace_no
                                                        ) h
                                            ON a.to_trace_no = h.to_trace_no
                                            LEFT JOIN m_tank i
                                            ON a.id_sloc = i.id_tank
                                            LEFT JOIN m_tank_detail j
                                            ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(j.id_tank_tail AS CHAR)))
                                            LEFT JOIN (
                                                SELECT h.to_trace_no, SUM(d.out_qty) AS supplier_qty
                                                FROM t_trace_header h
                                                JOIN t_trace_detail d
                                                    ON h.id_trace_head = d.id_trace_head
                                                WHERE d.out_qty > 0
                                                    AND h.status = 1
                                                GROUP BY h.to_trace_no
                                            ) bs ON bs.to_trace_no = a.to_trace_no
                                        WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                            AND a.out_qty > 0
                                            AND b.out_qty > 0
                                            AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                            AND (a.id_material = ? OR a.id_material = ?)
                                            AND a.status = 1
                                            AND a.id_plant = ?
                                        GROUP BY a.to_trace_no
                                        ORDER BY a.to_trace_no DESC
                                        LIMIT 1', [$idPlant, $feedId, self::$movType2, $idMaterial1, $idMaterial2, $idPlant]);

                    } elseif ($mode == 'LOG'){
                        $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material,
                                                FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                                GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                                FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                                                g.material_document, b.batch_sap,
                                                GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                                                IF(ABS(ROUND(SUM(b.out_qty),3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(SUM(b.out_qty),3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                                                CASE
                                                    WHEN a.to_trace_no = (SELECT to_trace_no
                                                                            FROM t_trace_header
                                                                        WHERE SUBSTRING(to_trace_no, 8, 3) = ?
                                                                            AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                                            AND (a.id_material = ? OR a.id_material = ?)
                                                                            AND `status` = 1
                                                                            AND id_plant = ?
                                                                        ORDER BY to_trace_no DESC LIMIT 1) THEN 1
                                                    ELSE NULL
                                                END AS is_last_row,
                                                CASE
                                                    WHEN a.to_trace_no = (SELECT from_trace_no
                                                                            FROM t_trace_header
                                                                        WHERE from_trace_no = a.to_trace_no
                                                                            AND (a.id_material = ? OR a.id_material = ?)
                                                                            AND `status` = 1
                                                                            AND id_plant = ?
                                                                        ORDER BY from_trace_no DESC LIMIT 1) THEN 1
                                                    ELSE NULL
                                                END AS next_process
                                            FROM t_trace_header a
                                            LEFT JOIN t_trace_detail b
                                            ON a.id_trace_head = b.id_trace_head
                                            LEFT JOIN m_material c
                                            ON a.id_material = c.id_material
                                            LEFT JOIN m_supplier e
                                            ON e.id_supplier = b.id_supplier
                                            LEFT JOIN t_material_document g
                                            ON a.id_trace_head = g.id_trace_head
                                            LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                                            FROM t_trace_header a
                                                            WHERE a.`status` = 1 AND a.id_plant = ?
                                                            GROUP BY a.to_trace_no
                                                            ) h
                                            ON a.to_trace_no = h.to_trace_no
                                        WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                            AND a.out_qty > 0
                                            AND b.out_qty > 0
                                            AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                            AND (a.id_material = ? OR a.id_material = ?)
                                            AND a.status = 1
                                            AND a.id_plant = ?
                                        GROUP BY a.to_trace_no
                                        ORDER BY a.id_trace_head DESC
                                        ', [$feedId, self::$movType2, $idMaterial1, $idMaterial2, $idPlant,
                                            $idMaterial1, $idMaterial2, $idPlant, $idPlant,
                                            $feedId, self::$movType2, $idMaterial1, $idMaterial2, $idPlant]);

                    }
                } else {
                    if ($mode == 'LATEST'){
                        //dd($feedId . ' ' . $idMaterial . ' ' . self::$movType2);
                        $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material, g.material_document, a.id_sloc, a.id_tank_tail,
                                                FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                                GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                                b.batch_sap, FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                                                GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(b.out_qty,3), " MT") SEPARATOR " | ") AS supplier,
                                                IF(ABS(ROUND(bs.supplier_qty,3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(bs.supplier_qty,3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                                                CONCAT(i.description, 
                                                    IF(GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", ") IS NULL, 
                                                        "", 
                                                        CONCAT(" | ", GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", "))
                                                    )
                                                ) AS sloc
                                            FROM t_trace_header a
                                            LEFT JOIN t_trace_detail b
                                            ON a.id_trace_head = b.id_trace_head
                                            LEFT JOIN m_material c
                                            ON a.id_material = c.id_material
                                            LEFT JOIN m_supplier e
                                            ON e.id_supplier = b.id_supplier
                                            LEFT JOIN t_material_document g
                                            ON a.id_trace_head = g.id_trace_head
                                            LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                                            FROM t_trace_header a
                                                            WHERE a.`status` = 1 AND a.id_plant = ?
                                                            GROUP BY a.to_trace_no
                                                            ) h
                                            ON a.to_trace_no = h.to_trace_no
                                            LEFT JOIN m_tank i
                                            ON a.id_sloc = i.id_tank
                                            LEFT JOIN m_tank_detail j
                                            ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(j.id_tank_tail AS CHAR)))
                                            LEFT JOIN (
                                                SELECT h.to_trace_no, SUM(d.out_qty) AS supplier_qty
                                                FROM t_trace_header h
                                                JOIN t_trace_detail d
                                                    ON h.id_trace_head = d.id_trace_head
                                                WHERE d.out_qty > 0
                                                    AND h.status = 1
                                                GROUP BY h.to_trace_no
                                            ) bs ON bs.to_trace_no = a.to_trace_no
                                        WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                            AND a.out_qty > 0
                                            AND b.out_qty > 0
                                            AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                            AND a.id_material = ?
                                            AND a.status = 1
                                            AND a.id_plant = ?
                                        GROUP BY a.to_trace_no
                                        ORDER BY a.to_trace_no DESC
                                        LIMIT 1', [$idPlant, $feedId, self::$movType2, $idMaterial, $idPlant]);

                    } elseif ($mode == 'LOG'){
                        $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material,
                                                FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                                GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                                FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                                                g.material_document, b.batch_sap,
                                                GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                                                IF(ABS(ROUND(SUM(b.out_qty),3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(SUM(b.out_qty),3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                                                CASE
                                                    WHEN a.to_trace_no = (SELECT to_trace_no
                                                                            FROM t_trace_header
                                                                        WHERE SUBSTRING(to_trace_no, 8, 3) = ?
                                                                            AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                                            AND a.id_material = ?
                                                                            AND `status` = 1
                                                                            AND id_plant = ?
                                                                        ORDER BY to_trace_no DESC LIMIT 1) THEN 1
                                                    ELSE NULL
                                                END AS is_last_row,
                                                CASE
                                                    WHEN a.to_trace_no = (SELECT from_trace_no
                                                                            FROM t_trace_header
                                                                        WHERE SUBSTRING(from_trace_no, 1, 1) = ?
                                                                            AND SUBSTRING(from_trace_no, 10, 1) = ?
                                                                            AND a.id_material = ?
                                                                            AND `status` = 1
                                                                            AND id_plant = ?
                                                                        ORDER BY from_trace_no DESC LIMIT 1) THEN 1
                                                    ELSE NULL
                                                END AS next_process
                                            FROM t_trace_header a
                                            LEFT JOIN t_trace_detail b
                                            ON a.id_trace_head = b.id_trace_head
                                            LEFT JOIN m_material c
                                            ON a.id_material = c.id_material
                                            LEFT JOIN m_supplier e
                                            ON e.id_supplier = b.id_supplier
                                            LEFT JOIN t_material_document g
                                            ON a.id_trace_head = g.id_trace_head
                                            LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                                            FROM t_trace_header a
                                                            WHERE a.`status` = 1 AND a.id_plant = ?
                                                            GROUP BY a.to_trace_no
                                                            ) h
                                            ON a.to_trace_no = h.to_trace_no
                                        WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                            AND a.out_qty > 0
                                            AND b.out_qty > 0
                                            AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                            AND a.id_material = ?
                                            AND a.status = 1
                                            AND a.id_plant = ?
                                        GROUP BY a.to_trace_no
                                        ORDER BY a.id_trace_head DESC
                                        ', [$feedId, self::$movType2, $idMaterial, $idPlant,
                                            self::$movType2, substr($feedId, 1, 1), $idMaterial, $idPlant, $idPlant,
                                            $feedId, self::$movType2, $idMaterial, $idPlant]);
                    }
                }
            }

        } else {
            if ($mode == 'LATEST'){
                $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material, g.material_document, a.id_sloc, a.id_tank_tail,
                                         FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                         GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                         b.batch_sap, FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                                         GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                                         IF(ABS(ROUND(bs.supplier_qty,3) - ROUND(h.out_qty,3)) > 0.005, FORMAT(ROUND(bs.supplier_qty,3),3), FORMAT(ROUND(h.out_qty,3),3)) AS balance_supplier,
                                        CONCAT(i.description, 
                                            IF(GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", ") IS NULL, 
                                                "", 
                                                CONCAT(" | ", GROUP_CONCAT(DISTINCT j.tf_number ORDER BY j.tf_number ASC SEPARATOR ", "))
                                            )
                                        ) AS sloc
                                    FROM t_trace_header a
                                    LEFT JOIN t_trace_detail b
                                      ON a.id_trace_head = b.id_trace_head
                                    LEFT JOIN m_material c
                                      ON a.id_material = c.id_material
                                    LEFT JOIN m_supplier e
                                      ON e.id_supplier = b.id_supplier
                                    LEFT JOIN t_material_document g
                                      ON a.id_trace_head = g.id_trace_head
                                    LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                        				  FROM t_trace_header a
                                        				 WHERE a.`status` = 1 AND a.id_plant = ?
                                        				 GROUP BY a.to_trace_no
                                        				) h
                                         ON a.to_trace_no = h.to_trace_no
                                         LEFT JOIN m_tank i
                                         ON a.id_sloc = i.id_tank
                                         LEFT JOIN m_tank_detail j
                                         ON JSON_CONTAINS(a.id_tank_tail, JSON_QUOTE(CAST(j.id_tank_tail AS CHAR)))
                                         LEFT JOIN (
                                                SELECT h.to_trace_no, SUM(d.out_qty) AS supplier_qty
                                                FROM t_trace_header h
                                                JOIN t_trace_detail d
                                                    ON h.id_trace_head = d.id_trace_head
                                                WHERE d.out_qty > 0
                                                    AND h.status = 1
                                                GROUP BY h.to_trace_no
                                            ) bs ON bs.to_trace_no = a.to_trace_no
                                   WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                     AND a.out_qty > 0
                                     AND b.out_qty > 0
                                     AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                     AND a.status = 1
                                     AND a.id_plant = ?
                                   GROUP BY a.to_trace_no
                                   ORDER BY a.to_trace_no DESC
                                   LIMIT 1', [$idPlant, $feedId, self::$movType2, $idPlant]);

            } elseif ($mode == 'LOG'){
                $db = DB::select('SELECT a.id_trace_head, a.entry_date, CAST(a.to_trace_no AS CHAR) AS to_trace_no, a.id_balance_head, a.id_material,
                                         FORMAT(ROUND(h.out_qty,3),3) AS out_qty, a.created_by, a.updated_by, a.created_at, a.updated_at,
                                         GROUP_CONCAT(DISTINCT CONCAT(c.code, " :: ", c.description) SEPARATOR " | ") AS material,
                                         FORMAT(a.last_qtf,3) AS last_qtf, FORMAT(a.curr_qtf,3) AS curr_qtf,
                                         g.material_document, b.batch_sap,
                                         GROUP_CONCAT(DISTINCT CONCAT(a.from_trace_no, " / ", e.description, " / ", b.batch_sap, " / Qty: ", FORMAT(ROUND(b.out_qty,3),3), " MT") SEPARATOR " | ") AS supplier,
                                         IF(ABS(ROUND(SUM(b.out_qty),3) - ROUND(h.out_qty,3) ) > 0.005, FORMAT(ROUND(SUM(b.out_qty),3),3), FORMAT(ROUND(h.out_qty,3),3) ) AS balance_supplier,
                                         CASE
                                            WHEN a.to_trace_no = (SELECT to_trace_no
                                                                    FROM t_trace_header
                                                                   WHERE SUBSTRING(to_trace_no, 8, 3) = ?
                                                                     AND SUBSTRING(to_trace_no, 1, 1) = ?
                                                                     AND `status` = 1
                                                                     AND id_plant = ?
                                                                   ORDER BY to_trace_no DESC LIMIT 1) THEN 1
                                            ELSE NULL
                                        END AS is_last_row,
                                        CASE
                                            WHEN a.to_trace_no = (SELECT from_trace_no
                                                                    FROM t_trace_header
                                                                   WHERE SUBSTRING(from_trace_no, 1, 1) = ?
                                                                     AND SUBSTRING(from_trace_no, 10, 1) = ?
                                                                     AND `status` = 1
                                                                     AND id_plant = ?
                                                                   ORDER BY from_trace_no DESC LIMIT 1) THEN 1
                                            ELSE NULL
                                        END AS next_process
                                    FROM t_trace_header a
                                    LEFT JOIN t_trace_detail b
                                      ON a.id_trace_head = b.id_trace_head
                                    LEFT JOIN m_material c
                                      ON a.id_material = c.id_material
                                    LEFT JOIN m_supplier e
                                      ON e.id_supplier = b.id_supplier
                                    LEFT JOIN t_material_document g
                                      ON a.id_trace_head = g.id_trace_head
                                    LEFT JOIN (SELECT a.to_trace_no, SUM(a.out_qty) AS out_qty
                                        				  FROM t_trace_header a
                                        				 WHERE a.`status` = 1 AND a.id_plant = ?
                                        				 GROUP BY a.to_trace_no
                                        				) h
                                         ON a.to_trace_no = h.to_trace_no
                                   WHERE SUBSTRING(a.to_trace_no, 8, 3) = ?
                                     AND a.out_qty > 0
                                     AND b.out_qty > 0
                                     AND SUBSTRING(a.to_trace_no, 1, 1) = ?
                                     AND a.status = 1
                                     AND a.id_plant = ?
                                   GROUP BY a.to_trace_no
                                   ORDER BY a.id_trace_head DESC
                                ', [$feedId, self::$movType2, $idPlant, self::$movType2, substr($feedId, 1, 1),
                                    $idPlant, $idPlant, $feedId, self::$movType2, $idPlant]);

            }
        }

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
    static function get_feedNewBatchNumber($request){
        $feedID = $request->input('feedID');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $feedID = substr($feedID, 0, 3);
        $db = DB::select('SELECT a.feed_number
                            FROM (SELECT a.to_trace_no+1 AS feed_number
                                    FROM t_trace_header a
                                   WHERE SUBSTRING(a.to_trace_no,1,10) = CONCAT(3, DATE_FORMAT(CURDATE(), "%y%m%d"), ?)
                                     AND a.status = 1
                                     AND a.id_plant = ?
                                   ORDER BY a.id_trace_head DESC
                                   LIMIT 1 ) a
                            UNION ALL
                            SELECT CONCAT(3, DATE_FORMAT(CURDATE(), "%y%m%d"), ? , LPAD(RIGHT(?, 2), 2, "0"), "01") AS feed_number
                            LIMIT 1', [$feedID, $idPlant, $feedID, $idPlant]);
        return $db;
    }
    static function get_rundownNewBatchNumber($request){
        $rundownID = $request->input('rundownID');
        $section = substr($rundownID, 2, 1);
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        if ($section == 9){
          /* GET LATEST FEED TRACE NO */
          $db = DB::select('SELECT CONCAT(2, SUBSTRING(a.to_trace_no,2,6), ?, LPAD(RIGHT(?, 2), 2, "0"), SUBSTRING(a.to_trace_no,13,2)) AS rundown_number
                              FROM (SELECT to_trace_no + 1 AS to_trace_no
                                      FROM t_trace_header a
                                     WHERE a.status = 1
                                       AND a.id_plant = ?
                                       AND SUBSTRING(a.to_trace_no,1,10) = CONCAT(2, DATE_FORMAT(CURDATE(), "%y%m%d"), ?)
                                     ORDER BY to_trace_no DESC
                                     LIMIT 1) a
                                     UNION ALL
                                    SELECT CONCAT(2, DATE_FORMAT(CURDATE(), "%y%m%d"), ? , LPAD(RIGHT(?, 2), 2, "0"), "01") AS rundown_number
                                     LIMIT 1', [$rundownID, $idPlant, $idPlant, $rundownID, $rundownID, $idPlant]);
        } elseif ($section == 8){
            $db = DB::select('SELECT CONCAT(2, SUBSTRING(a.to_trace_no,2,6), ?, LPAD(RIGHT(?, 2), 2, "0"), SUBSTRING(a.to_trace_no,13,2)) AS rundown_number
                              FROM (SELECT to_trace_no + 1 AS to_trace_no
                                      FROM t_trace_header a
                                     WHERE a.status = 1
                                       AND a.id_plant = ?
                                       AND SUBSTRING(a.to_trace_no,1,10) = CONCAT(2, DATE_FORMAT(CURDATE(), "%y%m%d"), ?)
                                     ORDER BY to_trace_no DESC
                                     LIMIT 1) a
                                     UNION ALL
                                    SELECT CONCAT(2, DATE_FORMAT(CURDATE(), "%y%m%d"), ? , LPAD(RIGHT(?, 2), 2, "0"), "01") AS rundown_number
                                     LIMIT 1', [$rundownID, $idPlant, $idPlant, $rundownID, $rundownID, $idPlant]);

        } else {
          $db = DB::select('SELECT a.rundown_number
                              FROM (SELECT a.to_trace_no+1 AS rundown_number
                                      FROM t_trace_header a
                                     WHERE SUBSTRING(a.to_trace_no,1,10) = CONCAT(2, DATE_FORMAT(CURDATE(), "%y%m%d"), ?)
                                       AND a.status = 1
                                       AND a.id_plant = ?
                                     ORDER BY a.id_trace_head DESC
                                     LIMIT 1 ) a
                             UNION ALL
                            SELECT CONCAT(2, DATE_FORMAT(CURDATE(), "%y%m%d"), ? , LPAD(RIGHT(?, 2), 2, "0"), "01") AS rundown_number
                             LIMIT 1', [$rundownID, $idPlant, $rundownID, $idPlant]);
        }

        return $db;
    }
    static function get_feedLastBatch($request){
        $feedID = $request->input('feedID');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $dat = DB::select('SELECT flow_type
                             FROM m_material_flow
                            WHERE `status` = 1');
        $flowType = $dat[0]->flow_type;

        if ($flowType == 'quantifier'){
            $db = DB::select('SELECT a.curr_qtf, a.entry_date, "-NORMAL-" AS status
                                FROM ( SELECT a.curr_qtf, a.entry_date
                                         FROM t_trace_header a
                                        WHERE SUBSTRING(a.to_trace_no,1,1) = 2
                                          AND SUBSTRING(a.to_trace_no,8,3) = ?
                                          AND a.status = 1
                                          AND a.id_plant = ?
                                        ORDER BY a.id_trace_head DESC
                                        LIMIT 1 ) a
                               UNION ALL
                              SELECT 0 AS curr_qtf, DATE_FORMAT(CURDATE(), "%Y-%m-%d") AS entry_date, "-INIT-" AS status
                               LIMIT 1', [$feedID, $idPlant]);

            if ($db[0]->curr_qtf <> 0){
                $db1 = DB::select('SELECT IFNULL(b.curr_qtf, 0) AS curr_qtf,
                                          IFNULL(b.entry_date, DATE_FORMAT(CURDATE(), "%Y-%m-%d")) AS entry_date,
                                          "-RESET-" AS status
                                     FROM m_material a
                                     LEFT JOIN (SELECT b.flowmeter, b.value AS curr_qtf, b.reset_date AS entry_date
                                                FROM t_reset_quantifier b
                                                WHERE b.status = 1
                                                ORDER BY id_reset DESC) b
                                       ON a.qtf_feed = b.flowmeter
                                    WHERE a.id_feed = ?
                                      AND b.curr_qtf IS NOT NULL
                                    ORDER BY b.entry_date DESC
                                    LIMIT 1', [$feedID]);
                $len = count($db1);
                if ($len == 0){
                    return $db;
                } else {
                    return $db1;
                }
            } else {
                return $db;
            }

        } else {
            $db = DB::select('SELECT 0 AS curr_qtf, DATE_FORMAT(CURDATE(), "%Y-%m-%d") AS entry_date, "-QTF-" AS `status`');

            return $db;
        }
    }
    static function get_rundownLastBatch($request){
        $rundownID = $request->input('rundownID');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $dat = DB::select('SELECT flow_type
                             FROM m_material_flow
                            WHERE `status` = 1');
        $flowType = $dat[0]->flow_type;

        if ($flowType == 'quantifier'){
            $db = DB::select('SELECT a.curr_qtf, a.entry_date, "-NORMAL-" AS status, a.created_at
                                FROM ( SELECT a.curr_qtf, a.entry_date, a.created_at
                                        FROM t_trace_header a
                                        WHERE SUBSTRING(a.to_trace_no,1,1) = 1
                                        AND SUBSTRING(a.to_trace_no,8,3) = ?
                                        AND a.status = 1
                                        AND a.id_plant = ?
                                        ORDER BY a.id_trace_head DESC
                                        LIMIT 1 ) a
                                UNION ALL
                                SELECT 0 AS curr_qtf, DATE_FORMAT(CURDATE(), "%Y-%m-%d") AS entry_date, "-INIT-" AS status, "" AS created_at
                                LIMIT 1', [$rundownID, $idPlant]);

            if ($db[0]->curr_qtf <> 0){
                $db1 = DB::select('SELECT IFNULL(b.curr_qtf, 0) AS curr_qtf, b.created_at,
                                        IFNULL(b.entry_date, DATE_FORMAT(CURDATE(), "%Y-%m-%d")) AS entry_date,
                                        "-RESET-" AS status
                                    FROM m_material a
                                    LEFT JOIN (SELECT b.flowmeter, b.value AS curr_qtf, b.reset_date AS entry_date, b.created_at
                                                FROM t_reset_quantifier b
                                                WHERE b.status = 1
                                                ORDER BY id_reset DESC) b
                                    ON a.qtf_rundown = b.flowmeter
                                    WHERE a.id_rundown = ?
                                    AND b.curr_qtf IS NOT NULL
                                    ORDER BY b.entry_date DESC
                                    LIMIT 1', [$rundownID]);
                $len = count($db1);
                if ($len == 0){
                    return $db;
                } else {
                    return $db1;
                }

            } else {

            return $db;

            }
        } else {

            $db = DB::select('SELECT 0 AS curr_qtf, DATE_FORMAT(CURDATE(), "%Y-%m-%d") AS entry_date, "-QTF-" AS `status`');

            return $db;
        }
    }
    static function get_cmbActiveTank_trf($request){
        $sloc = $request->input('sloc');
        $feedId = $request->input('feedID');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT b.id_tank, b.description AS tank
                            FROM m_material a
                            LEFT JOIN m_tank b
                              ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
                           WHERE a.status = 1
                             AND a.id_feed = ?
                           GROUP BY b.id_tank', [$idPlant, $feedId]);
        return $db;
    }
    static function get_cmbActiveTank_rundown($request){
        $rundownID = $request->input('rundownID');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT b.id_tank, b.description AS tank
                            FROM m_material a
                            LEFT JOIN m_tank b
                              ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
                           WHERE a.status = 1
                             AND a.id_rundown = ?
                           GROUP BY b.id_tank', [$idPlant, $rundownID]);

        return $db;
    }
    static function get_cmbActiveSpecificTank_trf($request){
        $sloc = $request->input('sloc');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        $db = DB::select('SELECT a.id_tank_tail, a.tf_number AS tankNo
                            FROM m_tank_detail a
                           WHERE a.status = 1
                             AND a.id_tank = ?
                           ORDER BY a.tf_number ASC', [$sloc]);
        return $db;
    }
    static function post_materialFeed($user, $request){
        $feedID = $request->input('feed_id');
        $id = $request->input('id');
        $mode = $request->input('mode');
        $last_qtf = $request->input('last_feed');
        $id_tank = $request->input('tank');
        $id_tank_tail = $request->input('tankNo');
        $id_tank_tail_json = json_encode($id_tank_tail);
        $curr_qtf = $request->input('curr_feed');
        $curr_entryDate = $request->input('curr_entryDate');
        $entry_no = $request->input('batch_no');
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        //$feed_id = substr($feedID, 0, 2);
        $feed_id = $feedID;

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

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

        /* CALCULATE OUT QTY FROM BALANCE */
            $out_qty = $curr_qtf - $last_qtf;

        /* GET TOTAL RESERVE BALANCE IN TANK */
            $datHead = DB::select('SELECT IFNULL(SUM(b.qty),0) AS qty
                                     FROM m_material a
                                     LEFT JOIN t_balance_header b
                                       ON a.id_material = b.id_material AND b.`status` = 1
                                    WHERE a.id_feed = ?
                                      AND a.`status` = 1
                                      AND b.qty > "0.0001"
                                      AND b.id_tank = ?
                                      AND b.id_plant = ?
                                    ORDER BY b.id_balance_head ASC', [$feed_id, $id_tank, $idPlant]);

            $total_reserve = $datHead[0]->qty;
            if (($total_reserve - $out_qty) < -0.000001){
                $db = [ (object)['response' => 3 ]];
                return $db;
            }


        /* ROUTING FOR RESERVE BALANCE IS ENOUGH */
            if ($mode == 'ADD'){
                /* GET ID_BALANCE_HEADER */
                $datHead = DB::select('SELECT b.id_balance_head, b.qty, b.in_qty, b.out_qty, b.init_qty, b.trace_no, a.id_material
                                        FROM m_material a
                                        LEFT JOIN t_balance_header b
                                        ON a.id_material = b.id_material AND b.`status` = 1
                                        WHERE a.id_feed = ?
                                        AND a.`status` = 1
                                        AND b.qty > "0.0001"
                                        AND b.id_tank = ?
                                        AND b.id_plant = ?
                                        ORDER BY b.id_balance_head ASC', [$feed_id, $id_tank, $idPlant]);

                $len = count($datHead);
                if ($len == 0){
                    $db = [ (object)['response' => 4 ]];
                    return $db;
                }

                /* CHECK SAME INPUT IN THE SAME DATE */
                    $datDouble = DB::select('SELECT COUNT(id_trace_head) AS flag
                                               FROM t_trace_header
                                              WHERE `status` = 1
                                                AND entry_date = ?
                                                AND id_sloc = ?
                                                AND id_material = ?
                                                AND in_qty = 0
                                                AND SUBSTRING(to_trace_no,1,1) = 3
                                                AND id_plant = ?
                                            ', [$curr_entryDate, $id_tank, $datHead[0]->id_material, $idPlant]);


                    if ($datDouble[0]->flag > 0){
                        $db = [ (object)['response' => 2 ]];
                        return $db;
                    }

                for ($i = 0; $i < $len; $i++) {
                    $idHead = $datHead[$i]->id_balance_head;
                    $qty = $datHead[$i]->qty;
                    $total_in_qty = $datHead[$i]->in_qty;
                    $total_out_qty = $datHead[$i]->out_qty;
                    $init_qty = $datHead[$i]->init_qty;
                    $from_trace_no = $datHead[$i]->trace_no;
                    $id_material = $datHead[$i]->id_material;

                    $new_total_in_qty = $total_in_qty;
                    $new_total_out_qty = $total_out_qty + $out_qty;

                    $tail_out_qty = $out_qty;

                    $balanceAfter = $qty - $out_qty;

                    if ($balanceAfter < 0){
                        if ($len == 1){
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

                    /* GET ID_BALANCE_DETAIL */
                        $datTail = DB::select('SELECT id_balance_tail, id_supplier, qty, in_qty, out_qty, init_qty, batch_sap
                                                 FROM t_balance_detail
                                                WHERE id_balance_head = ?
                                                  AND `status` = 1
                                                  AND qty > "0.0001"
                                                  AND id_plant = ?
                                                ORDER BY id_balance_tail ASC', [$idHead, $idPlant]);
                        $lenTail = count($datTail);

                        if ($lenTail == 0){
                            $db = [ (object)['response' => 4 ]];
                            return $db;
                        }

                    /* UPDATE INTO T_BALANCE_HEADER */
                        DB::update('UPDATE t_balance_header
                                    SET qty = ?,
                                        in_qty = ?,
                                        out_qty = ?,
                                        updated_by = ?
                                    WHERE id_balance_head = ?',
                                    [$new_balance, $new_total_in_qty, $new_total_out_qty, $user, $idHead]);

                    /* INSERT INTO T_TRACE_HEADER */
                        $idTraceHead = DB::table('t_trace_header')->insertGetId([
                                'from_trace_no' => $from_trace_no,
                                'to_trace_no' => $entry_no,
                                'id_balance_head' => $idHead,
                                'id_material' => $id_material,
                                'entry_date' => $curr_entryDate,
                                'id_sloc' => $id_tank,
                                'id_tank_tail' => $id_tank_tail_json,
                                'out_qty' => $out_qty,
                                'last_qtf' => $last_qtf,
                                'curr_qtf' => $curr_qtf,
                                'created_by' => $user,
                                'id_plant' => $idPlant,
                        ]);

                    /* HEADER LOGGING */
                        DB::insert('INSERT INTO log_transactions
                                        (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'UPDATE BALANCE', 'IDHEAD: ' . $idHead . ' | DATE: ' . $curr_entryDate .
                                                            ' / TANK: ' . $id_tank . ' / MATERIAL: ' . $id_material . ' / QTY: ' . $qty . ' >>> ' . $new_balance .
                                                            ' / IN_QTY: ' . $total_in_qty . ' >>> ' . $new_total_in_qty .
                                                            ' / OUT_QTY: ' . $total_out_qty . ' >>> ' . $new_total_out_qty .
                                                            ' | Status: 1', $user ]);


                        $dataPerHead = [];

                        foreach ($datTail as $k => $tail) {
                            $dataPerHead[$i][$k] = [
                                'idTail'          => $tail->id_balance_tail,
                                'qty'             => $tail->qty,
                                'out'             => $tail_out_qty,   // original requested out
                                'rundownSupplier' => $tail_out_qty    // will be adjusted
                            ];
                        }

                        adjustRundownToTotal($dataPerHead, $out_qty);

                        foreach ($dataPerHead[$i] as $k => $item) {
                            $tail               = $datTail[$k];
                            $idTail             = $item['idTail'];
                            $supplierRundown    = $item['rundownSupplier'];
                            $new_tail_balance   = $tail->qty - $supplierRundown;
                            $new_tail_total_out = $tail->out_qty + $supplierRundown;

                            /* UPDATE T_BALANCE_DETAIL */
                            DB::update('UPDATE t_balance_detail
                                        SET qty = ?, out_qty = ?, updated_by = ?
                                        WHERE id_balance_tail = ?', [round($new_tail_balance, 4), round($new_tail_total_out, 4), $user, $idTail]);

                            /* INSERT TRACE DETAIL */
                            DB::table('t_trace_detail')->insert([
                                'id_trace_head'  => $idTraceHead,
                                'id_balance_tail'=> $idTail,
                                'id_supplier'    => $tail->id_supplier,
                                'id_material'    => $id_material,
                                'out_qty'        => round($supplierRundown, 4),
                                'batch_sap'      => $tail->batch_sap,
                                'created_by'     => $user,
                                'id_sloc'        => $id_tank,
                                'id_tank_tail'   => $id_tank_tail_json,
                                'id_plant'       => $idPlant,
                            ]);

                        /* IF THIS SUPPLIER CAN COVER FEED → STOP */
                        if ($new_tail_balance > 0) {
                            break;
                        }
                    }

                    /* IF CURRENT BATCH BALANCE HAVE ENOUGH RESERVE TO FEED */
                        if ($balanceAfter >= 0){
                            $db = [ (object)['response' => 1 ]];
                            break;
                        }

                    /* ROUTING FOR USING NEXT BATCH BALANCE RESERVE */
                        $out_qty = $temp_out_qty;

                }
            } elseif ($mode == 'UPDATE'){

            };

        /* THROW OUTPUT */
            $db = [ (object)['response' => 1 ]];
            return $db;
    }
    static function post_materialRundown($user, $request){
        $rundown_id = $request->input('rundown_id');
        $id = $request->input('id');
        $mode = $request->input('mode');
        $last_qtf = $request->input('last_rundown');
        $curr_qtf = $request->input('curr_rundown');
        $curr_entryDate = $request->input('curr_entryDate');
        $entry_no = $request->input('batch_no');
        $id_tank = $request->input('tank');
        $id_tank_tail = $request->input('tankNo');
        $id_tank_tail_json = json_encode($id_tank_tail);
        $idPlant = \App\Models\BaseModel::resolvePlant($request);

        /* CHECK LOCK PERIOD */
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

        /* CALCULATE OUT QTY FROM BALANCE */
            $in_qty = $curr_qtf - $last_qtf;
            DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        /* ROUTING FOR RESERVE BALANCE IS ENOUGH */
        if ($mode == 'ADD'){
            /* CHECK FOR SAME TRACE NO */
                $originalTraceNo = $entry_no;
                $maxAttempts = 10;

                for ($i = 0; $i < $maxAttempts; $i++) {
                    $datTraceNo = DB::select(
                        'SELECT COUNT(to_trace_no) AS flag
                           FROM t_trace_header
                          WHERE to_trace_no = ?
                            AND `status` = 1
                            AND id_plant = ?',
                        [$entry_no, $idPlant]
                    );

                    if ($datTraceNo[0]->flag == 0) {
                        break;
                    }

                    $entry_no = $originalTraceNo + ($i + 1);
                }

                $datTraceNo = DB::select('SELECT count(to_trace_no) AS flag
                                            FROM t_trace_header
                                           WHERE to_trace_no = ?
                                             AND id_plant = ?
                                             AND `status` = 1', [$entry_no, $idPlant]);
                if ($datTraceNo[0]->flag > 0){
                    $db = [ (object)['response' => 7 ]];
                    return $db;
                }

            /* GET FEED TRACE RELATED TO RUNDOWN */
                $second_char = substr($rundown_id, 2, 1);
                $feed_id = '00' . $second_char;
                $batch_seq = substr($entry_no, 12,2);

                /* CHANGE FILTER BASED ON ENTRY_DATE AND REMOVE BATCH_SEQ = 1 */
                $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, SUM(out_qty) AS out_qty, id_material
                                              FROM t_trace_header
                                             WHERE SUBSTRING(to_trace_no,1,1) = ?
                                               AND SUBSTRING(to_trace_no,8,3) = ?
                                               AND entry_date = ?
                                               AND id_plant = ?
                                               AND `status` = 1
                                               AND out_qty > "0.0001"
                                             ORDER BY id_trace_head DESC
                                             LIMIT 1', [self::$movType2, $feed_id, $curr_entryDate, $idPlant]);

                if ($datTraceHead[0]->out_qty == null){
                    $db = [ (object)['response' => 4 ]];
                    return $db;
                }

                $feed_idTraceHead = $datTraceHead[0]->id_trace_head;
                $from_trace_no = $datTraceHead[0]->to_trace_no;
                $feed_qty = $datTraceHead[0]->out_qty;

                $datMaterial = DB::select('SELECT id_material
                                             FROM m_material
                                            WHERE id_rundown = ?
                                              AND `status` = 1', [$rundown_id]);
                $id_material = $datMaterial[0]->id_material;

            /* CHECK SAME INPUT IN THE SAME DATE */
                $datDouble = DB::select('SELECT COUNT(id_trace_head) AS flag
                                           FROM t_trace_header
                                          WHERE `status` = 1
                                            AND entry_date = ?
                                            AND id_sloc = ?
                                            AND id_material = ?
                                            AND out_qty = 0
                                            AND id_plant = ?
                                            AND SUBSTRING(to_trace_no,1,1) = 2
                                        ', [$curr_entryDate, $id_tank, $id_material, $idPlant]);

                if ($datDouble[0]->flag > 0){
                    $db = [ (object)['response' => 2 ]];
                    return $db;
                };

            /* CALCULATE YIELD */
                if ($feed_qty > 0){
                    $process_yield = $in_qty / $feed_qty;
                } elseif ($feed_qty == 0){
                    $process_yield = 0;
                } else {
                    $db = [ (object)['response' => 5 ]];
                    return $db;
                }

            /* INSERT INTO T_BALANCE_HEADER */
                $idHead = DB::table('t_balance_header')->insertGetId([
                    'entry_date' => $curr_entryDate,
                    'trace_no' => $entry_no,
                    'id_material' => $id_material,
                    'id_tank' => $id_tank,
                    'id_tank_tail' => $id_tank_tail_json,
                    'qty' => $in_qty,
                    'in_qty' => $in_qty,
                    'init_qty' => $in_qty,
                    'id_plant' => $idPlant,
                    'created_by' => $user,
                ]);
            /* INSERT INTO T_TRACE_HEADER */
                $idTraceHead = DB::table('t_trace_header')->insertGetId([
                    'from_trace_no' => $from_trace_no,
                    'to_trace_no' => $entry_no,
                    'id_balance_head' => $idHead,
                    'id_material' => $id_material,
                    'entry_date' => $curr_entryDate,
                    'id_sloc' => $id_tank,
                    'id_tank_tail' => $id_tank_tail_json,
                    'in_qty' => $in_qty,
                    'last_qtf' => $last_qtf,
                    'curr_qtf' => $curr_qtf,
                    'id_plant' => $idPlant,
                    'created_by' => $user,
                ]);

            /* HEADER LOGGING */
                DB::insert('INSERT INTO log_transactions
                                   (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'ADD BALANCE', 'IDHEAD: ' . $idHead . ' | DATE: ' . $curr_entryDate .
                                                    ' / MATERIAL: ' . $id_material . ' / QTY: ' . $in_qty .
                                                    ' / IN_QTY: ' . $in_qty .
                                                    ' / OUT_QTY: 0' .
                                                    ' | Status: 1', $user ]);


            $datTraceHead = DB::select('SELECT to_trace_no, id_trace_head, out_qty, id_material
                                          FROM t_trace_header
                                         WHERE SUBSTRING(to_trace_no,1,1) = ?
                                           AND SUBSTRING(to_trace_no,8,3) = ?
                                           AND entry_date = ?
                                           AND `status` = 1
                                           AND out_qty > "0.0001"
                                           AND id_plant = ?
                                         ORDER BY id_trace_head DESC',
                                         [self::$movType2, $feed_id, $curr_entryDate, $idPlant]);
            $len = count($datTraceHead);

            $dataPerHead = [];
            foreach ($datTraceHead as $head) {
                $feed_idTraceHead = $head->id_trace_head;
                $feed_qty = $head->out_qty;
                $id_material = $head->id_material;

                $datTraceTail = DB::select('SELECT id_trace_tail, id_balance_tail, id_supplier, out_qty, batch_sap
                                            FROM t_trace_detail
                                            WHERE id_trace_head = ?
                                            AND `status` = 1
                                            AND id_plant = ?
                                            ORDER BY id_trace_tail ASC', [$feed_idTraceHead, $idPlant]);

                if (count($datTraceTail) === 0) {
                    return [(object)['response' => 6]];
                }

                foreach ($datTraceTail as $tail) {
                    $rundownSupplier = round($process_yield * $tail->out_qty, 4);
                    $dataPerHead[$idTraceHead][] = [
                        'id_trace_head' => $idTraceHead,
                        'feed_qty' => $feed_qty,
                        'id_material' => $id_material,
                        'id_supplier' => $tail->id_supplier,
                        'batch_sap' => $tail->batch_sap,
                        'rundownSupplier' => $rundownSupplier
                    ];
                }
            }

            adjustRundownToTotal($dataPerHead, $in_qty);

            foreach ($dataPerHead as $idTraceHead => $records) {
                // Eksekusi query berdasarkan hasil akhir
                foreach ($records as $item) {
                    $idSupplier = $item['id_supplier'];
                    $batchSap = $item['batch_sap'];
                    $rundownSupplier = $item['rundownSupplier'];

                    $existing = DB::select('SELECT count(id_trace_tail) AS cnt, id_trace_tail, in_qty, out_qty, id_balance_tail
                                            FROM t_trace_detail
                                            WHERE `status` = 1
                                            AND id_trace_head = ?
                                            AND id_supplier = ?
                                            AND batch_sap = ?', [$idTraceHead, $idSupplier, $batchSap]);

                    $cnt = $existing[0]->cnt;

                    if ($cnt == 0){
                        $idTail = DB::table('t_balance_detail')->insertGetId([
                            'id_balance_head' => $idHead,
                            'id_supplier' => $idSupplier,
                            'id_material' => $id_material,
                            'id_tank' => $id_tank,
                            'id_tank_tail' => $id_tank_tail_json,
                            'qty' => $rundownSupplier,
                            'in_qty' => $rundownSupplier,
                            'init_qty' => $rundownSupplier,
                            'batch_sap' => $batchSap,
                            'id_plant' => $idPlant,
                            'created_by' => $user,
                        ]);

                        $idTraceTail = DB::table('t_trace_detail')->insertGetId([
                            'id_trace_head' => $idTraceHead,
                            'id_balance_tail' => $idTail,
                            'id_supplier' => $idSupplier,
                            'id_material' => $id_material,
                            'id_sloc' => $id_tank,
                            'id_tank_tail' => $id_tank_tail_json,
                            'in_qty' => $rundownSupplier,
                            'batch_sap' => $batchSap,
                            'id_plant' => $idPlant,
                            'created_by' => $user,
                        ]);

                    } else {
                        $idTail = $existing[0]->id_balance_tail;
                        $idTraceTail = $existing[0]->id_trace_tail;
                        $inQtyTail = $existing[0]->in_qty;

                        $newInQtyTail = $inQtyTail + $rundownSupplier;
                        $newInQtyTail = round($newInQtyTail, 4); 

                        DB::update('UPDATE t_balance_detail SET qty = ?, in_qty = ?, init_qty = ?, updated_by = ? WHERE id_balance_tail = ?',
                            [$newInQtyTail, $newInQtyTail, $newInQtyTail, $user, $idTail]);

                        DB::update('UPDATE t_trace_detail SET in_qty = ?, updated_by = ? WHERE id_trace_tail = ?',
                            [$newInQtyTail, $user, $idTraceTail]);
                    }

                    DB::insert('INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)', [
                        'T_BALANCE_TAIL', 'ADD BALANCE',
                        ' IDTAIL: ' . $idTail .
                        ' / SUPPLIER: ' . $idSupplier .
                        ' / MATERIAL: ' . $id_material .
                        ' / QTY: ' . $rundownSupplier .
                        ' / IN_QTY: ' . $rundownSupplier .
                        ' / OUT_QTY: ' . $rundownSupplier .
                        ' / INIT_QTY: ' . $rundownSupplier .
                        ' | Status: 1',
                        $user
                    ]);
                }
            }

        } elseif ($mode == 'UPDATE'){

        };

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function post_cancelFeed($user, $request){
        $traceNo = $request->input('traceNo');

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

        /* MAIN ROUTING */
        $traceHead = DB::select('SELECT id_trace_head, id_balance_head,
                                        in_qty, out_qty
                                   FROM t_trace_header
                                  WHERE to_trace_no = ?
                                    AND `status` = 1
                                  ORDER BY id_trace_head DESC', [$traceNo]);
        $lenTraceHead = count($traceHead);

        for ($i = 0; $i < $lenTraceHead; $i++) {
            $idTraceHead = $traceHead[$i]->id_trace_head;
            $idBalanceHead = $traceHead[$i]->id_balance_head;
            $traceHead_inQty = $traceHead[$i]->in_qty;
            $traceHead_outQty = $traceHead[$i]->out_qty;

            /* UPDATE TRACE HEAD STATUS 1 >>> 0 */
                DB::update('UPDATE t_trace_header
                               SET `status` = 0,
                                   `updated_by` = ?
                             WHERE `id_trace_head` = ?
                               AND `status` = 1', [$user, $idTraceHead]);
                /* LOGGING */
                DB::insert('INSERT INTO log_transactions
                                   (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_TRACE_HEAD', 'DELETE', 'IDTRACEHEAD: ' . $idTraceHead . 'IDHEAD: ' . $idBalanceHead .
                                                    ' | Status: 1 >>> 0', $user ]);

            /* UPDATE BALANCE HEAD */
                $balanceHead = DB::select('SELECT qty, in_qty, out_qty
                                             FROM t_balance_header
                                            WHERE id_balance_head = ?
                                              AND `status` = 1', [$idBalanceHead]);
                $old_qty = $balanceHead[0]->qty;
                $old_in_qty = $balanceHead[0]->in_qty;
                $old_out_qty = $balanceHead[0]->out_qty;

                $new_qty = $old_qty + $traceHead_outQty;
                $new_in_qty = $old_in_qty;
                $new_out_qty = $old_out_qty - $traceHead_outQty;

                /* UPDATE BALANCE HEADER */
                  DB::update('UPDATE t_balance_header
                                 SET `qty` = ?,
                                     `in_qty` = ?,
                                     `out_qty` = ?,
                                     `updated_by` = ?
                               WHERE `id_balance_head` = ?
                                 AND `status` = 1', [$new_qty, $new_in_qty, $new_out_qty, $user, $idBalanceHead]);
                  /* LOGGING */
                  DB::insert('INSERT INTO log_transactions
                                        (log_module, log_type, log_description, created_by)
                                  VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'UPDATE', 'IDHEAD: ' . $idBalanceHead .
                                                          ' | QTY: ' . $old_qty . ' >>> ' . $new_qty .
                                                          ' / IN_QTY: ' . $old_in_qty . ' >>> ' . $new_in_qty .
                                                          ' / OUT_QTY: ' . $old_out_qty . ' >>> ' . $new_out_qty .
                                                          ' | Status: 1', $user ]);

            /* GET TRACE DETAIL FOR EACH TRACE HEADER */
                $traceTail = DB::select('SELECT id_trace_tail, id_balance_tail, in_qty, out_qty
                                           FROM t_trace_detail
                                          WHERE id_trace_head = ?
                                            AND `status` = 1
                                          ORDER BY id_trace_tail DESC', [$idTraceHead]);
                $lenTraceTail = count($traceTail);
                for ($k = 0; $k < $lenTraceTail; $k++) {
                    $idTraceTail = $traceTail[$k]->id_trace_tail;
                    $idBalanceTail = $traceTail[$k]->id_balance_tail;
                    $traceTail_inQty = $traceTail[$k]->in_qty;
                    $traceTail_outQty = $traceTail[$k]->out_qty;

                    /* UPDATE TRACE TAIL STATUS 1 >>> 0 */
                        DB::update('UPDATE t_trace_detail
                                       SET `status` = 0,
                                           `updated_by` = ?
                                     WHERE `id_trace_tail` = ?
                                       AND `status` = 1', [$user, $idTraceTail]);
                        /* LOGGING */
                        DB::insert('INSERT INTO log_transactions
                                           (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)', [ 'T_TRACE_HEAD', 'DELETE', 'IDTRACEHEAD: ' . $idTraceHead . 'IDHEAD: ' . $idBalanceHead .
                                                            ' | Status: 1 >>> 0', $user ]);

                    /* UPDATE BALANCE TAIL */
                        $balanceTail = DB::select('SELECT qty, in_qty, out_qty, init_qty
                                                     FROM t_balance_detail
                                                    WHERE id_balance_tail = ?
                                                      AND `status` = 1
                                                    ORDER BY id_balance_tail DESC', [$idBalanceTail]);
                        $old_qtyTail = $balanceTail[0]->qty;
                        $old_in_qtyTail = $balanceTail[0]->in_qty;
                        $old_out_qtyTail = $balanceTail[0]->out_qty;
                        $old_initQty = $balanceTail[0]->init_qty;

                        $new_in_qtyTail = $old_in_qtyTail;
                        $new_qtyTail = $old_qtyTail + $traceTail_outQty;
                        $new_out_qtyTail = $old_out_qtyTail - $traceTail_outQty;

                        /* UPDATE BALANCE DETAIL */
                        DB::update('UPDATE t_balance_detail
                                       SET `qty` = ?,
                                           `in_qty` = ?,
                                           `out_qty` = ?,
                                           `updated_by` = ?
                                     WHERE `id_balance_tail` = ?
                                       AND `status` = 1', [$new_qtyTail, $new_in_qtyTail, $new_out_qtyTail, $user, $idBalanceTail]);
                        /* LOGGING */
                        DB::insert('INSERT INTO log_transactions
                                            (log_module, log_type, log_description, created_by)
                                        VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'UPDATE', 'IDTAIL: ' . $idBalanceTail .
                                                                ' | QTY: ' . $old_qtyTail . ' >>> ' . $new_qtyTail .
                                                                ' / IN_QTY: ' . $old_in_qtyTail . ' >>> ' . $new_in_qtyTail .
                                                                ' / OUT_QTY: ' . $old_out_qtyTail . ' >>> ' . $new_out_qtyTail .
                                                                ' | Status: 1', $user ]);

                }
        }

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }
    static function post_cancelRundown($user, $request){
        $traceNo = $request->input('traceNo');

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

        /* MAIN ROUTE */
        $traceHead = DB::select('SELECT id_trace_head, id_balance_head,
                                        in_qty, out_qty
                                   FROM t_trace_header
                                  WHERE to_trace_no = ?
                                    AND `status` = 1
                                  ORDER BY id_trace_head DESC', [$traceNo]);
        $lenTraceHead = count($traceHead);

        for ($i = 0; $i < $lenTraceHead; $i++) {
            $idTraceHead = $traceHead[$i]->id_trace_head;
            $idBalanceHead = $traceHead[$i]->id_balance_head;
            $traceHead_inQty = $traceHead[$i]->in_qty;
            $traceHead_outQty = $traceHead[$i]->out_qty;

            /* UPDATE TRACE HEAD STATUS 1 >>> 0 */
                DB::update('UPDATE t_trace_header
                               SET `status` = 0,
                                   `updated_by` = ?
                             WHERE `id_trace_head` = ?
                               AND `status` = 1', [$user, $idTraceHead]);
                /* LOGGING */
                DB::insert('INSERT INTO log_transactions
                                   (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)', [ 'T_TRACE_HEAD', 'DELETE', 'IDTRACEHEAD: ' . $idTraceHead . 'IDHEAD: ' . $idBalanceHead .
                                                    ' | Status: 1 >>> 0', $user ]);

            /* UPDATE BALANCE HEADER */
                DB::update('UPDATE t_balance_header
                               SET `status` = 0,
                                   `updated_by` = ?
                             WHERE `id_balance_head` = ?
                               AND `status` = 1', [$user, $idBalanceHead]);
                /* LOGGING */
                DB::insert('INSERT INTO log_transactions
                                       (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)', [ 'T_BALANCE_HEAD', 'UPDATE', 'IDHEAD: ' . $idBalanceHead .
                                                        ' | Status: 1 >>> 0', $user ]);

            /* GET TRACE DETAIL FOR EACH TRACE HEADER */
                $traceTail = DB::select('SELECT id_trace_tail, id_balance_tail
                                           FROM t_trace_detail
                                          WHERE id_trace_head = ?
                                            AND `status` = 1
                                          ORDER BY id_trace_tail DESC', [$idTraceHead]);
                $lenTraceTail = count($traceTail);
                for ($k = 0; $k < $lenTraceTail; $k++) {
                    $idTraceTail = $traceTail[$k]->id_trace_tail;
                    $idBalanceTail = $traceTail[$k]->id_balance_tail;

                    /* UPDATE TRACE TAIL STATUS 1 >>> 0 */
                        DB::update('UPDATE t_trace_detail
                                       SET `status` = 0,
                                           `updated_by` = ?
                                     WHERE `id_trace_tail` = ?
                                       AND `status` = 1', [$user, $idTraceTail]);
                        /* LOGGING */
                        DB::insert('INSERT INTO log_transactions
                                           (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)', [ 'T_TRACE_HEAD', 'DELETE', 'IDTRACEHEAD: ' . $idTraceHead . 'IDHEAD: ' . $idBalanceHead .
                                                            ' | Status: 1 >>> 0', $user ]);
                    /* UPDATE BALANCE DETAIL */
                        DB::update('UPDATE t_balance_detail
                                       SET `status` = 0,
                                           `updated_by` = ?
                                     WHERE `id_balance_tail` = ?
                                       AND `status` = 1', [$user, $idBalanceTail]);
                        /* LOGGING */
                        DB::insert('INSERT INTO log_transactions
                                            (log_module, log_type, log_description, created_by)
                                        VALUES (?, ?, ?, ?)', [ 'T_BALANCE_TAIL', 'UPDATE', 'IDTAIL: ' . $idBalanceTail .
                                                                ' | Status: 1 >>> 0', $user ]);
                }
        }

        /* CHECKING FOR ADJUSTMENT */
            $adjCode = substr(strval($traceNo), 0, 1);

            if ($adjCode == '9'){
                $getAdjustHead = DB::select('SELECT id_adjust_head
                                               FROM t_adjustment_header
                                              WHERE adjust_no = ?', [$traceNo]);
                $idAdjustHead = $getAdjustHead[0]->id_adjust_head;
                DB::update('UPDATE t_adjustment_header
                               SET `status` = 0,
                                   updated_by = ?
                             WHERE id_adjust_head = ?
                               AND `status` = 1', [$user, $idAdjustHead]);
                DB::update('UPDATE t_adjustment_detail
                               SET `status` = 0,
                                   updated_by = ?
                             WHERE id_adjust_head = ?
                               AND `status` = 1', [$user, $idAdjustHead]);
            }

        /* THROW OUTPUT */
        $db = [ (object)['response' => 1 ]];
        return $db;
    }


    static function get_tracingBackward(){
        $db = DB::select('WITH RECURSIVE BOM AS (
                                -- Anchor member: Start with the initial trace (parent)
                                SELECT b.to_trace_no AS parent_trace_no,
                                       b.to_trace_no AS trace_no,
                                       b.id_material,
                                       b.in_qty,
                                       b.out_qty,
                                       b.entry_date,
                                       b.from_trace_no AS child_trace_no,
                                       1 AS level  -- Starting level for hierarchical depth
                                  FROM t_balance_header a
                                  LEFT JOIN t_trace_header b
                                    ON a.id_balance_head = b.id_balance_head
                                 WHERE b.to_trace_no = ?
                                   AND a.`status` = ?

                             UNION ALL

                                -- Recursive member: Join to find child components
                                SELECT BOM.parent_trace_no,
                                       t.to_trace_no AS trace_no,
                                       t.id_material,
                                       t.in_qty,
                                       t.out_qty,
                                       t.entry_date,
                                       t.from_trace_no AS child_trace_no,
                                       BOM.level + 1
                                  FROM BOM
                                  JOIN t_trace_header t ON BOM.child_trace_no = t.to_trace_no AND t.status = ?
                            )
                            -- Final select from the CTE
                            SELECT parent_trace_no,
                                   trace_no,
                                   entry_date,
                                   d.`description` AS material,
                                   in_qty,
                                   out_qty,
                                   `level`
                              FROM BOM c
                              LEFT JOIN m_material d
                                ON c.id_material = d.id_material
                             ORDER BY `level`, parent_trace_no, trace_no;', [12406240201, 1, 1]);
    }
    static function get_tracingForward(){
        $db = DB::select('WITH RECURSIVE ForwardBOM AS (
                    -- Anchor member: Start with the initial trace (parent)
                    SELECT
                        b.to_trace_no AS parent_trace_no,
                        b.id_trace_head,
                        b.from_trace_no,
                        b.to_trace_no AS trace_no,
                        b.id_material,
                        b.in_qty,
                        b.out_qty,
                        b.entry_date,
                        b.to_trace_no AS child_trace_no,
                        1 AS level,  -- Starting level for hierarchical depth
                        CAST("1" AS CHAR(255)) AS path  -- Initialize path with level 1
                    FROM
                        t_balance_header a
                    LEFT JOIN
                        t_trace_header b
                        ON a.id_balance_head = b.id_balance_head
                    WHERE
                        b.to_trace_no = "12406290001"
                        AND a.`status` = "1"

                    UNION ALL

                    -- Recursive member: Join to find child components
                    SELECT
                        ForwardBOM.parent_trace_no,
                        t.id_trace_head,
                        t.from_trace_no,
                        t.to_trace_no AS trace_no,
                        t.id_material,
                        t.in_qty,
                        t.out_qty,
                        t.entry_date,
                        t.to_trace_no AS child_trace_no,
                        ForwardBOM.level + 1,
                        CONCAT(ForwardBOM.path, ".",
                            LPAD((SELECT COUNT(*)
                                    FROM t_trace_header t2
                                    WHERE t2.from_trace_no = t.from_trace_no
                                    AND t2.to_trace_no <= t.to_trace_no
                                    AND t2.`status` = "1"), 2, "0")) AS path
                    FROM
                        ForwardBOM
                    JOIN
                        t_trace_header t
                        ON ForwardBOM.child_trace_no = t.from_trace_no
                        AND t.`status` = "1"
                )
                -- Final select from the CTE
                SELECT
                    c.parent_trace_no,
                    c.id_trace_head,
                    c.from_trace_no,
                    c.trace_no,
                    c.entry_date,
                    d.`description` AS material,
                    c.in_qty,
                    c.out_qty,
                    IF(e.in_qty > "0.0001",
                        GROUP_CONCAT(DISTINCT CONCAT(f.`description`, " / ", e.batch_sap, " / ", e.in_qty, " MT") SEPARATOR " || "),
                        GROUP_CONCAT(DISTINCT CONCAT(f.`description`, " / ", e.batch_sap, " / ", e.out_qty, " MT") SEPARATOR " || ")) AS supplier,
                    c.`level`,
                    c.path
                FROM
                    ForwardBOM c
                LEFT JOIN
                    m_material d
                    ON c.id_material = d.id_material
                LEFT JOIN
                    t_trace_detail e
                    ON c.id_trace_head = e.id_trace_head
                LEFT JOIN
                    m_supplier f
                    ON f.id_supplier = e.id_supplier
                GROUP BY
                    trace_no, path
                ORDER BY
                    path;

                ');
    }

    static function get_quantifierData($request){
        $date = $request->input('date');
        $tagNumber = $request->input('tagNumber');

        // Tambahkan 1 hari ke tanggal yang diterima
        $nextDate = Carbon::parse($date)->addDay()->format('Y-m-d');

        $db = DB::connection('dwsql')->select("SELECT FORMAT(`value`,3) AS `value`,
                                                      CONCAT(?, ' 07:00') AS `timestamp`
                                                 FROM `{$tagNumber}`
                                                WHERE DATE_FORMAT(`timestamp`, '%Y-%m-%d') = ?
                                                UNION ALL
                                               SELECT 0 AS `value`, CONCAT(?, ' 07:00') AS `timestamp`
                                                LIMIT 1
                                            ", [$nextDate, $nextDate, $nextDate]);
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

    function normalizeNumber($num) {
        if ($num === null) return "0";

        $numStr = (string)$num;

        if (stripos($numStr, 'e') !== false) {
            $numStr = sprintf('%.14F', (float)$numStr);
        }

        return $numStr;
    }

    function adjustRundownToTotal(&$dataPerHead, $targetTotal) {
        $targetTotal = normalizeNumber($targetTotal);
        // Step 1: Hitung total awal/Calculate the initial total
        $total = '0';
        foreach ($dataPerHead as $head) {
            foreach ($head as $item) {
                $value = normalizeNumber($item['rundownSupplier']);
                $total = bcadd($total, $value, 10);
            }
        }

        if (bccomp($total, '0', 10) == 0) {
            return; // Tidak perlu adjust kalau total 0/No need to adjust if the total is 0
        }

        // Step 2: Hitung faktor/Calculate factor
        $factor = bcdiv($targetTotal, $total, 10);

        // Step 3: Kalikan semua dan simpan delta/Multiply everything and save the delta
        $newTotal = '0';
        $lastHeadKey = array_key_last($dataPerHead);
        $lastItemKey = array_key_last($dataPerHead[$lastHeadKey]);

        foreach ($dataPerHead as $headKey => &$headItems) {
            foreach ($headItems as $itemKey => &$item) {
                $current = normalizeNumber($item['rundownSupplier']);
                $adjusted = bcmul($current, $factor, 10);
                $adjusted = round($adjusted, 4);
                $item['rundownSupplier'] = $adjusted;
                $newTotal = bcadd($newTotal, normalizeNumber($adjusted), 10);
            }
        }

        // Step 4: Koreksi selisih ke item terakhir/Adjust the difference to the last item
        $delta = bcsub($targetTotal, $newTotal, 10);
        $delta = round((float)$delta, 4);
        $dataPerHead[$lastHeadKey][$lastItemKey]['rundownSupplier'] += $delta;
    }

