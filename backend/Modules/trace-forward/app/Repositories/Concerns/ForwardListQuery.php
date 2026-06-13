<?php declare(strict_types=1);

namespace Modules\TraceForward\Repositories\Concerns;

use Illuminate\Database\Connection;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;

final class ForwardListQuery
{
    use PlantFilterTrait;

    public function __construct(private Connection $connection) {}

    public function execute(array $filters = []): array
    {
        $plantId = $filters['id_plant'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('bh', $plantId, $userId);

        $where = ['bh.status = 1', $plantFilter['sql']];
        $bindings = $plantFilter['bindings'];
        $where[] = "(SUBSTRING(bh.trace_no,1,1)='1' OR SUBSTRING(bh.trace_no,1,1)='9')";

        $sql = "
            SELECT bh.id_balance_head, CAST(bh.trace_no AS CHAR) AS trace_no,
                   bh.entry_date,
                   CONCAT(m.code, ' :: ', m.description) AS material,
                   t.description AS tank, t.code_3 AS tank_type,
                   FORMAT(SUM(DISTINCT bh.init_qty),3) AS init_qty,
                   FORMAT(SUM(DISTINCT bh.qty),3) AS qty,
                   GROUP_CONCAT(DISTINCT CONCAT(s.code, ' :: ', s.description,
                       ' / ', bd.batch_sap, ' / Qty: ', FORMAT(bd.init_qty,3), ' MT') SEPARATOR ' | ') AS supplier,
                   MAX(bd.batch_sap) AS batch_sap,
                   GROUP_CONCAT(DISTINCT h.description ORDER BY h.description ASC SEPARATOR ', ') AS tf_number,
                   IF(SUM(bd.out_qty) = 0, 'N/A', 'TRACED') AS traced,
                   md.material_document,
                   md.po_so,
                   bh.created_at, bh.created_by,
                   bh.id_material
              FROM t_balance_header bh
              LEFT JOIN m_material m ON bh.id_material = m.id_material
              LEFT JOIN m_sloc t ON bh.id_sloc = t.id_sloc
              LEFT JOIN t_balance_detail bd ON bh.id_balance_head = bd.id_balance_head AND bd.status = 1
              LEFT JOIN m_supplier s ON bd.id_supplier = s.id_supplier
              LEFT JOIN m_sloc h ON bh.id_sloc = h.id_sloc
              LEFT JOIN (SELECT f.id_balance_head, g.material_document, g.po_so
                           FROM t_trace_header f
                           LEFT JOIN t_material_document g ON f.id_trace_head = g.id_trace_head
                          WHERE f.status = 1 GROUP BY f.id_balance_head) md
                ON md.id_balance_head = bh.id_balance_head
             WHERE " . implode(' AND ', $where) . "
             GROUP BY bh.id_balance_head
             ORDER BY bh.entry_date DESC, bh.id_balance_head DESC
        ";

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;

        $countResult = $this->connection->select(
            "SELECT COUNT(*) AS total FROM ({$sql}) AS cnt",
            $bindings
        );
        $total = (int) ($countResult[0]->total ?? 0);

        $paginatedSql = $sql . " LIMIT {$perPage} OFFSET {$offset}";
        $data = $this->connection->select($paginatedSql, $bindings);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }
}
