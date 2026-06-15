<?php declare(strict_types=1);

namespace Modules\TsShipment\Repositories;

use Modules\TsShipment\Repositories\Contracts\ShipmentRepositoryInterface;
use Modules\Shared\Helpers\QuantityDistributionHelper;
use Modules\Shared\Services\PeriodLockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * @todo Technical Debt: This class is 834 lines (limit: 200). Requires refactoring into smaller, focused classes.
 * - Split into: ShipmentQueryRepository (getDtShipEntry, getActiveFgProduct, getWipMaterialByFgProduct, getActiveBatchProduct, lookup methods),
 *   ShipmentTransactionRepository (store, cancel, updateSo, adjustQtyToTotal, generateTraceNo),
 *   ShipmentExternalIntegration (getDatShipment, getDatSoAllocation, getShipmentBatchPackaging, getPreparationRecord, label lookups via SAP/OEE)
 */
class EloquentShipmentRepository implements ShipmentRepositoryInterface
{
    protected string $connection = 'eudr_ts';

    public function getDtShipEntry(): Collection
    {
        $results = DB::connection($this->connection)->select('
            SELECT a.id_ship_head, a.entry_date, CONCAT(CAST(dd.from_trace_no AS CHAR), " >>> ", CAST(dd.trace_no AS CHAR) ) AS fromto_trace_no,
                   a.so_no, a.id_material_fg, FORMAT(ROUND(f.qty,3), 3) AS qty , a.status, a.created_by, a.created_at, a.updated_by, a.updated_at,
                   IF(SUBSTRING(a.from_trace_no,1,1) < 3, g.`description`, c.`description`) AS material,
                   f.id_trace_head, f.id_balance_head, a.trace_no, a.from_trace_no, f.batch_no,
                   GROUP_CONCAT(DISTINCT CONCAT(d.description, " / ", d.batch_sap, " / Qty: ", FORMAT(d.qty,3), " MT") SEPARATOR " | ") AS supplier,
                   FORMAT(ROUND(dd.qty,3),3) AS balance_supplier, a.doc_url,
                   CASE
                      WHEN LENGTH(CAST(a.trace_no AS CHAR)) >= 14 THEN
                         CASE SUBSTRING(a.trace_no, 11, 2)
                            WHEN "01" THEN "EOMB"
                            WHEN "02" THEN "EOB1"
                            WHEN "03" THEN "EOB2"
                            WHEN "05" THEN "EOB5"
                            WHEN "07" THEN "EOB3"
                            ELSE CASE a.id_plant
                                WHEN "1002" THEN "EOB1"
                                WHEN "1003" THEN "EOB2"
                                WHEN "1007" THEN "EOB3"
                                WHEN "1001" THEN "EOMB"
                                ELSE COALESCE(a.id_plant, "EOB1")
                            END
                         END
                      ELSE CASE a.id_plant
                          WHEN "1002" THEN "EOB1"
                          WHEN "1003" THEN "EOB2"
                          WHEN "1007" THEN "EOB3"
                          WHEN "1001" THEN "EOMB"
                          ELSE COALESCE(a.id_plant, "EOB1")
                      END
                   END AS plant_name,
                   CASE
                      WHEN a.trace_no = (SELECT to_trace_no
                                           FROM t_trace_header
                                          WHERE SUBSTRING(to_trace_no, 1, 1) = 5
                                            AND `status` = 1
                                          ORDER BY id_trace_head DESC LIMIT 1) THEN 1
                      ELSE NULL
                   END AS is_last_row,
                   CASE
                      WHEN a.trace_no = (SELECT from_trace_no
                                           FROM t_trace_header
                                          WHERE SUBSTRING(from_trace_no, 8, 3) = "001"
                                            AND SUBSTRING(from_trace_no, 1, 1) = 4
                                            AND `status` = 1
                                          ORDER BY from_trace_no DESC LIMIT 1) THEN 1
                      ELSE NULL
                   END AS next_process
              FROM t_shipment_header a
              LEFT JOIN m_material_pck c ON a.id_material_fg = c.id_materialpck
              LEFT JOIN (SELECT dd.trace_no, e.description, d.batch_sap, SUM(ROUND(d.qty,4)) AS qty
                           FROM t_shipment_header dd
                           LEFT JOIN t_shipment_detail d ON dd.id_ship_head = d.id_ship_head
                           LEFT JOIN m_supplier e ON e.id_supplier = d.id_supplier
                          WHERE d.status = 1 AND dd.status = 1
                          GROUP BY dd.trace_no, d.batch_sap
                        ) d ON a.trace_no = d.trace_no
              LEFT JOIN (SELECT dd.trace_no, SUM(ROUND(ee.qty,4)) AS qty, GROUP_CONCAT(DISTINCT CAST(dd.from_trace_no AS CHAR) SEPARATOR " + ") AS from_trace_no
                           FROM t_shipment_header dd
                           LEFT JOIN t_shipment_detail ee ON dd.id_ship_head = ee.id_ship_head
                          WHERE dd.status = 1
                          GROUP BY dd.trace_no
                        ) dd ON a.trace_no = dd.trace_no
              LEFT JOIN (SELECT f.to_trace_no, f.id_trace_head, f.id_balance_head, ff.batch_no,
                                SUM(ROUND(f.out_qty,4)) AS qty
                           FROM t_trace_header f
                           LEFT JOIN t_warehouse_header ff ON f.id_balance_head = ff.id_whx_head AND ff.status = 1
                          WHERE f.status = 1
                          GROUP BY f.to_trace_no
                          ) f ON f.to_trace_no = a.trace_no
              LEFT JOIN m_material g ON g.id_material = a.id_material_fg
             WHERE a.status = 1
             GROUP BY a.trace_no
             ORDER BY a.entry_date DESC, id_ship_head DESC
        ');

        return collect($results);
    }

    public function getActiveFgProduct(): Collection
    {
        $results = DB::connection($this->connection)->select('
            SELECT CONCAT(UPPER(a.description), " (", a.code, ")") AS material,
                   CONCAT("PCK|", a.id_materialpck) AS id_material
              FROM m_material_pck a
             WHERE a.status = 1
             ORDER BY material ASC
        ');
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
            $results = DB::connection($this->connection)->select('
                SELECT FORMAT(IFNULL(SUM(a.qty),0), 3) AS balance,
                       CONCAT(b.description, " (", b.code, ") || Balance : ", FORMAT(IFNULL(SUM(a.qty),0),3), " MT") AS wip_material
                  FROM m_material b
                  LEFT JOIN t_balance_header a ON a.id_material = b.id_material AND a.status = 1 AND a.id_plant = ?
                 WHERE b.id_material = ?
                   AND b.status = 1
            ', [$idPlant, $idMaterial]);
        } else {
            // Start from m_material_pck so description always returns even with zero balance
            $results = DB::connection($this->connection)->select('
                SELECT FORMAT(IFNULL(SUM(a.qty),0), 3) AS balance,
                       CONCAT(b.description, " (", b.code, ") || Balance : ", FORMAT(IFNULL(SUM(a.qty),0),3), " MT") AS wip_material
                  FROM m_material_pck b
                  LEFT JOIN t_warehouse_header a ON a.id_material_fg = b.id_materialpck AND a.status = 1 AND a.id_plant = ?
                 WHERE b.id_materialpck = ?
                   AND b.status = 1
            ', [$idPlant, $idMaterial]);
        }

        return collect($results);
    }

    public function getActiveBatchProduct(array $data): Collection
    {
        $idMaterialPck = $data['idMaterial'] ?? '';
        $parts = explode('|', $idMaterialPck);
        $idMaterial = $parts[1] ?? 0;
        $idPlant = $data['id_plant'] ?? null;

        $results = DB::connection($this->connection)->select('
            SELECT a.batch_no, CONCAT(a.batch_no, " | Qty : ", FORMAT(b.qty,3)) AS `description`
              FROM t_warehouse_header a
              LEFT JOIN (SELECT b.id_material_fg, b.batch_no, SUM(b.qty) AS qty
                           FROM t_warehouse_header b
                          WHERE b.status = 1
                          GROUP BY b.id_material_fg, b.batch_no) b ON a.batch_no = b.batch_no AND a.id_material_fg = b.id_material_fg
             WHERE a.id_material_fg = ?
               AND a.`status` = 1
               AND a.qty > "0.000001"
               AND a.id_plant = ?
             GROUP BY a.batch_no
        ', [$idMaterial, $idPlant]);

        return collect($results);
    }

    public function getShipmentBatchPackaging(array $data): Collection
    {
        $batchNo = trim((string) ($data['batchNo'] ?? ''));

        if ($batchNo === '') {
            return collect([]);
        }

        try {
            $results = DB::connection($this->connection)->select('
                SELECT a.entry_date, a.tf_number, a.batch_no, a.spec, a.production_order,
                       a.lot_qty, a.qty, a.product, b.id_process, c.id_packing, d.id_pallet,
                       CONCAT(b.id_process, " , ", b.code, " , ", b.description) AS process,
                       CONCAT(c.code, " , ", c.description) AS packing,
                       CONCAT(d.code, " , ", d.description) AS pallet,
                       e.url_link AS label_link, f.url_link AS splabel_link,
                       g.url_link AS csmark_link, a.id_special_label, a.id_customer_mark,
                       CONCAT(a.id_sloc, ",", a.tf_number) AS id_tank, a.csmark_isCheck, a.splabel_isCheck,
                       CONCAT(a.id_product, ",", a.product) AS id_product, a.long_text,
                       a.approved_by, a.approved_at,
                       a.created_by, a.id_prdexecution, a.created_at,
                       a.status, e.id_label, h.id_customer, CONCAT(h.code, " , ", h.description) AS customer,
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

        try {
            $results = DB::connection($this->connection)->select('
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

        $results = DB::connection($this->connection)->select('
            SELECT a.url_link
              FROM oee_756.m_label a
             WHERE a.status = "1"
               AND a.id_label = ?
        ', [$label]);

        return collect($results);
    }

    public function getSpecialLabel(array $data): Collection
    {
        $label = $data['label'] ?? '';

        $results = DB::connection($this->connection)->select('
            SELECT a.url_link
              FROM oee_756.m_special_label a
             WHERE a.status = "1"
               AND a.id_label = ?
        ', [$label]);

        return collect($results);
    }

    public function getCustomerMark(array $data): Collection
    {
        $label = $data['label'] ?? '';

        $results = DB::connection($this->connection)->select('
            SELECT a.url_link
              FROM oee_756.m_customer_mark a
             WHERE a.status = "1"
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
            $response = Http::timeout(30)->get($eobUrl);
            if ($response->failed()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
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
            $response = Http::timeout(30)->get($eobUrl);
            if ($response->failed()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
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
            // Create shipment batch trace no
            $datPckBatch = DB::connection($this->connection)->select('
                SELECT a.pck_batch
                  FROM (SELECT a.to_trace_no + 1 AS pck_batch
                          FROM t_trace_header a
                         WHERE SUBSTRING(a.to_trace_no,1,10) = CONCAT(5, DATE_FORMAT(CURDATE(), "%y%m%d"), ?)
                           AND a.status = 1
                         ORDER BY a.id_trace_head DESC
                         LIMIT 1 ) a
                UNION ALL
                SELECT CONCAT(5, DATE_FORMAT(CURDATE(), "%y%m%d"), ? , LPAD(RIGHT(?, 2), 2, "0"), "01") AS pck_batch
                 LIMIT 1
            ', [$shID, $shID, $idPlant]);

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
                        $remainingOutQty = round($remainingOutQty - $qty, 4);
                    } else {
                        $useQtyWh = $remainingOutQty;
                        $newBalance = round($qty - $remainingOutQty, 4);
                        $newTotalOutQty = round($totalOutQty + $remainingOutQty, 4);
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
                    ]);

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
                    ]);

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
                            $qtyWhTail = round($qtyWhTail - $qtyTail, 4);
                        } else {
                            $useTailQty = $qtyWhTail;
                            $newTailBalance = round($qtyTail - $qtyWhTail, 4);
                            $newTailTotalOutQty = round($outQtyTail + $qtyWhTail, 4);
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
                   AND `status` = 1
            ', [$traceNo]);

            if (empty($entryDate)) {
                return ['response' => 0, 'message' => 'Active trace not found.'];
            }

            if (PeriodLockService::isLocked($entryDate[0]->entry_date)) {
                return ['response' => 99, 'message' => 'Period is locked.'];
            }

            $datTraceHead = DB::connection($this->connection)->select('
                SELECT from_trace_no, id_balance_head, out_qty, id_trace_head
                  FROM t_trace_header
                 WHERE to_trace_no = ?
                   AND `status` = 1
            ', [$traceNo]);

            if (count($datTraceHead) === 0) {
                return ['response' => 4, 'message' => 'Trace records not found.'];
            }

            $fromTraceNo = $datTraceHead[0]->from_trace_no;
            preg_match('/\d/', $fromTraceNo, $matches);
            $origin = (int)($matches[0] ?? 4);

            return DB::connection($this->connection)->transaction(function () use ($datTraceHead, $traceNo, $origin, $user) {
                foreach ($datTraceHead as $headRow) {
                    $idTraceHead = $headRow->id_trace_head;
                    $idHead = $headRow->id_balance_head;
                    $outQtyShip = (float)$headRow->out_qty;
                    $fromTraceNo = $headRow->from_trace_no;

                    $datTraceTail = DB::connection($this->connection)->select('
                        SELECT id_balance_tail, out_qty
                          FROM t_trace_detail
                         WHERE id_trace_head = ?
                           AND `status` = 1
                    ', [$idTraceHead]);

                    if ($origin == 4) {
                        // Retrieve current qty in warehouse
                        $datWhxHead = DB::connection($this->connection)->select('
                            SELECT qty, out_qty FROM t_warehouse_header WHERE id_whx_head = ? AND `status` = 1
                        ', [$idHead]);

                        if (!empty($datWhxHead)) {
                            $whxBalQty = (float)$datWhxHead[0]->qty;
                            $whxOutQty = (float)$datWhxHead[0]->out_qty;

                            DB::connection($this->connection)->update('
                                UPDATE t_warehouse_header SET qty = ?, out_qty = ?, updated_by = ? WHERE id_whx_head = ? AND `status` = 1
                            ', [$whxBalQty + $outQtyShip, $whxOutQty - $outQtyShip, $user, $idHead]);

                            // Log
                            DB::connection($this->connection)->insert('
                                INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)
                            ', ['T_WAREHOUSE_HEAD', 'UPDATE', 'IDHEAD: ' . $idHead . ' | QTY: ' . $whxBalQty . ' >>> ' . ($whxBalQty + $outQtyShip), $user]);
                        }

                        // Update shipment status
                        DB::connection($this->connection)->update('UPDATE t_trace_header SET `status` = 0, updated_by = ? WHERE id_trace_head = ?', [$user, $idTraceHead]);
                        
                        DB::connection($this->connection)->update('
                            UPDATE t_shipment_header SET `status` = 0, updated_by = ?
                             WHERE from_trace_no = ? AND trace_no = ? AND qty = ?
                        ', [$user, $fromTraceNo, $traceNo, $outQtyShip]);

                        $idShipHead = DB::connection($this->connection)->select('
                            SELECT id_ship_head FROM t_shipment_header
                             WHERE from_trace_no = ? AND trace_no = ? AND qty = ?
                        ', [$fromTraceNo, $traceNo, $outQtyShip]);

                        if (!empty($idShipHead)) {
                            DB::connection($this->connection)->update('
                                UPDATE t_shipment_detail SET `status` = 0, updated_by = ? WHERE id_ship_head = ?
                            ', [$user, $idShipHead[0]->id_ship_head]);
                        }

                        // Loop details
                        foreach ($datTraceTail as $tailRow) {
                            $idTail = $tailRow->id_balance_tail;
                            $outQtyShipTail = (float)$tailRow->out_qty;

                            $datWhxTail = DB::connection($this->connection)->select('
                                SELECT qty, out_qty FROM t_warehouse_detail WHERE id_whx_tail = ?
                            ', [$idTail]);

                            if (!empty($datWhxTail)) {
                                $whxBalQtyTail = (float)$datWhxTail[0]->qty;
                                $whxOutQtyTail = (float)$datWhxTail[0]->out_qty;

                                DB::connection($this->connection)->update('
                                    UPDATE t_warehouse_detail SET qty = ?, out_qty = ?, updated_by = ? WHERE id_whx_tail = ?
                                ', [$whxBalQtyTail + $outQtyShipTail, $whxOutQtyTail - $outQtyShipTail, $user, $idTail]);

                                DB::connection($this->connection)->update('UPDATE t_trace_detail SET `status` = 0, updated_by = ? WHERE id_trace_tail = ?', [$user, $idTail]);

                                // Log
                                DB::connection($this->connection)->insert('
                                    INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)
                                ', ['T_WAREHOUSE_DETAIL', 'UPDATE', 'IDTAIL: ' . $idTail . ' | QTY: ' . $whxBalQtyTail . ' >>> ' . ($whxBalQtyTail + $outQtyShipTail), $user]);
                            }
                        }

                    } elseif ($origin == 1) {
                        // Retrieve current qty in WIP
                        $datWipHead = DB::connection($this->connection)->select('
                            SELECT qty, out_qty FROM t_balance_header WHERE id_balance_head = ? AND `status` = 1
                        ', [$idHead]);

                        if (!empty($datWipHead)) {
                            $wipBalQty = (float)$datWipHead[0]->qty;
                            $wipOutQty = (float)$datWipHead[0]->out_qty;

                            DB::connection($this->connection)->update('
                                UPDATE t_balance_header SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_head = ? AND `status` = 1
                            ', [$wipBalQty + $outQtyShip, $wipOutQty - $outQtyShip, $user, $idHead]);

                            // Log
                            DB::connection($this->connection)->insert('
                                INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                                VALUES (?, ?, ?, ?)
                            ', ['T_BALANCE_HEAD', 'UPDATE', 'IDHEAD: ' . $idHead . ' | QTY: ' . $wipBalQty . ' >>> ' . ($wipBalQty + $outQtyShip), $user]);
                        }

                        // Update status
                        DB::connection($this->connection)->update('UPDATE t_trace_header SET `status` = 0, updated_by = ? WHERE id_trace_head = ?', [$user, $idTraceHead]);
                        
                        DB::connection($this->connection)->update('
                            UPDATE t_shipment_header SET `status` = 0, updated_by = ?
                             WHERE from_trace_no = ? AND trace_no = ? AND qty = ?
                        ', [$user, $fromTraceNo, $traceNo, $outQtyShip]);

                        $idShipHead = DB::connection($this->connection)->select('
                            SELECT id_ship_head FROM t_shipment_header
                             WHERE from_trace_no = ? AND trace_no = ? AND qty = ?
                        ', [$fromTraceNo, $traceNo, $outQtyShip]);

                        if (!empty($idShipHead)) {
                            DB::connection($this->connection)->update('
                                UPDATE t_shipment_detail SET `status` = 0, updated_by = ? WHERE id_ship_head = ?
                            ', [$user, $idShipHead[0]->id_ship_head]);
                        }

                        // Loop details
                        foreach ($datTraceTail as $tailRow) {
                            $idTail = $tailRow->id_balance_tail;
                            $outQtyShipTail = (float)$tailRow->out_qty;

                            $datWhxTail = DB::connection($this->connection)->select('
                                SELECT qty, out_qty FROM t_balance_detail WHERE id_balance_tail = ?
                            ', [$idTail]);

                            if (!empty($datWhxTail)) {
                                $whxBalQtyTail = (float)$datWhxTail[0]->qty;
                                $whxOutQtyTail = (float)$datWhxTail[0]->out_qty;

                                DB::connection($this->connection)->update('
                                    UPDATE t_balance_detail SET qty = ?, out_qty = ?, updated_by = ? WHERE id_balance_tail = ?
                                ', [$whxBalQtyTail + $outQtyShipTail, $whxOutQtyTail - $outQtyShipTail, $user, $idTail]);

                                DB::connection($this->connection)->update('UPDATE t_trace_detail SET `status` = 0, updated_by = ? WHERE id_trace_tail = ?', [$user, $idTail]);

                                // Log
                                DB::connection($this->connection)->insert('
                                    INSERT INTO log_transactions (log_module, log_type, log_description, created_by)
                                    VALUES (?, ?, ?, ?)
                                ', ['T_BALANCE_DETAIL', 'UPDATE', 'IDTAIL: ' . $idTail . ' | QTY: ' . $whxBalQtyTail . ' >>> ' . ($whxBalQtyTail + $outQtyShipTail), $user]);
                            }
                        }
                    }
                }

                return ['response' => 1, 'message' => 'Shipment cancelled successfully.'];
            });

        } catch (Exception $e) {
            return ['response' => 0, 'message' => 'Cancellation failed: ' . $e->getMessage()];
        }
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
        } catch (Exception $e) {
            return ['response' => 0, 'message' => 'Failed to update SO: ' . $e->getMessage()];
        }
    }

    private function adjustQtyToTotal(&$dataPerHead, $targetTotal): void
    {
        // $dataPerHead is [0 => [{qty: ...}, ...]] — unwrap the outer array
        $flat = $dataPerHead[0] ?? [];
        $dataPerHead[0] = QuantityDistributionHelper::adjustToTotal($flat, (float) $targetTotal, 'qty');
    }

    public function generateTraceNo(int $plantId): string
    {
        $shID = '001';
        $plantStr = str_pad((string)$plantId, 2, "0", STR_PAD_LEFT);
        
        $datPckBatch = DB::connection($this->connection)->select('
            SELECT a.pck_batch
              FROM (SELECT CONCAT(5, DATE_FORMAT(CURDATE(), "%y%m%d"), ?, ?, LPAD(SUBSTRING(a.to_trace_no,13,2) + 1,2,0)) AS pck_batch
                      FROM t_trace_header a
                     WHERE SUBSTRING(a.to_trace_no,1,12) = CONCAT(5, DATE_FORMAT(CURDATE(), "%y%m%d"), ?, ?)
                       AND a.status = 1
                     ORDER BY a.id_trace_head DESC
                     LIMIT 1 ) a
             UNION ALL
             SELECT CONCAT(5, DATE_FORMAT(CURDATE(), "%y%m%d"), ?, ?, "01") AS pck_batch
              LIMIT 1
        ', [$shID, $plantStr, $shID, $plantStr, $shID, $plantStr]);

        return $datPckBatch[0]->pck_batch ?? '';
    }
}
