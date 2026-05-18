<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Stock extends Model
{
    protected $connection = 'eudr_ts';
    protected static $movType1 = "1";
    protected static $movType2 = "2";
    protected static $movType3 = "3";
    protected static $movType4 = "4";
    protected static $movType5 = "5";
    protected static $movType6 = "6";
    protected static $movType7 = "7";
    protected static $movType8 = "8";
    protected static $movType9 = "9";
    protected static $idPlantEob1 = "1002";

    static function get_activeMaterialStock($materialStock=null, $stockType=null){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        if ($materialStock == null){
            $db = DB::select('SELECT a.id_material, UPPER(a.material) AS material
                                FROM (SELECT CONCAT("WIP - ", a.description, " (", a.code, " / ", a.type, ")") AS material,
                                            CONCAT("WIP|", a.id_material) AS id_material
                                        FROM m_material a
                                    WHERE a.status = 1
                                    GROUP BY a.code
                                    ORDER BY a.description ASC) a
                            UNION ALL
                            SELECT a.id_material, UPPER(a.material) AS material
                                FROM (SELECT CONCAT("WH - ", a.description, " (", a.code, ")") AS material,
                                            CONCAT("WH|", a.id_materialpck) AS id_material
                                        FROM m_material_pck a
                                    WHERE a.status = 1
                                    ORDER BY a.description ASC) a
                            ORDER BY material
                            ');
        } else {
            if ($stockType == "WIP"){
                $db = DB::select('SELECT a.id_material, UPPER(a.material) AS material
                                    FROM (SELECT CONCAT(a.description, " (", a.code, " / ", a.type, ")") AS material,
                                                 CONCAT("WIP|", a.id_material) AS id_material
                                            FROM m_material a
                                           WHERE a.status = 1
                                             AND a.description LIKE CONCAT("%",?,"%")
                                           GROUP BY a.code
                                           ORDER BY a.description ASC) a
                                   ORDER BY material
                                ', [$materialStock]);
            } elseif ($stockType == "WH"){
                $db = DB::select('SELECT a.id_material, UPPER(a.material) AS material
                                    FROM (SELECT CONCAT(a.description, " (", a.code, ")") AS material,
                                                 CONCAT("WH|", a.id_materialpck) AS id_material
                                            FROM m_material_pck a
                                           WHERE a.status = 1
                                             AND a.description LIKE CONCAT("%",?,"%")
                                           ORDER BY a.description ASC) a
                                   ORDER BY material
                                ', [$materialStock]);
            } else {
                $db = DB::select('SELECT a.id_material, UPPER(a.material) AS material
                                    FROM (SELECT CONCAT("WIP - ", a.description, " (", a.code, " / ", a.type, ")") AS material,
                                                 CONCAT("WIP|", a.id_material) AS id_material
                                            FROM m_material a
                                           WHERE a.status = 1
                                             AND a.description LIKE CONCAT("%",?,"%")
                                           GROUP BY a.code
                                           ORDER BY a.description ASC) a
                                   UNION ALL
                                  SELECT a.id_material, UPPER(a.material) AS material
                                    FROM (SELECT CONCAT("WH - ", a.description, " (", a.code, ")") AS material,
                                                 CONCAT("WH|", a.id_materialpck) AS id_material
                                            FROM m_material_pck a
                                           WHERE a.status = 1
                                             AND a.description LIKE CONCAT("%",?,"%")
                                           ORDER BY a.description ASC) a
                                   ORDER BY material
                                ', [$materialStock, $materialStock]);
            }
        }
        return $db;
    }
    static function get_dtStock($request){
        $startDate = new \DateTime($request->input('startDate'));
        $endDate = new \DateTime($request->input('endDate'));
        $idMaterial = $request->input('idMaterial');
        $mode = $request->input('mode');
        $idSloc = $request->input('idSloc');

        $parts = explode('|', $idMaterial);
        $type = $parts[0]; // "WIP"
        $idMaterialFix = $parts[1]; // "16"

        $interval = $startDate->diff($endDate);
        $lenDays = $interval->days;

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        if ($type == 'WIP'){
            if ($mode == 'NORMAL'){
                $db = DB::select('WITH requested_material AS (
                                    SELECT a.id_material
                                      FROM m_material z
                                      LEFT JOIN (SELECT a.code, a.id_material
                                                   FROM m_material a
                                                  WHERE a.status = 1
                                                    ) a
                                        ON z.code = a.code
                                     WHERE z.id_material = ?
                                    ),

                                    bh_filtered AS (
                                        SELECT bb.id_balance_head, bb.id_tank, bb.id_material, bb.trace_no
                                          FROM t_balance_header bb
                                          JOIN requested_material rm
                                            ON bb.id_material = rm.id_material      -- lock to requested id_material
                                         WHERE bb.status = 1
                                           AND bb.id_tank <> 4
                                           AND LEFT(bb.trace_no,1) IN (1,2,3,7,8,9)
                                    ),

                                    th_grouped AS (
                                        SELECT
                                              b.id_balance_head, b.id_material, b.id_trace_head, b.entry_date, SUM(b.in_qty) AS in_qty,
                                              SUM(b.out_qty) AS out_qty, SUM(b.in_qty) - SUM(b.out_qty) AS balance,
                                              GROUP_CONCAT(DISTINCT mt.description SEPARATOR "|") AS sloc
                                         FROM t_trace_header b
                                         LEFT JOIN m_tank mt
                                           ON b.id_sloc = mt.id_tank
                                          AND mt.id_plant = ?                       -- (2) plant
                                        WHERE b.status = 1
                                          AND b.entry_date <= ?                    -- (3) cutoff date
                                        GROUP BY b.id_balance_head, b.id_material, b.entry_date, b.id_trace_head
                                    ),

                                    begin_rows AS (
                                        SELECT
                                            bb.id_material, b.entry_date, b.id_trace_head, "Beginning Balance" AS description,
                                            b.balance, b.sloc, b.in_qty  AS `in`, b.out_qty AS `out`
                                        FROM bh_filtered bb
                                        JOIN th_grouped b
                                          ON b.id_balance_head = bb.id_balance_head
                                         AND b.id_material = bb.id_material
                                    ),

                                    begin_agg AS (
                                        SELECT rm.id_material, SUM(br.balance) AS balance_raw,
                                               GROUP_CONCAT(DISTINCT br.description SEPARATOR "|") AS description,
                                               ROUND(SUM(br.`in`),  3) AS in_raw, ROUND(SUM(br.`out`), 3) AS out_raw,
                                               br.sloc
                                          FROM requested_material rm
                                          LEFT JOIN begin_rows br
                                            ON rm.id_material = br.id_material
                                            WHERE br.sloc <> "-"
                                         GROUP BY rm.id_material
                                    ),

                                    td_filtered AS (
                                        SELECT
                                        	 c.id_balance_head, cc.id_trace_head, c.id_material, cc.id_trace_tail,
                                             cc.id_supplier, cc.batch_sap, ROUND(cc.in_qty,  4) AS in_qty, ROUND(cc.out_qty, 4) AS out_qty
                                        FROM t_trace_header c
										LEFT JOIN t_trace_detail cc
										  ON cc.id_trace_head = c.id_trace_head
                                        JOIN requested_material rm
                                          ON c.id_material = rm.id_material      -- lock to requested id_material
                                       WHERE cc.status = 1
                                         AND (cc.in_qty > 0.001 OR cc.out_qty > 0.001)
                                    ),

                                    th_filtered AS (
                                        SELECT
                                            c.id_trace_head, c.id_balance_head, c.entry_date, c.id_material, c.to_trace_no, c.id_sloc
                                        FROM t_trace_header c
                                        JOIN requested_material rm
                                          ON c.id_material = rm.id_material      -- lock to requested id_material
                                        WHERE c.status = 1
                                          AND c.entry_date <= ?                    -- (5) cutoff date
                                    ),

                                    supplier_lines AS (
                                        SELECT
                                            th.id_material,
                                            th.id_trace_head,
                                            th.entry_date,
                                            td.id_trace_tail,
                                            th.id_balance_head,
                                            (SUM(td.in_qty) - SUM(td.out_qty)) AS balance,
                                            sup.description AS supplier,
                                            td.batch_sap,
                                            th.to_trace_no,
                                            th.id_sloc
                                        FROM th_filtered th
                                        JOIN td_filtered td
                                          ON td.id_trace_head  = th.id_trace_head
                                         AND td.id_material     = th.id_material   -- safe material join
                                        LEFT JOIN m_supplier sup
                                          ON td.id_supplier = sup.id_supplier
                                       GROUP BY
                                            th.id_material, sup.description, td.batch_sap, th.id_sloc
                                    ),

                                    bh_filtered2 AS (   -- same filter, reused for supplier path
                                        SELECT
                                              bb.id_balance_head,
                                              bb.id_tank,
                                              bb.id_material
                                         FROM t_balance_header bb
                                         JOIN requested_material rm
                                           ON bb.id_material = rm.id_material
                                        WHERE bb.status = 1
                                          AND bb.id_tank <> 4
                                          AND LEFT(bb.trace_no,1) IN (1,2,3,7,8,9)
                                    ),

                                    supplier_plant AS (
                                        SELECT
                                            sl.*
                                        FROM supplier_lines sl
                                        JOIN bh_filtered2 bh
                                          ON sl.id_balance_head = bh.id_balance_head
                                         AND sl.id_material     = bh.id_material   -- safe material join
                                        JOIN m_tank t
                                          ON t.id_tank = bh.id_tank
                                       WHERE t.id_plant = ?                      -- (6) plant
                                    ),

                                    supplier_roll AS (
                                        SELECT
                                            sp.id_material, sp.id_trace_tail,
                                            SUM(sp.balance) AS balance_supplier_raw,
                                            GROUP_CONCAT(
                                                DISTINCT CONCAT(
                                                sp.id_trace_tail, " / ",
                                                sp.supplier, " / ",
                                                sp.batch_sap, " / Qty: ",
                                                FORMAT(ROUND(sp.balance,3),3),
                                                " MT / ",
                                                sp.to_trace_no
                                            ) SEPARATOR " | "
                                            ) AS supplier
                                        FROM supplier_plant sp
                                        WHERE sp.balance > 0.0015
                                        GROUP BY sp.id_material
                                    )

                                    SELECT
                                        SUBSTRING(?,1,10) AS entry_date,          -- (1) for display only
                                        COALESCE(bgn.description, "Beginning Balance") AS `description`,
                                        FORMAT(COALESCE(SUM(bgn.in_raw),  0), 3) AS `in`,
                                        FORMAT(COALESCE(SUM(bgn.out_raw), 0), 3) AS `out`,
                                        FORMAT(COALESCE(SUM(bgn.balance_raw), 0), 3) AS balance,
                                        GROUP_CONCAT(COALESCE(sup.supplier, "|") SEPARATOR "|") AS supplier,
                                        COALESCE(bgn.sloc, "-")     AS sloc,
                                        COALESCE(SUM(bgn.balance_raw), 0) AS balances,
                                        IF(
                                            ABS(COALESCE(SUM(sup.balance_supplier_raw),0) - COALESCE(SUM(bgn.balance_raw),0)) < 0.01,
                                            FORMAT(ROUND(COALESCE(SUM(bgn.balance_raw),0),3),3),
                                            FORMAT(ROUND(COALESCE(SUM(sup.balance_supplier_raw),0),3),3)
                                        ) AS balance_supplier
                                    FROM requested_material rm
                                    LEFT JOIN begin_agg     bgn ON bgn.id_material = rm.id_material
                                    LEFT JOIN supplier_roll  sup ON sup.id_material = rm.id_material

                                    UNION ALL

                                    SELECT
                                        SUBSTRING(?,1,10) AS entry_date,          -- (1) again for the union row
                                        "Beginning Balance" AS `description`,
                                        FORMAT(0,3) AS `in`, FORMAT(0,3) AS `out`, FORMAT(0,3) AS balance,
                                        "|" AS supplier, "-" AS sloc, 0  AS balances, FORMAT(0,3) AS balance_supplier


                                    LIMIT 1
                                    ;
                            ', [$idMaterialFix, $idSloc, $startDate, $startDate,
                                $idSloc, $startDate, $startDate
                                ]);

            } elseif ($mode == 'STORAGE'){
                $db = DB::select('SELECT SUBSTRING(?,1,10) AS entry_date, bgn.`description`, bgn.`in` AS `in`, bgn.`out` AS `out`,
                                         FORMAT(ROUND(bgn.`balance`, 3), 3) AS balance, bgn.`supplier`, bgn.`sloc`, bgn.`balance` AS balances,
                                         IF(ABS(bgn.balance_supplier - bgn.`balance`) > 0.009, FORMAT(ROUND(bgn.balance_supplier,3),3), FORMAT(ROUND(bgn.`balance`,3),3)) AS balance_supplier
                                    FROM (SELECT GROUP_CONCAT(DISTINCT b.`description` SEPARATOR "|") AS description,
                                                 FORMAT(ROUND(SUM(b.in),3),3) AS `in`, FORMAT(ROUND(SUM(b.out),3),3) AS `out`, SUM(b.`balance`) AS `balance`,
                                                 b.sloc, GROUP_CONCAT(DISTINCT c.supplier SEPARATOR "|") AS supplier,
                                                 c.`balance_supplier` AS balance_supplier
                                            FROM m_material z
                                            LEFT JOIN (SELECT a.code, a.id_material
                                                         FROM m_material a
                                                        WHERE a.status = 1) a
                                              ON z.code = a.code
                                            LEFT JOIN (SELECT bb.id_material, bb.id_trace_head, bb.entry_date, "Beginning Balance" AS `description`,
                                                              bb.`balance` AS balance, bbb.description AS sloc,
                                                              bb.`in` AS `in`, bb.`out` AS `out`
                                                         FROM m_tank bbb
                                                         LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, bb.entry_date,
                                                                           b.id_trace_head, b.balance, b.in, b.out
                                                                      FROM t_balance_header bb
                                                                      LEFT JOIN (SELECT b.id_balance_head, b.id_material, b.id_trace_head, b.entry_date,
                                                                                        SUM(b.in_qty) - SUM(b.out_qty) AS `balance`, SUM(b.in_qty) AS `in`,
                                                                                        SUM(b.out_qty) AS `out`
                                                                                   FROM t_trace_header b
                                                                                  WHERE b.`status` = 1
                                                                                    AND b.entry_date <= ?
                                                                                  GROUP BY b.id_balance_head, b.id_material
                                                                                ) b
                                                                        ON b.id_balance_head = bb.id_balance_head AND b.id_material = bb.id_material
                                                                     WHERE bb.status = 1
                                                                       AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 9 OR SUBSTRING(bb.trace_no,1,1) = 7)
                                                                       AND bb.id_tank = 4
                                                                       AND b.id_trace_head IS NOT NULL
                                                                   ) bb
                                                          ON bbb.id_tank = bb.id_tank
                                                       WHERE bb.id_trace_head IS NOT NULL
                                                         AND bbb.id_plant = ?
                                                     ) b
                                              ON a.id_material = b.id_material
                                            LEFT JOIN (SELECT d.id_material, d.id_trace_head, d.entry_date, SUM(d.`balance`) AS balance_supplier,
                                                              GROUP_CONCAT(DISTINCT CONCAT(d.id_trace_tail, " / ", d.supplier, " / ", d.`batch_sap`, " / Qty: ", FORMAT(d.`balance`,3), " MT / ", d.to_trace_no) SEPARATOR " | ") AS supplier
                                                         FROM (SELECT cc.id_material, cc.id_trace_head, cc.entry_date, cc.id_trace_tail,
                                                                      SUM(ROUND(cc.in_qty,4)) - SUM(ROUND(cc.out_qty,4)) AS `balance`, cc.supplier, cc.`batch_sap`,
                                                                      cc.to_trace_no
                                                                 FROM m_tank bbb
                                                                 LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, bb.entry_date, c.id_trace_head,
                                                                                   c.supplier, c.in_qty, c.out_qty, c.batch_sap, c.id_trace_tail, c.to_trace_no
                                                                              FROM t_balance_header bb
                                                                              LEFT JOIN (SELECT c.id_material, c.id_trace_head, c.id_balance_head,
                                                                                                c.in_qty, c.out_qty, c.`batch_sap`, c.supplier, c.id_trace_tail,
                                                                                                c.to_trace_no
                                                                                           FROM (SELECT c.id_trace_head, c.id_balance_head,
                                                                                                        c.id_material, ccc.`description` AS supplier, cc.batch_sap,
                                                                                                        cc.in_qty, cc.out_qty,
                                                                                                        c.to_trace_no, cc.id_trace_tail
                                                                                                   FROM t_trace_header c
                                                                                                   LEFT JOIN (SELECT cc.id_trace_head, cc.batch_sap, cc.id_supplier,
                                                                                                                     ROUND(cc.in_qty,4) AS in_qty,
                                                                                                                     ROUND(cc.out_qty,4) AS out_qty,
                                                                                                                     cc.id_material, cc.id_trace_tail
                                                                                                                FROM t_trace_detail cc
                                                                                                               WHERE cc.`status` = 1
                                                                                                                 AND (cc.in_qty > "0.0001" OR cc.out_qty > "0.0001")
                                                                                                            ) cc
                                                                                                     ON c.id_trace_head = cc.id_trace_head
                                                                                                   LEFT JOIN m_supplier ccc
                                                                                                     ON cc.id_supplier = ccc.id_supplier
                                                                                                  WHERE c.entry_date <= ?
                                                                                                    AND c.`status` = 1
                                                                                                ) c
                                                                                        ) c
                                                                                  ON c.id_balance_head = bb.id_balance_head
                                                                               WHERE bb.status = 1
                                                                                 AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 9 OR SUBSTRING(bb.trace_no,1,1) = 7)
                                                                                 AND bb.id_tank = 4
                                                                                 AND c.id_trace_head IS NOT NULL
                                                                            ) cc
                                                                   ON bbb.id_tank = cc.id_tank
                                                                WHERE bbb.id_plant = ?
                                                                GROUP BY cc.id_material, cc.batch_sap, cc.supplier, cc.id_balance_head
                                                              ) d
                                                           WHERE d.balance >= "0.001"
                                                           GROUP BY d.id_material
                                                        ) c
                                              ON a.id_material = c.id_material
                                           WHERE b.`entry_date` IS NOT NULL
                                             AND z.id_material = ?
                                           UNION ALL
                                          SELECT "Beginning Balance" AS `description`, 0 AS `in`, 0 AS `out`, 0 AS `balance`, "|" AS supplier, "-" AS sloc, 0 AS `balance_supplier`
                                           LIMIT 1) bgn
                                  WHERE bgn.`description` IS NOT NULL
                                ', [$startDate, $startDate, $idSloc,
                                    $startDate, $idSloc,
                                    $idMaterialFix]);
            }

            $stock = $db[0]->balances;
            if ($stock == null){
              $stock = 0;
            }
        } elseif ($type == 'WH'){
            $db = DB::select('SELECT bgn.entry_date, bgn.`description`, bgn.`in`, bgn.`out`, bgn.`supplier`, bgn.`sloc`,
                                     FORMAT(bgn.`balance`, 3) AS `balance`, bgn.`balance` AS balances,
                                     IF(ABS(bgn.balance_supplier - bgn.`balance`) < 0.0005, FORMAT(bgn.balance_supplier,3), FORMAT(bgn.`balance`,3)) AS balance_supplier
                                FROM (SELECT SUBSTRING(?,1,10) AS entry_date, b.`description`, FORMAT(b.`in`,3) AS `in`,
                                             FORMAT(b.`out`,3) AS `out`, IFNULL(b.balance,0) AS balance, c.`supplier`,
                                             SUM(DISTINCT c.`balance_supplier`) AS balance_supplier,
                                             b.sloc
                                        FROM m_material_pck a
                                        LEFT JOIN (SELECT b.id_material, b.id_trace_head, b.entry_date, "Beginning Balance" AS `description`,
                                                          SUM(b.in_qty) AS `in`, SUM(b.out_qty) AS `out`, SUM(b.in_qty) - SUM(b.out_qty) AS `balance`,
                                                          bb.sloc
                                                     FROM t_trace_header b
                                                     LEFT JOIN (SELECT bb.id_whx_head, bbb.description AS sloc
                                                                  FROM t_warehouse_header bb
                                                                  LEFT JOIN m_warehouse bbb
                                                                    ON bb.id_section = bbb.id_warehouse
                                                                 WHERE bb.status = 1
                                                                   AND bb.id_material_fg = ?
                                                                   AND (SUBSTRING(bb.trace_no,1,1) = 5 OR SUBSTRING(bb.trace_no,1,1) = 4 OR SUBSTRING(bb.trace_no,1,1) = 6)
                                                                   AND SUBSTRING(bb.trace_no,8,2) <> "00" ) bb
                                                       ON b.id_balance_head = bb.id_whx_head
                                                    WHERE b.entry_date <= ?
                                                      AND b.id_material = ?
                                                      AND b.`status` = 1
                                                      AND (SUBSTRING(b.to_trace_no,1,1) = 5 OR SUBSTRING(b.to_trace_no,1,1) = 4 OR SUBSTRING(b.to_trace_no,1,1) = 6)
                                                      AND SUBSTRING(b.to_trace_no,8,2) <> "00"
                                                    GROUP BY b.id_material) b
                                          ON a.id_materialpck = b.id_material
                                        LEFT JOIN (SELECT c.id_material, GROUP_CONCAT(DISTINCT CONCAT(c.id_trace_tail, " / ", c.`description`, " / ", c.`batch_sap`, " / Qty: ", FORMAT(c.`balance`,1), " MT") SEPARATOR " | ") AS supplier,
                                                          c.`balance` AS balance_supplier
                                                     FROM (SELECT c.id_trace_head, c.id_material, ccc.`description`, cc.batch_sap,
                                                                  SUM(cc.in_qty) AS in_qty, SUM(cc.out_qty) AS out_qty,
                                                                  SUM(ROUND(cc.in_qty,4)) - SUM(ROUND(cc.out_qty,4)) AS `balance`,
                                                                  cc.id_trace_tail
                                                             FROM t_trace_header c
                                                             LEFT JOIN (SELECT cc.id_trace_head, cc.batch_sap, cc.id_supplier,
                                                                               cc.in_qty, cc.out_qty, CONCAT(cc.id_trace_head, "-", cc.id_trace_tail) AS id_trace_tail
                                                                          FROM t_trace_detail cc
                                                                         WHERE cc.`status` = 1
                                                                           AND cc.id_material = ?) cc
                                                               ON c.id_trace_head = cc.id_trace_head
                                                             LEFT JOIN m_supplier ccc
                                                               ON cc.id_supplier = ccc.id_supplier
                                                            WHERE c.entry_date <= ?
                                                              AND c.id_material = ?
                                                              AND c.`status` = 1
                                                              AND (SUBSTRING(c.to_trace_no,1,1) = 5 OR SUBSTRING(c.to_trace_no,1,1) = 4 OR SUBSTRING(c.to_trace_no,1,1) = 6)
                                                              AND SUBSTRING(c.to_trace_no,8,2) <> "00"
                                                            GROUP BY ccc.id_supplier, cc.batch_sap) c
                                                    GROUP BY c.id_material) c
                                          ON a.id_materialpck = c.id_material
                                       WHERE b.`entry_date` IS NOT NULL
                                       UNION ALL
                                      SELECT ? AS entry_date, "Beginning Balance" AS `description`, 0 AS `in`, 0 AS `out`, 0 AS `balance`, "|" AS supplier, "-" AS sloc, 0 AS `balance_supplier`
                                       LIMIT 1) bgn
                            ', [$startDate, $idMaterialFix, $startDate, $idMaterialFix, $idMaterialFix, $startDate, $idMaterialFix, $startDate]);
            $stock = $db[0]->balances;
            if ($stock == null){
              $stock = 0;
            }
        }

        for ($i = 0; $i <= $lenDays; $i++) {
            $currDate = clone $startDate;
            $currDate->modify("+{$i} day");
            $j = $i+1;
            $nextDate = clone $startDate;
            $nextDate->modify("+{$j} day");

            if ($type == 'WIP'){
                if ($mode == 'NORMAL'){
                    $db1 = DB::select('WITH requested_material AS (
                                    SELECT a.id_material
                                      FROM m_material z
                                      LEFT JOIN (SELECT a.code, a.id_material
                                                   FROM m_material a
                                                  WHERE a.status = 1
                                                    ) a
                                        ON z.code = a.code
                                     WHERE z.id_material = ?
                                    ),

                                    bh_filtered AS (
                                        SELECT bb.id_balance_head, bb.id_tank, bb.id_material, bb.trace_no
                                          FROM t_balance_header bb
                                          JOIN requested_material rm
                                            ON bb.id_material = rm.id_material      -- lock to requested id_material
                                         WHERE bb.status = 1
                                           AND bb.id_tank <> 4
                                           AND LEFT(bb.trace_no,1) IN (1,2,3,7,8,9)
                                    ),

                                    th_grouped AS (
                                        SELECT
                                              b.id_balance_head, b.id_material, b.id_trace_head, b.entry_date, SUM(b.in_qty) AS in_qty,
                                              SUM(b.out_qty) AS out_qty, SUM(b.in_qty) - SUM(b.out_qty) AS balance,
                                              GROUP_CONCAT(DISTINCT mt.description SEPARATOR "|") AS sloc, b.to_trace_no
                                         FROM t_trace_header b
                                         LEFT JOIN m_tank mt
                                           ON b.id_sloc = mt.id_tank
                                          AND mt.id_plant = ?                       -- (2) plant
                                        WHERE b.status = 1
                                          AND b.entry_date > ?
                                          AND b.entry_date <= ?                    -- (3) cutoff date
                                        GROUP BY b.id_balance_head, b.id_material, b.entry_date, b.id_trace_head
                                    ),

                                    begin_rows AS (
                                        SELECT
                                            bb.id_material, b.entry_date, b.id_trace_head, "Beginning Balance" AS description,
                                            b.balance, b.sloc, b.in_qty  AS `in`, b.out_qty AS `out`, b.to_trace_no
                                        FROM bh_filtered bb
                                        JOIN th_grouped b
                                          ON b.id_balance_head = bb.id_balance_head
                                         AND b.id_material = bb.id_material
                                    ),

                                    begin_agg AS (
                                        SELECT rm.id_material, SUM(br.balance) AS balance_raw,
                                               GROUP_CONCAT(DISTINCT br.description SEPARATOR "|") AS description,
                                               ROUND(SUM(br.`in`),  3) AS in_raw, ROUND(SUM(br.`out`), 3) AS out_raw,
                                               br.sloc, br.to_trace_no
                                          FROM requested_material rm
                                          LEFT JOIN begin_rows br
                                            ON rm.id_material = br.id_material
                                            WHERE br.sloc <> "-"
                                         GROUP BY rm.id_material
                                    ),

                                    td_filtered AS (
                                        SELECT
                                        	 c.id_balance_head, cc.id_trace_head, c.id_material, cc.id_trace_tail,
                                             cc.id_supplier, cc.batch_sap, ROUND(cc.in_qty,  4) AS in_qty, ROUND(cc.out_qty, 4) AS out_qty
                                        FROM t_trace_header c
										LEFT JOIN t_trace_detail cc
										  ON cc.id_trace_head = c.id_trace_head
                                        JOIN requested_material rm
                                          ON c.id_material = rm.id_material      -- lock to requested id_material
                                       WHERE cc.status = 1
                                         AND (cc.in_qty > 0.001 OR cc.out_qty > 0.001)
                                    ),

                                    th_filtered AS (
                                        SELECT
                                            c.id_trace_head, c.id_balance_head, c.entry_date, c.id_material, c.to_trace_no, c.id_sloc
                                        FROM t_trace_header c
                                        JOIN requested_material rm
                                          ON c.id_material = rm.id_material      -- lock to requested id_material
                                        WHERE c.status = 1
                                          AND c.entry_date <= ?                    -- (5) cutoff date
                                    ),

                                    supplier_lines AS (
                                        SELECT
                                            th.id_material,
                                            th.id_trace_head,
                                            th.entry_date,
                                            td.id_trace_tail,
                                            th.id_balance_head,
                                            (SUM(td.in_qty) - SUM(td.out_qty)) AS balance,
                                            sup.description AS supplier,
                                            td.batch_sap,
                                            th.to_trace_no,
                                            th.id_sloc
                                        FROM th_filtered th
                                        JOIN td_filtered td
                                          ON td.id_trace_head  = th.id_trace_head
                                         AND td.id_material     = th.id_material   -- safe material join
                                        LEFT JOIN m_supplier sup
                                          ON td.id_supplier = sup.id_supplier
                                       GROUP BY
                                            th.id_material, sup.description, td.batch_sap, th.id_sloc
                                    ),

                                    bh_filtered2 AS (   -- same filter, reused for supplier path
                                        SELECT
                                              bb.id_balance_head,
                                              bb.id_tank,
                                              bb.id_material
                                         FROM t_balance_header bb
                                         JOIN requested_material rm
                                           ON bb.id_material = rm.id_material
                                        WHERE bb.status = 1
                                          AND bb.id_tank <> 4
                                          AND LEFT(bb.trace_no,1) IN (1,2,3,7,8,9)
                                    ),

                                    supplier_plant AS (
                                        SELECT
                                            sl.*
                                        FROM supplier_lines sl
                                        JOIN bh_filtered2 bh
                                          ON sl.id_balance_head = bh.id_balance_head
                                         AND sl.id_material     = bh.id_material   -- safe material join
                                        JOIN m_tank t
                                          ON t.id_tank = bh.id_tank
                                       WHERE t.id_plant = ?                      -- (6) plant
                                    ),

                                    supplier_roll AS (
                                        SELECT
                                            sp.id_material, sp.id_trace_tail,
                                            SUM(sp.balance) AS balance_supplier_raw,
                                            GROUP_CONCAT(
                                                DISTINCT CONCAT(
                                                sp.id_trace_tail, " / ",
                                                sp.supplier, " / ",
                                                sp.batch_sap, " / Qty: ",
                                                FORMAT(ROUND(sp.balance,3),3),
                                                " MT / ",
                                                sp.to_trace_no
                                            ) SEPARATOR " | "
                                            ) AS supplier
                                        FROM supplier_plant sp
                                        WHERE sp.balance > 0.0015
                                        GROUP BY sp.id_material
                                    )

                                    SELECT SUBSTRING(?,1,10) AS entry_date, bgn.`to_trace_no` AS `description`, bgn.`in` AS `in`, bgn.`out` AS `out`,
                                              FORMAT(ROUND(bgn.`balance` + ?, 3),3) AS balance, bgn.`supplier`, bgn.`sloc`, bgn.`balance` + ? AS balances,
                                              IF(ABS(REPLACE(bgn.balance_supplier,",","") - REPLACE(bgn.`balance` + ?,",","")) < 0.0099,
                                              FORMAT(ROUND(bgn.`balance` + ?,3),3), FORMAT(ROUND(bgn.`balance_supplier`,3),3)
                                              ) AS balance_supplier
                                         FROM (SELECT GROUP_CONCAT(c.to_trace_no SEPARATOR "|") AS to_trace_no,
                                                      FORMAT(ROUND(SUM(c.in_raw),3),3) AS `in`,
                                                      FORMAT(ROUND(SUM(c.out_raw),3),3) AS `out`, SUM(c.`balance_raw`) AS `balance`,
                                                      GROUP_CONCAT(DISTINCT c.sloc SEPARATOR "|") AS sloc,
                                                      GROUP_CONCAT(DISTINCT d.supplier SEPARATOR "|") AS supplier,
                                                      SUM(d.balance_supplier_raw) AS balance_supplier
                                                FROM requested_material rm
                                                LEFT JOIN begin_agg     c ON c.id_material = rm.id_material
                                                LEFT JOIN supplier_roll d ON d.id_material = rm.id_material
                                            ) bgn
                                    ORDER BY bgn.`supplier` ASC
                                        ', [$idMaterialFix, $idSloc, $currDate, $nextDate, $nextDate, $idSloc,
                                            $nextDate, $stock, $stock, $stock, $stock
                                            ]);

                    if ($db1[0]->balances !== null){
                        $stock = $db1[0]->balances;
                    }

                } elseif ($mode == 'STORAGE'){
                    $db1 = DB::select('SELECT SUBSTRING(?,1,10) AS entry_date, bgn.`to_trace_no` AS `description`, bgn.`in` AS `in`, bgn.`out` AS `out`,
                                              FORMAT(ROUND(bgn.`balance` + ?, 3),3) AS balance, bgn.`supplier`, bgn.`sloc`, bgn.`balance` + ? AS balances,
                                              IF(ABS(bgn.balance_supplier - bgn.`balance` + ?) > 0.009, FORMAT(ROUND(bgn.balance_supplier,3),3), FORMAT(ROUND(bgn.`balance` + ?,3),3)) AS balance_supplier
                                         FROM (SELECT GROUP_CONCAT(DISTINCT c.to_trace_no SEPARATOR "|") AS to_trace_no, FORMAT(ROUND(SUM(c.in),3),3) AS `in`,
                                                      FORMAT(ROUND(SUM(c.out),3),3) AS `out`, SUM(c.`balance`) AS `balance`, c.sloc,
                                                      GROUP_CONCAT(DISTINCT d.supplier SEPARATOR "|") AS supplier,
                                                      SUM(DISTINCT d.balance_supplier) AS balance_supplier
                                                 FROM m_material z
                                                 LEFT JOIN (SELECT a.code, a.id_material
                                                              FROM m_material a
                                                             WHERE a.status = 1) a
                                                   ON z.code = a.code
                                                 LEFT JOIN (SELECT cc.id_material, cc.id_trace_head, cc.entry_date,
                                                                   cc.`to_trace_no`, cc.in, cc.out, cc.balance, bbb.description AS sloc
                                                              FROM m_tank bbb
                                                              LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, bb.entry_date, d.id_trace_head,
                                                                                d.to_trace_no, d.in, d.out, d.balance
                                                                           FROM t_balance_header bb
                                                                           LEFT JOIN (SELECT d.id_balance_head, d.id_material, d.id_trace_head,
                                                                                             d.to_trace_no, SUM(d.in_qty) AS `in`, SUM(d.out_qty) AS `out`,
                                                                                             SUM(d.in_qty) - SUM(d.out_qty) AS `balance`
                                                                                        FROM t_trace_header d
                                                                                       WHERE d.entry_date > ?
                                                                                         AND d.entry_date <= ?
                                                                                         AND d.`status` = 1
                                                                                       GROUP BY d.id_balance_head, d.id_material) d
                                                                             ON d.id_balance_head = bb.id_balance_head
                                                                          WHERE bb.status = 1
                                                                            AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 9 OR SUBSTRING(bb.trace_no,1,1) = 7)
                                                                            AND bb.id_tank = 4
                                                                            AND d.id_trace_head IS NOT NULL
                                                                        ) cc
                                                                ON bbb.id_tank = cc.id_tank
                                                             WHERE cc.id_trace_head IS NOT NULL
                                                               AND bbb.id_plant = ?
                                                            ) c
                                                   ON a.id_material = c.id_material
                                                 LEFT JOIN (SELECT a.code, a.id_material, SUM(d.balance_supplier) AS balance_supplier,
                                                                   GROUP_CONCAT(DISTINCT d.supplier SEPARATOR " | ") AS supplier
                                                              FROM m_material a
                                                              LEFT JOIN (SELECT d.id_material, d.id_trace_head, d.entry_date, d.balance,
                                                                                GROUP_CONCAT(DISTINCT CONCAT(d.id_trace_tail, " / ", d.supplier, " / ", d.`batch_sap`, " / Qty: ", FORMAT(d.`balance`,3), " MT / ", d.to_trace_no) SEPARATOR " | ") AS supplier,
                                                                                SUM(d.`balance`) AS balance_supplier
                                                                           FROM (SELECT cc.id_material, cc.id_trace_head, cc.entry_date, cc.id_trace_tail, cc.to_trace_no,
                                                                                        SUM(ROUND(cc.in_qty,4)) - SUM(ROUND(cc.out_qty,4)) AS `balance`, cc.supplier, cc.`batch_sap`
                                                                                   FROM m_tank bbb
                                                                                   LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, bb.entry_date, c.id_trace_head,
                                                                                                     c.supplier, c.in_qty, c.out_qty, c.batch_sap, c.id_trace_tail, c.to_trace_no
                                                                                                FROM t_balance_header bb
                                                                                                LEFT JOIN (SELECT c.id_material, c.id_trace_head, c.id_balance_head, c.to_trace_no,
                                                                                                                    c.in_qty, c.out_qty, c.`batch_sap`, c.supplier, c.id_trace_tail
                                                                                                                FROM (SELECT c.id_trace_head, c.id_balance_head,
                                                                                                                            c.id_material, ccc.`description` AS supplier, cc.batch_sap,
                                                                                                                            cc.in_qty, cc.out_qty, cc.id_trace_tail, c.to_trace_no
                                                                                                                        FROM t_trace_header c
                                                                                                                        LEFT JOIN (SELECT cc.id_trace_head, cc.batch_sap, cc.id_supplier,
                                                                                                                                          ROUND(cc.in_qty,4) AS in_qty,
                                                                                                                                          ROUND(cc.out_qty,4) AS out_qty,
                                                                                                                                          cc.id_material, cc.id_trace_tail
                                                                                                                                     FROM t_trace_detail cc
                                                                                                                                    WHERE cc.`status` = 1
                                                                                                                                      AND (cc.in_qty > "0.0001" OR cc.out_qty > "0.0001")
                                                                                                                                    ) cc
                                                                                                                        ON c.id_trace_head = cc.id_trace_head
                                                                                                                        LEFT JOIN m_supplier ccc
                                                                                                                        ON cc.id_supplier = ccc.id_supplier
                                                                                                                    WHERE c.entry_date <= ?
                                                                                                                        AND c.`status` = 1
                                                                                                                    ) c
                                                                                                            ) c
                                                                                                    ON c.id_balance_head = bb.id_balance_head
                                                                                                  WHERE bb.status = 1
                                                                                                    AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 9 OR SUBSTRING(bb.trace_no,1,1) = 7)
                                                                                                    AND bb.id_tank = 4
                                                                                                    AND c.id_trace_head IS NOT NULL
                                                                                                ) cc
                                                                                        ON bbb.id_tank = cc.id_tank
                                                                                    WHERE cc.id_trace_head IS NOT NULL
                                                                                    AND bbb.id_plant = ?
                                                                                    GROUP BY cc.id_material, cc.batch_sap, cc.supplier, cc.id_balance_head
                                                                                    ) d
                                                                                WHERE d.balance >= "0.001"
                                                                                GROUP BY d.id_material
                                                                            ) d
                                                                ON a.id_material = d.id_material
                                                             GROUP BY a.code
                                                            ) d
                                                   ON z.code = d.code
                                                WHERE c.`entry_date` IS NOT NULL
                                                  AND z.id_material = ?
                                                GROUP BY a.id_material
                                                UNION ALL
                                               SELECT "-" AS `description`, 0 AS `in`, 0 AS `out`, 0 AS `balance`, "|" AS supplier, "-" AS sloc, 0 AS `balance_supplier`
                                                LIMIT 1) bgn
                                    ', [$nextDate, $stock, $stock, $stock, $stock,
                                        $currDate, $nextDate, $idSloc,
                                        $nextDate, $idSloc,
                                        $idMaterialFix]);

                    $stock = $db1[0]->balances;
                }
            } elseif ($type == 'WH'){
                $db1 = DB::select('SELECT SUBSTRING(?,1,10) AS entry_date, bgn.`description`, bgn.`in`, bgn.`out`, bgn.`balance`, bgn.`supplier`, bgn.`sloc`,
                                          bgn.`balance` + ? AS balances, FORMAT(bgn.`balance` + ?,3) AS `balance`,
                                          IF(ABS(bgn.balance_supplier - bgn.`balance` + ?) < 0.0005, bgn.balance_supplier, ROUND(bgn.`balance` + ?,3)) AS balance_supplier
                                    FROM (SELECT d.`description`, FORMAT(d.`in`,3) AS `in`, FORMAT(SUM(DISTINCT c.`balance_supplier`),3) AS balance_supplier,
                                                 FORMAT(d.`out`,3) AS `out`, IFNULL(d.balance,0) AS balance, c.`supplier`, d.sloc
                                            FROM m_material_pck a
                                            LEFT JOIN (SELECT b.id_material, b.id_trace_head, GROUP_CONCAT(DISTINCT b.to_trace_no SEPARATOR " | ") AS `description`, bb.sloc,
                                                              SUM(b.in_qty) AS `in`, SUM(b.out_qty) AS `out`, SUM(b.in_qty) - SUM(b.out_qty) AS `balance`, b.entry_date
                                                         FROM t_trace_header b
                                                         LEFT JOIN (SELECT bb.id_whx_head, bbb.description AS sloc
                                                                      FROM t_warehouse_header bb
                                                                      LEFT JOIN m_warehouse bbb
                                                                        ON bb.id_section = bbb.id_warehouse
                                                                     WHERE bb.status = 1
                                                                       AND bb.id_material_fg = ?
                                                                       AND (SUBSTRING(bb.trace_no,1,1) = 5 OR SUBSTRING(bb.trace_no,1,1) = 4 OR SUBSTRING(bb.trace_no,1,1) = 6)
                                                                       AND SUBSTRING(bb.trace_no,8,2) <> "00" ) bb
                                                           ON b.id_balance_head = bb.id_whx_head
                                                        WHERE b.entry_date > ?
                                                          AND b.entry_date <= ?
                                                          AND b.id_material = ?
                                                          AND b.`status` = 1
                                                          AND (SUBSTRING(b.to_trace_no,1,1) = 5 OR SUBSTRING(b.to_trace_no,1,1) = 4 OR SUBSTRING(b.to_trace_no,1,1) = 6)
                                                          AND SUBSTRING(b.to_trace_no,8,2) <> "00"
                                                        GROUP BY b.id_material) d
                                              ON a.id_materialpck = d.id_material
                                            LEFT JOIN (SELECT c.id_material, GROUP_CONCAT(DISTINCT CONCAT(c.id_trace_tail, " / ", c.`description`, " / ", c.`batch_sap`, " / Qty: ", FORMAT(c.`balance`,3), " MT") SEPARATOR " | ") AS supplier,
                                                              SUM(c.`balance`) AS balance_supplier
                                                         FROM (SELECT c.id_trace_head, c.id_material, ccc.`description`, cc.batch_sap,
                                                                      SUM(ROUND(cc.in_qty,4)) - SUM(ROUND(cc.out_qty,4)) AS `balance`, cc.id_trace_tail
                                                                 FROM t_trace_header c
                                                                 LEFT JOIN (SELECT cc.id_trace_head, cc.batch_sap, cc.id_supplier,
                                                                                   cc.in_qty, cc.out_qty, CONCAT(cc.id_trace_head, "-", cc.id_trace_tail) AS id_trace_tail
                                                                              FROM t_trace_detail cc
                                                                             WHERE cc.`status` = 1
                                                                               AND cc.id_material = ?) cc
                                                                   ON c.id_trace_head = cc.id_trace_head
                                                                 LEFT JOIN m_supplier ccc
                                                                   ON cc.id_supplier = ccc.id_supplier
                                                                WHERE c.entry_date <= ?
                                                                  AND c.id_material = ?
                                                                  AND c.`status` = 1
                                                                  AND (SUBSTRING(c.to_trace_no,1,1) = 5 OR SUBSTRING(c.to_trace_no,1,1) = 4 OR SUBSTRING(c.to_trace_no,1,1) = 6)
                                                                  AND SUBSTRING(c.to_trace_no,8,2) <> "00"
                                                                GROUP BY ccc.id_supplier) c
                                                        GROUP BY c.id_material) c
                                              ON a.id_materialpck = c.id_material
                                           WHERE d.`entry_date` IS NOT NULL
                                           UNION ALL
                                          SELECT "-" AS `description`, 0 AS `in`, 0 AS `out`, 0 AS `balance`, "|" AS supplier, "-" AS sloc, 0 AS `balance_supplier`
                                           LIMIT 1) bgn
                                ', [$nextDate, $stock, $stock, $stock, $stock,
                                    $idMaterialFix, $currDate, $nextDate, $idMaterialFix,
                                    $idMaterialFix, $nextDate, $idMaterialFix]);
                $stock = $db1[0]->balances;
            }

            $filtered_db1 = array_filter($db1, function($item) {
                return strpos($item->description, '-') === false;
            });
            $filtered_db1 = array_filter($filtered_db1, function($item) {
                return !is_null($item->in);
            });
            $db = array_merge($db, $filtered_db1);

        }

        return $db;
    }
    static function get_activeSloc(){
        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');
        $db = DB::select('SELECT id_plant, IF(id_plant = 1002, "EOB1", `description`) AS `description`
                            FROM m_tank
                           WHERE `status` = 1
                           GROUP BY id_plant
                           ORDER BY
                                    CASE
                                        WHEN id_plant = "1002" THEN 1
                                        WHEN id_plant = "1003" THEN 2
                                        WHEN id_plant = "1007" THEN 3
                                        WHEN id_plant = "1001" THEN 4
                                        ELSE 5
                                    END');

        return $db;
    }
    static function get_dtStockSummary($request){
        $s_stockDateStart = $request->input('stockDateStart');
        $s_stockDateEnd = $request->input('stockDateEnd');
        $stockDateStart = new \DateTime($request->input('stockDateStart'));
        $stockDateEnd = new \DateTime($request->input('stockDateEnd'));
        $mode = $request->input('mode');
        $idSloc = $request->input('idSloc');

        DB::select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""));');

        if ($mode == 'SUMMARY_WIP'){
            $db = DB::select('SELECT bgn.entry_date, bgn.`description`, bgn.`in`, bgn.`out`, bgn.`supplier`, bgn.`sloc`,
                                     bgn.`balance`, bgn.`balance` AS balances,
                                     IF(bgn.delta > 0.009, FORMAT(bgn.`balance_supplier`,3), bgn.last_balance) AS balance_supplier,
                                     bgn.material, bgn.init_balance, bgn.last_balance AS last_balance
                                FROM (SELECT CONCAT(?, " to ", ?) AS entry_date, bgn.`description`,
                                             FORMAT(bgn.`in`, 3) AS `in`, FORMAT(bgn.`out`, 3) AS `out`,
                                             FORMAT(bgn.`balance`, 3) AS balance, bgn.`supplier`, bgn.`sloc`, FORMAT(bgn.`balance`,3) AS balances,
                                             bgn.material, FORMAT(bgn.init_balance, 3) AS init_balance,
                                             ROUND(bgn.`init_balance` + bgn.in - bgn.out, 3) AS `last_balance`,
                                             bgn.balance_supplier,
                                             ABS(REPLACE(bgn.balance_supplier, ",", "") - REPLACE(ROUND(bgn.`init_balance` + bgn.in - bgn.out, 3), ",", "")) AS delta
                                        FROM (   SELECT GROUP_CONCAT(DISTINCT b.`description` SEPARATOR "|") AS description,
                                                        ROUND(SUM(b.in),3) AS `in`, ROUND(SUM(b.out),3) AS `out`, ROUND(SUM(b.`balance`),3) AS `balance`,
                                                        GROUP_CONCAT(DISTINCT b.sloc SEPARATOR "|") AS sloc,
                                                        GROUP_CONCAT(DISTINCT c.supplier SEPARATOR "|") AS supplier,
                                                        CONCAT(z.`description`, " (", z.`code`, ")") AS material,
                                                        ROUND(SUM(d.`balance`),3) AS `init_balance`, ROUND(SUM(c.`balance_supplier`),3) AS balance_supplier
                                                    FROM m_material z
                                                    LEFT JOIN (SELECT a.code, a.id_material
                                                                FROM m_material a
                                                                WHERE a.status = 1) a
                                                      ON z.code = a.code
                                                    LEFT JOIN (SELECT bb.id_material, bb.id_trace_head, bb.entry_date, "Current On-Hand" AS `description`,
                                                                      IFNULL(SUM(bb.`balance`),0) AS balance, bb.sloc,
                                                                      IFNULL(SUM(bb.`in`),0) AS `in`, IFNULL(SUM(bb.`out`),0) AS `out`
                                                                 FROM m_tank bbb
                                                                 LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, bb.entry_date,
                                                                                   b.id_trace_head, IFNULL(b.balance,0) AS balance,
                                                                                   IFNULL(b.in,0) AS `in`, IFNULL(b.out,0) AS `out`, b.sloc
                                                                              FROM t_balance_header bb
                                                                              LEFT JOIN (SELECT b.id_balance_head, b.id_material, b.id_trace_head, b.entry_date,
                                                                                                SUM(b.in_qty) - SUM(b.out_qty) AS `balance`, SUM(b.in_qty) AS `in`,
                                                                                                SUM(b.out_qty) AS `out`, GROUP_CONCAT(DISTINCT bb.description SEPARATOR "|") AS sloc
                                                                                           FROM t_trace_header b
                                                                                           LEFT JOIN m_tank bb
                                                                                             ON b.id_sloc = bb.id_tank
                                                                                          WHERE b.`status` = 1
                                                                                            AND b.entry_date >= ?
                                                                                            AND b.entry_date <= ?
                                                                                          GROUP BY b.id_balance_head, b.id_material, b.entry_date
                                                                                        ) b
                                                                                ON b.id_balance_head = bb.id_balance_head
                                                                             WHERE bb.status = 1
                                                                               AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 9)
                                                                               AND (bb.id_tank = 4)
                                                                        ) bb
                                                                ON bbb.id_tank = bb.id_tank
                                                            WHERE bbb.id_plant = ?
                                                            GROUP BY bb.id_material
                                                            ) b
                                                    ON a.id_material = b.id_material
                                                    LEFT JOIN (SELECT d.id_material, d.id_trace_head, d.entry_date, d.balance,
                                                                      GROUP_CONCAT(DISTINCT CONCAT(d.id_trace_tail, " / ", d.supplier, " / ", d.`batch_sap`, " / Qty: ", FORMAT(d.`balance`,3), " MT / ", d.to_trace_no) SEPARATOR " | ") AS supplier,
                                                                      SUM(d.`balance`) AS balance_supplier
                                                                FROM (SELECT cc.id_material, cc.id_trace_head, cc.entry_date, cc.id_trace_tail, cc.to_trace_no,
                                                                             SUM(cc.in_qty) - SUM(cc.out_qty) AS `balance`, cc.supplier, cc.`batch_sap`
                                                                        FROM m_tank bbb
                                                                        LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, c.entry_date, c.id_trace_head, c.to_trace_no,
                                                                                          c.supplier, IFNULL(c.in_qty,0) AS in_qty, IFNULL(c.out_qty,0) AS out_qty, c.batch_sap,
                                                                                          c.id_trace_tail
                                                                                     FROM t_balance_header bb
                                                                                     LEFT JOIN (SELECT c.id_material, c.id_trace_head, c.id_balance_head, c.entry_date,
                                                                                                       c.in_qty, c.out_qty, c.`batch_sap`, c.supplier, c.to_trace_no, c.id_trace_tail
                                                                                                  FROM (SELECT c.id_trace_head, c.id_balance_head, c.entry_date, c.to_trace_no,
                                                                                                               c.id_material, ccc.`description` AS supplier, cc.batch_sap,
                                                                                                               cc.in_qty, cc.out_qty, cc.id_trace_tail
                                                                                                          FROM t_trace_header c
                                                                                                          LEFT JOIN (SELECT cc.id_trace_head, cc.batch_sap, cc.id_supplier,
                                                                                                                            ROUND(cc.in_qty,4) AS in_qty,
                                                                                                                            ROUND(cc.out_qty,4) AS out_qty,
                                                                                                                            cc.id_material, cc.id_trace_tail
                                                                                                                       FROM t_trace_detail cc
                                                                                                                      WHERE cc.`status` = 1
                                                                                                                        AND (cc.in_qty > "0.0001" OR cc.out_qty > "0.0001")
                                                                                                                    ) cc
                                                                                                           ON c.id_trace_head = cc.id_trace_head
                                                                                                         LEFT JOIN m_supplier ccc
                                                                                                           ON cc.id_supplier = ccc.id_supplier
                                                                                                        WHERE c.entry_date <= ?
                                                                                                          AND c.`status` = 1
                                                                                                        ) c
                                                                                                ) c
                                                                                       ON c.id_balance_head = bb.id_balance_head
                                                                                    WHERE bb.status = 1
                                                                                      AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 9)
                                                                                      AND (bb.id_tank = 4)
                                                                                    ) cc
                                                                           ON bbb.id_tank = cc.id_tank
                                                                        WHERE bbb.id_plant = ?
                                                                        GROUP BY cc.id_material, cc.batch_sap, cc.supplier, cc.id_balance_head
                                                                    ) d
                                                                WHERE d.balance >= 0.001
                                                                GROUP BY d.id_material
                                                                ORDER BY d.id_trace_tail ASC
                                                                ) c
                                                    ON a.id_material = c.id_material
                                                    LEFT JOIN (SELECT bb.id_material, bb.id_trace_head, bb.entry_date, "Current On-Hand" AS `description`,
                                                                    IFNULL(SUM(bb.`balance`),0) AS balance, bbb.description AS sloc
                                                                FROM m_tank bbb
                                                                LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, bb.entry_date,
                                                                                b.id_trace_head, IFNULL(b.balance,0) AS balance,
                                                                                IFNULL(b.in,0) AS `in`, IFNULL(b.out,0) AS `out`
                                                                            FROM t_balance_header bb
                                                                            LEFT JOIN (SELECT b.id_balance_head, b.id_material, b.id_trace_head, b.entry_date,
                                                                                                SUM(b.in_qty) - SUM(b.out_qty) AS `balance`, SUM(b.in_qty) AS `in`,
                                                                                                SUM(b.out_qty) AS `out`
                                                                                        FROM t_trace_header b
                                                                                        WHERE b.`status` = 1
                                                                                            AND b.entry_date < ?
                                                                                        GROUP BY b.id_balance_head, b.id_material, b.entry_date
                                                                                        ) b
                                                                                ON b.id_balance_head = bb.id_balance_head
                                                                            WHERE bb.status = 1
                                                                            AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 9)
                                                                            AND (bb.id_tank = 4)
                                                                        ) bb
                                                                ON bbb.id_tank = bb.id_tank
                                                            WHERE bbb.id_plant = ?
                                                            GROUP BY bb.id_material
                                                            ) d
                                                    ON a.id_material = d.id_material
                                                WHERE b.`balance` IS NOT NULL
                                                GROUP BY a.code) bgn
                                        WHERE bgn.`description` IS NOT NULL
                                        UNION ALL
                                        SELECT CONCAT(?, " to ", ?) AS entry_date, bgn.`description`,
                                                FORMAT(bgn.`in`, 3) AS `in`, FORMAT(bgn.`out`, 3) AS `out`,
                                                FORMAT(bgn.`balance`, 3) AS balance, bgn.`supplier`, bgn.`sloc`, bgn.`balance` AS balances,
                                                bgn.material, FORMAT(bgn.init_balance, 3) AS init_balance,
                                                FORMAT(bgn.`init_balance` + bgn.in - bgn.out, 3) AS `last_balance`, bgn.balance_supplier,
                                                0 AS delta
                                            FROM (SELECT GROUP_CONCAT(DISTINCT b.`description` SEPARATOR "|") AS description,
                                                         SUM(DISTINCT b.in) AS `in`, SUM(DISTINCT b.out) AS `out`, SUM(DISTINCT b.`balance`) AS `balance`,
                                                         GROUP_CONCAT(DISTINCT b.sloc SEPARATOR "|") AS sloc,
                                                         GROUP_CONCAT(DISTINCT c.supplier SEPARATOR "|") AS supplier,
                                                         CONCAT(z.`description`, " (", z.`code`, ")") AS material,
                                                         SUM(DISTINCT d.`balance`) AS `init_balance`, SUM(DISTINCT c.`balance_supplier`) AS balance_supplier
                                                    FROM m_material z
                                                    LEFT JOIN (SELECT a.code, a.id_material
                                                                    FROM m_material a
                                                                    WHERE a.status = 1) a
                                                      ON z.code = a.code
                                                    LEFT JOIN (
                                                                SELECT bb.id_material, bb.id_trace_head, bb.entry_date, "Current On-Hand" AS `description`,
                                                                        IFNULL(SUM(bb.`balance`),0) AS balance, bbb.description AS sloc,
                                                                        IFNULL(SUM(bb.`in`),0) AS `in`, IFNULL(SUM(bb.`out`),0) AS `out`
                                                                    FROM m_tank bbb
                                                                    LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, bb.entry_date,
                                                                                    b.id_trace_head, IFNULL(b.balance,0) AS balance,
                                                                                    IFNULL(b.in,0) AS `in`, IFNULL(b.out,0) AS `out`
                                                                                FROM t_balance_header bb
                                                                                LEFT JOIN (SELECT b.id_balance_head, b.id_material, b.id_trace_head, b.entry_date,
                                                                                                    SUM(DISTINCT b.in_qty) - SUM(DISTINCT b.out_qty) AS `balance`, SUM(b.in_qty) AS `in`,
                                                                                                    SUM(b.out_qty) AS `out`, GROUP_CONCAT(DISTINCT bb.description SEPARATOR "|") AS sloc
                                                                                            FROM t_trace_header b
                                                                                            LEFT JOIN m_tank bb
                                                                                                ON b.id_sloc = bb.id_tank
                                                                                            WHERE b.`status` = 1
                                                                                                AND b.entry_date >= ?
                                                                                                AND b.entry_date <= ?
                                                                                            GROUP BY b.id_balance_head, b.id_material
                                                                                            ) b
                                                                                    ON b.id_balance_head = bb.id_balance_head
                                                                                WHERE bb.status = 1
                                                                                AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 2 OR
                                                                                        SUBSTRING(bb.trace_no,1,1) = 3 OR SUBSTRING(bb.trace_no,1,1) = 7 OR
                                                                                        SUBSTRING(bb.trace_no,1,1) = 8 OR SUBSTRING(bb.trace_no,1,1) = 9)
                                                                                AND bb.id_tank <> 4
                                                                                AND b.id_trace_head IS NOT NULL
                                                                            ) bb
                                                                    ON bbb.id_tank = bb.id_tank
                                                                WHERE bb.id_trace_head IS NOT NULL
                                                                    AND bbb.id_plant = ?
                                                                  GROUP BY bb.id_material
                                                                ) b
                                                      ON a.id_material = b.id_material
                                                    LEFT JOIN (SELECT d.id_material, d.id_trace_head, d.entry_date, d.balance,
                                                                      GROUP_CONCAT(DISTINCT CONCAT(d.id_trace_tail, " / ", d.supplier, " / ", d.`batch_sap`, " / Qty: ", FORMAT(d.`balance`,3), " MT /", d.to_trace_no) SEPARATOR " | ") AS supplier,
                                                                      SUM(d.`balance`) AS balance_supplier
                                                                 FROM (SELECT cc.id_material, cc.id_trace_head, cc.entry_date, cc.to_trace_no, cc.id_trace_tail,
                                                                              SUM(cc.in_qty) - SUM(cc.out_qty) AS `balance`, cc.supplier, cc.`batch_sap`
                                                                         FROM m_tank bbb
                                                                         LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, bb.entry_date, c.id_trace_head,
                                                                                               c.supplier, c.in_qty, c.out_qty, c.batch_sap, c.id_trace_tail, c.to_trace_no
                                                                                          FROM t_balance_header bb
                                                                                          LEFT JOIN (SELECT c.id_material, c.id_trace_head, c.id_balance_head, c.to_trace_no,
                                                                                                            c.in_qty, c.out_qty, c.`batch_sap`, c.supplier, c.id_trace_tail
                                                                                                       FROM (SELECT c.id_trace_head, c.id_balance_head,
                                                                                                                    c.id_material, ccc.`description` AS supplier, cc.batch_sap,
                                                                                                                    cc.in_qty, cc.out_qty, cc.id_trace_tail, c.to_trace_no
                                                                                                               FROM t_trace_header c
                                                                                                               LEFT JOIN (SELECT cc.id_trace_head, cc.batch_sap, cc.id_supplier,
                                                                                                                                 ROUND(cc.in_qty,4) AS in_qty,
                                                                                                                                 ROUND(cc.out_qty,4) AS out_qty,
                                                                                                                                 cc.id_material, cc.id_trace_tail
                                                                                                                            FROM t_trace_detail cc
                                                                                                                           WHERE cc.`status` = 1
                                                                                                                             AND (cc.in_qty > "0.0001" OR cc.out_qty > "0.0001")
                                                                                                                        ) cc
                                                                                                                 ON c.id_trace_head = cc.id_trace_head
                                                                                                               LEFT JOIN m_supplier ccc
                                                                                                                 ON cc.id_supplier = ccc.id_supplier
                                                                                                              WHERE c.entry_date <= ?
                                                                                                                AND c.`status` = 1
                                                                                                            ) c
                                                                                                    ) c
                                                                                            ON c.id_balance_head = bb.id_balance_head
                                                                                         WHERE bb.status = 1
                                                                                           AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 2 OR
                                                                                                SUBSTRING(bb.trace_no,1,1) = 3 OR SUBSTRING(bb.trace_no,1,1) = 7 OR
                                                                                                SUBSTRING(bb.trace_no,1,1) = 8 OR SUBSTRING(bb.trace_no,1,1) = 9)
                                                                                           AND bb.id_tank <> 4
                                                                                           AND c.id_trace_head IS NOT NULL
                                                                                        ) cc
                                                                               ON bbb.id_tank = cc.id_tank
                                                                            WHERE bbb.id_plant = ?
                                                                            GROUP BY cc.id_material, cc.batch_sap, cc.supplier, cc.id_balance_head
                                                                        ) d
                                                                    WHERE d.balance >= 0.001
                                                                    GROUP BY d.id_material
                                                                    ) c
                                                       ON a.id_material = c.id_material
                                                     LEFT JOIN (SELECT bb.id_material, bb.id_trace_head, bb.entry_date, "Current On-Hand" AS `description`,
                                                                        IFNULL(SUM(bb.`balance`),0) AS balance, bbb.description AS sloc
                                                                    FROM m_tank bbb
                                                                    LEFT JOIN (SELECT bb.id_tank, bb.id_balance_head, bb.id_material, bb.entry_date,
                                                                                    b.id_trace_head, IFNULL(b.balance,0) AS balance,
                                                                                    IFNULL(b.in,0) AS `in`, IFNULL(b.out,0) AS `out`
                                                                                FROM t_balance_header bb
                                                                                LEFT JOIN (SELECT b.id_balance_head, b.id_material, b.id_trace_head, b.entry_date,
                                                                                                    SUM(b.in_qty) - SUM(b.out_qty) AS `balance`, SUM(b.in_qty) AS `in`,
                                                                                                    SUM(b.out_qty) AS `out`
                                                                                            FROM t_trace_header b
                                                                                            WHERE b.`status` = 1
                                                                                                AND b.entry_date < ?
                                                                                            GROUP BY b.id_balance_head, b.id_material
                                                                                            ) b
                                                                                    ON b.id_balance_head = bb.id_balance_head
                                                                                WHERE bb.status = 1
                                                                                AND (SUBSTRING(bb.trace_no,1,1) = 1 OR SUBSTRING(bb.trace_no,1,1) = 2 OR
                                                                                        SUBSTRING(bb.trace_no,1,1) = 3 OR SUBSTRING(bb.trace_no,1,1) = 7 OR
                                                                                        SUBSTRING(bb.trace_no,1,1) = 8 OR SUBSTRING(bb.trace_no,1,1) = 9)
                                                                                AND bb.id_tank <> 4
                                                                            ) bb
                                                                    ON bbb.id_tank = bb.id_tank
                                                                WHERE bbb.id_plant = ?
                                                                GROUP BY bb.id_material
                                                                ) d
                                                       ON a.id_material = d.id_material
                                                    WHERE b.`balance` IS NOT NULL
                                                    GROUP BY a.code) bgn
                                            WHERE bgn.`description` IS NOT NULL) bgn
                               GROUP BY bgn.sloc, bgn.material
                                ', [$s_stockDateStart, $s_stockDateEnd,
                                    $stockDateStart, $stockDateEnd, $idSloc,
                                    $stockDateEnd, $idSloc,
                                    $stockDateStart, $idSloc,
                                    $s_stockDateStart, $s_stockDateEnd,
                                    $stockDateStart, $stockDateEnd, $idSloc,
                                    $stockDateEnd, $idSloc,
                                    $stockDateStart, $idSloc]);

        } elseif ($mode == 'SUMMARY_WH'){
            $db = DB::select('SELECT bgn.entry_date, bgn.`description`, bgn.`in`, bgn.`out`, bgn.`supplier`, bgn.`sloc`,
                                     FORMAT(bgn.`balance`, 3) AS `balance`, bgn.`balance` AS balances, bgn.material,
                                     FORMAT(bgn.`last_balance`, 3) AS `last_balance`, bgn.init_balance,
                                     FORMAT(bgn.balance_supplier,3) AS balance_supplier
                                FROM (SELECT CONCAT(?, " to ", ?) AS entry_date, b.`description`, FORMAT(b.`in`,3) AS `in`,
                                             FORMAT(b.`out`,3) AS `out`, b.balance, c.`supplier`, (IFNULL(d.init_balance,0) + b.in - b.out) AS last_balance,
                                             b.sloc, CONCAT(a.`description`, " (", a.`code`, ")") AS material,
                                             FORMAT(IFNULL(d.init_balance,0),3) AS init_balance, c.`balance_supplier`
                                        FROM m_material_pck a
                                        LEFT JOIN (SELECT b.id_material, b.id_trace_head, b.entry_date, "Beginning Balance" AS `description`,
                                                          SUM(b.in_qty) AS `in`, SUM(b.out_qty) AS `out`, SUM(b.in_qty) - SUM(b.out_qty) AS `balance`,
                                                          bb.sloc
                                                     FROM t_trace_header b
                                                     LEFT JOIN (SELECT bb.id_whx_head, bbb.description AS sloc
                                                                  FROM t_warehouse_header bb
                                                                  LEFT JOIN m_warehouse bbb
                                                                    ON bb.id_section = bbb.id_warehouse
                                                                 WHERE bb.status = 1
                                                                   AND (SUBSTRING(bb.trace_no,1,1) = 5 OR SUBSTRING(bb.trace_no,1,1) = 4 OR SUBSTRING(bb.trace_no,1,1) = 6)
                                                                ) bb
                                                       ON b.id_balance_head = bb.id_whx_head
                                                    WHERE b.entry_date >= ?
                                                      AND b.entry_date <= ?
                                                      AND b.`status` = 1
                                                      AND (SUBSTRING(b.to_trace_no,1,1) = 5 OR SUBSTRING(b.to_trace_no,1,1) = 4 OR SUBSTRING(b.to_trace_no,1,1) = 6)
                                                      AND SUBSTRING(b.to_trace_no,8,2) <> "00"
                                                    GROUP BY b.id_material) b
                                          ON a.id_materialpck = b.id_material
                                        LEFT JOIN (SELECT c.id_material, GROUP_CONCAT(DISTINCT CONCAT(c.`description`, " / ", c.`batch_sap`, " / Qty: ", FORMAT(c.`balance`,3), " MT") SEPARATOR " | ") AS supplier,
                                                          SUM(DISTINCT c.`balance`) AS balance_supplier
                                                     FROM (SELECT c.id_trace_head, c.id_material, ccc.`description`, cc.batch_sap,
                                                                  SUM(cc.in_qty) AS in_qty, SUM(cc.out_qty) AS out_qty,
                                                                  SUM(cc.in_qty) - SUM(cc.out_qty) AS `balance`
                                                             FROM t_trace_header c
                                                             LEFT JOIN (SELECT cc.id_trace_head, cc.batch_sap, cc.id_supplier,
                                                                               cc.in_qty, cc.out_qty
                                                                          FROM t_trace_detail cc
                                                                         WHERE cc.`status` = 1
                                                                        ) cc
                                                               ON c.id_trace_head = cc.id_trace_head
                                                             LEFT JOIN m_supplier ccc
                                                               ON cc.id_supplier = ccc.id_supplier
                                                            WHERE c.entry_date <= ?
                                                              AND c.`status` = 1
                                                              AND (SUBSTRING(c.to_trace_no,1,1) = 5 OR SUBSTRING(c.to_trace_no,1,1) = 4 OR SUBSTRING(c.to_trace_no,1,1) = 6)
                                                              AND SUBSTRING(c.to_trace_no,8,2) <> "00"
                                                            GROUP BY c.id_material, ccc.id_supplier) c
                                                    GROUP BY c.id_material) c
                                          ON a.id_materialpck = c.id_material
                                        LEFT JOIN (SELECT b.id_material, b.id_trace_head, b.entry_date, "Beginning Balance" AS `description`,
                                                          SUM(b.in_qty) - SUM(b.out_qty) AS `init_balance`
                                                     FROM t_trace_header b
                                                     LEFT JOIN (SELECT bb.id_whx_head, bbb.description AS sloc
                                                                  FROM t_warehouse_header bb
                                                                  LEFT JOIN m_warehouse bbb
                                                                    ON bb.id_section = bbb.id_warehouse
                                                                 WHERE bb.status = 1
                                                                   AND (SUBSTRING(bb.trace_no,1,1) = 5 OR SUBSTRING(bb.trace_no,1,1) = 4 OR SUBSTRING(bb.trace_no,1,1) = 6)
                                                                ) bb
                                                       ON b.id_balance_head = bb.id_whx_head
                                                    WHERE b.entry_date < ?
                                                      AND b.`status` = 1
                                                      AND (SUBSTRING(b.to_trace_no,1,1) = 5 OR SUBSTRING(b.to_trace_no,1,1) = 4 OR SUBSTRING(b.to_trace_no,1,1) = 6)
                                                      AND SUBSTRING(b.to_trace_no,8,2) <> "00"
                                                    GROUP BY b.id_material) d
                                          ON a.id_materialpck = d.id_material
                                       WHERE b.`entry_date` IS NOT NULL
                                    ) bgn
                            ', [$s_stockDateStart, $s_stockDateEnd,
                                $stockDateStart, $stockDateEnd,
                                $stockDateEnd,
                                $stockDateStart]);
        }

        return $db;
    }
}
