<?php
declare(strict_types=1);
namespace Modules\TraceForward\Repositories\Concerns;

use Illuminate\Database\Connection;
use Modules\Shared\Traits\DbCompatTrait;

final class ForwardDetailQuery
{
    use DbCompatTrait;

    public function __construct(private Connection $connection) {}

    public function execute(string $traceNo, int $idMaterial, ?int $plantId = null, ?int $userId = null): array
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
                    CAST(\'1\' AS TEXT) AS path,
                    CONCAT(b.from_trace_no, \'>\', b.to_trace_no) AS trace_chain
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
                    CONCAT(ForwardBOM.path, \'.\',
                        LPAD(CAST((SELECT COUNT(*)
                                FROM t_trace_header t2
                                WHERE t2.from_trace_no = t.from_trace_no
                                AND t2.to_trace_no <= t.to_trace_no
                                AND t2.status = \'1\') AS TEXT), 2, \'0\')) AS path,
                    CONCAT(ForwardBOM.trace_chain, \'>\', t.to_trace_no) AS trace_chain
                FROM ForwardBOM
                JOIN t_trace_header t ON ForwardBOM.child_trace_no = t.from_trace_no AND t.status = 1
                LEFT JOIN t_material_document tt ON tt.id_trace_head = t.id_trace_head
                WHERE ForwardBOM.level < 50
                  AND POSITION(CONCAT(\'>\', t.to_trace_no, \'>\') IN CONCAT(\'>\', ForwardBOM.trace_chain, \'>\')) = 0
            )';

        $inQtyFmt    = $this->dbNumberFormat('c.in_qty', 3);
        $outQtyFmt   = $this->dbNumberFormat('c.out_qty', 3);
        $tdInQtyFmt  = $this->dbNumberFormat('td.in_qty', 3);
        $tdOutQtyFmt = $this->dbNumberFormat('td.out_qty', 3);
        $supplierConcat    = "STRING_AGG(DISTINCT CONCAT(sup.description, ' / ', td.batch_sap, ' / ', {$tdInQtyFmt}, ' MT'), ' || ')";
        $supplierConcatOut = "STRING_AGG(DISTINCT CONCAT(sup.description, ' / ', td.batch_sap, ' / ', {$tdOutQtyFmt}, ' MT'), ' || ')";

        $select = $cte . "
            SELECT
                c.from_trace_no AS prev_trace,
                c.trace_no AS curr_trace,
                c.entry_date AS batch_date,
                CASE SUBSTRING(c.from_trace_no,1,1) WHEN '4' THEN UPPER(g.description) WHEN '5' THEN UPPER(g.description) ELSE UPPER(d.description) END AS material,
                {$inQtyFmt} AS in_qty,
                CASE WHEN SUBSTRING(c.from_trace_no,1,1) = '4' THEN UPPER(i.description)
                     WHEN SUBSTRING(c.from_trace_no,1,1) = '5' THEN UPPER(i.description)
                     ELSE CASE WHEN CAST(CASE WHEN jsonb_typeof(c.id_sloc) = 'array' THEN (c.id_sloc->>0)::text ELSE c.id_sloc::text END AS INTEGER) < 7 THEN CONCAT('EOB1 ', h.description) ELSE h.description END
                END AS sloc,
                {$outQtyFmt} AS out_qty,
                CASE WHEN COALESCE(e.sum_in, 0) <> 0
                    THEN e.supplier_in
                    ELSE e.supplier_out END AS supplier,
                c.material_document,
                c.level,
                c.path,
                COALESCE(e.detail_status, 0) AS status,
                e.created_at,
                e.created_by
            FROM ForwardBOM c
            LEFT JOIN m_material d ON c.id_material = d.id_material
            LEFT JOIN (SELECT td.id_trace_head,
                              SUM(td.in_qty) AS sum_in,
                              MAX(td.status) AS detail_status,
                              MAX(td.created_at) AS created_at,
                              MAX(td.created_by) AS created_by,
                              {$supplierConcat} AS supplier_in,
                              {$supplierConcatOut} AS supplier_out
                         FROM t_trace_detail td
                         LEFT JOIN m_supplier sup ON td.id_supplier = sup.id_supplier
                         WHERE td.status = 1
                         GROUP BY td.id_trace_head
                        ) e ON c.id_trace_head = e.id_trace_head
            LEFT JOIN m_material_pck g ON c.id_material = g.id_materialpck
            LEFT JOIN m_sloc h ON CAST(h.id_sloc AS TEXT) = CASE WHEN jsonb_typeof(c.id_sloc) = 'array' THEN (c.id_sloc->>0)::text ELSE c.id_sloc::text END
            LEFT JOIN m_warehouse i ON CAST(i.id_warehouse AS TEXT) = CASE WHEN jsonb_typeof(c.id_sloc) = 'array' THEN (c.id_sloc->>0)::text ELSE c.id_sloc::text END
            ORDER BY path
        ";

        return $this->connection->select($select, [$traceNo, $idMaterial]);
    }
}
