<?php

declare(strict_types=1);

namespace Modules\TsRmreport\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Repositories\Traits\PlantFilterTrait;
use Modules\Shared\Services\Contracts\PlantContextServiceInterface;
use Modules\Shared\Traits\DbCompatTrait;
use Modules\TsRmreport\Repositories\Contracts\RmReportRepositoryInterface;

class RmReportRepository implements RmReportRepositoryInterface
{
    use DbCompatTrait;
    use PlantFilterTrait;

    protected string $connection = 'eudr_ts';

    public function getRmReport(array $filters): array
    {
        return $this->getRmListDetail($filters);
    }

    public function getRmListDetail(array $filters): array
    {
        $plantId = $filters['plant_id'] ?? $filters['id_plant'] ?? null;
        $materialId = $filters['material_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $fmtSumQty = $this->dbNumberFormat('SUM(DISTINCT a.qty)', 3);
        $fmtSumInitQty = $this->dbNumberFormat('SUM(DISTINCT a.init_qty)', 3);
        $fmtInitQty = $this->dbNumberFormat('b.init_qty', 3);
        $fmtBalSupplier = $this->dbNumberFormat('bs.supplier_qty', 3);
        $gcSupplier = $this->dbGroupConcat(
            "CONCAT(e.code, ' :: ', e.description, ' / ', b.batch_sap, ' / Qty:', {$fmtInitQty}, ' MT', CASE WHEN b.out_qty = 0 THEN '' ELSE ' / BATCH TRANSFERRED' END)",
            ' | ',
            true
        );
        $selectDetail = "
            a.id_balance_head, CAST(a.trace_no AS TEXT) AS trace_no,
            {$fmtSumQty} AS qty,
            CONCAT(c.code, ' :: ', c.description) AS material,
            {$fmtSumInitQty} AS init_qty,
            a.entry_date, MAX(b.batch_sap) AS batch_sap,
            {$gcSupplier} AS supplier,
            f.material_document, f.po_so,
            {$fmtBalSupplier} AS balance_supplier
        ";
        $query = DB::connection($this->connection)->table('t_balance_header as a')
            ->selectRaw($selectDetail)
            ->leftJoin('t_balance_detail as b', function ($join) {
                $join->on('a.id_balance_head', '=', 'b.id_balance_head')->where('b.status', 1);
            })
            ->leftJoin('m_material as c', 'a.id_material', '=', 'c.id_material')
            ->leftJoin('m_sloc as d', function ($join) {
                $join->on(DB::raw('CAST(a.id_sloc AS TEXT)'), '=', DB::raw('CAST(d.id_sloc AS TEXT)'))->where('d.status', 1);
            })
            ->leftJoin('m_supplier as e', 'e.id_supplier', '=', 'b.id_supplier')
            ->leftJoin(DB::raw('(SELECT f.id_balance_head, MAX(g.material_document) AS material_document, MAX(g.po_so) AS po_so, MAX(f.id_trace_head) AS id_trace_head FROM t_trace_header f LEFT JOIN t_material_document g ON f.id_trace_head = g.id_trace_head WHERE f.status = 1 GROUP BY f.id_balance_head) as f'
            ), 'f.id_balance_head', '=', 'a.id_balance_head')
            ->leftJoin(DB::raw('(SELECT id_balance_head,SUM(init_qty) AS supplier_qty FROM t_balance_detail WHERE status=1 GROUP BY id_balance_head) as bs'), 'bs.id_balance_head', '=', 'a.id_balance_head')
            ->where('c.type', 'RM')
            ->where('a.status', 1)
            ->where(function ($q) {
                $q->whereRaw("SUBSTRING(a.trace_no,1,1)='1'")->orWhereRaw("SUBSTRING(a.trace_no,1,1)='9'");
            });

        if ($plantId) {
            $query->where(function ($q) use ($plantId) {
                $q->where('a.id_plant', $plantId);
            });
        }
        if ($materialId) {
            $query->where('a.id_material', $materialId);
        }
        if ($dateFrom) {
            $query->where('a.entry_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('a.entry_date', '<=', $dateTo);
        }

        $query->groupBy(
            'a.trace_no',
            'a.id_balance_head',
            'c.code',
            'c.description',
            'a.entry_date',
            'bs.supplier_qty',
            'f.material_document',
            'f.po_so'
        );
        $result = $query->orderByDesc('a.id_balance_head')->get();

        return json_decode(json_encode($result), true);
    }

    public function getRmSummaryRmPrd(array $filters): array
    {
        $selectedYear = $filters['selectedYear'] ?? $filters['year'] ?? date('Y');
        $plantId = $filters['plant_id'] ?? $filters['id_plant'] ?? '0';
        $plantCode3 = ($plantId && $plantId !== '0')
            ? app(PlantContextServiceInterface::class)->resolvePlantId($plantId)
            : null;
        // resolvePlantId() falls back to raw passthrough for unrecognized input;
        // whitelist before raw-SQL interpolation below to close the injection gap.
        if ($plantCode3 !== null && ! preg_match('/^[A-Za-z0-9]{1,10}$/', $plantCode3)) {
            $plantCode3 = null;
        }

        $storageSlocFilter = '';
        $wipFeedSlocFilter = '';
        $adjSlocFilter = '';

        if ($plantCode3) {
            $storageSlocFilter = "(EXISTS (SELECT 1 FROM m_sloc ms WHERE ms.status = 1 AND ms.code_3 = 'STORAGE' AND ms.id_plant = '{$plantCode3}' AND CAST(a.id_sloc AS TEXT) = CAST(ms.id_sloc AS TEXT)) OR CAST(a.id_sloc AS TEXT) = '4')";
            $wipFeedSlocFilter = "AND (EXISTS (SELECT 1 FROM m_sloc ms2 WHERE ms2.status = 1 AND ms2.code_3 IN ('WIP','FEED','STORAGE') AND ms2.id_plant = '{$plantCode3}' AND CAST(b.id_sloc AS TEXT) = CAST(ms2.id_sloc AS TEXT)) OR CAST(b.id_sloc AS TEXT) = '4')";
            $adjSlocFilter = "AND (EXISTS (SELECT 1 FROM m_sloc ms3 WHERE ms3.status = 1 AND ms3.code_3 = 'ADJUSTMENT OUT' AND ms3.id_plant = '{$plantCode3}' AND CAST(b.id_sloc AS TEXT) = CAST(ms3.id_sloc AS TEXT)) OR CAST(b.id_sloc AS TEXT) = '4')";
        } else {
            $storageSlocFilter = "(EXISTS (SELECT 1 FROM m_sloc ms WHERE ms.status = 1 AND ms.code_3 = 'STORAGE' AND CAST(a.id_sloc AS TEXT) = CAST(ms.id_sloc AS TEXT)) OR CAST(a.id_sloc AS TEXT) = '4')";
            $wipFeedSlocFilter = "AND (EXISTS (SELECT 1 FROM m_sloc ms2 WHERE ms2.status = 1 AND ms2.code_3 IN ('WIP','FEED','STORAGE') AND CAST(b.id_sloc AS TEXT) = CAST(ms2.id_sloc AS TEXT)) OR CAST(b.id_sloc AS TEXT) = '4')";
            $adjSlocFilter = "AND (EXISTS (SELECT 1 FROM m_sloc ms3 WHERE ms3.status = 1 AND ms3.code_3 = 'ADJUSTMENT OUT' AND CAST(b.id_sloc AS TEXT) = CAST(ms3.id_sloc AS TEXT)) OR CAST(b.id_sloc AS TEXT) = '4')";
        }

        $fmtSumQty = $this->dbNumberFormat('SUM(DISTINCT a.qty)', 3);
        $fmtSumInitQty = $this->dbNumberFormat('SUM(DISTINCT a.init_qty)', 3);
        $fmtInitQty = $this->dbNumberFormat('b.init_qty', 3);
        $gcBalTail = $this->dbGroupConcat('DISTINCT CAST(b.id_balance_tail AS TEXT)', ',');
        $gcSupplier = $this->dbGroupConcat(
            "CONCAT(e.code, ' :: ', e.description, ' / ', b.batch_sap, ' / Qty : ', {$fmtInitQty}, ' MT')",
            ' | ',
            true
        );

        $gcMaterial = $this->dbGroupConcat("DISTINCT CONCAT(c.code, ' :: ', c.description)", ' | ', true);
        $selectSummary = "
                MAX(a.id_balance_head) AS id_balance_head, MAX(a.id_material) AS id_material,
                MAX(a.id_sloc) AS id_sloc, MAX(a.status) AS status,
                CAST(a.trace_no AS TEXT) AS trace_no, {$fmtSumQty} AS qty,
                MAX(a.created_by) AS created_by, MAX(CAST(a.created_at AS TEXT)) AS created_at,
                {$gcMaterial} AS material, {$fmtSumInitQty} AS init_qty,
                MAX(CONCAT(CASE d.code_3 WHEN 'PRD' THEN 'PRODUCT' ELSE COALESCE(d.code_3, '') END,
                    CASE WHEN p2.code_2 IS NOT NULL THEN ' ' || p2.code_2 ELSE '' END)) AS tf_number,
                MAX(a.entry_date) AS entry_date, MAX(b.batch_sap) AS batch_sap,
                {$gcBalTail} AS id_balance_detail,
                {$gcSupplier} AS supplier,
                CASE WHEN COALESCE(MAX(b.out_qty), 0) = 0 THEN 'N/A' ELSE 'TRACED' END AS traced,
                MAX(mf.description) AS manufacturer_name,
                MAX(f.material_document) AS material_document, MAX(f.po_so) AS po_so,
                MAX(f.id_trace_head) AS id_trace_head,
                MAX(g.qty_tank) AS qty_tank, MAX(h.qty_warehouse) AS qty_warehouse,
                MAX(i.qty_adjustment) AS qty_adjustment
            ";
        $qtyBalSub = $this->dbNumberFormat('ROUND(SUM(b2.balance),3)', 3);
        $qtyWhSub = $this->dbNumberFormat('ROUND(SUM(b.balance),3)', 3);
        $qtyAdjSub = $this->dbNumberFormat('ROUND(SUM(b3.balance),3)', 3);

        $gSubquery = "(SELECT b2.id_material, {$qtyBalSub} AS qty_tank FROM (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material, SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance FROM t_balance_header b LEFT JOIN t_balance_detail bb ON b.id_balance_head = bb.id_balance_head WHERE b.status = 1 AND bb.status = 1 AND (EXISTS (SELECT 1 FROM m_sloc ms2 WHERE ms2.status = 1 AND ms2.code_3 IN ('WIP','FEED','STORAGE')".($plantCode3 ? " AND ms2.id_plant = '{$plantCode3}'" : '')." AND CAST(b.id_sloc AS TEXT) = CAST(ms2.id_sloc AS TEXT)) OR b.id_sloc = '4') GROUP BY b.id_sloc, b.id_balance_head, b.id_material, bb.batch_sap) b2 GROUP BY b2.id_material) as g";
        $hSubquery = "(SELECT b.id_material_fg, {$qtyWhSub} AS qty_warehouse FROM m_warehouse a LEFT JOIN (SELECT b.id_section, b.id_whx_head, bb.batch_sap, b.id_material_fg, b.trace_no, SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance, b.batch_no FROM t_warehouse_header b LEFT JOIN t_warehouse_detail bb ON b.id_whx_head = bb.id_whx_head WHERE b.status = 1 AND bb.status = 1 GROUP BY b.id_section, b.id_whx_head, bb.batch_sap, b.id_material_fg, b.trace_no, b.batch_no) b ON a.id_warehouse = b.id_section WHERE a.status = 1 AND (b.in_qty > '0.001' OR b.out_qty > '0.001') GROUP BY b.id_material_fg) as h";
        $iSubquery = "(SELECT b3.id_material, {$qtyAdjSub} AS qty_adjustment FROM (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material, SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance FROM t_balance_header b LEFT JOIN t_balance_detail bb ON b.id_balance_head = bb.id_balance_head WHERE b.status = 1 AND bb.status = 1 AND (EXISTS (SELECT 1 FROM m_sloc ms3 WHERE ms3.status = 1 AND ms3.code_3 = 'ADJUSTMENT OUT'".($plantCode3 ? " AND ms3.id_plant = '{$plantCode3}'" : '')." AND CAST(b.id_sloc AS TEXT) = CAST(ms3.id_sloc AS TEXT)) OR b.id_sloc = '4') GROUP BY b.id_sloc, b.id_balance_head, b.id_material, bb.batch_sap) b3 GROUP BY b3.id_material) as i";

        $aggSubquery = DB::connection($this->connection)->table('t_balance_header as a')
            ->selectRaw('a.trace_no, MAX(a.id_balance_head) AS id_balance_head')
            ->leftJoin('m_material as c', 'a.id_material', '=', 'c.id_material')
            ->where('a.status', 1)
            ->where('c.type', 'RM')
            ->where(function ($q) {
                $q->whereRaw("SUBSTRING(CAST(a.trace_no AS TEXT),1,1) = '1'")->orWhereRaw("SUBSTRING(CAST(a.trace_no AS TEXT),1,1) = '9'");
            })
            ->whereRaw($this->dbDateFormat('a.entry_date', '%Y').' = ?', [$selectedYear])
            ->groupBy('a.trace_no');

        $query = DB::connection($this->connection)->table('t_balance_header as a')
            ->selectRaw($selectSummary)
            ->joinSub($aggSubquery, 'agg', function ($join) {
                $join->on('a.id_balance_head', '=', 'agg.id_balance_head');
            })
            ->leftJoin('t_balance_detail as b', function ($join) {
                $join->on('a.id_balance_head', '=', 'b.id_balance_head')->where('b.status', 1);
            })
            ->leftJoin('m_material as c', 'a.id_material', '=', 'c.id_material')
            ->leftJoin('m_sloc as d', function ($join) {
                $join->on(DB::raw('CAST(a.id_sloc AS TEXT)'), '=', DB::raw('CAST(d.id_sloc AS TEXT)'))->where('d.status', 1);
            })
            ->leftJoin('m_plant as p2', \DB::raw('d.id_plant'), '=', \DB::raw('p2.code_3'))
            ->leftJoin('m_supplier as e', 'e.id_supplier', '=', 'b.id_supplier')
            ->leftJoin('m_manufacturer as mf', 'mf.id_manufacturer', '=', 'b.id_manufacturer')
            ->leftJoin(DB::raw('(SELECT f.id_balance_head, MAX(g.material_document) AS material_document, MAX(g.po_so) AS po_so, MAX(f.id_trace_head) AS id_trace_head FROM t_trace_header f LEFT JOIN t_material_document g ON f.id_trace_head = g.id_trace_head WHERE f.status = 1 GROUP BY f.id_balance_head) as f'
            ), 'f.id_balance_head', '=', 'a.id_balance_head')
            ->leftJoin(DB::raw($gSubquery), function ($join) {
                $join->on('g.id_material', '=', 'b.id_material');
            })
            ->leftJoin(DB::raw($hSubquery), function ($join) {
                $join->on('h.id_material_fg', '=', 'c.id_material');
            })
            ->leftJoin(DB::raw($iSubquery), function ($join) {
                $join->on('i.id_material', '=', 'b.id_material');
            })
            ->where('a.status', 1);

        $query->groupBy('a.trace_no');
        $result = $query->orderByDesc(DB::raw('MAX(a.id_balance_head)'))->get();

        return json_decode(json_encode($result), true);
    }

    public function getRmDetailRmPrdOnTank(string $batchSap): array
    {
        $fmtBalOuter = $this->dbNumberFormat('ROUND(SUM(a.balance),3)', 3);
        $fmtBalInner = $this->dbNumberFormat('ROUND(SUM(b.balance),3)', 3);
        $fmtIn = $this->dbNumberFormat('ROUND(SUM(b.in_qty),3)', 3);
        $fmtOut = $this->dbNumberFormat('ROUND(SUM(b.out_qty),3)', 3);

        return DB::connection($this->connection)->select(
            "SELECT '' AS sloc, 'BALANCE ON WIP' AS material,
                    '' AS out_qty, '' AS in_qty,
                    {$fmtBalOuter} AS balance
               FROM (
                      SELECT COALESCE(NULLIF(a.description, ''), a.code_3) AS sloc,
                           SUM(b.in_qty) AS in_qty, SUM(b.out_qty) AS out_qty,
                           SUM(b.balance) AS balance
                        FROM m_sloc a
                        LEFT JOIN (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material,
                                        SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                    FROM t_balance_header b
                                    LEFT JOIN t_balance_detail bb
                                    ON b.id_balance_head = bb.id_balance_head
                                    WHERE b.status = 1
                                    AND bb.status = 1
                                    AND bb.batch_sap = ?
                                    GROUP BY b.id_sloc, b.id_balance_head, b.id_material, bb.batch_sap
                             ) b
                              ON b.id_sloc = a.id_sloc
                           WHERE a.status = 1 AND a.code_3 IN ('WIP', 'FEED', 'PRD', 'STORAGE')
                             AND (b.in_qty > '0.001' OR b.out_qty > '0.001')
                           GROUP BY a.id_sloc, COALESCE(NULLIF(a.description, ''), a.code_3)
                       ) a
                  UNION ALL
                 SELECT a.sloc, a.material, a.out_qty, a.in_qty, a.balance
                   FROM (
                          SELECT COALESCE(NULLIF(a.description, ''), a.code_3) AS sloc, CONCAT('(', c.code, ') ', c.description) AS material,
                               {$fmtIn} AS in_qty, {$fmtOut} AS out_qty,
                               {$fmtBalInner} AS balance
                            FROM m_sloc a
                            LEFT JOIN (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material,
                                            SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                        FROM t_balance_header b
                                        LEFT JOIN t_balance_detail bb
                                        ON b.id_balance_head = bb.id_balance_head
                                        WHERE b.status = 1
                                        AND bb.status = 1
                                        AND bb.batch_sap = ?
                                        GROUP BY b.id_sloc, b.id_balance_head, b.id_material, bb.batch_sap
                                 ) b
                              ON b.id_sloc = a.id_sloc
                            LEFT JOIN m_material c
                              ON c.id_material = b.id_material
                           WHERE a.status = 1 AND a.code_3 IN ('WIP', 'FEED', 'PRD', 'STORAGE')
                             AND (b.in_qty > '0.001' OR b.out_qty > '0.001')
                           GROUP BY COALESCE(NULLIF(a.description, ''), a.code_3), b.id_material, c.code, c.description
                       ) a",
            [$batchSap, $batchSap]
        );
    }

    public function getRmDetailRmPrdOnAdjOut(string $batchSap): array
    {
        $fmtBalOuter = $this->dbNumberFormat('ROUND(SUM(a.balance),3)', 3);
        $fmtBalInner = $this->dbNumberFormat('ROUND(SUM(b.balance),3)', 3);
        $fmtIn = $this->dbNumberFormat('ROUND(SUM(b.in_qty),3)', 3);
        $fmtOut = $this->dbNumberFormat('ROUND(SUM(b.out_qty),3)', 3);

        return DB::connection($this->connection)->select(
            "SELECT '' AS sloc, 'BALANCE ON WIP' AS material,
                    '' AS out_qty, '' AS in_qty,
                    {$fmtBalOuter} AS balance
               FROM (
                      SELECT COALESCE(NULLIF(a.description, ''), a.code_3) AS sloc,
                           SUM(b.in_qty) AS in_qty, SUM(b.out_qty) AS out_qty,
                           SUM(b.balance) AS balance
                        FROM m_sloc a
                        LEFT JOIN (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material,
                                        SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                    FROM t_balance_header b
                                    LEFT JOIN t_balance_detail bb
                                    ON b.id_balance_head = bb.id_balance_head
                                    WHERE b.status = 1
                                    AND bb.status = 1
                                    AND bb.batch_sap = ?
                                    GROUP BY b.id_sloc, b.id_balance_head, b.id_material, bb.batch_sap
                             ) b
                              ON b.id_sloc = a.id_sloc
                           WHERE a.status = 1 AND a.code_3 = 'ADJUSTMENT OUT'
                             AND (b.in_qty > '0.001' OR b.out_qty > '0.001')
                           GROUP BY a.id_sloc, COALESCE(NULLIF(a.description, ''), a.code_3)
                       ) a
                  UNION ALL
                 SELECT a.sloc, a.material, a.out_qty, a.in_qty, a.balance
                   FROM (
                          SELECT COALESCE(NULLIF(a.description, ''), a.code_3) AS sloc, CONCAT('(', c.code, ') ', c.description) AS material,
                               {$fmtIn} AS in_qty, {$fmtOut} AS out_qty,
                               {$fmtBalInner} AS balance
                            FROM m_sloc a
                            LEFT JOIN (SELECT b.id_sloc, b.id_balance_head, bb.batch_sap, b.id_material,
                                            SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance
                                        FROM t_balance_header b
                                        LEFT JOIN t_balance_detail bb
                                        ON b.id_balance_head = bb.id_balance_head
                                        WHERE b.status = 1
                                        AND bb.status = 1
                                        AND bb.batch_sap = ?
                                        GROUP BY b.id_sloc, b.id_balance_head, b.id_material, bb.batch_sap
                                 ) b
                              ON b.id_sloc = a.id_sloc
                            LEFT JOIN m_material c
                              ON c.id_material = b.id_material
                           WHERE a.status = 1 AND a.code_3 = 'ADJUSTMENT OUT'
                             AND (b.in_qty > '0.001' OR b.out_qty > '0.001')
                           GROUP BY COALESCE(NULLIF(a.description, ''), a.code_3), b.id_material, c.code, c.description
                       ) a",
            [$batchSap, $batchSap]
        );
    }

    public function getRmDetailRmPrdOnWarehouse(string $batchSap): array
    {
        $fmtBalTotal = $this->dbNumberFormat('ROUND(SUM(a.balance),3)', 3);
        $fmtInTotal = $this->dbNumberFormat('ROUND(SUM(a.in_qty),3)', 3);
        $fmtOutTotal = $this->dbNumberFormat('ROUND(SUM(a.out_qty),3)', 3);
        $fmtInInner = $this->dbNumberFormat('ROUND(SUM(b.in_qty),3)', 3);
        $fmtOutInner = $this->dbNumberFormat('ROUND(SUM(b.out_qty),3)', 3);
        $fmtBalInner = $this->dbNumberFormat('ROUND(SUM(b.balance),3)', 3);
        $gcShip = $this->dbGroupConcat(
            "CONCAT(d.so_no, ' / Batch : ', b.batch_no, ' / Qty : ', d.qty, ' MT')",
            '|',
            true
        );
        $conn = DB::connection($this->connection);

        $total = $conn->select(
            "SELECT '' AS sloc, 'TOTAL' AS material, {$fmtOutTotal} AS out_qty, {$fmtInTotal} AS in_qty,
                    {$fmtBalTotal} AS balance, '' AS so_no, '' AS batch_no, '' AS shipment
               FROM (
                   SELECT SUM(b.in_qty) AS in_qty, SUM(b.out_qty) AS out_qty, SUM(b.balance) AS balance
                     FROM m_warehouse a
                     LEFT JOIN (SELECT b.id_section, b.id_whx_head, bb.batch_sap, b.id_material_fg, b.trace_no,
                                       SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance,
                                       MAX(b.batch_no) AS batch_no
                                  FROM t_warehouse_header b
                                  LEFT JOIN t_warehouse_detail bb
                                    ON b.id_whx_head = bb.id_whx_head
                                 WHERE b.status = 1
                                   AND bb.status = 1
                                   AND bb.batch_sap = ?
                                 GROUP BY b.id_section, b.id_whx_head, bb.id_material_fg, bb.batch_sap, b.id_material_fg, b.trace_no
                               ) b
                       ON a.id_warehouse = b.id_section
                    WHERE a.status = 1
                      AND (b.in_qty > '0.001' OR b.out_qty > '0.001')
              ) a",
            [$batchSap]
        );

        $details = $conn->select(
            "SELECT a.sloc, a.material, a.out_qty, a.in_qty, a.balance, a.so_no, a.batch_no, a.shipment
               FROM (
                   SELECT a.description AS sloc, CONCAT('(', c.code, ') ', c.description) AS material,
                          {$fmtInInner} AS in_qty, {$fmtOutInner} AS out_qty,
                          {$fmtBalInner} AS balance, MAX(d.so_no) AS so_no, MAX(b.batch_no) AS batch_no,
                          {$gcShip} AS shipment
                     FROM m_warehouse a
                     LEFT JOIN (SELECT b.id_section, b.id_whx_head, bb.batch_sap, b.id_material_fg, b.trace_no,
                                       SUM(bb.in_qty) AS in_qty, SUM(bb.out_qty) AS out_qty, SUM(bb.qty) AS balance,
                                       MAX(b.batch_no) AS batch_no
                                  FROM t_warehouse_header b
                                  LEFT JOIN t_warehouse_detail bb
                                    ON b.id_whx_head = bb.id_whx_head
                                 WHERE b.status = 1
                                   AND bb.status = 1
                                   AND bb.batch_sap = ?
                                 GROUP BY b.id_section, b.id_whx_head, bb.id_material_fg, bb.batch_sap, b.id_material_fg, b.trace_no
                               ) b
                       ON a.id_warehouse = b.id_section
                     LEFT JOIN m_material_pck c
                       ON c.id_materialpck = b.id_material_fg
                     LEFT JOIN (SELECT d.from_trace_no, d.so_no, ROUND(CAST(SUM(dd.qty) AS numeric),3) AS qty
                                  FROM t_shipment_header d
                                  LEFT JOIN t_shipment_detail dd
                                    ON d.id_ship_head = dd.id_ship_head
                                 WHERE d.status = 1
                                   AND dd.status = 1
                                   AND dd.batch_sap = ?
                                   AND dd.qty > '0.001'
                                 GROUP BY d.from_trace_no, d.so_no
                                   ) d
                       ON b.trace_no = d.from_trace_no
                    WHERE a.status = 1
                      AND (b.in_qty > '0.001' OR b.out_qty > '0.001')
                    GROUP BY a.id_warehouse, a.description, b.id_material_fg, c.code, c.description
              ) a",
            [$batchSap, $batchSap]
        );

        return array_merge($total, $details);
    }
}
