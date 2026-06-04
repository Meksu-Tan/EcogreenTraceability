<?php declare(strict_types=1);

namespace Modules\Dashboard\Repositories;

use Modules\Dashboard\Repositories\Contracts\DashboardRepositoryInterface;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;
use Illuminate\Support\Facades\DB;

class TraceRepository
{
    use PlantFilterTrait;

    protected string $connection = 'eudr_ts';

    /**
     * Forward Trace - Get traces forward from a trace number
     * Returns all traces that consume from this trace
     */
    public function forwardTrace(string $traceNo, mixed $plantId = null, ?int $userId = null): array
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        $plantFilter = $this->buildTablePlantFilter('th', $plantId, $userId);

        $sql = "
            SELECT th.id_trace_head, th.from_trace_no, th.to_trace_no,
                   th.entry_date, th.in_qty, th.out_qty, th.id_material,
                   CONCAT(m.code, ' :: ', m.description) AS material,
                   sl.description AS from_sloc, sl2.description AS to_sloc,
                   md.material_document AS mat_doc,
                   GROUP_CONCAT(DISTINCT CONCAT(s.description, ' / ', td.batch_sap, ' / ', FORMAT(td.out_qty,3), ' MT') SEPARATOR ' | ') AS supplier_trace,
                   th.id_plant
              FROM t_trace_header th
              LEFT JOIN m_material m ON th.id_material = m.id_material
              LEFT JOIN m_sloc sl ON th.id_sloc = sl.id_sloc
              LEFT JOIN m_tank t ON th.id_tank = t.id_tank
              LEFT JOIN m_sloc sl2 ON t.id_sloc = sl2.id_sloc
              LEFT JOIN t_material_document md ON th.id_trace_head = md.id_trace_head AND md.status = 1
              LEFT JOIN t_trace_detail td ON th.id_trace_head = td.id_trace_head AND td.status = 1
              LEFT JOIN m_supplier s ON td.id_supplier = s.id_supplier
             WHERE th.from_trace_no = ?
               AND th.status = 1
               AND (" . $plantFilter['sql'] . ")
            GROUP BY th.id_trace_head
            ORDER BY th.entry_date ASC
        ";

        return DB::connection($this->connection)->select($sql, array_merge([$traceNo], $plantFilter['bindings']));
    }

    /**
     * Backward Trace - Get traces backward to source/suppliers
     * Returns the trace that created this trace number
     */
    public function backwardTrace(string $traceNo, mixed $plantId = null, ?int $userId = null): array
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        $plantFilter = $this->buildTablePlantFilter('th', $plantId, $userId);

        $sql = "
            SELECT th.id_trace_head, th.from_trace_no, th.to_trace_no,
                   th.entry_date, th.in_qty, th.out_qty, th.id_material,
                   CONCAT(m.code, ' :: ', m.description) AS material,
                   bh.trace_no AS source_trace, bh.qty AS source_qty,
                   sl.description AS from_sloc, sl2.description AS to_sloc,
                   GROUP_CONCAT(DISTINCT CONCAT(s.description, ' / ', bd.batch_sap, ' / ', FORMAT(bd.qty,3), ' MT') SEPARATOR ' | ') AS supplier_details,
                   th.id_plant
              FROM t_trace_header th
              LEFT JOIN m_material m ON th.id_material = m.id_material
              LEFT JOIN t_trace_header parent ON th.from_trace_no = parent.to_trace_no AND parent.status = 1
              LEFT JOIN t_balance_header bh ON th.id_balance_head = bh.id_balance_head
              LEFT JOIN m_sloc sl ON th.id_sloc = sl.id_sloc
              LEFT JOIN m_tank t ON th.id_tank = t.id_tank
              LEFT JOIN m_sloc sl2 ON t.id_sloc = sl2.id_sloc
              LEFT JOIN t_balance_detail bd ON bh.id_balance_head = bd.id_balance_head AND bd.status = 1
              LEFT JOIN m_supplier s ON bd.id_supplier = s.id_supplier
             WHERE th.to_trace_no = ?
               AND th.status = 1
               AND (" . $plantFilter['sql'] . ")
            GROUP BY th.id_trace_head
            ORDER BY th.entry_date DESC
        ";

        return DB::connection($this->connection)->select($sql, array_merge([$traceNo], $plantFilter['bindings']));
    }

    /**
     * Get trace header by trace number
     */
    public function getTraceHeader(string $traceNo, mixed $plantId = null, ?int $userId = null): ?object
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        $plantFilter = $this->buildTablePlantFilter('th', $plantId, $userId);

        $sql = "
            SELECT th.*, CONCAT(m.code, ' :: ', m.description) AS material,
                   p.description AS plant_name,
                   md.material_document AS mat_doc,
                   sl.description AS sloc_name
              FROM t_trace_header th
              LEFT JOIN m_material m ON th.id_material = m.id_material
              LEFT JOIN m_plant p ON th.id_plant = p.code_3
              LEFT JOIN t_material_document md ON th.id_trace_head = md.id_trace_head AND md.status = 1
              LEFT JOIN m_tank t ON th.id_tank = t.id_tank
              LEFT JOIN m_sloc sl ON t.id_sloc = sl.id_sloc
             WHERE th.to_trace_no = ?
               AND th.status = 1
               AND (" . $plantFilter['sql'] . ")
            LIMIT 1
        ";

        $result = DB::connection($this->connection)->select($sql, array_merge([$traceNo], $plantFilter['bindings']));

        return $result[0] ?? null;
    }

    /**
     * Get forward chain - all traces that come after this trace
     */
    public function getForwardChain(string $traceNo, mixed $plantId = null, ?int $userId = null): array
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        $plantFilter = $this->buildTablePlantFilter('th', $plantId, $userId);

        $sql = "
            SELECT th.id_trace_head, th.from_trace_no, th.to_trace_no,
                   th.entry_date, th.in_qty, th.out_qty, th.id_material,
                   CONCAT(m.code, ' :: ', m.description) AS material,
                   th.id_plant
              FROM t_trace_header th
              LEFT JOIN m_material m ON th.id_material = m.id_material
             WHERE th.from_trace_no = ?
               AND th.status = 1
               AND (" . $plantFilter['sql'] . ")
            ORDER BY th.entry_date ASC
        ";

        return DB::connection($this->connection)->select($sql, array_merge([$traceNo], $plantFilter['bindings']));
    }

    /**
     * Get backward chain - all traces that created this trace
     */
    public function getBackwardChain(string $traceNo, mixed $plantId = null, ?int $userId = null): array
    {
        DB::connection($this->connection)->select(
            'SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))'
        );

        $plantFilter = $this->buildTablePlantFilter('th', $plantId, $userId);

        $sql = "
            SELECT th.id_trace_head, th.from_trace_no, th.to_trace_no,
                   th.entry_date, th.in_qty, th.out_qty, th.id_material,
                   CONCAT(m.code, ' :: ', m.description) AS material,
                   bh.trace_no AS source_trace, bh.qty AS source_qty,
                   th.id_plant
              FROM t_trace_header th
              LEFT JOIN m_material m ON th.id_material = m.id_material
              LEFT JOIN t_balance_header bh ON th.id_balance_head = bh.id_balance_head
             WHERE th.to_trace_no = ?
               AND th.status = 1
               AND (" . $plantFilter['sql'] . ")
            ORDER BY th.entry_date DESC
        ";

        return DB::connection($this->connection)->select($sql, array_merge([$traceNo], $plantFilter['bindings']));
    }

    /**
     * Search traces by material or batch number
     */
    public function searchTraces(mixed $materialId, ?string $batchNo = null, mixed $plantId = null, ?int $userId = null): array
    {
        DB::connection($this->connection)->select(
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

        return DB::connection($this->connection)->select($sql, $bindings);
    }
}