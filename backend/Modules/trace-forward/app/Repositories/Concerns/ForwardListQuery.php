<?php

declare(strict_types=1);

namespace Modules\TraceForward\Repositories\Concerns;

use Illuminate\Database\Connection;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;
use Modules\Shared\Traits\DbCompatTrait;

final class ForwardListQuery
{
    use DbCompatTrait;
    use PlantFilterTrait;

    public function __construct(private Connection $connection) {}

    public function execute(array $filters = []): array
    {
        $plantId = $filters['id_plant'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $search = $filters['search'] ?? null;
        $sortDir = strtoupper($filters['sort_dir'] ?? 'desc') === 'ASC' ? 'ASC' : 'DESC';
        $sortColumn = match ($filters['sort_by'] ?? 'entry_date') {
            'trace_no' => 'bh.trace_no',
            'material' => 'material',
            'batch_sap' => 'batch_sap',
            'supplier' => 'supplier',
            default => 'bh.entry_date',
        };

        $plantFilter = $this->buildTablePlantFilter('bh', $plantId, $userId);

        $where = ['bh.status = 1', $plantFilter['sql']];
        $bindings = $plantFilter['bindings'];
        $where[] = "(SUBSTRING(bh.trace_no,1,1)='1' OR SUBSTRING(bh.trace_no,1,1)='9')";
        $where[] = "SUBSTRING(bh.trace_no,8,3) = '000'";
        $where[] = "m.type = 'RM'";

        if ($search !== null && $search !== '') {
            $like = '%'.$search.'%';
            $where[] = '(CAST(bh.trace_no AS TEXT) ILIKE ? OR m.description ILIKE ?)';
            $bindings = array_merge($bindings, [$like, $like]);
        }

        $initQtyFmt = $this->dbNumberFormat('SUM(DISTINCT bh.init_qty)', 3);
        $qtyFmt = $this->dbNumberFormat('SUM(DISTINCT bh.qty)', 3);
        $bdInitQtyFmt = $this->dbNumberFormat('bd.init_qty', 3);
        $supplierConcat = $this->dbGroupConcat(
            "DISTINCT CONCAT(s.code, ' :: ', s.description, ' / ', bd.batch_sap, ' / Qty: ', {$bdInitQtyFmt}, ' MT')",
            ' | '
        );
        $mdSubquery = 'SELECT f.id_balance_head, MAX(g.material_document) AS material_document, MAX(g.po_so) AS po_so FROM t_trace_header f LEFT JOIN t_material_document g ON f.id_trace_head = g.id_trace_head WHERE f.status = 1 GROUP BY f.id_balance_head';

        $sql = "
            SELECT bh.id_balance_head, CAST(bh.trace_no AS TEXT) AS trace_no,
                   bh.entry_date,
                   CONCAT(m.code, ' :: ', m.description) AS material,
                   t.description AS tank, t.code_3 AS tank_type,
                   {$initQtyFmt} AS init_qty,
                   {$qtyFmt} AS qty,
                   {$supplierConcat} AS supplier,
                   MAX(bd.batch_sap) AS batch_sap,
                   CASE WHEN SUM(bd.out_qty) = 0 THEN 'N/A' ELSE 'TRACED' END AS traced,
                    md.material_document,
                    md.po_so,
                    bh.created_at, bh.created_by,
                    bh.id_material,
                    t.tf_number
              FROM t_balance_header bh
              LEFT JOIN m_material m ON bh.id_material = m.id_material
              LEFT JOIN m_sloc t ON {$this->dbSlocColumnClause('bh.id_sloc', 't.id_sloc')}
              LEFT JOIN t_balance_detail bd ON bh.id_balance_head = bd.id_balance_head AND bd.status = 1
              LEFT JOIN m_supplier s ON bd.id_supplier = s.id_supplier
              LEFT JOIN ({$mdSubquery}) md
                ON md.id_balance_head = bh.id_balance_head
             WHERE ".implode(' AND ', $where)."
              GROUP BY bh.id_balance_head, bh.trace_no, bh.entry_date,
                      m.code, m.description, t.description, t.code_3, t.tf_number,
                      md.material_document, md.po_so, bh.created_at, bh.created_by, bh.id_material
             ORDER BY {$sortColumn} {$sortDir}, bh.id_balance_head DESC
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
