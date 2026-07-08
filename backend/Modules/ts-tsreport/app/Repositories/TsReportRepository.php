<?php

declare(strict_types=1);

namespace Modules\TsTsreport\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Helpers\TraceHelper;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;
use Modules\Shared\Traits\DbCompatTrait;
use Modules\TsTsreport\Repositories\Contracts\TsReportRepositoryInterface;

class TsReportRepository implements TsReportRepositoryInterface
{
    use DbCompatTrait;
    use PlantFilterTrait;

    protected string $connection = 'eudr_ts';

    public function getTsReport(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('b', $plantId, $userId);
        $condWip = TraceHelper::warehouseCondition('b.to_trace_no', '<>', '000');

        $fmtInQty = $this->dbNumberFormat('b.in_qty', 3);
        $fmtOutQty = $this->dbNumberFormat('b.out_qty', 3);
        $fmtQty = $this->dbNumberFormat('c.qty', 3);
        $fmtBalSup = $this->dbNumberFormat('SUM(DISTINCT c.qty)', 3);
        $gcFTrace = $this->dbGroupConcat('DISTINCT c.from_trace_no', ' | ');
        $gcSupp = $this->dbGroupConcat(
            "CONCAT(d.description, ' / ', c.batch_sap, ' / Qty: ', {$fmtQty}, ' MT')",
            ' | ',
            true
        );

        return DB::connection($this->connection)->select(
            "SELECT aa.material, aa.id_material, aa.entry_date, aa.from_trace_no, aa.to_trace_no,
                    aa.section, aa.in_qty, aa.out_qty, aa.supplier, aa.balance_supplier
               FROM (
                     SELECT CONCAT(a.description, ' (', a.code, ')') AS material, a.id_material,
                            b.entry_date, b.from_trace_no, b.to_trace_no,
                            CASE WHEN b.in_qty = 0 THEN SUBSTRING(a.qtf_feed,1,3) ELSE SUBSTRING(a.qtf_rundown,1,3) END AS section,
                            {$fmtInQty} AS in_qty, {$fmtOutQty} AS out_qty,
                            b.supplier, b.balance_supplier
                       FROM m_material a
                       LEFT JOIN (
                                  SELECT b.id_trace_head, b.entry_date, b.id_material, b.to_trace_no,
                                         SUM(c.in_qty) AS in_qty, SUM(c.out_qty) AS out_qty,
                                         {$gcFTrace} AS from_trace_no,
                                         b.supplier, b.balance_supplier
                                    FROM (
                                           SELECT b.id_trace_head, b.entry_date, b.id_material, b.to_trace_no,
                                                  {$gcSupp} AS supplier,
                                                  {$fmtBalSup} AS balance_supplier
                                             FROM t_trace_header b
                                             LEFT JOIN (
                                                        SELECT c.id_trace_head, c.batch_sap, c.id_supplier,
                                                               CASE WHEN c.in_qty=0 THEN CASE WHEN c.out_qty=0 THEN 0 ELSE c.out_qty END ELSE c.in_qty END AS qty
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
                                             GROUP BY b.to_trace_no, b.id_trace_head, b.entry_date, b.id_material
                                         ) b
                                    LEFT JOIN t_trace_header c
                                      ON b.to_trace_no = c.to_trace_no AND c.status = 1
                                    GROUP BY b.id_material, b.to_trace_no, b.id_trace_head, b.entry_date, b.supplier, b.balance_supplier
                                 ) b ON a.id_material = b.id_material
                      WHERE a.status = 1
                        AND SUBSTRING(a.qtf_rundown,1,3) <> 'BLE'
                        AND SUBSTRING(a.qtf_rundown,1,3) <> 'TRA'
                       GROUP BY a.id_material, a.description, a.code, a.qtf_feed, a.qtf_rundown, a.id_rundown, b.to_trace_no, b.id_trace_head, b.entry_date, b.supplier, b.balance_supplier, b.from_trace_no, b.in_qty, b.out_qty
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

        $condRmDirect = TraceHelper::warehouseCondition('a.to_trace_no', '=', '000');
        $condRmTransfer = TraceHelper::warehouseCondition('a.to_trace_no', '<>', '000');

        $fmtInQty = $this->dbNumberFormat('SUM(DISTINCT a.in_qty)', 3);
        $fmtOutQty = $this->dbNumberFormat('SUM(DISTINCT a.out_qty)', 3);
        $fmtBQty = $this->dbNumberFormat('b.qty', 3);
        $fmtBalSup = $this->dbNumberFormat('SUM(DISTINCT b.qty)', 3);
        $gcTraceNo = $this->dbGroupConcat('DISTINCT a.to_trace_no', ' | ');
        $gcSupp = $this->dbGroupConcat(
            "CONCAT(d.description, ' / ', b.batch_sap, ' / Qty: ', {$fmtBQty}, ' MT')",
            ' | ',
            true
        );
        $slocCond = $this->dbSlocJsonClause('e.id_sloc', 'f.id_sloc');

        return DB::connection($this->connection)->select(
            "SELECT a.entry_date, a.id_trace_head, {$gcTraceNo} AS to_trace_no,
                    {$fmtInQty} AS in_qty, '-' AS from_trace_no,
                    CONCAT(c.description, ' (', c.code, ')') AS material, {$fmtOutQty} AS out_qty, 'STORAGE TANK' AS sloc,
                    {$gcSupp} AS supplier,
                    {$fmtBalSup} AS balance_supplier
               FROM t_trace_header a
               LEFT JOIN (SELECT b.id_trace_head, b.batch_sap, b.id_supplier,
                                 CASE WHEN b.in_qty = 0 THEN CASE WHEN b.out_qty = 0 THEN 0 ELSE b.out_qty END ELSE b.in_qty END AS qty
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
               GROUP BY c.code, c.description, c.id_material,
                      a.entry_date, a.id_trace_head, a.to_trace_no, a.in_qty, a.from_trace_no,
                      b.qty, b.batch_sap, b.id_supplier, d.description, d.id_supplier
              UNION ALL
             SELECT a.entry_date, a.id_trace_head, a.to_trace_no, a.in_qty, a.from_trace_no,
                    a.material, a.out_qty, a.sloc, a.supplier, a.balance_supplier
               FROM (
                       SELECT a.entry_date, a.id_trace_head, {$gcTraceNo} AS to_trace_no,
                              {$fmtInQty} AS in_qty, '-' AS from_trace_no,
                              CONCAT(c.description, ' (', c.code, ')') AS material, {$fmtOutQty} AS out_qty, f.description AS sloc,
                              {$gcSupp} AS supplier,
                              {$fmtBalSup} AS balance_supplier
                         FROM t_trace_header a
                         LEFT JOIN (SELECT b.id_trace_head, b.batch_sap, b.id_supplier,
                                         CASE WHEN b.in_qty = 0 THEN CASE WHEN b.out_qty = 0 THEN 0 ELSE b.out_qty END ELSE b.in_qty END AS qty
                                     FROM t_trace_detail b
                                     WHERE b.status = 1) b
                           ON a.id_trace_head = b.id_trace_head
                         LEFT JOIN m_material c ON a.id_material = c.id_material
                         LEFT JOIN m_supplier d ON b.id_supplier = d.id_supplier
                         LEFT JOIN t_balance_header e ON a.id_balance_head = e.id_balance_head
                         LEFT JOIN m_sloc f
                           ON {$slocCond}
                        WHERE a.status = 1
                          AND a.from_trace_no IS NOT NULL
                          AND (SUBSTRING(a.to_trace_no,1,1) = '1' OR SUBSTRING(a.to_trace_no,1,1) = '9')
                          AND {$condRmTransfer}
                          AND b.qty <> 0
                          AND a.entry_date = ?
                          AND ({$plantFilter['sql']})
                         GROUP BY a.to_trace_no, c.code, c.description, c.id_material, a.id_trace_head, a.entry_date, a.in_qty, a.out_qty, f.description
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

        $condPck = TraceHelper::warehouseCondition('a.to_trace_no', '<>', '000');

        $fmtInQty = $this->dbNumberFormat('SUM(DISTINCT a.in_qty)', 3);
        $fmtOutQty = $this->dbNumberFormat('SUM(DISTINCT a.out_qty)', 3);
        $fmtBQty = $this->dbNumberFormat('b.qty', 3);
        $fmtBalSup = $this->dbNumberFormat('SUM(DISTINCT b.qty)', 3);
        $gcSupp = $this->dbGroupConcat(
            "CONCAT(d.description,' / ',b.batch_sap,' / Qty: ',{$fmtBQty},' MT')",
            ' | ',
            true
        );

        return DB::connection($this->connection)->select(
            "SELECT a.entry_date, a.id_trace_head, a.to_trace_no,
                    {$fmtInQty} AS in_qty, e.batch_no, e.po_no,
                    {$fmtOutQty} AS out_qty, e.from_trace_no,
                    CASE WHEN SUBSTRING(a.to_trace_no,1,1)='4'
                       THEN CONCAT(c.description,' (',c.code,')')
                       ELSE CONCAT(f.description,' (',f.code,')') END AS material,
                    {$gcSupp} AS supplier,
                    {$fmtBalSup} AS balance_supplier
               FROM t_trace_header a
               LEFT JOIN (SELECT id_trace_head, batch_sap, id_supplier,
                                 CASE WHEN in_qty=0 THEN CASE WHEN out_qty=0 THEN 0 ELSE out_qty END ELSE in_qty END AS qty
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
              GROUP BY a.to_trace_no, a.id_trace_head, a.entry_date, a.from_trace_no, a.in_qty, a.out_qty,
                     b.qty, b.batch_sap, b.id_supplier, c.code, c.description, c.id_materialpck,
                     d.description, d.id_supplier, e.batch_no, e.po_no, e.from_trace_no, f.description, f.code, f.id_material",
            array_merge([$entryDate], $plantFilter['bindings'])
        );
    }

    public function getTsReportShipment(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('a', $plantId, $userId);

        $fmtInQty = $this->dbNumberFormat('SUM(DISTINCT a.in_qty)', 3);
        $fmtOutQty = $this->dbNumberFormat('SUM(DISTINCT a.out_qty)', 3);
        $fmtBQty = $this->dbNumberFormat('b.qty', 3);
        $fmtBalSup = $this->dbNumberFormat('SUM(DISTINCT b.qty)', 3);
        $gcSupp = $this->dbGroupConcat(
            "CONCAT(d.description,' / ',b.batch_sap,' / Qty: ',{$fmtBQty},' MT')",
            ' | ',
            true
        );

        return DB::connection($this->connection)->select(
            "SELECT a.entry_date, a.id_trace_head, a.to_trace_no,
                    {$fmtInQty} AS in_qty, e.so_no,
                    {$fmtOutQty} AS out_qty, a.from_trace_no,
                    CASE WHEN SUBSTRING(a.from_trace_no,1,1)='4'
                       THEN CONCAT(c.description,' (',c.code,')')
                       ELSE CONCAT(f.description,' (',f.code,')') END AS material,
                    {$gcSupp} AS supplier,
                    {$fmtBalSup} AS balance_supplier
               FROM t_trace_header a
               LEFT JOIN (SELECT id_trace_head, batch_sap, id_supplier,
                                 CASE WHEN in_qty=0 THEN CASE WHEN out_qty=0 THEN 0 ELSE out_qty END ELSE in_qty END AS qty
                            FROM t_trace_detail WHERE status=1) b
                 ON a.id_trace_head=b.id_trace_head
               LEFT JOIN m_material_pck c ON a.id_material=c.id_materialpck
               LEFT JOIN m_supplier d ON b.id_supplier=d.id_supplier
               LEFT JOIN t_shipment_header e ON a.to_trace_no=e.trace_no AND e.status=1
               LEFT JOIN m_material f ON f.id_material=a.id_material
              WHERE a.status=1 AND SUBSTRING(a.to_trace_no,1,1)='5'
                AND b.qty<>0 AND a.entry_date = ?
                AND ({$plantFilter['sql']})
              GROUP BY a.to_trace_no, a.id_trace_head, a.entry_date, a.from_trace_no, a.in_qty, a.out_qty,
                     b.qty, b.batch_sap, b.id_supplier, c.code, c.description, c.id_materialpck,
                     d.description, d.id_supplier, e.so_no, e.trace_no, f.description, f.code, f.id_material",
            array_merge([$entryDate], $plantFilter['bindings'])
        );
    }

    public function getTsReportTransfer(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('a', $plantId, $userId);

        $condTransfer = TraceHelper::warehouseCondition('a.to_trace_no', '<>', '000');

        $fmtInQty = $this->dbNumberFormat('SUM(DISTINCT a.in_qty)', 3);
        $fmtOutQty = $this->dbNumberFormat('SUM(DISTINCT a.out_qty)', 3);
        $fmtBQty = $this->dbNumberFormat('b.qty', 3);
        $fmtBalSup = $this->dbNumberFormat('SUM(DISTINCT b.qty)', 3);
        $gcSupp = $this->dbGroupConcat(
            "CONCAT(d.description,' / ',b.batch_sap,' / Qty: ',{$fmtBQty},' MT')",
            ' | ',
            true
        );
        $slocCond = $this->dbSlocJsonClause('a.id_sloc', 'f.id_sloc');

        return DB::connection($this->connection)->select(
            "SELECT a.entry_date, a.id_trace_head, a.to_trace_no,
                    {$fmtInQty} AS in_qty,
                    {$fmtOutQty} AS out_qty, a.from_trace_no,
                    CONCAT(c.description,' (',c.code,')') AS material, f.description AS sloc,
                    {$gcSupp} AS supplier,
                    {$fmtBalSup} AS balance_supplier
               FROM t_trace_header a
               LEFT JOIN (SELECT id_trace_head, batch_sap, id_supplier,
                                 CASE WHEN in_qty=0 THEN CASE WHEN out_qty=0 THEN 0 ELSE out_qty END ELSE in_qty END AS qty
                            FROM t_trace_detail WHERE status=1) b
                 ON a.id_trace_head=b.id_trace_head
               LEFT JOIN m_material c ON a.id_material=c.id_material
               LEFT JOIN m_supplier d ON b.id_supplier=d.id_supplier
               LEFT JOIN t_balance_header e ON a.to_trace_no=e.trace_no AND e.status=1
               LEFT JOIN m_sloc f
                 ON {$slocCond}
              WHERE a.status=1
                AND SUBSTRING(a.to_trace_no,1,1)='7'
                AND {$condTransfer}
                AND b.qty<>0 AND a.entry_date = ?
                AND (e.approval_status = 'APPROVED' OR e.approval_status IS NULL)
                AND ({$plantFilter['sql']})
              GROUP BY a.to_trace_no, a.id_trace_head, a.entry_date, a.from_trace_no, a.in_qty, a.out_qty,
                     b.qty, b.batch_sap, b.id_supplier, c.code, c.description, c.id_material,
                     d.description, d.id_supplier, f.description, f.id_sloc, f.code_3 ORDER BY a.id_trace_head DESC",
            array_merge([$entryDate], $plantFilter['bindings'])
        );
    }

    public function getTsReportWip(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('a', $plantId, $userId);

        $condWip = TraceHelper::warehouseCondition('a.to_trace_no', '<>', '000');

        $fmtInQty = $this->dbNumberFormat('SUM(DISTINCT a.in_qty)', 3);
        $fmtOutQty = $this->dbNumberFormat('SUM(DISTINCT a.out_qty)', 3);
        $fmtBQty = $this->dbNumberFormat('b.qty', 3);
        $fmtBalSup = $this->dbNumberFormat('SUM(DISTINCT b.qty)', 3);
        $gcSupp = $this->dbGroupConcat(
            "CONCAT(d.description,' / ',b.batch_sap,' / Qty: ',{$fmtBQty},' MT')",
            ' | ',
            true
        );

        return DB::connection($this->connection)->select(
            "SELECT aa.entry_date, aa.id_trace_head, aa.to_trace_no,
                    aa.wip_in, aa.wip_out, aa.section, aa.from_trace_no,
                    aa.material, aa.supplier, aa.balance_supplier
               FROM (
                     SELECT MAX(a.entry_date) AS entry_date, MAX(a.id_trace_head) AS id_trace_head, a.to_trace_no,
                            {$fmtInQty} AS wip_in,
                            {$fmtOutQty} AS wip_out,
                            CASE WHEN SUM(DISTINCT a.in_qty) = 0 THEN MAX(SUBSTRING(c.qtf_feed,1,3)) ELSE MAX(SUBSTRING(c.qtf_rundown,1,3)) END AS section,
                            MAX(a.from_trace_no) AS from_trace_no,
                            MAX(CONCAT(c.description,' (',c.code,')')) AS material,
                            {$gcSupp} AS supplier,
                            {$fmtBalSup} AS balance_supplier
                       FROM t_trace_header a
                       LEFT JOIN (SELECT id_trace_head, batch_sap, id_supplier,
                                         CASE WHEN in_qty=0 THEN CASE WHEN out_qty=0 THEN 0 ELSE out_qty END ELSE in_qty END AS qty
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

    public function getTsReportBlending(array $filters): array
    {
        $entryDate = $filters['entry_date'] ?? now()->toDateString();
        $plantId = $filters['id_plant'] ?? $filters['plant_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $plantFilter = $this->buildTablePlantFilter('a', $plantId, $userId);

        $fmtInQty = $this->dbNumberFormat('a.in_qty', 3);
        $fmtOutQty = $this->dbNumberFormat('a.out_qty', 3);
        $fmtQty = $this->dbNumberFormat('bs.qty', 3);
        $fmtBalSup = $this->dbNumberFormat('SUM(DISTINCT bs.qty)', 3);
        $slocCond = $this->dbSlocJsonClause('a.id_sloc', 'f.id_sloc');

        $suppExpr = "CASE WHEN td.in_qty=0 THEN CASE WHEN td.out_qty=0 THEN 0 ELSE td.out_qty END ELSE td.in_qty END";
        $fmtSuppQty = $this->dbNumberFormat($suppExpr, 3);
        $gcSupp = $this->dbGroupConcat(
            "CONCAT(s.description, ' / ', td.batch_sap, ' / Qty: ', {$fmtSuppQty}, ' MT')",
            ' | ',
            true
        );

        return DB::connection($this->connection)->select(
            "SELECT a.entry_date, a.id_trace_head, a.to_trace_no,
                    {$fmtInQty} AS in_qty,
                    {$fmtOutQty} AS out_qty, a.from_trace_no,
                    CONCAT(c.description,' (',c.code,')') AS material, f.description AS sloc,
                    {$gcSupp} AS supplier,
                    {$fmtBalSup} AS balance_supplier
               FROM t_trace_header a
               LEFT JOIN (SELECT td.id_trace_head,
                                 SUM({$suppExpr}) AS total_qty
                            FROM t_trace_detail td
                            WHERE td.status = 1
                            GROUP BY td.id_trace_head
                           ) bs ON a.id_trace_head = bs.id_trace_head
               LEFT JOIN t_trace_detail td ON a.id_trace_head = td.id_trace_head AND td.status = 1
               LEFT JOIN m_supplier s ON td.id_supplier = s.id_supplier
               LEFT JOIN m_material c ON a.id_material = c.id_material
               LEFT JOIN t_balance_header e ON a.to_trace_no = e.trace_no AND e.status = 1
               LEFT JOIN m_sloc f ON {$slocCond}
              WHERE a.status = 1
                AND SUBSTRING(a.to_trace_no,1,1) = '8'
                AND COALESCE(bs.total_qty, 0) <> 0
                AND a.entry_date = ?
                AND ({$plantFilter['sql']})
              GROUP BY a.to_trace_no, a.id_trace_head, a.entry_date, a.from_trace_no, a.in_qty, a.out_qty,
                     c.description, c.code, c.id_material, f.description, f.id_sloc, f.code_3
              ORDER BY a.id_trace_head DESC",
            array_merge([$entryDate], $plantFilter['bindings'])
        );
    }
}
