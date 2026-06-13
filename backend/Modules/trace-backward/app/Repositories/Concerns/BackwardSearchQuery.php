<?php declare(strict_types=1);

namespace Modules\TraceBackward\Repositories\Concerns;

use Illuminate\Database\Connection;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;

final class BackwardSearchQuery
{
    use PlantFilterTrait;

    public function __construct(private Connection $connection) {}

    public function execute(mixed $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array
    {
        $plantFilter = $this->buildTablePlantFilter('sh', $plantId, $userId);

        $bindings = array_merge([$materialId], $plantFilter['bindings']);
        $batchWhere = '';
        if ($batchNo) {
            $batchWhere = ' AND td.batch_sap LIKE ?';
            $bindings[] = "%{$batchNo}%";
        }

        $sql = "
            SELECT sh.id_ship_head AS id_shipment_head, sh.entry_date, CAST(sh.trace_no AS CHAR) AS trace_no,
                   sh.so_no, NULL AS batch_no,
                   NULL AS sloc,
                   CONCAT(m.code, ' :: ', m.description) AS material,
                   FORMAT(sh.qty,3) AS qty,
                   GROUP_CONCAT(DISTINCT CONCAT(s.code, ' :: ', s.description,
                       ' / ', td.batch_sap, ' / Qty: ', FORMAT(td.out_qty,3), ' MT') SEPARATOR ' | ') AS supplier,
                   CAST(th.from_trace_no AS CHAR) AS source_trace,
                   sh.created_at, sh.created_by
              FROM t_shipment_header sh
              LEFT JOIN m_material m ON sh.id_material_fg = m.id_material
              LEFT JOIN t_trace_header th ON sh.trace_no = th.to_trace_no AND th.status = 1
              LEFT JOIN t_trace_detail td ON th.id_trace_head = td.id_trace_head AND td.status = 1
              LEFT JOIN m_supplier s ON td.id_supplier = s.id_supplier
             WHERE sh.id_material_fg = ?
               AND sh.status = 1
               {$batchWhere}
               AND (" . $plantFilter['sql'] . ")
             GROUP BY sh.id_ship_head
             ORDER BY sh.entry_date DESC
             LIMIT 100
        ";

        return $this->connection->select($sql, $bindings);
    }
}
