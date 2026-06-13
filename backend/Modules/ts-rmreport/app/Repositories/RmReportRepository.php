<?php declare(strict_types=1);

namespace Modules\TsRmreport\Repositories;

use Modules\TsRmreport\Repositories\Contracts\RmReportRepositoryInterface;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;
use Illuminate\Support\Facades\DB;

class RmReportRepository implements RmReportRepositoryInterface
{
    use PlantFilterTrait;

    protected string $connection = 'eudr_ts';

    public function getRmReport(array $filters): array
    {
        return $this->getRmListDetail($filters);
    }

    public function getRmListDetail(array $filters): array
    {
        $plantId = $filters['plant_id'] ?? $filters['id_plant'] ?? null;
        $materialId = $filters['material_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $where = ["c.type = 'RM'", 'a.status = 1'];
        $bindings = [];

        if ($plantId) { $where[] = '(a.id_plant = ? OR ? = 0)'; $bindings[] = $plantId; $bindings[] = $plantId; }
        if ($materialId) { $where[] = 'a.id_material = ?'; $bindings[] = $materialId; }
        if ($dateFrom) { $where[] = 'a.entry_date >= ?'; $bindings[] = $dateFrom; }
        if ($dateTo) { $where[] = 'a.entry_date <= ?'; $bindings[] = $dateTo; }

        return DB::connection($this->connection)->select(
            "SELECT a.id_balance_head, CAST(a.trace_no AS CHAR) AS trace_no,
                    FORMAT(SUM(DISTINCT a.qty),3) AS qty,
                    CONCAT(c.code,' :: ',c.description) AS material,
                    FORMAT(SUM(DISTINCT a.init_qty),3) AS init_qty,
                    a.entry_date, b.batch_sap,
                    GROUP_CONCAT(DISTINCT CONCAT(e.code,' :: ',e.description,
                        ' / ',b.batch_sap,' / Qty:',FORMAT(b.init_qty,3),' MT',
                        IF(b.out_qty=0,'',' / BATCH TRANSFERRED'))
                        SEPARATOR ' | ') AS supplier,
                    f.material_document, f.po_so,
                    FORMAT(bs.supplier_qty,3) AS balance_supplier
               FROM t_balance_header a
               LEFT JOIN t_balance_detail b ON a.id_balance_head=b.id_balance_head AND b.status=1
               LEFT JOIN m_material c ON a.id_material=c.id_material
               LEFT JOIN m_sloc d ON a.id_sloc = d.id_sloc AND d.status=1
               LEFT JOIN m_supplier e ON e.id_supplier=b.id_supplier
               LEFT JOIN (SELECT f.id_balance_head,g.material_document,g.po_so,f.id_trace_head
                            FROM t_trace_header f
                            LEFT JOIN t_material_document g ON f.id_trace_head=g.id_trace_head
                           WHERE f.status=1 GROUP BY f.id_balance_head) f
                 ON f.id_balance_head=a.id_balance_head
               LEFT JOIN (SELECT id_balance_head,SUM(init_qty) AS supplier_qty
                            FROM t_balance_detail WHERE status=1 GROUP BY id_balance_head) bs
                 ON bs.id_balance_head=a.id_balance_head
              WHERE " . implode(' AND ', $where) . "
                AND (SUBSTRING(a.trace_no,1,1)='1' OR SUBSTRING(a.trace_no,1,1)='9')
                AND SUBSTRING(a.trace_no,8,3)='000'
              GROUP BY a.trace_no ORDER BY a.id_balance_head DESC",
            $bindings
        );
    }

    public function getRmListTransfer(array $filters): array
    {
        $plantId = $filters['plant_id'] ?? $filters['id_plant'] ?? null;

        $idTankFeed = DB::connection($this->connection)->table('m_sloc')
            ->where('status', 1)->where('code_3', 'FEED')->where('id_plant', 1002)->value('id_sloc');

        $where = ["c.type = 'RM'", 'a.status = 1'];
        $bindings = [];

        if ($plantId) { $where[] = '(a.id_plant = ? OR ? = 0)'; $bindings[] = $plantId; $bindings[] = $plantId; }

        return DB::connection($this->connection)->select(
            "SELECT a.id_balance_head, CAST(a.trace_no AS CHAR) AS trace_no,
                    aa.qty, aa.init_qty,
                    CONCAT(c.code,' :: ',c.description) AS material,
                    a.entry_date, b.batch_sap,
                    GROUP_CONCAT(DISTINCT CONCAT(e.code,' :: ',e.description,
                        ' / ',b.batch_sap,' / Qty:',FORMAT(b.init_qty,3),' MT',
                        IF(b.out_qty=0,'',' / BATCH USED IN WIP'))
                        SEPARATOR ' | ') AS supplier,
                    f.material_document, f.po_so,
                    FORMAT(bs.supplier_qty,3) AS balance_supplier
               FROM t_balance_header a
               LEFT JOIN (SELECT trace_no,FORMAT(SUM(qty),3) AS qty,FORMAT(SUM(init_qty),3) AS init_qty
                            FROM t_balance_header WHERE status=1
                              AND (SUBSTRING(trace_no,1,1)='1' OR SUBSTRING(trace_no,1,1)='2')
                            GROUP BY trace_no) aa ON a.trace_no=aa.trace_no
               LEFT JOIN t_balance_detail b ON a.id_balance_head=b.id_balance_head AND b.status=1
               LEFT JOIN m_material c ON a.id_material=c.id_material
               LEFT JOIN m_sloc d ON a.id_sloc = d.id_sloc AND d.status=1
               LEFT JOIN m_supplier e ON e.id_supplier=b.id_supplier
               LEFT JOIN (SELECT f.id_balance_head,g.material_document,g.po_so,f.id_trace_head
                            FROM t_trace_header f
                            LEFT JOIN t_material_document g ON f.id_trace_head=g.id_trace_head
                           WHERE f.status=1 GROUP BY f.id_balance_head) f
                  ON f.id_balance_head=a.id_balance_head
               LEFT JOIN (SELECT id_balance_head,SUM(init_qty) AS supplier_qty
                            FROM t_balance_detail WHERE status=1 GROUP BY id_balance_head) bs
                  ON bs.id_balance_head=a.id_balance_head
              WHERE " . implode(' AND ', $where) . "
                AND (SUBSTRING(a.trace_no,1,1)='1' OR SUBSTRING(a.trace_no,1,1)='2')
                AND a.id_sloc=?
              GROUP BY a.trace_no ORDER BY a.id_balance_head DESC",
            array_merge($bindings, [$idTankFeed])
        );
    }

    public function getRmSummaryRmPrd(array $filters): array
    {
        $selectedYear = $filters['selectedYear'] ?? $filters['year'] ?? date('Y');
        $plantId = $filters['plant_id'] ?? $filters['id_plant'] ?? '0';

        return DB::connection($this->connection)->select(
            'SELECT a.id_balance_head, a.id_material, a.id_sloc, a.status,
                    CAST(a.trace_no AS CHAR) AS trace_no, FORMAT(SUM(DISTINCT a.qty),3) AS qty, a.created_by, a.created_at,
                    CONCAT(c.code, " :: ", c.description) AS material, FORMAT(SUM(DISTINCT a.init_qty),3) AS init_qty,
                    d.description AS tf_number, a.entry_date, b.batch_sap,
                    GROUP_CONCAT(DISTINCT b.id_balance_tail SEPARATOR ",") AS id_balance_detail,
                    GROUP_CONCAT(DISTINCT CONCAT(e.code, " :: ", e.description, " / ", b.batch_sap, " / Qty : ", FORMAT(b.init_qty, 3), " MT") SEPARATOR " | ") AS supplier,
                    IF(b.out_qty = 0, "N/A", "") AS traced, f.material_document, f.po_so, f.id_trace_head,
                    g.qty_tank, h.qty_warehouse, i.qty_adjustment
               FROM t_balance_header a
               LEFT JOIN t_balance_detail b
                 ON a.id_balance_head = b.id_balance_head AND b.status = 1
               LEFT JOIN m_material c
                 ON a.id_material = c.id_material
               LEFT JOIN m_sloc d
                 ON (d.id_sloc = a.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(d.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(d.id_sloc AS CHAR)))))) AND d.status = 1 AND (d.id_plant = ? OR ? = 0)
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
                             FROM m_sloc a
                             LEFT JOIN (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material,
                                               SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                          FROM t_balance_header b
                                          LEFT JOIN t_balance_detail bb
                                            ON b.id_balance_head = bb.id_balance_head
                                         WHERE b.status = 1
                                           AND bb.status = 1
                                         GROUP BY b.id_sloc, bb.id_balance_head, bb.id_material, bb.batch_sap
                                       ) b
                               ON (a.id_sloc = b.id_sloc OR (JSON_VALID(b.id_sloc) AND (JSON_CONTAINS(b.id_sloc, CAST(a.id_sloc AS CHAR)) OR JSON_CONTAINS(b.id_sloc, JSON_QUOTE(CAST(a.id_sloc AS CHAR))))))
                            WHERE a.status = 1 AND a.code_3 IN ("WIP", "PRD", "STORAGE")
                              AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                            GROUP BY b.batch_sap
                    ) g
                 ON g.batch_sap = b.batch_sap
               LEFT JOIN ( SELECT b.batch_sap AS batch_sap, FORMAT(ROUND(SUM(b.balance),3),3) AS qty_warehouse
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
               LEFT JOIN ( SELECT b.batch_sap AS batch_sap, FORMAT(ROUND(SUM(b.balance),3),3) AS qty_adjustment
                             FROM m_sloc a
                             LEFT JOIN (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material,
                                               SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                          FROM t_balance_header b
                                          LEFT JOIN t_balance_detail bb
                                            ON b.id_balance_head = bb.id_balance_head
                                         WHERE b.status = 1
                                           AND bb.status = 1
                                         GROUP BY b.id_sloc, bb.id_balance_head, bb.id_material, bb.batch_sap
                                       ) b
                               ON (a.id_sloc = b.id_sloc OR (JSON_VALID(b.id_sloc) AND (JSON_CONTAINS(b.id_sloc, CAST(a.id_sloc AS CHAR)) OR JSON_CONTAINS(b.id_sloc, JSON_QUOTE(CAST(a.id_sloc AS CHAR))))))
                            WHERE a.status = 1 AND a.plant_name = "ADJUSTMENT OUT"
                              AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                            GROUP BY b.batch_sap
                    ) i
                 ON i.batch_sap = b.batch_sap
              WHERE c.type = "RM"
                AND (SUBSTRING(a.trace_no,1,1) = "1" OR SUBSTRING(a.trace_no,1,1) = "9")
                AND SUBSTRING(a.trace_no,8,2) = "00"
                AND a.status = 1
                AND d.code_3 = "STORAGE"
                AND (a.id_plant = ? OR ? = 0)
                AND YEAR(a.entry_date) = ?
              GROUP BY a.trace_no
              ORDER BY a.id_balance_head DESC',
            [$plantId, $plantId, $plantId, $plantId, $selectedYear]
        );
    }

    public function getRmDetailRmPrdOnTank(string $batchSap): array
    {
        return DB::connection($this->connection)->select(
            'SELECT "" AS sloc, "BALANCE ON WIP" AS material,
                    "" AS out_qty, "" AS in_qty,
                    FORMAT(ROUND(SUM(a.balance),3),3) AS balance
               FROM (
                     SELECT a.description AS sloc, CONCAT("(", c.code, ") ", c.description) AS material,
                           SUM(b.in_qty) AS in_qty, SUM(b.out_qty) AS out_qty,
                           SUM(b.balance) AS balance
                       FROM m_sloc a
                       LEFT JOIN (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material,
                                       SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                   FROM t_balance_header b
                                   LEFT JOIN t_balance_detail bb
                                   ON b.id_balance_head = bb.id_balance_head
                                   WHERE b.status = 1
                                   AND bb.status = 1
                                   AND bb.batch_sap = ?
                                   GROUP BY b.id_sloc, bb.id_balance_head, bb.id_material, bb.batch_sap
                            ) b
                         ON (a.id_sloc = b.id_sloc OR (JSON_VALID(b.id_sloc) AND (JSON_CONTAINS(b.id_sloc, CAST(a.id_sloc AS CHAR)) OR JSON_CONTAINS(b.id_sloc, JSON_QUOTE(CAST(a.id_sloc AS CHAR))))))
                       LEFT JOIN m_material c
                         ON c.id_material = b.id_material
                      WHERE a.status = 1 AND a.code_3 IN ("WIP", "PRD", "STORAGE")
                        AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                      GROUP BY a.id_sloc, b.id_material
                   ) a
              UNION ALL
             SELECT a.sloc, a.material, a.out_qty, a.in_qty, a.balance
               FROM (
                     SELECT a.description AS sloc, CONCAT("(", c.code, ") ", c.description) AS material,
                           FORMAT(ROUND(SUM(b.in_qty),3),3) AS in_qty, FORMAT(ROUND(SUM(b.out_qty),3),3) AS out_qty,
                           FORMAT(ROUND(SUM(b.balance),3),3) AS balance
                       FROM m_sloc a
                       LEFT JOIN (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material,
                                       SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                   FROM t_balance_header b
                                   LEFT JOIN t_balance_detail bb
                                   ON b.id_balance_head = bb.id_balance_head
                                   WHERE b.status = 1
                                   AND bb.status = 1
                                   AND bb.batch_sap = ?
                                   GROUP BY b.id_sloc, bb.id_balance_head, bb.id_material, bb.batch_sap
                            ) b
                         ON (a.id_sloc = b.id_sloc OR (JSON_VALID(b.id_sloc) AND (JSON_CONTAINS(b.id_sloc, CAST(a.id_sloc AS CHAR)) OR JSON_CONTAINS(b.id_sloc, JSON_QUOTE(CAST(a.id_sloc AS CHAR))))))
                       LEFT JOIN m_material c
                         ON c.id_material = b.id_material
                      WHERE a.status = 1 AND a.code_3 IN ("WIP", "PRD", "STORAGE")
                        AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                      GROUP BY a.id_sloc, b.id_material
                   ) a',
            [$batchSap, $batchSap]
        );
    }

    public function getRmDetailRmPrdOnAdjOut(string $batchSap): array
    {
        return DB::connection($this->connection)->select(
            'SELECT "" AS sloc, "BALANCE ON WIP" AS material,
                    "" AS out_qty, "" AS in_qty,
                    FORMAT(ROUND(SUM(a.balance),3),3) AS balance
               FROM (
                     SELECT a.description AS sloc, CONCAT("(", c.code, ") ", c.description) AS material,
                           SUM(b.in_qty) AS in_qty, SUM(b.out_qty) AS out_qty,
                           SUM(b.balance) AS balance
                       FROM m_sloc a
                       LEFT JOIN (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material,
                                       SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                   FROM t_balance_header b
                                   LEFT JOIN t_balance_detail bb
                                   ON b.id_balance_head = bb.id_balance_head
                                   WHERE b.status = 1
                                   AND bb.status = 1
                                   AND bb.batch_sap = ?
                                   GROUP BY b.id_sloc, bb.id_balance_head, bb.id_material, bb.batch_sap
                            ) b
                         ON (a.id_sloc = b.id_sloc OR (JSON_VALID(b.id_sloc) AND (JSON_CONTAINS(b.id_sloc, CAST(a.id_sloc AS CHAR)) OR JSON_CONTAINS(b.id_sloc, JSON_QUOTE(CAST(a.id_sloc AS CHAR))))))
                       LEFT JOIN m_material c
                         ON c.id_material = b.id_material
                      WHERE a.status = 1 AND a.plant_name = "ADJUSTMENT OUT"
                        AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                      GROUP BY a.id_sloc, b.id_material
                   ) a
              UNION ALL
             SELECT a.sloc, a.material, a.out_qty, a.in_qty, a.balance
               FROM (
                     SELECT a.description AS sloc, CONCAT("(", c.code, ") ", c.description) AS material,
                           FORMAT(ROUND(SUM(b.in_qty),3),3) AS in_qty, FORMAT(ROUND(SUM(b.out_qty),3),3) AS out_qty,
                           FORMAT(ROUND(SUM(b.balance),3),3) AS balance
                       FROM m_sloc a
                       LEFT JOIN (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material,
                                       SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                   FROM t_balance_header b
                                   LEFT JOIN t_balance_detail bb
                                   ON b.id_balance_head = bb.id_balance_head
                                   WHERE b.status = 1
                                   AND bb.status = 1
                                   AND bb.batch_sap = ?
                                   GROUP BY b.id_sloc, bb.id_balance_head, bb.id_material, bb.batch_sap
                            ) b
                         ON (a.id_sloc = b.id_sloc OR (JSON_VALID(b.id_sloc) AND (JSON_CONTAINS(b.id_sloc, CAST(a.id_sloc AS CHAR)) OR JSON_CONTAINS(b.id_sloc, JSON_QUOTE(CAST(a.id_sloc AS CHAR))))))
                       LEFT JOIN m_material c
                         ON c.id_material = b.id_material
                      WHERE a.status = 1 AND a.plant_name = "ADJUSTMENT OUT"
                        AND (b.in_qty > "0.001" OR b.out_qty > "0.001")
                      GROUP BY a.id_sloc, b.id_material
                   ) a',
            [$batchSap, $batchSap]
        );
    }

    public function getRmDetailRmPrdOnWarehouse(string $batchSap): array
    {
        return DB::connection($this->connection)->select(
            'SELECT "" AS sloc, "TOTAL" AS material, FORMAT(ROUND(SUM(a.out_qty),3),3) AS out_qty, FORMAT(ROUND(SUM(a.in_qty),3),3) AS in_qty,
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
              ) a',
            [$batchSap, $batchSap, $batchSap, $batchSap]
        );
    }
}
