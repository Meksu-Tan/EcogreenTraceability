<?php declare(strict_types=1);

namespace Modules\TsPackage\Repositories;

use Modules\TsPackage\Repositories\Contracts\PackageRepositoryInterface;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Traits\DbCompatTrait;
use Modules\Shared\Traits\TraceNumberGeneratorTrait;
use Modules\Shared\Helpers\Feed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * @todo Technical Debt: This class is 671 lines (limit: 200). Requires refactoring into smaller, focused classes.
 * - Split into: PackageQueryRepository (getDtPckEntry, getActiveFgProduct, getAllWarehouses, getWipMaterialByFgProduct, lookup methods),
 *   PackageWriteRepository (store, cancel, updatePo, updateBatch, updateSubTank),
 *   PackageBatchGenerator (generateTraceNo, batch/trace number generation logic)
 */
class EloquentPackageRepository implements PackageRepositoryInterface
{
    use DbCompatTrait, TraceNumberGeneratorTrait;

    protected string $connection = 'eudr_ts';
    protected static string $movType1 = "4";

    public function getDtPckEntry(int $plantId = 0, int $page = 1, int $perPage = 50): array
    {
        $gcSloc     = "''";
        $gcSupplier = $this->dbGroupConcat(
            "CONCAT(d.description, ' / ', d.batch_sap, ' / Init: ', {$this->dbNumberFormat('d.init_qty', 3)}, ' MT / Balance: ', {$this->dbNumberFormat('d.qty', 3)}, ' MT')",
            ' | ',
            true
        );
        $gcFromTrace = $this->dbGroupConcat('DISTINCT g.from_trace_no', ' ');
        $fmtInitQty  = $this->isPgsql()
            ? $this->dbNumberFormat('MIN(wh_sub.init_qty)', 3)
            : $this->dbNumberFormat('wh_sub.init_qty', 3);
        $fmtQty      = $this->isPgsql()
            ? $this->dbNumberFormat('MIN(wh_sub.qty)', 3)
            : $this->dbNumberFormat('wh_sub.qty', 3);
        $fmtBalSup   = $this->dbNumberFormat('SUM(DISTINCT dd.init_qty)', 3);

        $groupKw = $this->isPgsql() ? 'GROUP BY a.trace_no' : 'GROUP BY a.trace_no';

        $wherePlant = '';
        $bindings = [];
        if ($plantId > 0) {
            $wherePlant = ' AND a.id_plant = ?';
            $bindings[] = $plantId;
        }
        $bindings[] = $perPage;
        $bindings[] = ($page - 1) * $perPage;

        $results = DB::connection($this->connection)->select("
            SELECT" . ($this->isPgsql() ? "
                   MIN(a.id_whx_head) AS id_whx_head,
                   MIN(a.entry_date) AS entry_date,
                   MIN(CONCAT(wh_sub.from_trace_no, ' >>> ', a.trace_no)) AS fromto_trace_no,
                   MIN(a.id_material_feed) AS id_material_feed,
                   MIN(a.id_material_fg) AS id_material_fg,
                   MIN(a.batch_no) AS batch_no,
                   MIN(f.id_trace_head) AS id_trace_head,
                   MIN(f.id_balance_head) AS id_balance_head,
                   {$gcSloc} AS sloc,
                   {$fmtInitQty} AS init_qty, {$fmtQty} AS balance,
                   MIN(a.status) AS status,
                   MIN(a.created_by) AS created_by,
                   MIN(a.created_at) AS created_at,
                   MIN(a.updated_by) AS updated_by,
                   MIN(a.updated_at) AS updated_at,
                   MIN(UPPER(b.description)) AS feed,
                   MIN(UPPER(c.description)) AS fg,
                   a.trace_no,
                   MIN(a.po_no) AS po_no,
                   MIN(wh.code) AS whx,
                   MIN(a.id_section) AS id_section,
                   {$gcSupplier} AS supplier,
                   {$fmtBalSup} AS balance_supplier,
                   MIN(" . \Modules\Shared\Helpers\TraceHelper::plantNameExpression('a.trace_no') . ") AS plant_name,
                    MAX(CASE
                       WHEN a.trace_no = (SELECT tth.to_trace_no
                                            FROM t_trace_header tth
                                           WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('tth.to_trace_no', '<>', '000') . "
                                             AND SUBSTRING(tth.to_trace_no, 1, 1) = '4'
                                             AND tth.status = 1
                                           ORDER BY tth.id_trace_head DESC LIMIT 1) THEN 1
                       ELSE NULL
                    END) AS is_last_row,
                    MAX(CASE
                       WHEN EXISTS (SELECT 1 FROM t_trace_header tth2
                                    WHERE tth2.from_trace_no = a.trace_no
                                      AND tth2.status = 1) THEN 1
                       ELSE NULL
                    END) AS next_process" : "
                   a.id_whx_head, a.entry_date,
                   CONCAT(wh_sub.from_trace_no, ' >>> ', a.trace_no) AS fromto_trace_no,
                   a.id_material_feed, a.id_material_fg, a.batch_no, f.id_trace_head, f.id_balance_head,
                   {$gcSloc} AS sloc,
                   {$fmtInitQty} AS init_qty, {$fmtQty} AS balance, a.status, a.created_by, a.created_at, a.updated_by, a.updated_at,
                   UPPER(b.description) AS feed, UPPER(c.description) AS fg, a.trace_no, a.po_no, wh.code AS whx, a.id_section,
                   {$gcSupplier} AS supplier,
                   {$fmtBalSup} AS balance_supplier,
                   " . \Modules\Shared\Helpers\TraceHelper::plantNameExpression('a.trace_no') . " AS plant_name,
                   CASE
                      WHEN a.trace_no = (SELECT to_trace_no
                                           FROM t_trace_header
                                          WHERE " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('to_trace_no', '<>', '000') . "
                                            AND SUBSTRING(to_trace_no, 1, 1) = '4'
                                            AND status = 1
                                          ORDER BY id_trace_head DESC LIMIT 1) THEN 1
                      ELSE NULL
                   END AS is_last_row,
                   CASE
                      WHEN a.trace_no = (SELECT from_trace_no
                                           FROM t_trace_header
                                          WHERE from_trace_no = a.trace_no
                                            AND status = 1
                                          LIMIT 1) THEN 1
                      ELSE NULL
                   END AS next_process")
              . "
       FROM t_warehouse_header a
       LEFT JOIN m_material b ON a.id_material_feed = b.id_material
       LEFT JOIN m_material_pck c ON a.id_material_fg = c.id_materialpck
       LEFT JOIN (SELECT dd.trace_no, e.description, d.batch_sap, SUM(d.init_qty) AS init_qty, SUM(d.qty) AS qty
                    FROM t_warehouse_header dd
                    LEFT JOIN t_warehouse_detail d ON dd.id_whx_head = d.id_whx_head
                    LEFT JOIN m_supplier e ON e.id_supplier = d.id_supplier
                   WHERE d.status = 1 AND dd.status = 1
                   GROUP BY dd.trace_no, d.batch_sap, e.description
                 ) d ON a.trace_no = d.trace_no
       LEFT JOIN (SELECT dd.trace_no, SUM(d.init_qty) AS init_qty, SUM(d.qty) AS qty
                    FROM t_warehouse_header dd
                    LEFT JOIN t_warehouse_detail d ON dd.id_whx_head = d.id_whx_head
                   WHERE d.status = 1 AND dd.status = 1
                   GROUP BY dd.trace_no
                 ) dd ON a.trace_no = dd.trace_no
       LEFT JOIN t_trace_header f ON f.to_trace_no = a.trace_no AND f.status = 1
       LEFT JOIN (SELECT g.trace_no,
                         {$gcFromTrace} AS from_trace_no,
                         SUM(g.init_qty) AS init_qty,
                         SUM(g.qty) AS qty
                    FROM t_warehouse_header g
                   WHERE g.status = 1
                   GROUP BY g.trace_no
                 ) wh_sub ON a.trace_no = wh_sub.trace_no
       LEFT JOIN m_warehouse wh ON wh.id_warehouse = a.id_section
      WHERE a.status = 1
        AND wh_sub.from_trace_no IS NOT NULL
        {$wherePlant}
      {$groupKw}
      ORDER BY " . ($this->isPgsql() ? 'MIN(a.entry_date) DESC, MIN(a.id_whx_head) DESC' : 'a.entry_date DESC, a.id_whx_head DESC') . "
      LIMIT ? OFFSET ?
        ", $bindings);

        $countBindings = $plantId > 0 ? [$plantId] : [];
        $countResult = DB::connection($this->connection)->select("
            SELECT COUNT(DISTINCT a.trace_no) AS total
              FROM t_warehouse_header a
              LEFT JOIN (SELECT g.trace_no, STRING_AGG(DISTINCT g.from_trace_no, ' ') AS from_trace_no
                           FROM t_warehouse_header g
                          WHERE g.status = 1
                          GROUP BY g.trace_no
                        ) wh_sub ON a.trace_no = wh_sub.trace_no
             WHERE a.status = 1
               AND wh_sub.from_trace_no IS NOT NULL
               " . ($plantId > 0 ? 'AND a.id_plant = ?' : '') . "
        ", $countBindings);

        return [
            'data'  => $results,
            'total' => (int) ($countResult[0]->total ?? 0),
        ];
    }

    public function getActiveFgProduct(): Collection
    {
        $results = DB::connection($this->connection)->select("
            SELECT a.id_materialpck, UPPER(CONCAT(a.description, ' (', a.code, ')')) AS material,
                   a.batch_prefix
              FROM m_material_pck a
             WHERE a.status = 1
             ORDER BY a.description ASC
        ");
        return collect($results);
    }

    public function getAllWarehouses(): Collection
    {
        $results = DB::connection($this->connection)->select('
            SELECT a.id_warehouse, a.description AS warehouse, a.id_batch
              FROM m_warehouse a
             WHERE a.status = 1
             ORDER BY a.id_warehouse ASC
        ');
        return collect($results);
    }

    public function getWipMaterialByFgProduct(array $data): Collection
    {
        $idMaterialPck = $data['idMaterialPck'] ?? null;
        $idSloc = $data['tank'] ?? null;
        $idPlant = $data['id_plant'] ?? null;

        if (!$idMaterialPck) {
            return collect([]);
        }

        if (!$idPlant && $idSloc) {
            $idPlant = DB::connection($this->connection)->table('m_sloc')
                ->where('id_sloc', $idSloc)
                ->value('id_plant');
        }

        $balanceFmt = $this->dbNumberFormat('SUM(c.qty)', 3);

        $bindings = [$idMaterialPck, (string)$idPlant];

        $results = DB::connection($this->connection)->select("
             SELECT COALESCE(CONCAT(a.description, ' (', a.code, ') || Balance : ', COALESCE(a.balance, '0'), ' MT'), CONCAT(a.description, ' (', a.code, ') || Balance : 0.0 MT')) AS wip_material,
                    COALESCE(a.balance, '0') AS balance, a.id_rundown
              FROM (
                  SELECT b.description, b.code, COALESCE({$balanceFmt}, '0') AS balance, b.id_rundown
                    FROM (
                          SELECT DISTINCT b.id_material, b.code, b.description, b.id_rundown
                            FROM m_material_pck a
                            LEFT JOIN m_material b ON a.id_material = b.id_material AND b.status = 1
                           WHERE a.id_materialpck = ?
                          ) b
                    LEFT JOIN (
                          SELECT c.id_material, c.qty
                            FROM t_balance_header c
                           WHERE c.status = 1 AND c.id_plant = ?
                          ) c ON b.id_material = c.id_material
                    GROUP BY b.code, b.description, b.id_rundown
              ) a
        ", $bindings);

        return collect($results);
    }

    public function getCmbActiveTankPck(array $data): Collection
    {
        $plantCode  = $data['plant_code'] ?? null;
        $plantWhere = ($plantCode && $plantCode !== '0') ? 'AND id_plant = ?' : '';
        $bindings   = ($plantCode && $plantCode !== '0') ? [$plantCode] : [];

        return collect(DB::connection($this->connection)->select(
            "SELECT MIN(id_sloc) AS id_sloc,
                    COALESCE(MIN(NULLIF(description,'')), 'PRD') AS tank,
                    code_3,
                    id_plant
               FROM m_sloc
              WHERE status = 1
                AND code_2 = 'PRD'
                AND code_3 = 'PRD'
                {$plantWhere}
              GROUP BY code_3, id_plant
              ORDER BY id_plant ASC",
            $bindings
        ));
    }

    public function getCmbActiveWarehousePck(array $data): Collection
    {
        $batchNoTmp = $data['batchNo'] ?? '';

        if (strpos($batchNoTmp, '-') !== false) {
            $parts = array_map('trim', explode('-', $batchNoTmp));
            $batchNo = $parts[0];
        } else {
            $batchNo = substr($batchNoTmp, 0, 2);
        }

        $results = DB::connection($this->connection)->select('
            SELECT a.id_warehouse, a.description AS warehouse
              FROM m_warehouse a
             WHERE a.status = 1
               AND a.id_batch = ?
        ', [$batchNo]);

        return collect($results);
    }

    public function getCmbActiveSpecificTank(array $data): Collection
    {
        $sloc = $data['sloc'] ?? null;
        $fgProduct = $data['fgProduct'] ?? null;
        if (!$sloc) return collect([]);

        $balanceJoin = "";
        $balanceSelect = "NULL";
        $groupClause = "";
        $bindings = [$sloc, $sloc];

        if ($fgProduct) {
            $idMaterial = $fgProduct;
            $datMaterial = DB::connection($this->connection)->select('
                SELECT b.id_material
                  FROM m_material_pck a
                  JOIN m_material b ON a.id_material = b.id_material
                 WHERE a.id_materialpck = ?
            ', [$idMaterial]);
            $rawMaterialId = $datMaterial[0]->id_material ?? 0;

            $balanceSelect = "COALESCE(SUM(c.qty), 0)";
            $balanceJoin = "LEFT JOIN t_balance_header c ON c.id_sloc = a.id_sloc AND c.id_material = ? AND c.status = 1";
            $groupClause = "GROUP BY a.id_sloc";
            $bindings = [$rawMaterialId, $sloc, $sloc];
        }

        $results = DB::connection($this->connection)->select("
            SELECT a.id_sloc, a.id_sloc AS id_sloc_tail, 
                   CASE WHEN {$balanceSelect} IS NOT NULL THEN CONCAT(a.tf_number, ' || Balance: ', CAST({$balanceSelect} AS TEXT), ' MT') ELSE a.tf_number END AS tf_number
              FROM m_sloc a
              {$balanceJoin}
             WHERE a.status = 1
               AND a.code_3 = (SELECT code_3 FROM m_sloc WHERE id_sloc = ? LIMIT 1)
               AND a.id_plant = (SELECT id_plant FROM m_sloc WHERE id_sloc = ? LIMIT 1)
             {$groupClause}
             ORDER BY a.id_sloc ASC
        ", $bindings);

        return collect($results);
    }

    public function store(string $user, array $data): array
    {
        try {
            $entryDate = $data['entryDate'];
            $idMaterialPck = $data['fgProduct'];
            $batchNo = $data['batchNo'];
            $qtyPck = (float)$data['qty'];
            $poNo = $data['poNo'] ?? null;
            $idSloc = $data['tank'];
            $tankNo = $data['tankNo'] ?? [];
            $slocIds = (!empty($tankNo) && is_array($tankNo)) ? array_map('intval', $tankNo) : [(int)$idSloc];
            $idSlocJson = json_encode($slocIds);
            
            $idWarehouse = $data['warehouse'];
            $idPlant = $data['id_plant'] ?? null;

            if (!$idPlant) {
                $idPlant = DB::connection($this->connection)->table('m_sloc')
                    ->where('id_sloc', $idSloc)
                    ->value('id_plant');
            }

            if (PeriodLockService::isLocked($entryDate)) {
                return ['response' => 99, 'message' => 'Period is locked.'];
            }

            $whID = str_pad((string)$idWarehouse, 3, "0", STR_PAD_LEFT);
            $plantStr = str_pad((string)$idPlant, 2, "0", STR_PAD_LEFT);

            // Generate batch/trace number
            $fmtDate = $this->dbDateFormat($this->dbCurDate(), '%y%m%d');

            $datPckBatch = DB::connection($this->connection)->select("
                SELECT a.pck_batch
                  FROM (SELECT CONCAT(CAST(? AS TEXT), {$fmtDate}, CAST(? AS TEXT), CAST(? AS TEXT), LPAD(CAST(CAST(SUBSTRING(a.to_trace_no,13,2) AS INTEGER) + 1 AS TEXT), 2, '0')) AS pck_batch
                          FROM t_trace_header a
                         WHERE " . \Modules\Shared\Helpers\TraceHelper::only14Digit('a.to_trace_no') . "
                           AND SUBSTRING(a.to_trace_no,1,12) = CONCAT(CAST(? AS TEXT), {$fmtDate}, CAST(? AS TEXT), CAST(? AS TEXT))
                           AND a.status = 1
                         ORDER BY a.id_trace_head DESC
                         LIMIT 1 ) a
                 UNION ALL
                 SELECT CONCAT(CAST(? AS TEXT), {$fmtDate}, CAST(? AS TEXT), CAST(? AS TEXT), '01') AS pck_batch
                  LIMIT 1
            ", [self::$movType1, $plantStr, $whID, self::$movType1, $plantStr, $whID, self::$movType1, $plantStr, $whID]);

            $traceNoWhx = $datPckBatch[0]->pck_batch;
            $traceNoTrf = substr_replace($traceNoWhx, '000', 7, 3);

            // Get Material Feed
            $datMaterial = DB::connection($this->connection)->select('
                SELECT a.id_material, b.code
                  FROM m_material_pck a
                  LEFT JOIN m_material b ON a.id_material = b.id_material
                 WHERE a.id_materialpck = ?
            ', [$idMaterialPck]);

            if (empty($datMaterial)) {
                return ['response' => 4, 'message' => 'Material not found.'];
            }
            $idMaterialFeed = $datMaterial[0]->id_material;
            $codeMaterial = $datMaterial[0]->code;

            // Check balance stock
            $inClause = implode(',', array_fill(0, count($slocIds), '?'));
            $bindings = array_merge([$codeMaterial], $slocIds);
            
            $datBalQty = DB::connection($this->connection)->select("
                SELECT SUM(b.qty) AS total
                  FROM m_material a
                  LEFT JOIN t_balance_header b ON b.id_material = a.id_material
                 WHERE a.code = ?
                   AND a.status = 1
                   AND b.status = 1
                   AND b.qty > '0.0001'
                   AND b.id_sloc IN ({$inClause})
            ", $bindings);

            $totalStock = (float)($datBalQty[0]->total ?? 0);
            if (($totalStock - $qtyPck) < -0.000001) {
                return ['response' => 4, 'message' => "Insufficient stock balance. Stock: {$totalStock}, Needed: {$qtyPck}, Code: {$codeMaterial}, Sloc: {$idSloc}, Plant: {$idPlant}"];
            }

            // Check for orphan balance heads
            $orphanHeads = DB::connection($this->connection)->select("
                SELECT bh.id_balance_head, bh.trace_no
                  FROM t_balance_header bh
                  LEFT JOIN t_balance_detail bd
                    ON bh.id_balance_head = bd.id_balance_head
                   AND bd.status = 1
                   AND bd.qty > 0.0001
                 WHERE bh.status = 1
                   AND bh.qty > 0.0001
                   AND bh.id_material = ?
                   AND bh.id_sloc IN ({$inClause})
                   AND bd.id_balance_tail IS NULL
            ", array_merge([$idMaterialFeed], $slocIds));

            if (count($orphanHeads) > 0) {
                return ['response' => 6, 'message' => 'Orphan balance heads found. Supplier origin cannot be traced.'];
            }

            // Perform Feed using generalFeed
            $feedResult = Feed::generalFeed([
                'user'         => $user,
                'entry_date'   => $entryDate,
                'id_material'  => $idMaterialFeed,
                'id_sloc'      => $idSlocJson,
                'id_plant'     => $idPlant,
                'qty'          => $qtyPck,
                'to_trace_no'  => $traceNoTrf,
            ]);

            if (($feedResult['response'] ?? 0) != 1) {
                return ['response' => 3, 'message' => 'Failed to feed from stock.'];
            }

            Feed::normalizeSupplierRundown($feedResult['trace_head_ids'], $qtyPck);

            $finalSupplierDetails = DB::connection($this->connection)->select('
                SELECT td.id_supplier, td.batch_sap, SUM(td.out_qty) AS qty
                  FROM t_trace_detail td
                 WHERE td.status = 1
                   AND td.id_trace_head IN ('.implode(',', $feedResult['trace_head_ids']).')
                 GROUP BY td.id_supplier, td.batch_sap
            ');

            $fromTraceNo = $feedResult['used_heads'][0]['from_trace_no'];

            return DB::connection($this->connection)->transaction(function () use ($entryDate, $fromTraceNo, $traceNoWhx, $idMaterialFeed, $idMaterialPck, $idWarehouse, $idSloc, $idSlocJson, $batchNo, $poNo, $qtyPck, $idPlant, $user, $traceNoTrf, $finalSupplierDetails) {
                
                // Insert Warehouse Header
                $idWhxHead = DB::connection($this->connection)->table('t_warehouse_header')->insertGetId([
                    'entry_date' => $entryDate,
                    'from_trace_no' => $fromTraceNo,
                    'trace_no' => $traceNoWhx,
                    'id_material_feed' => $idMaterialFeed,
                    'id_material_fg' => $idMaterialPck,
                    'id_section' => $idWarehouse,
                    'id_sloc' => $idSlocJson,
                    'batch_no' => $batchNo,
                    'po_no' => $poNo,
                    'qty' => $qtyPck,
                    'in_qty' => $qtyPck,
                    'init_qty' => $qtyPck,
                    'id_plant' => $idPlant,
                    'created_by' => $user
                ], 'id_whx_head');

                // Insert Trace Header Rundown
                $idTraceHeadRundown = DB::connection($this->connection)->table('t_trace_header')->insertGetId([
                    'from_trace_no' => $traceNoTrf,
                    'to_trace_no' => $traceNoWhx,
                    'id_balance_head' => $idWhxHead,
                    'id_material' => $idMaterialPck,
                    'entry_date' => $entryDate,
                    'id_sloc' => $idSlocJson,
                    'in_qty' => $qtyPck,
                    'curr_qtf' => $qtyPck,
                    'id_plant' => $idPlant,
                    'created_by' => $user,
                ], 'id_trace_head');

                foreach ($finalSupplierDetails as $detail) {
                    $idWhxTail = DB::connection($this->connection)->table('t_warehouse_detail')->insertGetId([
                        'id_whx_head'      => $idWhxHead,
                        'id_material_feed' => $idMaterialFeed,
                        'id_material_fg'   => $idMaterialPck,
                        'id_supplier'      => $detail->id_supplier,
                        'batch_sap'        => $detail->batch_sap,
                        'qty'              => $detail->qty,
                        'in_qty'           => $detail->qty,
                        'init_qty'         => $detail->qty,
                        'id_plant'         => $idPlant,
                        'id_sloc'          => $idSlocJson,
                        'created_by'       => $user
                    ], 'id_whx_tail');

                    DB::connection($this->connection)->table('t_trace_detail')->insert([
                        'id_trace_head'   => $idTraceHeadRundown,
                        'id_balance_tail' => $idWhxTail,
                        'id_supplier'     => $detail->id_supplier,
                        'id_material'     => $idMaterialPck,
                        'in_qty'          => $detail->qty,
                        'batch_sap'       => $detail->batch_sap,
                        'id_sloc'         => $idSlocJson,
                        'id_plant'        => $idPlant,
                        'created_by'      => $user,
                    ]);
                }

                return ['response' => 1, 'message' => 'Package entry stored successfully.'];
            });

        } catch (Exception $e) {
            return ['response' => 0, 'message' => 'Store failed: ' . $e->getMessage()];
        }
    }

    public function cancel(string $user, array $data): array
    {
        try {
            $traceNo = $data['traceNo'] ?? null;
            if (!$traceNo) {
                return ['response' => 0, 'message' => 'Trace number is required.'];
            }

            // Check period lock
            $entryDate = DB::connection($this->connection)->select('
                SELECT entry_date
                  FROM t_trace_header
                 WHERE to_trace_no = ?
                   AND status = 1
            ', [$traceNo]);

            if (empty($entryDate)) {
                return ['response' => 0, 'message' => 'Active entry not found.'];
            }

            if (PeriodLockService::isLocked($entryDate[0]->entry_date)) {
                return ['response' => 99, 'message' => 'Period is locked.'];
            }

            // Get all trace headers associated with the trace no
            $datPack = DB::connection($this->connection)->select('
                SELECT a.id_trace_head, a.in_qty
                  FROM t_trace_header a
                 WHERE a.status = 1
                   AND a.to_trace_no = ?
            ', [$traceNo]);

            return DB::connection($this->connection)->transaction(function () use ($datPack, $traceNo, $user) {
                foreach ($datPack as $row) {
                    $idTraceHead = $row->id_trace_head;
                    $inQtyWhx = $row->in_qty;

                    $datWhx = DB::connection($this->connection)->select('
                        SELECT a.id_whx_head
                          FROM t_warehouse_header a
                         WHERE a.status = 1
                           AND a.trace_no = ?
                           AND a.init_qty = ?
                    ', [$traceNo, $inQtyWhx]);

                    if (empty($datWhx)) continue;
                    $idWhxHead = $datWhx[0]->id_whx_head;

                    $datTraceHeadFeed = DB::connection($this->connection)->select('
                        SELECT b.id_trace_head, b.id_balance_head
                          FROM t_trace_header a
                          LEFT JOIN t_trace_header b ON a.from_trace_no = b.to_trace_no AND b.status = 1 AND a.in_qty = b.out_qty
                         WHERE a.id_trace_head = ?
                           AND a.status = 1
                    ', [$idTraceHead]);

                    if (empty($datTraceHeadFeed)) continue;
                    $idBalHead = $datTraceHeadFeed[0]->id_balance_head;

                    $datWhxHead = DB::connection($this->connection)->select('
                        SELECT init_qty
                          FROM t_warehouse_header
                         WHERE id_whx_head = ?
                           AND status = 1
                    ', [$idWhxHead]);

                    $whxQty = (float)$datWhxHead[0]->init_qty;

                    $datBalHead = DB::connection($this->connection)->select('
                        SELECT qty, out_qty
                          FROM t_balance_header
                         WHERE id_balance_head = ?
                           AND status = 1
                    ', [$idBalHead]);

                    if (!empty($datBalHead)) {
                        $balQty = (float)$datBalHead[0]->qty;
                        $balOutQty = (float)$datBalHead[0]->out_qty;

                        $newBalQty = $balQty + $whxQty;
                        $newBalOutQty = $balOutQty - $whxQty;

                        DB::connection($this->connection)->update('
                            UPDATE t_balance_header
                               SET qty = ?,
                                   out_qty = ?,
                                   updated_by = ?
                             WHERE id_balance_head = ?
                               AND status = 1
                        ', [$newBalQty, $newBalOutQty, $user, $idBalHead]);

                        // Log
                        DB::connection($this->connection)->insert('
                            INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)
                        ', ['T_BALANCE_HEAD', 'UPDATE', 'IDHEAD: ' . $idBalHead . ' | QTY: ' . $balQty . ' >>> ' . $newBalQty . ' / OUT_QTY: ' . $balOutQty . ' >>> ' . $newBalOutQty, $user]);
                    }

                    // Return Tail
                    $datWhxTail = DB::connection($this->connection)->select('
                        SELECT a.id_whx_tail, a.init_qty, b.from_trace_no, d.id_balance_tail, d.qty, d.out_qty
                          FROM t_warehouse_detail a
                          LEFT JOIN t_warehouse_header b ON a.id_whx_head = b.id_whx_head AND b.status = 1
                          LEFT JOIN t_balance_header c ON c.trace_no = b.from_trace_no AND c.status = 1
                          LEFT JOIN t_balance_detail d ON c.id_balance_head = d.id_balance_head AND d.status = 1 AND a.batch_sap = d.batch_sap
                         WHERE a.id_whx_head = ?
                           AND a.status = 1
                    ', [$idWhxHead]);

                    foreach ($datWhxTail as $tailRow) {
                        $whxQtyTail = (float)$tailRow->init_qty;
                        $idBalTail = $tailRow->id_balance_tail;
                        $balQtyTail = (float)$tailRow->qty;
                        $balOutQtyTail = (float)$tailRow->out_qty;

                        $newBalQtyTail = $balQtyTail + $whxQtyTail;
                        $newBalOutQtyTail = $balOutQtyTail - $whxQtyTail;

                        if ($newBalOutQtyTail < 0) continue;

                        DB::connection($this->connection)->update('
                            UPDATE t_balance_detail
                               SET qty = ?,
                                   out_qty = ?,
                                   updated_by = ?
                             WHERE id_balance_tail = ?
                               AND status = 1
                        ', [$newBalQtyTail, $newBalOutQtyTail, $user, $idBalTail]);

                        // Log
                        DB::connection($this->connection)->insert('
                            INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                            VALUES (?, ?, ?, ?)
                        ', ['T_BALANCE_TAIL', 'UPDATE', 'IDTAIL: ' . $idBalTail . ' | QTY: ' . $balQtyTail . ' >>> ' . $newBalQtyTail . ' / OUT_QTY: ' . $balOutQtyTail . ' >>> ' . $newBalOutQtyTail, $user]);
                    }

                    // Set header and detail status to 0
                    DB::connection($this->connection)->update('UPDATE t_warehouse_header SET status = 0, updated_by = ? WHERE id_whx_head = ?', [$user, $idWhxHead]);
                    DB::connection($this->connection)->update('UPDATE t_warehouse_detail SET status = 0, updated_by = ? WHERE id_whx_head = ?', [$user, $idWhxHead]);
                    DB::connection($this->connection)->update('UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ?', [$user, $idTraceHead]);
                    DB::connection($this->connection)->update('UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_head = ?', [$user, $idTraceHead]);
                    DB::connection($this->connection)->update('UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ?', [$user, $datTraceHeadFeed[0]->id_trace_head]);
                    DB::connection($this->connection)->update('UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_head = ?', [$user, $datTraceHeadFeed[0]->id_trace_head]);
                }

                return ['response' => 1, 'message' => 'Packaging entry cancelled successfully.'];
            });

        } catch (Exception $e) {
            return ['response' => 0, 'message' => 'Cancellation failed: ' . $e->getMessage()];
        }
    }

    public function updatePo(string $user, array $data): array
    {
        try {
            $id = $data['id'];
            $poNo = $data['poNo'] ?? null;

            DB::connection($this->connection)->update('
                UPDATE t_warehouse_header
                   SET po_no = ?,
                       updated_by = ?
                 WHERE id_whx_head = ?
            ', [$poNo, $user, $id]);

            return ['response' => 1, 'message' => 'PO number updated successfully.'];
        } catch (Exception $e) {
            return ['response' => 0, 'message' => 'Failed to update PO: ' . $e->getMessage()];
        }
    }

    public function updateBatch(string $user, array $data): array
    {
        try {
            $id = $data['id'];
            $batchNo = $data['batchNo'] ?? null;
            $idSection = $data['warehouse'] ?? null;

            DB::connection($this->connection)->update('
                UPDATE t_warehouse_header
                   SET batch_no = ?,
                       id_section = ?,
                       updated_by = ?
                 WHERE id_whx_head = ?
            ', [$batchNo, $idSection, $user, $id]);

            return ['response' => 1, 'message' => 'Batch and warehouse updated successfully.'];
        } catch (Exception $e) {
            return ['response' => 0, 'message' => 'Failed to update batch: ' . $e->getMessage()];
        }
    }

    public function updateSubTank(string $user, array $data): array
    {
        try {
            $idHead = $data['id'];
            $slocs = $data['idSlocTail'] ?? [];

            if (!is_array($slocs)) {
                return ['response' => 0, 'message' => 'INVALID SLOC DATA'];
            }

            $jsonSlocs = json_encode(array_values(array_unique(array_map('intval', $slocs))));

            $row = DB::connection($this->connection)->selectOne('
                SELECT id_sloc, trace_no 
                  FROM t_warehouse_header 
                 WHERE id_whx_head = ? AND status = 1
            ', [$idHead]);

            if (!$row) {
                return ['response' => 0, 'message' => 'Warehouse header not found.'];
            }

            DB::connection($this->connection)->transaction(function () use ($idHead, $jsonSlocs, $user, $row, $slocs) {
                // Update warehouse header
                DB::connection($this->connection)->update('
                    UPDATE t_warehouse_header
                       SET id_sloc = ?, updated_by = ?
                     WHERE id_whx_head = ?
                ', [$jsonSlocs, $user, $idHead]);

                // Update trace header
                DB::connection($this->connection)->update('
                    UPDATE t_trace_header
                       SET id_sloc = ?, updated_by = ?
                     WHERE id_balance_head = ?
                ', [$jsonSlocs, $user, $idHead]);

                // Update warehouse details
                DB::connection($this->connection)->update('
                    UPDATE t_warehouse_detail
                       SET id_sloc = ?, updated_by = ?
                     WHERE id_whx_head = ?
                ', [$jsonSlocs, $user, $idHead]);

                // Update trace details
                DB::connection($this->connection)->update('
                    UPDATE t_trace_detail
                       SET id_sloc = ?, updated_by = ?
                     WHERE id_trace_head IN (
                         SELECT id_trace_head 
                           FROM t_trace_header 
                          WHERE id_balance_head = ?
                     )
                ', [$jsonSlocs, $user, $idHead]);

                // Log
                DB::connection($this->connection)->insert('
                    INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                    VALUES (?, ?, ?, ?)
                ', ['T_WAREHOUSE_HEAD', 'UPDATE_SUBTANK', 'IDHEAD: ' . $idHead . ' | TRACE: ' . $row->trace_no . ' | SLOCS: ' . implode(',', $slocs), $user]);
            });

            return ['response' => 1, 'message' => 'Sloc updated successfully.'];
        } catch (Exception $e) {
            return ['response' => 0, 'message' => 'Failed to update sloc: ' . $e->getMessage()];
        }
    }

    public function generateTraceNo(int $materialId, int $plantId): string
    {
        return $this->generateTraceNumberForMaterial(
            self::$movType1,
            $materialId,
            $plantId,
            't_trace_header',
            'to_trace_no',
            'id_trace_head'
        );
    }
}
