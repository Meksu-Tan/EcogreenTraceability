<?php
declare(strict_types=1);
namespace Modules\TraceBackward\Repositories\Concerns;

use Illuminate\Database\Connection;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;
use Modules\Shared\Traits\DbCompatTrait;

final class BackwardSearchQuery
{
    use PlantFilterTrait, DbCompatTrait;

    public function __construct(private Connection $connection) {}

    public function execute(mixed $materialId, ?string $batchNo = null, ?int $plantId = null, ?int $userId = null): array
    {
        $plantFilter = $this->buildTablePlantFilter('sh', $plantId, $userId);

        $bindings = array_merge([$materialId], $plantFilter['bindings']);
        $batchWhere = '';
        if ($batchNo) {
            $batchWhere = ' AND td.batch_sap LIKE ?';
            $bindings[] = "{$batchNo}%";
        }

        $sql = "
            SELECT sh.id_ship_head AS id_shipment_head, sh.entry_date, CAST(sh.trace_no AS TEXT) AS trace_no,
                   sh.so_no, NULL AS batch_no,
                   NULL AS sloc,
                   CASE WHEN SUBSTRING(CAST(sh.from_trace_no AS VARCHAR), 1, 1) < '3' OR sh.from_trace_no IS NULL
                        THEN CONCAT(m.code, ' :: ', m.description)
                        ELSE CONCAT(mp.code, ' :: ', mp.description)
                   END AS material,
                   {$this->dbNumberFormat('sh.qty', 3)} AS qty,
                   {$this->dbGroupConcat("CONCAT(s.code, ' :: ', s.description, ' / ', td.batch_sap, ' / Qty: ', {$this->dbNumberFormat('td.out_qty', 3)}, ' MT')", ' | ', true)} AS supplier,
                   CAST(th.from_trace_no AS TEXT) AS source_trace,
                   sh.created_at, sh.created_by
              FROM t_shipment_header sh
              LEFT JOIN m_material m ON sh.id_material_fg = m.id_material
              LEFT JOIN m_material_pck mp ON sh.id_material_fg = mp.id_materialpck
              LEFT JOIN t_trace_header th ON sh.trace_no = th.to_trace_no AND th.status = 1
              LEFT JOIN t_trace_detail td ON th.id_trace_head = td.id_trace_head AND td.status = 1
              LEFT JOIN m_supplier s ON td.id_supplier = s.id_supplier
             WHERE sh.id_material_fg = ?
               AND sh.status = 1
               {$batchWhere}
               AND (" . $plantFilter['sql'] . ")
             GROUP BY sh.id_ship_head, sh.entry_date, sh.trace_no, sh.so_no,
                     m.code, m.description, mp.code, mp.description, sh.qty, th.from_trace_no,
                     sh.created_at, sh.created_by, sh.id_material_fg, sh.from_trace_no
             ORDER BY sh.entry_date DESC
             LIMIT 100
        ";

        return $this->connection->select($sql, $bindings);
    }
}
