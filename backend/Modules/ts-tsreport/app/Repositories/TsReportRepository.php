<?php declare(strict_types=1);

namespace Modules\TsTsreport\Repositories;

use Modules\TsTsreport\Repositories\Contracts\TsReportRepositoryInterface;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;
use Modules\Shared\Helpers\TraceHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TsReportRepository implements TsReportRepositoryInterface
{
    use PlantFilterTrait;

    protected string $connection = 'eudr_ts';

    public function getTsReport(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('b', $plantId, $userId);
        $condWip = TraceHelper::plantCondition('b.to_trace_no', ['00'], 'NOT IN');

        return DB::connection($this->connection)->select(
            "SELECT aa.material, aa.id_material, aa.entry_date, aa.from_trace_no, aa.to_trace_no,
                    aa.section, aa.in_qty, aa.out_qty, aa.supplier, aa.balance_supplier
               FROM (
                     SELECT CONCAT(a.description, ' (', a.code, ')') AS material, a.id_material,
                            b.entry_date, b.from_trace_no, b.to_trace_no,
                            IF(b.in_qty = 0, SUBSTRING(a.qtf_feed,1,3), SUBSTRING(a.qtf_rundown,1,3)) AS section,
                            FORMAT(b.in_qty,3) AS in_qty, FORMAT(b.out_qty,3) AS out_qty,
                            b.supplier, b.balance_supplier
                       FROM m_material a
                       LEFT JOIN (
                                  SELECT b.id_trace_head, b.entry_date, b.id_material, b.to_trace_no,
                                         SUM(c.in_qty) AS in_qty, SUM(c.out_qty) AS out_qty,
                                         GROUP_CONCAT(DISTINCT c.from_trace_no SEPARATOR ' | ') AS from_trace_no,
                                         b.supplier, b.balance_supplier
                                    FROM (
                                           SELECT b.id_trace_head, b.entry_date, b.id_material, b.to_trace_no,
                                                  GROUP_CONCAT(DISTINCT CONCAT(d.description, ' / ', c.batch_sap,
                                                      ' / Qty: ', FORMAT(c.qty,3), ' MT') SEPARATOR ' | ') AS supplier,
                                                  FORMAT(SUM(DISTINCT c.qty),3) AS balance_supplier
                                             FROM t_trace_header b
                                             LEFT JOIN (
                                                        SELECT c.id_trace_head, c.batch_sap, c.id_supplier,
                                                               IF(c.in_qty=0, IF(c.out_qty=0,0,c.out_qty), c.in_qty) AS qty
                                                          FROM t_trace_detail c
                                                         WHERE c.status = 1
                                                       ) c ON b.id_trace_head = c.id_trace_head
                                             LEFT JOIN m_supplier d ON d.id_supplier = c.id_supplier
                                            WHERE b.status = 1
                                              AND c.qty <> 0
                                              AND {$condWip}
                                              AND SUBSTRING(b.to_trace_no,1,1) NOT IN ('1', '6', '7', '8', '9')
                                              AND b.entry_date = ?
                                              AND ({$plantFilter['sql']})
                                            GROUP BY b.to_trace_no
                                         ) b
                                    LEFT JOIN t_trace_header c
                                      ON b.to_trace_no = c.to_trace_no AND c.status = 1
                                   GROUP BY b.id_material, b.to_trace_no
                                 ) b ON a.id_material = b.id_material
                      WHERE a.status = 1
                        AND SUBSTRING(a.qtf_rundown,1,3) <> 'BLE'
                        AND SUBSTRING(a.qtf_rundown,1,3) <> 'TRA'
                      GROUP BY a.id_material, b.to_trace_no
                      ORDER BY a.id_rundown ASC
                   ) aa
              WHERE aa.section <> '-'
              ORDER BY aa.section ASC",
            array_merge([$entryDate], $plantFilter['bindings'])
        );
    }

    public function getTsReportRm(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('a', $plantId, $userId);

        $condRmDirect = TraceHelper::plantCondition('a.to_trace_no', ['00']);
        $condRmTransfer = TraceHelper::plantCondition('a.to_trace_no', ['01', '02']);

        return DB::connection($this->connection)->select(
            "SELECT a.entry_date, a.id_trace_head, GROUP_CONCAT(DISTINCT a.to_trace_no SEPARATOR ' | ') AS to_trace_no,
                    FORMAT(SUM(DISTINCT a.in_qty),3) AS in_qty, '-' AS from_trace_no,
                    CONCAT(c.description, ' (', c.code, ')') AS material, FORMAT(SUM(DISTINCT a.out_qty),3) AS out_qty, 'STORAGE TANK' AS sloc,
                    GROUP_CONCAT(DISTINCT CONCAT(d.description, ' / ', b.batch_sap, ' / Qty: ', FORMAT(b.qty,3), ' MT') SEPARATOR ' | ') AS supplier,
                    FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
               FROM t_trace_header a
               LEFT JOIN (SELECT b.id_trace_head, b.batch_sap, b.id_supplier,
                                 IF(b.in_qty = 0, IF(b.out_qty = 0, 0, b.out_qty), b.in_qty) AS qty
                            FROM t_trace_detail b
                           WHERE b.status = 1) b
                 ON a.id_trace_head = b.id_trace_head
               LEFT JOIN m_material c ON a.id_material = c.id_material
               LEFT JOIN m_supplier d ON b.id_supplier = d.id_supplier
              WHERE a.status = 1
                AND a.from_trace_no IS NULL
                AND (SUBSTRING(a.to_trace_no,1,1) = '1' OR SUBSTRING(a.to_trace_no,1,1) = '9')
                AND {$condRmDirect}
                AND b.qty <> 0
                AND a.entry_date = ?
                AND ({$plantFilter['sql']})
               GROUP BY c.code
              UNION ALL
             SELECT a.entry_date, a.id_trace_head, a.to_trace_no, a.in_qty, a.from_trace_no,
                    a.material, a.out_qty, a.sloc, a.supplier, a.balance_supplier
               FROM (
                       SELECT a.entry_date, a.id_trace_head, GROUP_CONCAT(DISTINCT a.to_trace_no SEPARATOR ' | ') AS to_trace_no,
                              FORMAT(SUM(DISTINCT a.in_qty),3) AS in_qty, '-' AS from_trace_no,
                              CONCAT(c.description, ' (', c.code, ')') AS material, FORMAT(SUM(DISTINCT a.out_qty),3) AS out_qty, f.description AS sloc,
                              GROUP_CONCAT(DISTINCT CONCAT(d.description, ' / ', b.batch_sap, ' / Qty: ', FORMAT(b.qty,3), ' MT') SEPARATOR ' | ') AS supplier,
                              FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
                         FROM t_trace_header a
                         LEFT JOIN (SELECT b.id_trace_head, b.batch_sap, b.id_supplier,
                                         IF(b.in_qty = 0, IF(b.out_qty = 0, 0, b.out_qty), b.in_qty) AS qty
                                     FROM t_trace_detail b
                                     WHERE b.status = 1) b
                           ON a.id_trace_head = b.id_trace_head
                         LEFT JOIN m_material c ON a.id_material = c.id_material
                         LEFT JOIN m_supplier d ON b.id_supplier = d.id_supplier
                         LEFT JOIN t_balance_header e ON a.id_balance_head = e.id_balance_head
                         LEFT JOIN m_sloc f
                           ON e.id_sloc = f.id_sloc OR (JSON_VALID(e.id_sloc) AND (JSON_CONTAINS(e.id_sloc, CAST(f.id_sloc AS CHAR)) OR JSON_CONTAINS(e.id_sloc, JSON_QUOTE(CAST(f.id_sloc AS CHAR)))))
                        WHERE a.status = 1
                          AND a.from_trace_no IS NOT NULL
                          AND (SUBSTRING(a.to_trace_no,1,1) = '1' OR SUBSTRING(a.to_trace_no,1,1) = '9')
                          AND {$condRmTransfer}
                          AND b.qty <> 0
                          AND a.entry_date = ?
                          AND ({$plantFilter['sql']})
                        GROUP BY a.to_trace_no, c.code
                        ORDER BY a.to_trace_no DESC ) a",
            array_merge([$entryDate], $plantFilter['bindings'], [$entryDate], $plantFilter['bindings'])
        );
    }

    public function getTsReportPck(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('a', $plantId, $userId);

        $condPck = TraceHelper::plantCondition('a.to_trace_no', ['00'], 'NOT IN');

        return DB::connection($this->connection)->select(
            "SELECT a.entry_date, a.id_trace_head, a.to_trace_no,
                    FORMAT(SUM(DISTINCT a.in_qty),3) AS in_qty, e.batch_no, e.po_no,
                    FORMAT(SUM(DISTINCT a.out_qty),3) AS out_qty, e.from_trace_no,
                    IF(SUBSTRING(a.to_trace_no,1,1)='4',
                       CONCAT(c.description,' (',c.code,')'),
                       CONCAT(f.description,' (',f.code,')')) AS material,
                    GROUP_CONCAT(DISTINCT CONCAT(d.description,' / ',b.batch_sap,
                        ' / Qty: ',FORMAT(b.qty,3),' MT') SEPARATOR ' | ') AS supplier,
                    FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
               FROM t_trace_header a
               LEFT JOIN (SELECT id_trace_head, batch_sap, id_supplier,
                                 IF(in_qty=0,IF(out_qty=0,0,out_qty),in_qty) AS qty
                            FROM t_trace_detail WHERE status=1) b
                 ON a.id_trace_head=b.id_trace_head
               LEFT JOIN m_material_pck c ON a.id_material=c.id_materialpck
               LEFT JOIN m_supplier d ON b.id_supplier=d.id_supplier
               LEFT JOIN t_warehouse_header e ON a.to_trace_no=e.trace_no AND e.status=1
               LEFT JOIN m_material f ON f.id_material=a.id_material
              WHERE a.status=1
                AND (SUBSTRING(a.to_trace_no,1,1)='4' OR SUBSTRING(a.to_trace_no,1,1)='9')
                AND {$condPck}
                AND SUBSTRING(a.from_trace_no,1,1)<>'9'
                AND b.qty<>0 AND a.entry_date = ?
                AND ({$plantFilter['sql']})
              GROUP BY a.to_trace_no",
            array_merge([$entryDate], $plantFilter['bindings'])
        );
    }

    public function getTsReportShipment(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('a', $plantId, $userId);

        return DB::connection($this->connection)->select(
            "SELECT a.entry_date, a.id_trace_head, a.to_trace_no,
                    FORMAT(SUM(DISTINCT a.in_qty),3) AS in_qty, e.so_no,
                    FORMAT(SUM(DISTINCT a.out_qty),3) AS out_qty, a.from_trace_no,
                    IF(SUBSTRING(a.from_trace_no,1,1)='4',
                       CONCAT(c.description,' (',c.code,')'),
                       CONCAT(f.description,' (',f.code,')')) AS material,
                    GROUP_CONCAT(DISTINCT CONCAT(d.description,' / ',b.batch_sap,
                        ' / Qty: ',FORMAT(b.qty,3),' MT') SEPARATOR ' | ') AS supplier,
                    FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
               FROM t_trace_header a
               LEFT JOIN (SELECT id_trace_head, batch_sap, id_supplier,
                                 IF(in_qty=0,IF(out_qty=0,0,out_qty),in_qty) AS qty
                            FROM t_trace_detail WHERE status=1) b
                 ON a.id_trace_head=b.id_trace_head
               LEFT JOIN m_material_pck c ON a.id_material=c.id_materialpck
               LEFT JOIN m_supplier d ON b.id_supplier=d.id_supplier
               LEFT JOIN t_shipment_header e ON a.to_trace_no=e.trace_no AND e.status=1
               LEFT JOIN m_material f ON f.id_material=a.id_material
              WHERE a.status=1 AND SUBSTRING(a.to_trace_no,1,1)='5'
                AND b.qty<>0 AND a.entry_date = ?
                AND ({$plantFilter['sql']})
              GROUP BY a.to_trace_no",
            array_merge([$entryDate], $plantFilter['bindings'])
        );
    }

    public function getTsReportTransfer(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('a', $plantId, $userId);

        $condTransfer = TraceHelper::plantCondition('a.to_trace_no', ['00'], 'NOT IN');

        return DB::connection($this->connection)->select(
            "SELECT a.entry_date, a.id_trace_head, a.to_trace_no,
                    FORMAT(SUM(DISTINCT a.in_qty),3) AS in_qty,
                    FORMAT(SUM(DISTINCT a.out_qty),3) AS out_qty, a.from_trace_no,
                    CONCAT(c.description,' (',c.code,')') AS material, f.description AS sloc,
                    GROUP_CONCAT(DISTINCT CONCAT(d.description,' / ',b.batch_sap,
                        ' / Qty: ',FORMAT(b.qty,3),' MT') SEPARATOR ' | ') AS supplier,
                    FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
               FROM t_trace_header a
               LEFT JOIN (SELECT id_trace_head, batch_sap, id_supplier,
                                 IF(in_qty=0,IF(out_qty=0,0,out_qty),in_qty) AS qty
                            FROM t_trace_detail WHERE status=1) b
                 ON a.id_trace_head=b.id_trace_head
               LEFT JOIN m_material c ON a.id_material=c.id_material
               LEFT JOIN m_supplier d ON b.id_supplier=d.id_supplier
               LEFT JOIN t_balance_header e ON a.to_trace_no=e.trace_no AND e.status=1
               LEFT JOIN m_sloc f 
                 ON a.id_sloc = f.id_sloc OR (JSON_VALID(a.id_sloc) AND (JSON_CONTAINS(a.id_sloc, CAST(f.id_sloc AS CHAR)) OR JSON_CONTAINS(a.id_sloc, JSON_QUOTE(CAST(f.id_sloc AS CHAR)))))
              WHERE a.status=1
                AND (SUBSTRING(a.to_trace_no,1,1)='7' OR SUBSTRING(a.to_trace_no,1,1)='9')
                AND {$condTransfer}
                AND b.qty<>0 AND a.entry_date = ?
                AND ({$plantFilter['sql']})
              GROUP BY a.to_trace_no ORDER BY a.id_trace_head DESC",
            array_merge([$entryDate], $plantFilter['bindings'])
        );
    }

    public function getTsReportWip(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('a', $plantId, $userId);
        
        $condWip = TraceHelper::plantCondition('a.to_trace_no', ['00'], 'NOT IN');

        return DB::connection($this->connection)->select(
            "SELECT aa.entry_date, aa.id_trace_head, aa.to_trace_no,
                    aa.wip_in, aa.wip_out, aa.section, aa.from_trace_no,
                    aa.material, aa.supplier, aa.balance_supplier
               FROM (
                     SELECT a.entry_date, a.id_trace_head, a.to_trace_no,
                            FORMAT(SUM(DISTINCT a.in_qty),3) AS wip_in,
                            FORMAT(SUM(DISTINCT a.out_qty),3) AS wip_out,
                            IF(SUM(DISTINCT a.in_qty) = 0, SUBSTRING(c.qtf_feed,1,3), SUBSTRING(c.qtf_rundown,1,3)) AS section,
                            a.from_trace_no,
                            CONCAT(c.description,' (',c.code,')') AS material,
                            GROUP_CONCAT(DISTINCT CONCAT(d.description,' / ',b.batch_sap,
                                ' / Qty: ',FORMAT(b.qty,3),' MT') SEPARATOR ' | ') AS supplier,
                            FORMAT(SUM(DISTINCT b.qty),3) AS balance_supplier
                       FROM t_trace_header a
                       LEFT JOIN (SELECT id_trace_head, batch_sap, id_supplier,
                                         IF(in_qty=0,IF(out_qty=0,0,out_qty),in_qty) AS qty
                                    FROM t_trace_detail WHERE status=1) b
                          ON a.id_trace_head=b.id_trace_head
                       LEFT JOIN m_material c ON a.id_material=c.id_material
                       LEFT JOIN m_supplier d ON b.id_supplier=d.id_supplier
                      WHERE a.status=1
                        AND {$condWip}
                        AND SUBSTRING(a.to_trace_no,1,1) NOT IN ('1', '6', '7', '8', '9')
                        AND a.entry_date = ?
                        AND b.qty<>0
                        AND SUBSTRING(c.qtf_rundown,1,3) <> 'BLE'
                        AND SUBSTRING(c.qtf_rundown,1,3) <> 'TRA'
                        AND ({$plantFilter['sql']})
                      GROUP BY a.to_trace_no
                    ) aa
              WHERE aa.section <> '-'
              ORDER BY aa.id_trace_head DESC",
            array_merge([$entryDate], $plantFilter['bindings'])
        );
    }
}
