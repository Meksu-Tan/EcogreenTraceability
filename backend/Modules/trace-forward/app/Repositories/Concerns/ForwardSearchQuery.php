<?php declare(strict_types=1);

namespace Modules\TraceForward\Repositories\Concerns;

use Illuminate\Database\Connection;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;

final class ForwardSearchQuery
{
    use PlantFilterTrait;

    public function __construct(private Connection $connection) {}

    public function execute(mixed $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array
    {
        $this->connection->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        $plantFilter = $this->buildTablePlantFilter('th', $plantId, $userId);

        $bindings = array_merge([$materialId], $plantFilter['bindings']);
        $batchWhere = '';
        if ($batchNo) {
            $batchWhere = ' AND td.batch_sap LIKE ?';
            $bindings[] = "%{$batchNo}%";
        }

        $sql = "
            SELECT th.id_trace_head, th.from_trace_no, th.to_trace_no,
                   th.entry_date, th.in_qty, th.out_qty, th.id_material,
                   CONCAT(m.code, ' :: ', m.description) AS material,
                   p.description AS plant_name,
                   GROUP_CONCAT(DISTINCT CONCAT(s.description, ' / ', td.batch_sap, ' / ', FORMAT(td.out_qty,3), ' MT') SEPARATOR ' | ') AS supplier_trace,
                   th.id_plant
              FROM t_trace_header th
              LEFT JOIN m_material m ON th.id_material = m.id_material
              LEFT JOIN m_plant p ON th.id_plant = p.code_3
              LEFT JOIN t_trace_detail td ON th.id_trace_head = td.id_trace_head AND td.status = 1
              LEFT JOIN m_supplier s ON td.id_supplier = s.id_supplier
             WHERE th.id_material = ?
               AND th.status = 1
               {$batchWhere}
               AND (" . $plantFilter['sql'] . ")
             GROUP BY th.id_trace_head
             ORDER BY th.entry_date DESC
             LIMIT 100
        ";

        return $this->connection->select($sql, $bindings);
    }
}
