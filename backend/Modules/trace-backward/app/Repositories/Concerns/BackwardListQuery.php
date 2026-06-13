<?php declare(strict_types=1);

namespace Modules\TraceBackward\Repositories\Concerns;

use Illuminate\Database\Connection;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;

final class BackwardListQuery
{
    use PlantFilterTrait;

    public function __construct(private Connection $connection) {}

    public function execute(array $filters = []): array
    {
        $plantId = $filters['id_plant'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('sh', $plantId, $userId);

        $bindings = $plantFilter['bindings'];
        $plantSql = $plantFilter['sql'];

        $sql = "
            SELECT sh.id_ship_head AS id_shipment_head, sh.entry_date, CAST(sh.trace_no AS CHAR) AS trace_no,
                   sh.so_no, NULL AS batch_no,
                   NULL AS sloc,
                   CONCAT(m.code, ' :: ', m.description) AS material,
                   FORMAT(sh.qty,3) AS qty,
                   GROUP_CONCAT(DISTINCT CONCAT(s.code, ' :: ', s.description,
                       ' / ', td.batch_sap, ' / Qty: ', FORMAT(td.out_qty,3), ' MT') SEPARATOR ' | ') AS supplier,
                   CAST(th.from_trace_no AS CHAR) AS source_trace,
                   td2.po_so,
                   src.po_so AS source,
                   sh.created_at, sh.created_by,
                   sh.id_material_fg AS id_material
              FROM t_shipment_header sh
              LEFT JOIN m_material m ON sh.id_material_fg = m.id_material
              LEFT JOIN t_trace_header th ON sh.trace_no = th.to_trace_no AND th.status = 1
              LEFT JOIN t_trace_detail td ON th.id_trace_head = td.id_trace_head AND td.status = 1
              LEFT JOIN m_supplier s ON td.id_supplier = s.id_supplier
              LEFT JOIN t_material_document td2 ON th.id_trace_head = td2.id_trace_head AND td2.status = 1
              LEFT JOIN (SELECT batch_sap, GROUP_CONCAT(DISTINCT po_so SEPARATOR ' | ') AS po_so
                           FROM ( SELECT hh.batch_sap, CONCAT(hh.batch_sap, ' :: ', h.trace_no, ' / ', hhhh.po_so) AS po_so
                                    FROM t_balance_header h
                                    LEFT JOIN t_balance_detail hh ON h.id_balance_head = hh.id_balance_head
                                    LEFT JOIN t_trace_header hhh ON h.id_balance_head = hhh.id_balance_head AND hhh.status = 1
                                    LEFT JOIN t_material_document hhhh ON hhh.id_trace_head = hhhh.id_trace_head
                                   WHERE h.status = 1 AND h.in_qty <> 0
                                     AND SUBSTRING(h.trace_no,1,1) = '1'
                                     AND hhhh.po_so IS NOT NULL
                                   UNION ALL
                                  SELECT hh.batch_sap, IFNULL(CONCAT(hh.batch_sap, ' :: ', h.trace_no, ' / ', hhhh.po_so), CONCAT(hh.batch_sap, ' :: ', h.trace_no)) AS po_so
                                    FROM t_balance_header h
                                    LEFT JOIN t_balance_detail hh ON h.id_balance_head = hh.id_balance_head
                                    LEFT JOIN t_trace_header hhh ON h.id_balance_head = hhh.id_balance_head AND hhh.status = 1
                                    LEFT JOIN t_material_document hhhh ON hhh.id_trace_head = hhhh.id_trace_head
                                   WHERE h.status = 1 AND h.in_qty <> 0
                                     AND SUBSTRING(h.trace_no,1,1) = '9'
                               ) src_inner
                          GROUP BY batch_sap
              ) src ON src.batch_sap = td.batch_sap
             WHERE sh.status = 1
               AND ({$plantSql})
             GROUP BY sh.id_ship_head
             ORDER BY sh.entry_date DESC, sh.id_ship_head DESC
        ";

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;

        $countResult = $this->connection->select(
            "SELECT COUNT(*) AS total FROM ({$sql}) AS cnt", $bindings
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
