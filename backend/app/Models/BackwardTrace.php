<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BackwardTrace extends Model
{
    protected $connection = 'eudr_ts';

    static function get_dtBackwardList(){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT a.id_ship_head, a.entry_date, f.id_trace_head, f.id_balance_head,
                                 CAST(a.trace_no AS CHAR) AS trace_no, CAST(a.from_trace_no AS CHAR) AS from_trace_no, f.batch_no,
                                 a.so_no, a.id_material_fg AS id_material, FORMAT(f.qty, 3) AS qty , a.status, a.created_by, a.created_at, a.updated_by, a.updated_at,
                                 IF(SUBSTRING(a.from_trace_no,1,1) < 3, g.`description`, c.`description`) AS material, f.sloc,
                                 GROUP_CONCAT(DISTINCT CONCAT(d.description, " / ", d.batch_sap, " / Qty: ", FORMAT(d.qty,3), " MT") SEPARATOR " | ") AS supplier,
                                 GROUP_CONCAT(DISTINCT h.po_so SEPARATOR " | ") AS source
                            FROM t_shipment_header a
                            LEFT JOIN m_material_pck c
                              ON a.id_material_fg = c.id_materialpck
                            LEFT JOIN (SELECT dd.trace_no, e.description, d.batch_sap, SUM(d.qty) AS qty
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
                            LEFT JOIN (SELECT f.to_trace_no, f.id_trace_head, f.id_balance_head, ff.batch_no,
                                              SUM(f.out_qty) AS qty, fff.code AS sloc
                                         FROM t_trace_header f
                                         LEFT JOIN t_warehouse_header ff
                                           ON f.id_balance_head = ff.id_whx_head AND ff.status = 1
                                         LEFT JOIN m_warehouse fff
                                           ON fff.id_warehouse = ff.id_section
                                        WHERE f.status = 1
                                        GROUP BY f.to_trace_no
                                    ) f
                              ON f.to_trace_no = a.trace_no
                            LEFT JOIN m_material g
                              ON g.id_material = a.id_material_fg
                            LEFT JOIN (SELECT a.batch_sap, GROUP_CONCAT(DISTINCT a.po_so SEPARATOR "|") AS po_so
                                         FROM (SELECT hh.batch_sap, CONCAT(hh.batch_sap, " :: ", h.trace_no, " / ", hhhh.po_so) AS po_so
                                                 FROM t_balance_header h
                                                 LEFT JOIN t_balance_detail hh
                                                   ON h.id_balance_head = hh.id_balance_head
                                                 LEFT JOIN t_trace_header hhh
                                                   ON h.id_balance_head = hhh.id_balance_head AND hhh.status = 1
                                                 LEFT JOIN t_material_document hhhh
                                                   ON hhh.id_trace_head = hhhh.id_trace_head
                                                WHERE h.`status` = 1
                                                  AND h.in_qty <> 0
                                                  AND SUBSTRING(h.trace_no,1,1) = 1
                                                  AND hhhh.po_so IS NOT NULL
                                                UNION ALL
                                               SELECT hh.batch_sap, IFNULL(CONCAT(hh.batch_sap, " :: ", h.trace_no, " / ", hhhh.po_so), CONCAT(hh.batch_sap, " :: ", h.trace_no)) AS po_so
                                                 FROM t_balance_header h
                                                 LEFT JOIN t_balance_detail hh
                                                   ON h.id_balance_head = hh.id_balance_head
                                                 LEFT JOIN t_trace_header hhh
                                                   ON h.id_balance_head = hhh.id_balance_head AND hhh.status = 1
                                                 LEFT JOIN t_material_document hhhh
                                                   ON hhh.id_trace_head = hhhh.id_trace_head
                                                WHERE h.`status` = 1
                                                  AND h.in_qty <> 0
                                                  AND SUBSTRING(h.trace_no,1,1) = 9) a
                                                GROUP BY a.batch_sap
                                    ) h
                              ON d.batch_sap = h.batch_sap
                           WHERE a.status = 1
                           GROUP BY a.trace_no
                           ORDER BY a.entry_date DESC, id_ship_head DESC');

        return $db;
    }
    static function get_dtBackwardTrace($request){
        $traceNo = $request->input('traceNo');
        $idMaterial = $request->input('idMaterial');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('WITH RECURSIVE BackwardBOM AS (
                                -- Initial selection to start backward trace
                                SELECT
                                    b.from_trace_no AS parent_trace_no,
                                    b.id_trace_head,
                                    b.to_trace_no AS trace_no,
                                    b.from_trace_no,
                                    b.id_material,
                                    b.in_qty,
                                    b.out_qty,
                                    b.entry_date,
                                    c.material_document,
                                    b.from_trace_no AS child_trace_no,
                                    b.id_sloc,
                                    1 AS level,  -- Starting level for hierarchical depth
                                    CAST("1" AS CHAR(255)) AS path  -- Initialize path with level 1
                                FROM
                                    t_trace_header b
                                LEFT JOIN
                                    t_material_document c
                                    ON b.id_trace_head = c.id_trace_head
                                WHERE
                                    b.to_trace_no = ?
                                    AND b.id_material = ?
                                    AND b.status = 1

                                UNION ALL

                                -- Recursive part for backward tracing
                                SELECT
                                    BackwardBOM.parent_trace_no,
                                    t.id_trace_head,
                                    t.to_trace_no AS trace_no,
                                    t.from_trace_no,
                                    t.id_material,
                                    t.in_qty,
                                    t.out_qty,
                                    t.entry_date,
                                    tt.material_document,
                                    t.from_trace_no AS child_trace_no,
                                    t.id_sloc,
                                    BackwardBOM.level + 1,
                                    CONCAT(BackwardBOM.path, ".",
                                        LPAD((SELECT COUNT(*)
                                                FROM t_trace_header t2
                                               WHERE t2.to_trace_no = t.to_trace_no
                                                 AND t2.from_trace_no <= t.from_trace_no
                                                 AND t2.`status` = "1"), 2, "0")) AS path
                                FROM
                                    BackwardBOM
                                JOIN
                                    t_trace_header t
                                    ON BackwardBOM.child_trace_no = t.to_trace_no AND t.status = 1
                                LEFT JOIN
                                    t_material_document tt
                                    ON tt.id_trace_head = t.id_trace_head
                            )
                            SELECT
                                a.parent_trace_no,
                                a.id_trace_head,
                                CAST(a.trace_no AS CHAR) AS trace_no,
                                CAST(a.from_trace_no AS CHAR) AS from_trace_no,
                                a.entry_date,
                                a.material,
                                FORMAT(SUM(a.in_qty),3) AS in_qty,
                                FORMAT(SUM(a.out_qty),3) AS out_qty,
                                GROUP_CONCAT(a.supplier SEPARATOR " || ") AS supplier,
                                a.`level`,
                                a.`path`,
                                a.material_document,
                                a.`status`,
                                a.created_at,
                                a.created_by,
                                a.sloc
                            FROM ( SELECT
                                c.parent_trace_no,
                                c.id_trace_head,
                                c.trace_no,
                                c.from_trace_no,
                                c.entry_date,
                                IF(SUBSTRING(c.from_trace_no,1,1) = 4, UPPER(g.`description`), IF(SUBSTRING(c.from_trace_no,1,1) = 5, UPPER(g.`description`), UPPER(d.`description`) ) ) AS material,
                                c.in_qty AS in_qty,
                                c.out_qty AS out_qty,
                                IF(e.in_qty <> 0,
                                    GROUP_CONCAT(DISTINCT CONCAT(f.`description`, " / ", e.batch_sap, " / ", e.in_qty, " MT") SEPARATOR " || "),
                                    GROUP_CONCAT(DISTINCT CONCAT(f.`description`, " / ", e.batch_sap, " / ", e.out_qty, " MT") SEPARATOR " || ")) AS supplier,
                                c.`level`,
                                c.`path`,
                                c.material_document,
                                IFNULL(e.status,0) AS `status`,
                                e.created_at,
                                e.created_by,
                                IF(SUBSTRING(c.from_trace_no,1,1) = 4, UPPER(i.`description`), IF(SUBSTRING(c.from_trace_no,1,1) = 5, UPPER(i.`description`), IF(c.id_sloc < 7, CONCAT("EOB1", " ", h.description), h.description) ) ) AS sloc
                            FROM
                                BackwardBOM c
                            LEFT JOIN
                                m_material d
                                ON c.id_material = d.id_material
                            LEFT JOIN ( SELECT
                                            e.id_trace_head, e.batch_sap, FORMAT(SUM(e.in_qty),3) AS in_qty, FORMAT(SUM(e.out_qty),3) AS out_qty,
                                            e.status, e.created_at, e.created_by, e.id_supplier, e.id_material
                                        FROM
                                            t_trace_detail e
                                        WHERE
                                            e.status = 1
                                        GROUP BY
                                            e.id_trace_head, e.batch_sap
                                        ) e
                                ON c.id_trace_head = e.id_trace_head
                            LEFT JOIN
                                m_supplier f
                                ON f.id_supplier = e.id_supplier
                            LEFT JOIN
                                m_material_pck g
                                ON c.id_material = g.id_materialpck
                            LEFT JOIN
                                m_tank h
                                ON c.id_sloc = h.id_tank
                            LEFT JOIN
                                m_warehouse i
                                ON c.id_sloc = i.id_warehouse
                            WHERE e.status <> 0
                            GROUP BY
                                `path`, `level`, in_qty, out_qty
                            ORDER BY
                                `path`) a
                        GROUP BY
                            `path`
                        ', [$traceNo, $idMaterial]);

        return $db;
    }

}
