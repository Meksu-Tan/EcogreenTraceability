<?php
declare(strict_types=1);
namespace Modules\TsRaw\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\TsRaw\Models\TraceHeader;
use Modules\Material\Models\Material;
use Modules\Shared\Helpers\Rundown;
use Modules\Shared\Helpers\Feed;
use Exception;
use Modules\Shared\Traits\DbCompatTrait;

trait RmEntryTransactionTrait
{
    use DbCompatTrait;
    public function checkStockSynchronization(string $entryNo, int $materialId = null): array
    {
        $tempQuery = 'SELECT COUNT(*) as temp_count, SUM(qty) as temp_qty FROM t_balance_temporary WHERE entry_no = ? AND status = 1';
        $tempParams = [$entryNo];

        if ($materialId) {
            $tempQuery .= ' AND id_material = ?';
            $tempParams[] = $materialId;
        }

        $tempData = DB::connection('eudr_ts')->select($tempQuery, $tempParams);
        $tempCount = $tempData[0]->temp_count ?? 0;
        $tempQty = $tempData[0]->temp_qty ?? 0;

        $balanceCheck = DB::connection('eudr_ts')->select(
            'SELECT COUNT(*) as balance_count, SUM(qty) as balance_qty FROM t_balance_header WHERE trace_no = ? AND status = 1',
            [$entryNo]
        );
        $balanceCount = $balanceCheck[0]->balance_count ?? 0;
        $balanceQty = $balanceCheck[0]->balance_qty ?? 0;

        return [
            'has_temporary_data' => $tempCount > 0,
            'temporary_quantity' => floatval($tempQty),
            'has_balance_data' => $balanceCount > 0,
            'balance_quantity' => floatval($balanceQty),
            'is_synchronized' => $balanceCount > 0 && $tempCount == 0,
            'status' => $balanceCount > 0 ? 'processed' : ($tempCount > 0 ? 'pending' : 'no_data'),
            'message' => $balanceCount > 0 ? 'RM Entry has been processed and stock is synchronized' :
                        ($tempCount > 0 ? 'RM Entry has temporary data but not yet processed' : 'No data found for this entry')
        ];
    }

    public function debugFifoStock(array $params): array
    {
        return Feed::debugStock($params);
    }

    public function verifySeparateEntries(int $materialId, int $tankId, int $plantId, int $hoursBack = 24): array
    {
        $since = now()->subHours($hoursBack);

        $jsonCond = 'id_sloc = CAST(? AS TEXT)';

        $entries = DB::connection('eudr_ts')->select(
            "SELECT id_balance_head, trace_no, qty, init_qty, entry_date, created_at
               FROM t_balance_header
              WHERE id_material = ?
                AND {$jsonCond}
                AND id_plant = ?
                AND status = 1
                AND created_at >= ?
              ORDER BY id_balance_head ASC",
            [$materialId, $tankId, $plantId, $since]
        );

        return [
            'total_entries' => count($entries),
            'entries' => $entries,
            'total_qty' => array_sum(array_column($entries, 'qty')),
            'separate_entries_created' => count($entries) > 1,
            'parameters' => [
                'id_material' => $materialId,
                'tf_number' => $tankId,
                'id_plant' => $plantId,
                'hours_back' => $hoursBack
            ]
        ];
    }

    public function updateRmEntry(int $id, array $data, string $user): array
    {
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $balanceHeader = DB::connection('eudr_ts')->table('t_balance_header')
                ->where('id_balance_head', $id)
                ->where('status', 1)
                ->first();

            if (!$balanceHeader) {
                throw new Exception('RM Entry not found');
            }

            if (isset($data['material_document']) || isset($data['po_so'])) {
                $updateData = [];
                if (isset($data['material_document'])) {
                    $updateData['material_document'] = $data['material_document'];
                }
                if (isset($data['po_so'])) {
                    $updateData['po_so'] = $data['po_so'];
                }
                $updateData['updated_by'] = $user;

                DB::connection('eudr_ts')->table('t_balance_header')
                    ->where('id_balance_head', $id)
                    ->update($updateData);
            }

            if (isset($data['id_sloc'])) {
                $slocVal = null;
                if (!empty($data['id_sloc'])) {
                    if (is_array($data['id_sloc'])) {
                        $slocVal = json_encode(array_map('strval', array_values($data['id_sloc'])));
                    } else {
                        $decoded = json_decode($data['id_sloc'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $slocVal = json_encode(array_map('strval', array_values($decoded)));
                        } else {
                            $slocVal = json_encode([(string)$data['id_sloc']]);
                        }
                    }
                }
                DB::connection('eudr_ts')->table('t_balance_header')
                    ->where('id_balance_head', $id)
                    ->update([
                        'id_sloc' => $slocVal,
                        'updated_by' => $user
                    ]);
            }

            DB::connection('eudr_ts')->commit();
            return ['success' => true, 'id' => $id];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            throw $e;
        }
    }

    public function saveRmEntry(array $data, string $user): array
    {
        $firstSlocId = null;
        if (!empty($data['id_sloc'])) {
            if (is_array($data['id_sloc'])) {
                $firstSlocId = $data['id_sloc'][0] ?? null;
            } else {
                $decoded = json_decode($data['id_sloc'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $firstSlocId = $decoded[0] ?? null;
                } else {
                    $firstSlocId = $data['id_sloc'];
                }
            }
        }
        $slocPlant = null;
        if ($firstSlocId) {
            $q = DB::connection('eudr_ts')->table('m_sloc');
            if (is_numeric($firstSlocId)) {
                $q->where('id_sloc', (int) $firstSlocId);
            } else {
                $q->where('description', $firstSlocId)->orWhere('code_3', $firstSlocId);
            }
            $slocPlant = $q->value('id_plant');
        }
        $data['id_plant'] = $this->resolvePlantCode($slocPlant ?: ($data['id_plant'] ?? 0));

        DB::connection('eudr_ts')->beginTransaction();

        try {
            $entry_no = $data['rm_number'];
            $qty = floatval($data['total_qty']);

            $dat = $this->getTempData($entry_no);

            $supplierRows = [];
            foreach ($dat as $row) {
                if ($row->qty_tail <= 0) continue;
                if (empty($row->id_supplier)) continue;
                $supplierRows[] = [
                    'id_supplier' => $row->id_supplier,
                    'id_manufacturer' => $row->id_manufacturer ?? null,
                    'batch_sap' => $row->batch_sap,
                    'rundownSupplier' => round((float)$row->qty_tail, 4),
                ];
            }

            if (empty($supplierRows)) {
                throw new Exception('No supplier data found for this entry');
            }

            Rundown::adjustRundownToTotal($supplierRows, $qty);

            $slocVal = null;
            if (!empty($data['id_sloc'])) {
                if (is_array($data['id_sloc'])) {
                    $slocVal = json_encode(array_map('strval', array_values($data['id_sloc'])));
                } else {
                    $decoded = json_decode($data['id_sloc'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $slocVal = json_encode(array_map('strval', array_values($decoded)));
                    } else {
                        $slocVal = json_encode([(string)$data['id_sloc']]);
                    }
                }
            }

            $rundownResult = Rundown::generalRundown([
                'user' => $user,
                'entry_date' => $data['entry_date'],
                'from_trace_no' => null,
                'trace_no' => $entry_no,
                'id_material' => $data['id_material'],
                'id_sloc' => $slocVal,
                'id_sloc_tail' => null,
                'in_qty' => $qty,
                'last_qtf' => 0,
                'curr_qtf' => $qty,
                'id_plant' => $data['id_plant'],
                'supplier_rows' => $supplierRows,
            ]);

            if ($rundownResult['response'] != 1) {
                throw new Exception('Rundown failed');
            }

            $idTraceHead = $rundownResult['id_trace_head'];

            if (!empty($data['material_document'])) {
                app(\Modules\Shared\Services\TransactionCoreService::class)
                    ->createMaterialDocument($user, $idTraceHead, $data['material_document'], 'ADD');
            }

            $this->clearTempData($entry_no, $user);
            $this->logTransaction('RM_ENTRY', 'ADD', 'ID: ' . $rundownResult['id_balance_head'] . ' | Trace No: ' . $entry_no, $user);

            DB::connection('eudr_ts')->commit();
            return ['success' => true, 'id' => $rundownResult['id_balance_head']];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            throw $e;
        }
    }

    public function saveRmTrfEntry(array $data, string $user): array
    {
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $entry_no = $data['entry_no'];
            $curr_entryDate = $data['entry_date'];
            $id_slocSource = is_array($data['source_tank']) ? $data['source_tank'] : [$data['source_tank']];
            $id_sloc = is_array($data['trf_tank']) ? $data['trf_tank'] : [$data['trf_tank']];
            $materialDoc = $data['material_document'] ?? null;
            $idPlant = $this->resolvePlantCode($data['id_plant'] ?? 0);

            $id_slocSource_json = json_encode(array_map('strval', array_values($id_slocSource)));
            $id_sloc_json = json_encode(array_map('strval', array_values($id_sloc)));

            $srcTankRec = DB::connection('eudr_ts')->table('m_sloc')
                ->select('tf_number AS code', 'description', 'id_plant', 'plant_name')
                ->whereIn('id_sloc', $id_slocSource)
                ->where('status', 1)
                ->get();
            $tgtTankRec = DB::connection('eudr_ts')->table('m_sloc')
                ->select('tf_number AS code', 'description', 'id_plant', 'plant_name')
                ->whereIn('id_sloc', $id_sloc)
                ->where('status', 1)
                ->get();

            if ($srcTankRec->isEmpty() || $tgtTankRec->isEmpty()) {
                throw new Exception('Invalid tank selection');
            }

            $isStorageTank = false;
            foreach ($srcTankRec as $rec) {
                if (str_contains(strtoupper($rec->description), 'STORAGE')) {
                    $isStorageTank = true;
                    break;
                }
            }

            $srcPlant = !empty($srcTankRec->first()?->id_plant) ? $this->resolvePlantCode($srcTankRec->first()->id_plant) : $idPlant;
            $tgtPlant = !empty($tgtTankRec->first()?->id_plant) ? $this->resolvePlantCode($tgtTankRec->first()->id_plant) : $idPlant;
            $balancePlant = $srcPlant;

            $datTempMaterial = $this->getTempData($entry_no);
            if (empty($datTempMaterial)) {
                throw new Exception('No temporary material data found');
            }

            $batch_entryDate = substr($entry_no, 1, 6);
            $batch_idPlant = substr($entry_no, 10, 2);
            $batch_sequence = (int) substr($entry_no, -2);

            foreach ($datTempMaterial as $row) {
                $id_material = $row->id_material;
                $out_qty = floatval($row->qty_tail);

                $feedParams = [
                    'id_material' => $id_material,
                    'id_sloc' => $id_slocSource_json,
                    'id_sloc_tail' => null,
                    'balance_plant' => $balancePlant,
                    'trace_prefixes' => ['1'],
                    'tank_matching' => 'flexible',
                ];

                $availableQty = Feed::getAvailableQty($feedParams);
                if (round($availableQty, 4) < round($out_qty, 4)) {
                    $material = Material::find($id_material);
                    $matLabel = $material ? ($material->code . ' :: ' . $material->description) : (string) $id_material;

                    $tempCheck = DB::connection('eudr_ts')->select(
                        'SELECT COUNT(*) as count FROM t_balance_temporary WHERE entry_no = ? AND status = 1 AND id_material = ? AND qty > 0',
                        [$entry_no, $id_material]
                    );

                    $slocNames = $srcTankRec->map(function($tank) {
                        return $tank->description ?: ($tank->plant_name ? ($tank->plant_name . ' - ' . $tank->code) : $tank->code);
                    })->filter()->implode(', ');
                    if (empty($slocNames)) {
                        $slocNames = implode(', ', $id_slocSource);
                    }

                    if ($tempCheck[0]->count > 0) {
                        throw new Exception(
                             'Stock synchronization issue detected in Sloc (' . $slocNames . '). Material ' . $matLabel .
                            ' has temporary data but stock not updated. Available: ' . number_format($availableQty, 3) .
                            ' MT, requested: ' . number_format($out_qty, 3) . ' MT. Please complete RM Entry process first.'
                        );
                    }

                    throw new Exception(
                        'Insufficient stock in Sloc (' . $slocNames . ') for ' . $matLabel .
                        '. Available: ' . number_format($availableQty, 3) .
                        ' MT, requested: ' . number_format($out_qty, 3) . ' MT (FIFO sloc/sub-sloc/plant).'
                    );
                }

                if (substr($entry_no, 7, 3) === '000') {
                    $entryTrfNo_in = substr_replace($entry_no, '1', 9, 1);
                } else {
                    $entryTrfNo_in = substr_replace($entry_no, '0', 8, 1);
                }

                $feedResult = Feed::generalFeed(array_merge($feedParams, [
                    'user' => $user,
                    'entry_date' => $curr_entryDate,
                    'id_plant' => $srcPlant,
                    'qty' => $out_qty,
                    'to_trace_no' => $this->traceNoToInt($entryTrfNo_in),
                    'tank_matching' => 'flexible',
                ]));

                if ($feedResult['response'] != 1) {
                    throw new Exception('Feed failed');
                }

                 $supplierRows = DB::connection('eudr_ts')->select(
                    'SELECT id_supplier, id_manufacturer, batch_sap, SUM(out_qty) AS rundown_supplier
                       FROM t_trace_detail
                      WHERE status = 1
                        AND id_trace_head IN (
                            SELECT id_trace_head
                              FROM t_trace_header
                             WHERE status = 1
                               AND to_trace_no = ?
                        )
                      GROUP BY id_supplier, id_manufacturer, batch_sap',
                    [$this->traceNoToInt($entryTrfNo_in)]
                );

                if (empty($supplierRows)) {
                    throw new Exception(
                        'Supplier breakdown is empty for transfer ' . number_format($out_qty, 3) .
                        ' MT. Ensure RM entry has active supplier data.'
                    );
                }

                $supplierRowsFormatted = [];
                foreach ($supplierRows as $r) {
                    $supplierRowsFormatted[] = [
                        'id_supplier'     => $r->id_supplier,
                        'id_manufacturer' => $r->id_manufacturer,
                        'batch_sap'       => $r->batch_sap,
                        'rundownSupplier' => (float) ($r->rundown_supplier ?? $r->rundownsupplier ?? 0),
                    ];
                }

                Rundown::adjustRundownToTotal($supplierRowsFormatted, $out_qty);

                $rundownResult = Rundown::generalRundown([
                    'user' => $user,
                    'entry_date' => $curr_entryDate,
                    'trace_no' => $this->traceNoToInt($entry_no),
                    'from_trace_no' => $this->traceNoToInt($entryTrfNo_in),
                    'id_material' => $id_material,
                    'id_sloc' => $id_sloc_json,
                    'id_sloc_tail' => null,
                    'id_plant' => $tgtPlant,
                    'in_qty' => $out_qty,
                    'last_qtf' => 0,
                    'curr_qtf' => $out_qty,
                    'supplier_rows' => $supplierRowsFormatted,
                ]);

                if (($rundownResult['response'] ?? 0) != 1) {
                    throw new Exception('Rundown failed for feed tank');
                }

                if (!empty($materialDoc)) {
                    app(\Modules\Shared\Services\TransactionCoreService::class)
                        ->createMaterialDocument($user, $rundownResult['id_trace_head'], $materialDoc, 'ADD');
                }
            }

            $this->clearTempData($entry_no, $user);
            $this->logTransaction('RMTRF_ENTRY', 'ADD', 'Transfer to Feed Tank | Entry No: ' . $entry_no, $user);

            DB::connection('eudr_ts')->commit();
            return ['success' => true];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            throw $e;
        }
    }

    public function deactivateRmEntry(int $id, string $user): array
    {
        return app(\Modules\Shared\Services\TransactionCancellationService::class)
            ->deactivateRmEntry($id, $user);
    }

    public function deactivateFeedLogEntry(int $id, string $user): array
    {
        return app(\Modules\Shared\Services\TransactionCancellationService::class)
            ->deactivateFeedLogEntry($id, $user);
    }

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array
    {
        return app(\Modules\Shared\Services\TransactionCoreService::class)
            ->updateEntrySubTank($user, $idHead, $tails);
    }

    public function deactivateRmEntryTrf(int $id, string $user): array
    {
        return app(\Modules\Shared\Services\TransactionCancellationService::class)
            ->deactivateRmEntryTrf($id, $user);
    }
}
