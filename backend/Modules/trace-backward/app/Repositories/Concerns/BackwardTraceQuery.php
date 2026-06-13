<?php declare(strict_types=1);

namespace Modules\TraceBackward\Repositories\Concerns;

use Illuminate\Database\Connection;

final class BackwardTraceQuery
{
    public function __construct(private Connection $connection) {}

    public function execute(string $traceNo, ?int $idMaterial = null): array
    {
        $materialFilter = $idMaterial ? ' AND b.id_material = ?' : '';
        $materialBinding = $idMaterial ? [$traceNo, $idMaterial] : [$traceNo];

        $cte = '
            WITH RECURSIVE BackwardBOM AS (
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
                    1 AS level,
                    CAST("1" AS CHAR(255)) AS path
                FROM t_trace_header b
                LEFT JOIN t_material_document c ON b.id_trace_head = c.id_trace_head
                WHERE b.to_trace_no = ? AND b.status = 1' . $materialFilter . '
                UNION ALL
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
                FROM BackwardBOM
                JOIN t_trace_header t ON BackwardBOM.child_trace_no = t.to_trace_no AND t.status = 1
                LEFT JOIN t_material_document tt ON tt.id_trace_head = t.id_trace_head
            )';

        $select = $cte . '
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
                a.material_document AS mat_doc,
                a.sloc AS to_sloc
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
                IF(SUBSTRING(c.from_trace_no,1,1) = 4, UPPER(i.`description`), IF(SUBSTRING(c.from_trace_no,1,1) = 5, UPPER(i.`description`), IF(c.id_sloc < 7, CONCAT("EOB1", " ", h.description), h.description) ) ) AS sloc
            FROM BackwardBOM c
            LEFT JOIN m_material d ON c.id_material = d.id_material
            LEFT JOIN ( SELECT
                            e.id_trace_head, e.batch_sap, FORMAT(SUM(e.in_qty),3) AS in_qty, FORMAT(SUM(e.out_qty),3) AS out_qty,
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
            GROUP BY `path`, `level`, in_qty, out_qty
            ORDER BY `path`) a
            GROUP BY `path`
        ';

        return $this->connection->select($select, $materialBinding);
    }
}
