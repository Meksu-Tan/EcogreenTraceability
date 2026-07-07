<?php

declare(strict_types=1);

namespace Modules\TraceBackward\Repositories\Concerns;

use Illuminate\Database\Connection;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;
use Modules\Shared\Traits\DbCompatTrait;

final class BackwardListQuery
{
    use DbCompatTrait, PlantFilterTrait;

    public function __construct(private Connection $connection) {}

    public function execute(array $filters = []): array
    {
        $plantId = $filters['id_plant'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $search = $filters['search'] ?? null;
        $sortDir = strtoupper($filters['sort_dir'] ?? 'desc') === 'ASC' ? 'ASC' : 'DESC';
        $sortColumn = match ($filters['sort_by'] ?? 'entry_date') {
            'trace_no' => 'sh.trace_no',
            'so_no' => 'sh.so_no',
            'material' => 'material',
            'batch_no' => 'batch_no',
            'supplier' => 'supplier',
            default => 'sh.entry_date',
        };

        $plantFilter = $this->buildTablePlantFilter('sh', $plantId, $userId);
        $bindings = $plantFilter['bindings'];
        $plantSql = $plantFilter['sql'];

        $searchSql = '';
        if ($search !== null && $search !== '') {
            $like = '%'.$search.'%';
            $searchSql = ' AND (CAST(sh.trace_no AS TEXT) ILIKE ? OR sh.so_no ILIKE ?
                            OR m.description ILIKE ? OR mp.description ILIKE ?)';
            $bindings = array_merge($bindings, [$like, $like, $like, $like]);
        }

        $sql = "
            SELECT sh.id_ship_head AS id_shipment_head, sh.entry_date, CAST(sh.trace_no AS TEXT) AS trace_no,
                   sh.so_no, MAX(wh.batch_no) AS batch_no,
                   MAX(mw.description) AS sloc,
                   CASE WHEN SUBSTRING(CAST(sh.from_trace_no AS VARCHAR), 1, 1) < '3' OR sh.from_trace_no IS NULL
                        THEN CONCAT(m.code, ' :: ', m.description)
                        ELSE CONCAT(mp.code, ' :: ', mp.description)
                   END AS material,
                   {$this->dbNumberFormat('sh.qty', 3)} AS qty,
                   {$this->dbGroupConcat("CONCAT(s.code, ' :: ', s.description, ' / ', td.batch_sap, ' / Qty: ', {$this->dbNumberFormat('td.out_qty', 3)}, ' MT')", ' | ', true)} AS supplier,
                   CAST(th.from_trace_no AS TEXT) AS source_trace,
                   MAX(td2.po_so) AS po_so,
                   MAX(src.po_so) AS source,
                   sh.created_at, sh.created_by,
                   sh.id_material_fg AS id_material
              FROM t_shipment_header sh
              LEFT JOIN m_material m ON sh.id_material_fg = m.id_material
              LEFT JOIN m_material_pck mp ON sh.id_material_fg = mp.id_materialpck
              LEFT JOIN t_trace_header th ON sh.trace_no = th.to_trace_no AND th.status = 1
              LEFT JOIN t_warehouse_header wh ON th.id_balance_head = wh.id_whx_head AND wh.status = 1
              LEFT JOIN m_warehouse mw ON wh.id_section = mw.id_warehouse
              LEFT JOIN t_trace_detail td ON th.id_trace_head = td.id_trace_head AND td.status = 1
              LEFT JOIN m_supplier s ON td.id_supplier = s.id_supplier
              LEFT JOIN t_material_document td2 ON th.id_trace_head = td2.id_trace_head AND td2.status = 1
              LEFT JOIN (SELECT batch_sap, {$this->dbGroupConcat('po_so', ' | ', true)} AS po_so
                           FROM ( SELECT hh.batch_sap, CONCAT(hh.batch_sap, ' :: ', h.trace_no, ' / ', hhhh.po_so) AS po_so
                                    FROM t_balance_header h
                                    LEFT JOIN t_balance_detail hh ON h.id_balance_head = hh.id_balance_head
                                    LEFT JOIN t_trace_header hhh ON h.id_balance_head = hhh.id_balance_head AND hhh.status = 1
                                    LEFT JOIN t_material_document hhhh ON hhh.id_trace_head = hhhh.id_trace_head
                                   WHERE h.status = 1 AND h.in_qty <> 0
                                     AND SUBSTRING(CAST(h.trace_no AS TEXT),1,1) = '1'
                                     AND hhhh.po_so IS NOT NULL
                                   UNION ALL
                                  SELECT hh.batch_sap, COALESCE(CONCAT(hh.batch_sap, ' :: ', h.trace_no, ' / ', hhhh.po_so), CONCAT(hh.batch_sap, ' :: ', h.trace_no)) AS po_so
                                    FROM t_balance_header h
                                    LEFT JOIN t_balance_detail hh ON h.id_balance_head = hh.id_balance_head
                                    LEFT JOIN t_trace_header hhh ON h.id_balance_head = hhh.id_balance_head AND hhh.status = 1
                                    LEFT JOIN t_material_document hhhh ON hhh.id_trace_head = hhhh.id_trace_head
                                   WHERE h.status = 1 AND h.in_qty <> 0
                                     AND SUBSTRING(CAST(h.trace_no AS TEXT),1,1) = '9'
                               ) src_inner
                          GROUP BY batch_sap
              ) src ON src.batch_sap = td.batch_sap
             WHERE sh.status = 1
               AND ({$plantSql}){$searchSql}
             GROUP BY sh.id_ship_head, sh.entry_date, sh.trace_no, sh.so_no,
                     m.code, m.description, mp.code, mp.description, sh.qty, th.from_trace_no,
                     sh.created_at, sh.created_by, sh.id_material_fg, sh.from_trace_no
             ORDER BY {$sortColumn} {$sortDir}, sh.id_ship_head DESC
        ";

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;

        $countResult = $this->connection->select(
            "SELECT COUNT(*) AS total FROM ({$sql}) AS cnt", $bindings
        );
        $total = (int) ($countResult[0]->total ?? 0);

        $data = $this->connection->select($sql." LIMIT {$perPage} OFFSET {$offset}", $bindings);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }
}
