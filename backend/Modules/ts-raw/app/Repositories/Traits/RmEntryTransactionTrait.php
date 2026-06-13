<?php declare(strict_types=1);

namespace Modules\TsRaw\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\TsRaw\Models\TraceHeader;
use Modules\Material\Models\Material;
use Modules\Shared\Helpers\Rundown;
use Modules\Shared\Helpers\Feed;
use Exception;

trait RmEntryTransactionTrait
{
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

        $entries = DB::connection('eudr_ts')->select(
            'SELECT id_balance_head, trace_no, qty, init_qty, entry_date, created_at
               FROM t_balance_header
              WHERE id_material = ?
                AND JSON_CONTAINS(id_sloc, JSON_ARRAY(?))
                AND id_plant = ?
                AND status = 1
                AND created_at >= ?
              ORDER BY id_balance_head ASC',
            [$materialId, $tankId, $plantId, $since]
        );

        return [
            'total_entries' => count($entries),
            'entries' => $entries,
            'total_qty' => array_sum(array_column($entries, 'qty')),
            'separate_entries_created' => count($entries) > 1,
            'parameters' => [
                'id_material' => $materialId,
                'id_tank' => $tankId,
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
            $slocPlant = DB::connection('eudr_ts')->table('m_sloc')
                ->where('id_sloc', $firstSlocId)
                ->value('id_plant');
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
                DB::connection('eudr_ts')->table('t_material_document')->insert([
                    'id_trace_head' => $idTraceHead,
                    'material_document' => $data['material_document'],
                    'po_so' => $data['po_so'] ?? null,
                    'created_by' => $user,
                ]);
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
            $id_tankSource = is_array($data['source_tank']) ? $data['source_tank'] : [$data['source_tank']];
            $id_tank = is_array($data['trf_tank']) ? $data['trf_tank'] : [$data['trf_tank']];
            $materialDoc = $data['material_document'] ?? null;
            $idPlant = $this->resolvePlantCode($data['id_plant'] ?? 0);

            $id_tankSource_json = json_encode(array_map('strval', array_values($id_tankSource)));
            $id_tank_json = json_encode(array_map('strval', array_values($id_tank)));

            $srcTankRec = DB::connection('eudr_ts')->table('m_sloc')
                ->select('id_tank AS code', 'description', 'id_plant', 'plant_name')
                ->whereIn('id_sloc', $id_tankSource)
                ->where('status', 1)
                ->get();
            $tgtTankRec = DB::connection('eudr_ts')->table('m_sloc')
                ->select('id_tank AS code', 'description', 'id_plant', 'plant_name')
                ->whereIn('id_sloc', $id_tank)
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
                    'id_sloc' => $id_tankSource_json,
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
                        $slocNames = implode(', ', $id_tankSource);
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

                $entryTrfNo_in = $this->buildTraceNo('1', $batch_entryDate, '000', $batch_idPlant, $batch_sequence);

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
                    'SELECT id_supplier, id_manufacturer, batch_sap, SUM(out_qty) AS rundownSupplier
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
                        'rundownSupplier' => (float) $r->rundownSupplier,
                    ];
                }

                Rundown::adjustRundownToTotal($supplierRowsFormatted, $out_qty);

                $rundownResult = Rundown::generalRundown([
                    'user' => $user,
                    'entry_date' => $curr_entryDate,
                    'trace_no' => $this->traceNoToInt($entry_no),
                    'from_trace_no' => $this->traceNoToInt($entryTrfNo_in),
                    'id_material' => $id_material,
                    'id_sloc' => $id_tank_json,
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
                    DB::connection('eudr_ts')->table('t_material_document')->insert([
                        'id_trace_head' => $rundownResult['id_trace_head'],
                        'material_document' => $materialDoc,
                        'created_by' => $user,
                        'created_at' => now(),
                    ]);
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
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $used = TraceHeader::where('id_balance_head', $id)
                ->where('out_qty', '!=', 0)
                ->where('status', 1)
                ->count();

            if ($used > 0) {
                throw new Exception('RM Entry has been used and cannot be deactivated');
            }

            DB::connection('eudr_ts')->table('t_balance_header')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            DB::connection('eudr_ts')->table('t_balance_detail')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            $traceHead = TraceHeader::where('id_balance_head', $id)
                ->where('status', 1)
                ->first();

            if ($traceHead) {
                DB::connection('eudr_ts')->table('t_trace_header')
                    ->where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);

                DB::connection('eudr_ts')->table('t_trace_detail')
                    ->where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);
            }

            $this->logTransaction('RM_ENTRY', 'DEACTIVATE', 'ID: ' . $id, $user);
            DB::connection('eudr_ts')->commit();
            return ['success' => true];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            throw $e;
        }
    }

    public function deactivateFeedLogEntry(int $id, string $user): array
    {
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $traceHead = DB::connection('eudr_ts')->table('t_trace_header')
                ->where('id_trace_head', $id)
                ->where('status', 1)
                ->first();

            if (!$traceHead) {
                throw new Exception('Feed log entry not found');
            }

            $toTraceNo = (string) ($traceHead->to_trace_no ?? '');
            if (substr($toTraceNo, 0, 1) === '7') {
                throw new Exception('Use transfer deactivation for transfer entries');
            }

            // Check if this balance has been used by downstream operations
            $usedCount = DB::connection('eudr_ts')->table('t_trace_header')
                ->where('id_balance_head', $traceHead->id_balance_head)
                ->where('out_qty', '!=', 0)
                ->where('status', 1)
                ->count();

            if ($usedCount > 0) {
                throw new Exception('Feed log entry has been used and cannot be deactivated');
            }

            // Deactivate balance header
            DB::connection('eudr_ts')->table('t_balance_header')
                ->where('id_balance_head', $traceHead->id_balance_head)
                ->update(['status' => 0, 'updated_by' => $user]);

            // Deactivate balance detail
            DB::connection('eudr_ts')->table('t_balance_detail')
                ->where('id_balance_head', $traceHead->id_balance_head)
                ->update(['status' => 0, 'updated_by' => $user]);

            // Deactivate trace header
            DB::connection('eudr_ts')->table('t_trace_header')
                ->where('id_trace_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            // Deactivate trace detail
            DB::connection('eudr_ts')->table('t_trace_detail')
                ->where('id_trace_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            $this->logTransaction(
                'FEED_LOG',
                'DEACTIVATE',
                'ID: ' . $id . ' | Trace: ' . $toTraceNo,
                $user
            );

            DB::connection('eudr_ts')->commit();
            return ['success' => true];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            throw $e;
        }
    }

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array
    {
        if (!is_array($tails)) {
            return ['response' => 0, 'message' => 'INVALID SUBTANK DATA'];
        }

        $jsonTails = json_encode(array_values(array_unique($tails)));

        $row = DB::connection('eudr_ts')->selectOne(
            'SELECT trace_no FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
            [$idHead]
        );

        if (!$row) {
            return ['response' => 0, 'message' => 'BALANCE HEAD NOT FOUND'];
        }

        DB::connection('eudr_ts')->update(
            'UPDATE t_balance_header SET updated_by = ? WHERE id_balance_head = ?',
            [$user, $idHead]
        );

        DB::connection('eudr_ts')->update(
            'UPDATE t_trace_header SET updated_by = ? WHERE id_balance_head = ?',
            [$user, $idHead]
        );

        DB::connection('eudr_ts')->update(
            'UPDATE t_balance_detail SET updated_by = ? WHERE id_balance_head = ?',
            [$user, $idHead]
        );

        DB::connection('eudr_ts')->update(
            'UPDATE t_trace_detail SET updated_by = ?
              WHERE id_trace_head IN (SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ?)',
            [$user, $idHead]
        );

        $this->logTransaction('T_BALANCE_HEAD', 'UPDATE_SUBTANK',
            'IDHEAD: ' . $idHead . ' | TRACE: ' . $row->trace_no . ' | SUBTANKS: ' . implode(',', $tails),
            $user);

        return ['response' => 1];
    }

    public function deactivateRmEntryTrf(int $id, string $user): array
    {
        DB::connection('eudr_ts')->beginTransaction();

        try {
            $head = DB::connection('eudr_ts')->selectOne(
                'SELECT trace_no FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
                [$id]
            );
            if (!$head) {
                throw new Exception('RM Entry not found');
            }

            $traceNo = $head->trace_no;

            $traceHead = DB::connection('eudr_ts')->selectOne(
                'SELECT id_trace_head, from_trace_no, out_qty FROM t_trace_header
                 WHERE from_trace_no = ? AND status = 1 LIMIT 1',
                [$traceNo]
            );

            if ($traceHead) {
                $sourceTraceNo = $traceHead->from_trace_no;
                $sourceTraceHead = DB::connection('eudr_ts')->selectOne(
                    'SELECT id_trace_head, id_balance_head FROM t_trace_header WHERE to_trace_no = ? AND status = 1 LIMIT 1',
                    [$sourceTraceNo]
                );

                if ($sourceTraceHead) {
                    $balanceHead = DB::connection('eudr_ts')->selectOne(
                        'SELECT id_balance_head, qty, out_qty FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
                        [$sourceTraceHead->id_balance_head]
                    );

                    if ($balanceHead) {
                        DB::connection('eudr_ts')->update(
                            'UPDATE t_balance_header SET qty = qty + ?, out_qty = out_qty - ?, updated_by = ? WHERE id_balance_head = ? AND status = 1',
                            [$traceHead->out_qty, $traceHead->out_qty, $user, $sourceTraceHead->id_balance_head]
                        );
                    }
                }
            }

            DB::connection('eudr_ts')->table('t_balance_header')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            DB::connection('eudr_ts')->table('t_balance_detail')
                ->where('id_balance_head', $id)
                ->update(['status' => 0, 'updated_by' => $user]);

            $traceHead = DB::connection('eudr_ts')->selectOne(
                'SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ? AND status = 1 LIMIT 1',
                [$id]
            );

            if ($traceHead) {
                DB::connection('eudr_ts')->table('t_trace_header')
                    ->where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);

                DB::connection('eudr_ts')->table('t_trace_detail')
                    ->where('id_trace_head', $traceHead->id_trace_head)
                    ->update(['status' => 0, 'updated_by' => $user]);
            }

            $this->logTransaction('RMTRF_ENTRY', 'DEACTIVATE', 'ID: ' . $id . ' | Trace: ' . $traceNo, $user);

            DB::connection('eudr_ts')->commit();
            return ['success' => true];

        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            throw $e;
        }
    }
}
