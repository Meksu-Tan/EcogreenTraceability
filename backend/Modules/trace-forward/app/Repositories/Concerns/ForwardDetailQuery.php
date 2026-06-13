<?php declare(strict_types=1);

namespace Modules\TraceForward\Repositories\Concerns;

use Illuminate\Database\Connection;

final class ForwardDetailQuery
{
    public function __construct(private Connection $connection) {}

    public function execute(string $traceNo, int $idMaterial): array
    {
        $cte = '
            WITH RECURSIVE ForwardBOM AS (
                SELECT
                    b.to_trace_no AS parent_trace_no,
                    b.id_trace_head,
                    b.from_trace_no,
                    b.to_trace_no AS trace_no,
                    b.id_material,
                    b.in_qty,
                    b.out_qty,
                    b.entry_date,
                    c.material_document,
                    b.to_trace_no AS child_trace_no,
                    b.id_sloc,
                    1 AS level,
                    CAST("1" AS CHAR(255)) AS path
                FROM t_trace_header b
                LEFT JOIN t_material_document c ON b.id_trace_head = c.id_trace_head
                WHERE b.to_trace_no = ? AND b.id_material = ? AND b.status = 1

                UNION ALL

                SELECT
                    ForwardBOM.parent_trace_no,
                    t.id_trace_head,
                    t.from_trace_no,
                    t.to_trace_no AS trace_no,
                    t.id_material,
                    t.in_qty,
                    t.out_qty,
                    t.entry_date,
                    tt.material_document,
                    t.to_trace_no AS child_trace_no,
                    t.id_sloc,
                    ForwardBOM.level + 1,
                    CONCAT(ForwardBOM.path, ".",
                        LPAD((SELECT COUNT(*)
                                FROM t_trace_header t2
                                WHERE t2.from_trace_no = t.from_trace_no
                                AND t2.to_trace_no <= t.to_trace_no
                                AND t2.`status` = "1"), 2, "0")) AS path
                FROM ForwardBOM
                JOIN t_trace_header t ON ForwardBOM.child_trace_no = t.from_trace_no AND t.status = 1
                LEFT JOIN t_material_document tt ON tt.id_trace_head = t.id_trace_head
            )';

        $select = $cte . '
            SELECT
                c.from_trace_no AS prev_trace,
                c.trace_no AS curr_trace,
                c.entry_date AS batch_date,
                IF(SUBSTRING(c.from_trace_no,1,1) = 4, UPPER(g.`description`), IF(SUBSTRING(c.from_trace_no,1,1) = 5, UPPER(g.`description`), UPPER(d.`description`) ) ) AS material,
                FORMAT(c.in_qty,3) AS in_qty,
                IF(SUBSTRING(c.from_trace_no,1,1) = 4, UPPER(i.`description`), IF(SUBSTRING(c.from_trace_no,1,1) = 5, UPPER(i.`description`), IF(c.id_sloc < 7, CONCAT("EOB1", " ", h.description), h.description) ) ) AS sloc,
                FORMAT(c.out_qty,3) AS out_qty,
                IF(e.in_qty <> 0,
                    GROUP_CONCAT(DISTINCT CONCAT(f.`description`, " / ", e.batch_sap, " / ", e.in_qty, " MT") SEPARATOR " || "),
                    GROUP_CONCAT(DISTINCT CONCAT(f.`description`, " / ", e.batch_sap, " / ", e.out_qty, " MT") SEPARATOR " || ")) AS supplier,
                c.material_document,
                c.`level`,
                c.`path`,
                e.created_at,
                e.created_by
            FROM ForwardBOM c
            LEFT JOIN m_material d ON c.id_material = d.id_material
            LEFT JOIN (SELECT e.id_trace_head, e.batch_sap, FORMAT(SUM(e.in_qty),3) AS in_qty, FORMAT(SUM(e.out_qty),3) AS out_qty,
                              e.status, e.created_at, e.created_by, e.id_supplier, e.id_material
                         FROM t_trace_detail e
                        WHERE e.status = 1
                        GROUP BY e.id_trace_head, e.batch_sap
                        ) e ON c.id_trace_head = e.id_trace_head
            LEFT JOIN m_supplier f ON f.id_supplier = e.id_supplier
            LEFT JOIN m_material_pck g ON c.id_material = g.id_materialpck
            LEFT JOIN m_sloc h ON c.id_sloc = h.id_sloc
            LEFT JOIN m_warehouse i ON c.id_sloc = i.id_warehouse
            WHERE e.status <> 0
            GROUP BY trace_no, `path`
            ORDER BY `path`
        ';

        return $this->connection->select($select, [$traceNo, $idMaterial]);
    }
}
