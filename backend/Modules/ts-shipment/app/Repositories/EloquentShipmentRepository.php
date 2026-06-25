<?php declare(strict_types=1);

namespace Modules\TsShipment\Repositories;

use Modules\TsShipment\Repositories\Contracts\ShipmentRepositoryInterface;
use Modules\Shared\Helpers\QuantityDistributionHelper;
use Modules\Shared\Traits\DbCompatTrait;
use Modules\Shared\Traits\TraceNumberGeneratorTrait;
use Modules\Shared\Services\PeriodLockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * @todo Technical Debt: This class is 834 lines (limit: 200). Requires refactoring into smaller, focused classes.
 * - Split into: ShipmentQueryRepository (getDtShipEntry, getActiveFgProduct, getWipMaterialByFgProduct, getActiveBatchProduct, lookup methods),
 *   ShipmentTransactionRepository (store, cancel, updateSo, adjustQtyToTotal, generateTraceNo),
 *   ShipmentExternalIntegration (getDatShipment, getDatSoAllocation, getShipmentBatchPackaging, getPreparationRecord, label lookups via SAP/OEE)
 */
class EloquentShipmentRepository implements ShipmentRepositoryInterface
{
    use DbCompatTrait, TraceNumberGeneratorTrait;

    protected string $connection = 'eudr_ts';

    public function getDtShipEntry(int $plantId = 0, int $page = 1, int $perPage = 50): array
    {
        $castToText = $this->isPgsql() ? 'TEXT' : 'CHAR';
        $orderBy  = $this->isPgsql()
            ? 'ORDER BY a.entry_date DESC, MIN(a.id_ship_head) DESC'
            : 'ORDER BY a.entry_date DESC, a.id_ship_head DESC';

        $rndD = $this->isPgsql() ? 'SUM(ROUND(CAST(d.qty AS numeric),4))' : 'SUM(ROUND(d.qty,4))';
        $rndEe = $this->isPgsql() ? 'SUM(ROUND(CAST(ee.qty AS numeric),4))' : 'SUM(ROUND(ee.qty,4))';
        $rndF = $this->isPgsql() ? 'SUM(ROUND(CAST(f.out_qty AS numeric),4))' : 'SUM(ROUND(f.out_qty,4))';
        $wherePlant = '';
        $bindings = [];
        if ($plantId > 0) {
            $wherePlant = ' AND a.id_plant = ?';
            $bindings[] = $plantId;
        }
        $bindings[] = $perPage;
        $bindings[] = ($page - 1) * $perPage;

        $results = DB::connection($this->connection)->select("
            SELECT
            MIN(a.id_ship_head) AS id_ship_head, a.entry_date,
            CONCAT(CAST(dd.from_trace_no AS {$castToText}), ' >>> ', CAST(a.trace_no AS {$castToText})) AS fromto_trace_no,
            a.so_no, a.id_material_fg,
            {$this->dbNumberFormat('ROUND(CAST(MIN(f.qty) AS numeric),3)', 3)} AS qty,
            a.status, MIN(a.created_by) AS created_by, MIN(a.created_at) AS created_at, MIN(a.updated_by) AS updated_by, MIN(a.updated_at) AS updated_at,
                   MAX(CASE WHEN SUBSTRING(CAST(a.from_trace_no AS {$castToText}),1,1) IN ('1', '2') THEN g.description ELSE c.description END) AS material,
                   MIN(f.id_trace_head) AS id_trace_head, MIN(f.id_balance_head) AS id_balance_head, a.trace_no, a.from_trace_no, MIN(f.batch_no) AS batch_no,
                   {$this->dbGroupConcat("CONCAT(d.description, ' / ', d.batch_sap, ' / Qty: ', {$this->dbNumberFormat('d.qty', 3)}, ' MT')", ' | ', true)} AS supplier,
                   {$this->dbNumberFormat('ROUND(CAST(dd.qty AS numeric),3)', 3)} AS balance_supplier, MAX(a.doc_url) AS doc_url,
                   MAX(" . \Modules\Shared\Helpers\TraceHelper::plantNameExpression('a.trace_no') . ") AS plant_name,
                   MAX(CASE
                       WHEN a.trace_no = (SELECT to_trace_no
                                            FROM t_trace_header
                                           WHERE SUBSTRING(to_trace_no, 1, 1) = '5'
                                             AND status = 1
                                           ORDER BY id_trace_head DESC LIMIT 1) THEN 1
                      ELSE NULL
                   END) AS is_last_row,
                   MAX(CASE
                       WHEN a.trace_no = (SELECT from_trace_no
                                            FROM t_trace_header
                                           WHERE SUBSTRING(from_trace_no, 1, 1) = '4'
                                             AND " . \Modules\Shared\Helpers\TraceHelper::warehouseCondition('from_trace_no', '<>', '000') . "
                                             AND status = 1
                                           ORDER BY from_trace_no DESC LIMIT 1) THEN 1
                      ELSE NULL
                   END) AS next_process
              FROM t_shipment_header a
              LEFT JOIN m_material_pck c ON a.id_material_fg = c.id_materialpck
               LEFT JOIN (SELECT dd.trace_no, e.description, d.batch_sap, {$rndD} AS qty
                           FROM t_shipment_header dd
                           LEFT JOIN t_shipment_detail d ON dd.id_ship_head = d.id_ship_head
                           LEFT JOIN m_supplier e ON e.id_supplier = d.id_supplier
                          WHERE d.status = 1 AND dd.status = 1
                          GROUP BY dd.trace_no, e.description, d.batch_sap
                        ) d ON a.trace_no = d.trace_no
               LEFT JOIN (SELECT dd.trace_no, {$rndEe} AS qty,
                                {$this->dbGroupConcat('CAST(dd.from_trace_no AS ' . $castToText . ')', ' + ', true)} AS from_trace_no
                           FROM t_shipment_header dd
                           LEFT JOIN t_shipment_detail ee ON dd.id_ship_head = ee.id_ship_head
                          WHERE dd.status = 1
                          GROUP BY dd.trace_no
                        ) dd ON a.trace_no = dd.trace_no
               LEFT JOIN (SELECT f.to_trace_no, f.id_trace_head, f.id_balance_head, ff.batch_no,
                                 {$rndF} AS qty
                           FROM t_trace_header f
                           LEFT JOIN t_warehouse_header ff ON f.id_balance_head = ff.id_whx_head AND ff.status = 1
                          WHERE f.status = 1
                          GROUP BY f.to_trace_no, f.id_trace_head, f.id_balance_head, ff.batch_no
                        ) f ON f.to_trace_no = a.trace_no
              LEFT JOIN m_material g ON g.id_material = a.id_material_fg
             WHERE a.status = 1
               {$wherePlant}
             GROUP BY a.trace_no, a.entry_date, a.so_no, a.id_material_fg, a.status, a.from_trace_no, a.id_plant, dd.from_trace_no, dd.qty, c.description, g.description
             {$orderBy}
             LIMIT ? OFFSET ?
        ", $bindings);

        $countBindings = $plantId > 0 ? [$plantId] : [];
        $countResult = DB::connection($this->connection)->select("
            SELECT COUNT(DISTINCT a.trace_no) AS total
              FROM t_shipment_header a
             WHERE a.status = 1
               " . ($plantId > 0 ? 'AND a.id_plant = ?' : '') . "
        ", $countBindings);

        return [
            'data'  => collect($results),
            'total' => (int) ($countResult[0]->total ?? 0),
        ];
    }

    public function getActiveFgProduct(): Collection
    {
        $results = DB::connection($this->connection)->select("
            SELECT CONCAT(UPPER(a.description), ' (', a.code, ')') AS material,
                   CONCAT('PCK|', a.id_materialpck) AS id_material
              FROM m_material_pck a
             WHERE a.status = 1
             ORDER BY material ASC
        ");
        return collect($results);
    }

    public function getWipMaterialByFgProduct(array $data): Collection
    {
        $idMaterialPck = $data['idMaterial'] ?? '';
        $parts = explode('|', $idMaterialPck);
        $type = $parts[0] ?? '';
        $idMaterial = $parts[1] ?? 0;
        $idPlant = $data['id_plant'] ?? null;

        if ($type == 'WIP') {
            // Start from m_material so description always returns even with zero balance
            $results = DB::connection($this->connection)->select("
                SELECT {$this->dbNumberFormat('COALESCE(SUM(a.qty),0)', 3)} AS balance,
                       CONCAT(b.description, ' (', b.code, ') || Balance : ', {$this->dbNumberFormat('COALESCE(SUM(a.qty),0)', 3)}, ' MT') AS wip_material
                  FROM m_material b
                  LEFT JOIN t_balance_header a ON a.id_material = b.id_material AND a.status = 1 AND a.id_plant = ?
                 WHERE b.id_material = ?
                   AND b.status = 1
                 GROUP BY b.description, b.code
            ", [$idPlant, $idMaterial]);
        } else {
            // Start from m_material_pck so description always returns even with zero balance
            $results = DB::connection($this->connection)->select("
                SELECT {$this->dbNumberFormat('COALESCE(SUM(a.qty),0)', 3)} AS balance,
                       CONCAT(b.description, ' (', b.code, ') || Balance : ', {$this->dbNumberFormat('COALESCE(SUM(a.qty),0)', 3)}, ' MT') AS wip_material
                  FROM m_material_pck b
                  LEFT JOIN t_warehouse_header a ON a.id_material_fg = b.id_materialpck AND a.status = 1 AND a.id_plant = ?
                 WHERE b.id_materialpck = ?
                   AND b.status = 1
                 GROUP BY b.description, b.code
            ", [$idPlant, $idMaterial]);
        }

        return collect($results);
    }

    public function getActiveBatchProduct(array $data): Collection
    {
        $idMaterialPck = $data['idMaterial'] ?? '';
        $parts = explode('|', $idMaterialPck);
        $idMaterial = $parts[1] ?? 0;
        $idPlant = $data['id_plant'] ?? null;

        $results = DB::connection($this->connection)->select("
            SELECT " . ($this->isPgsql() ? 'DISTINCT' : '') . " a.batch_no, CONCAT(a.batch_no, ' | Qty : ', {$this->dbNumberFormat('b.qty', 3)}) AS description
              FROM t_warehouse_header a
              LEFT JOIN (SELECT b.id_material_fg, b.batch_no, SUM(b.qty) AS qty
                           FROM t_warehouse_header b
                          WHERE b.status = 1
                          GROUP BY b.id_material_fg, b.batch_no) b ON a.batch_no = b.batch_no AND a.id_material_fg = b.id_material_fg
             WHERE a.id_material_fg = ?
               AND a.status = 1
               AND a.qty > '0.000001'
               AND a.id_plant = ?
             " . ($this->isPgsql() ? '' : 'GROUP BY a.batch_no') . "
        ", [$idMaterial, $idPlant]);

        return collect($results);
    }

    public function getShipmentBatchPackaging(array $data): Collection
    {
        $batchNo = trim((string) ($data['batchNo'] ?? ''));

        if ($batchNo === '') {
            return collect([]);
        }

        // OEE queries â€” use dedicated oee connection (fails silently until OEE server is configured)
        $conn = 'oee';

        try {
            $results = DB::connection($conn)->select('
                SELECT a.entry_date, a.tf_number, a.batch_no, a.spec, a.production_order,
                       a.lot_qty, a.qty, a.product, b.id_process, c.id_packing, d.id_pallet,
                       CONCAT(b.id_process, \' , \', b.code, \' , \', b.description) AS process,
                       CONCAT(c.code, \' , \', c.description) AS packing,
                       CONCAT(d.code, \' , \', d.description) AS pallet,
                       e.url_link AS label_link, f.url_link AS splabel_link,
                       g.url_link AS csmark_link, a.id_special_label, a.id_customer_mark,
                       CONCAT(a.id_sloc, \',\', a.tf_number) AS id_sloc, a.csmark_isCheck, a.splabel_isCheck,
                       CONCAT(a.id_product, \',\', a.product) AS id_product, a.long_text,
                       a.approved_by, a.approved_at,
                       a.created_by, a.id_prdexecution, a.created_at,
                       a.status, e.id_label, h.id_customer, CONCAT(h.code, \' , \', h.description) AS customer,
                       CONCAT(e.description) AS label, CONCAT(f.description) AS splabel,
                       CONCAT(g.description) AS csmark, a.updated_by, UPPER(a.uom) AS uom,
                       a.updated_at AS updated_at, a.finished_by, a.finished_at,
                       a.p_label_link, a.p_splabel_link, a.p_csmark_link, a.tank_data, a.started_at, a.started_by
                  FROM oee_756.t_prd_execution a
                  LEFT JOIN oee_756.m_process b ON a.id_process = b.id_process
                  LEFT JOIN oee_756.m_packing c ON a.id_packing = c.id_packing
                  LEFT JOIN oee_756.m_pallet d ON a.id_pallet = d.id_pallet
                  LEFT JOIN oee_756.m_label e ON a.id_label = e.id_label
                  LEFT JOIN oee_756.m_special_label f ON a.id_special_label = f.id_label
                  LEFT JOIN oee_756.m_customer_mark g ON a.id_customer_mark = g.id_label
                  LEFT JOIN oee_756.m_customer h ON a.id_customer = h.id_customer
                 WHERE a.batch_no = ?
                 ORDER BY a.id_prdexecution DESC
                 LIMIT 1
            ', [$batchNo]);
        } catch (\Throwable $e) {
            $results = [];
        }

        return collect($results);
    }

    public function getPreparationRecord(array $data): Collection
    {
        $batchNo = $data['batchNo'] ?? '';

        // OEE queries â€” use dedicated oee connection (fails silently until OEE server is configured)
        $conn = 'oee';

        try {
            $results = DB::connection($conn)->select('
                SELECT a.id_prepentry, a.id_prdexecution, a.batch_no, a.type,
                       a.description, a.created_by, a.created_at, a.updated_at, a.status
                  FROM oee_756.t_prep_entry a
                 WHERE a.batch_no = ?
                 ORDER BY a.type ASC, a.created_at ASC
            ', [$batchNo]);
        } catch (\Throwable $e) {
            $results = [];
        }

        return collect($results);
    }

    public function getLabel(array $data): Collection
    {
        $label = $data['label'] ?? '';
        $conn = 'oee';

        $results = DB::connection($conn)->select('
            SELECT a.url_link
              FROM oee_756.m_label a
             WHERE a.status = 1
               AND a.id_label = ?
        ', [$label]);

        return collect($results);
    }

    public function getSpecialLabel(array $data): Collection
    {
        $label = $data['label'] ?? '';
        $conn = 'oee';

        $results = DB::connection($conn)->select('
            SELECT a.url_link
              FROM oee_756.m_special_label a
             WHERE a.status = 1
               AND a.id_label = ?
        ', [$label]);

        return collect($results);
    }

    public function getCustomerMark(array $data): Collection
    {
        $label = $data['label'] ?? '';
        $conn = 'oee';

        $results = DB::connection($conn)->select('
            SELECT a.url_link
              FROM oee_756.m_customer_mark a
             WHERE a.status = 1
               AND a.id_label = ?
        ', [$label]);

        return collect($results);
    }

    public function getDatShipment(array $data): array
    {
        $batchNo = trim((string) ($data['batchNo'] ?? ''));
        $soNo = trim((string) ($data['soNo'] ?? ''));
        $soItem = trim((string) ($data['soItem'] ?? ''));

        if ($soNo === '') {
            return [];
        }
        if (strcasecmp($soItem, 'No Doc') === 0) {
            $soItem = '';
        }

        $sapClient = config('eudr.sap_client');
        $sapReqUrl = config('eudr.sap_url');
        $sapFm     = '&FM=ZFM_EUDR_SHIPMENT';
        $input1    = '&SO_NUM=' . urlencode($soNo);
        $input2    = '&SO_ITEM=' . urlencode($soItem);
        $input3    = '&BATCH=' . urlencode($batchNo);

        if ($batchNo == "FB" || $batchNo == "IS" || $batchNo == "VS") {
            $eobUrl = $sapReqUrl . $sapClient . $sapFm . $input1 . $input2;
        } else {
            $eobUrl = $sapReqUrl . $sapClient . $sapFm . $input1 . $input2 . $input3;
        }

        try {
            $response = Http::timeout(10)->get($eobUrl);
            if ($response->failed()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getDatSoAllocation(array $data): array
    {
        $batchNo = trim((string) ($data['batchNo'] ?? ''));

        if ($batchNo === '') {
            return [];
        }

        $sapClient = config('eudr.sap_client');
        $sapReqUrl = config('eudr.sap_url');
        $sapFm     = '&FM=ZFM_AD001';
        $input1    = '&BATCH_NO=' . urlencode($batchNo);

        $eobUrl = $sapReqUrl . $sapClient . $sapFm . $input1;

        try {
            $response = Http::timeout(10)->get($eobUrl);
            if ($response->failed()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function store(string $user, array $data): array
    {
        try {
            $entryDate = $data['entryDate'];
            $idMaterialPck = $data['fgProduct'];
            $soNo = $data['soNo'];
            $outQty = (float)$data['qty'];
            $batchNo = $data['batch_no'];
            $fileName = $data['filename'] ?? null;
            $idPlant = $data['id_plant'] ?? null;

            $parts = explode('|', $idMaterialPck);
            $type = $parts[0] ?? '';
            $idMaterial = $parts[1] ?? 0;

            if (PeriodLockService::isLocked($entryDate)) {
                return ['response' => 99, 'message' => 'Period is locked.'];
            }

            if ($type !== 'PCK') {
                return ['response' => 0, 'message' => 'Only packaging type is currently supported.'];
            }

            $shID = '001';
            $plantStr = str_pad(substr((string)$idPlant, -2), 2, "0", STR_PAD_LEFT);

            $lpadExpr = "LPAD(CAST(CAST(SUBSTRING(a.to_trace_no,13,2) AS INTEGER) + 1 AS TEXT), 2, '0')";

            // Create shipment batch trace no
            $datPckBatch = DB::connection($this->connection)->select("
                SELECT a.pck_batch
                  FROM (SELECT CONCAT(CAST(5 AS TEXT), {$this->dbDateFormat($this->dbCurDate(), '%y%m%d')}, CAST(? AS TEXT), CAST(? AS TEXT), {$lpadExpr}) AS pck_batch
                          FROM t_trace_header a
                         WHERE " . \Modules\Shared\Helpers\TraceHelper::only14Digit('a.to_trace_no') . "
                           AND SUBSTRING(a.to_trace_no,1,12) = CONCAT(CAST(5 AS TEXT), {$this->dbDateFormat($this->dbCurDate(), '%y%m%d')}, CAST(? AS TEXT), CAST(? AS TEXT))
                           AND a.status = 1
                         ORDER BY a.id_trace_head DESC
                         LIMIT 1 ) a
                 UNION ALL
                 SELECT CONCAT(CAST(5 AS TEXT), {$this->dbDateFormat($this->dbCurDate(), '%y%m%d')}, CAST(? AS TEXT), CAST(? AS TEXT), '01') AS pck_batch
                  LIMIT 1
            ", [$shID, $plantStr, $shID, $plantStr, $shID, $plantStr]);

            $traceNo = $datPckBatch[0]->pck_batch;

            // Find stock headers
            $datHead = DB::connection($this->connection)->select('
                SELECT a.id_whx_head, a.qty, a.out_qty, a.trace_no, a.init_qty, a.id_section
                  FROM t_warehouse_header a
                 WHERE a.status = 1
                   AND a.qty > 0.0001
                   AND a.id_material_fg = ?
                   AND a.batch_no = ?
                   AND a.id_plant = ?
                 ORDER BY a.id_whx_head ASC
            ', [$idMaterial, $batchNo, $idPlant]);

            $totalStock = array_sum(array_column($datHead, 'qty'));
            if (($totalStock - $outQty) < -0.000001) {
                return ['response' => 3, 'message' => 'Insufficient stock balance.'];
            }

            $lenHead = count($datHead);
            $remainingOutQty = $outQty;

            return DB::connection($this->connection)->transaction(function () use ($lenHead, $datHead, $remainingOutQty, $traceNo, $idMaterial, $entryDate, $idPlant, $soNo, $fileName, $user) {
                for ($i = 0; $i < $lenHead; $i++) {
                    if ($remainingOutQty <= 0) break;

                    $idHead = $datHead[$i]->id_whx_head;
                    $fromTraceNo = $datHead[$i]->trace_no;
                    $qty = (float)$datHead[$i]->qty;
                    $totalOutQty = (float)$datHead[$i]->out_qty;
                    $initQty = (float)$datHead[$i]->init_qty;
                    $idWarehouse = $datHead[$i]->id_section;

                    if ($qty <= 0) continue;

                    $balanceAfter = $qty - $remainingOutQty;
                    if ($balanceAfter < 0) {
                        $useQtyWh = $qty;
                        $newBalance = 0;
                        $newTotalOutQty = $initQty;
                        $remainingOutQty = round((float) $remainingOutQty - $qty, 4);
                    } else {
                        $useQtyWh = $remainingOutQty;
                        $newBalance = round((float) $qty - $remainingOutQty, 4);
                        $newTotalOutQty = round((float) $totalOutQty + $remainingOutQty, 4);
                        $remainingOutQty = 0;
                    }

                    // Update Warehouse Header
                    DB::connection($this->connection)->update('
                        UPDATE t_warehouse_header
                           SET qty = ?, out_qty = ?, updated_by = ?
                         WHERE id_whx_head = ?
                    ', [$newBalance, $newTotalOutQty, $user, $idHead]);

                    // Insert Trace Header
                    $idTraceHead = DB::connection($this->connection)->table('t_trace_header')->insertGetId([
                        'from_trace_no' => $fromTraceNo,
                        'to_trace_no' => $traceNo,
                        'id_balance_head' => $idHead,
                        'id_material' => $idMaterial,
                        'entry_date' => $entryDate,
                        'id_sloc' => $idWarehouse,
                        'out_qty' => $useQtyWh,
                        'curr_qtf' => $useQtyWh,
                        'id_plant' => $idPlant,
                        'created_by' => $user,
                    ], 'id_trace_head');

                    // Insert Shipment Header
                    $idShipHead = DB::connection($this->connection)->table('t_shipment_header')->insertGetId([
                        'entry_date' => $entryDate,
                        'from_trace_no' => $fromTraceNo,
                        'trace_no' => $traceNo,
                        'so_no' => $soNo,
                        'id_material_fg' => $idMaterial,
                        'qty' => $useQtyWh,
                        'id_plant' => $idPlant,
                        'doc_url' => $fileName,
                        'created_by' => $user,
                    ], 'id_ship_head');

                    // Log transactions
                    DB::connection($this->connection)->insert('
                        INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)
                    ', ['T_TRACE_HEAD', 'ADD SHIP', 'IDTRACEHEAD: ' . $idTraceHead . 'IDHEAD: ' . $idHead . ' | DATE: ' . $entryDate . ' / OUT_QTY: ' . $useQtyWh, $user]);

                    DB::connection($this->connection)->insert('
                        INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                        VALUES (?, ?, ?, ?)
                    ', ['T_WH_HEAD', 'ADD SHIP', 'IDSHIPHEAD: ' . $idShipHead . 'IDTRACEHEAD: ' . $idTraceHead . ' | DATE: ' . $entryDate . ' / IN_QTY: ' . $useQtyWh, $user]);

                    // Deduct warehouse details
                    $datTail = DB::connection($this->connection)->select('
                        SELECT a.id_whx_tail, a.id_supplier, a.batch_sap, a.qty, a.out_qty, a.init_qty
                          FROM t_warehouse_detail a
                         WHERE a.status = 1
                           AND a.id_whx_head = ?
                         ORDER BY a.id_whx_tail ASC
                    ', [$idHead]);

                    $qtyWhTail = $useQtyWh;

                    foreach ($datTail as $tailRow) {
                        if ($qtyWhTail <= 0) break;

                        $idTail = $tailRow->id_whx_tail;
                        $idSupplier = $tailRow->id_supplier;
                        $batchSap = $tailRow->batch_sap;
                        $qtyTail = (float)$tailRow->qty;
                        $outQtyTail = (float)$tailRow->out_qty;
                        $initQtyTail = (float)$tailRow->init_qty;

                        if ($qtyTail <= 0) continue;

                        $tailBalanceAfter = $qtyTail - $qtyWhTail;
                        if ($tailBalanceAfter < 0) {
                            $useTailQty = $qtyTail;
                            $newTailBalance = 0;
                            $newTailTotalOutQty = $initQtyTail;
                            $qtyWhTail = round((float) $qtyWhTail - $qtyTail, 4);
                        } else {
                            $useTailQty = $qtyWhTail;
                            $newTailBalance = round((float) $qtyTail - $qtyWhTail, 4);
                            $newTailTotalOutQty = round((float) $outQtyTail + $qtyWhTail, 4);
                            $qtyWhTail = 0;
                        }

                        // Update Warehouse Detail
                        DB::connection($this->connection)->update('
                            UPDATE t_warehouse_detail
                               SET qty = ?, out_qty = ?, updated_by = ?
                             WHERE id_whx_tail = ?
                        ', [$newTailBalance, $newTailTotalOutQty, $user, $idTail]);

                        // Insert Trace Detail
                        DB::connection($this->connection)->table('t_trace_detail')->insert([
                            'id_trace_head' => $idTraceHead,
                            'id_balance_tail' => $idTail,
                            'id_supplier' => $idSupplier,
                            'id_material' => $idMaterial,
                            'out_qty' => $useTailQty,
                            'batch_sap' => $batchSap,
                            'id_plant' => $idPlant,
                            'id_sloc' => $idWarehouse,
                            'created_by' => $user,
                        ]);

                        // Insert Shipment Detail
                        DB::connection($this->connection)->table('t_shipment_detail')->insert([
                            'id_ship_head' => $idShipHead,
                            'id_material_fg' => $idMaterial,
                            'id_supplier' => $idSupplier,
                            'batch_sap' => $batchSap,
                            'qty' => $useTailQty,
                            'id_plant' => $idPlant,
                            'created_by' => $user,
                        ]);
                    }

                    // Apply Proportional adjustments if needed
                    $supplierAdjustList = [];
                    foreach ($datTail as $tl) {
                        $consumed = DB::connection($this->connection)->select('
                            SELECT out_qty FROM t_trace_detail
                             WHERE id_trace_head = ? AND id_balance_tail = ?
                             ORDER BY id_trace_tail DESC LIMIT 1
                        ', [$idTraceHead, $tl->id_whx_tail]);

                        if (!empty($consumed) && (float)$consumed[0]->out_qty > 0) {
                            $supplierAdjustList[] = [
                                'id_tail'        => $tl->id_whx_tail,
                                'id_supplier'    => $tl->id_supplier,
                                'batch_sap'      => $tl->batch_sap,
                                'qty'            => (float)$consumed[0]->out_qty,
                            ];
                        }
                    }

                    if (count($supplierAdjustList) > 1) {
                        $tempData = [array_map(function ($x) {
                            return ['qty' => $x['qty']];
                        }, $supplierAdjustList)];

                        $this->adjustQtyToTotal($tempData, $useQtyWh);

                        foreach ($supplierAdjustList as $idx => $rowVal) {
                            $newQty = $tempData[0][$idx]['qty'];

                            DB::connection($this->connection)->update('
                                UPDATE t_warehouse_detail
                                   SET qty = init_qty - ?, out_qty = ?
                                 WHERE id_whx_tail = ?
                            ', [$newQty, $newQty, $rowVal['id_tail']]);

                            DB::connection($this->connection)->update('
                                UPDATE t_trace_detail
                                   SET out_qty = ?
                                 WHERE id_trace_head = ? AND id_balance_tail = ?
                            ', [$newQty, $idTraceHead, $rowVal['id_tail']]);

                            DB::connection($this->connection)->update('
                                UPDATE t_shipment_detail
                                   SET qty = ?
                                 WHERE id_ship_head = ? AND id_material_fg = ? AND id_supplier = ? AND batch_sap = ?
                            ', [$newQty, $idShipHead, $idMaterial, $rowVal['id_supplier'], $rowVal['batch_sap']]);
                        }
                    }
                }

                return ['response' => 1, 'message' => 'Shipment stored successfully.'];
            });

        } catch (\Throwable $e) {
            return ['response' => 0, 'message' => 'Store failed: ' . $e->getMessage()];
        }
    }

    public function cancel(string $user, array $data): array
    {
        $traceNo = $data['traceNo'] ?? null;
        if (!$traceNo) {
            return ['response' => 0, 'message' => 'Trace number is required.'];
        }

        return app(\Modules\Shared\Services\TransactionCancellationService::class)
            ->cancelShipment((string) $traceNo, $user);
    }

    public function updateSo(string $user, array $data): array
    {
        try {
            $id = $data['id'];
            $soNo = $data['soNo'];

            DB::connection($this->connection)->update('
                UPDATE t_shipment_header
                   SET so_no = ?,
                       updated_by = ?
                 WHERE id_ship_head = ?
            ', [$soNo, $user, $id]);

            return ['response' => 1, 'message' => 'SO updated successfully.'];
        } catch (\Throwable $e) {
            return ['response' => 0, 'message' => 'Failed to update SO: ' . $e->getMessage()];
        }
    }

    private function adjustQtyToTotal(&$dataPerHead, $targetTotal): void
    {
        // $dataPerHead is [0 => [{qty: ...}, ...]] â€” unwrap the outer array
        $flat = $dataPerHead[0] ?? [];
        $dataPerHead[0] = QuantityDistributionHelper::adjustToTotal($flat, (float) $targetTotal, 'qty');
    }

    public function generateTraceNo(int $materialId, int $plantId): string
    {
        return $this->generateTraceNumberForMaterial(
            '5',
            $materialId,
            $plantId,
            't_trace_header',
            'to_trace_no',
            'id_trace_head'
        );
    }
}
