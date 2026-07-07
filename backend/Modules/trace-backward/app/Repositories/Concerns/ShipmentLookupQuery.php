<?php

declare(strict_types=1);

namespace Modules\TraceBackward\Repositories\Concerns;

use Illuminate\Database\Connection;

final class ShipmentLookupQuery
{
    public function __construct(private Connection $connection) {}

    public function findBySoNo(string $soNo): ?object
    {
        return $this->find('sh.so_no = ?', $soNo);
    }

    public function findByTraceNo(string $traceNo): ?object
    {
        return $this->find('sh.trace_no = ?', $traceNo);
    }

    private function find(string $where, string $binding): ?object
    {
        $sql = "
            SELECT sh.id_ship_head, CAST(sh.trace_no AS TEXT) AS trace_no,
                   sh.so_no, sh.id_plant, wh.batch_no
              FROM t_shipment_header sh
              LEFT JOIN t_warehouse_header wh ON sh.from_trace_no = wh.trace_no AND wh.status = 1
             WHERE {$where} AND sh.status = 1
             ORDER BY sh.id_ship_head DESC
             LIMIT 1
        ";

        return $this->connection->selectOne($sql, [$binding]) ?: null;
    }
}
