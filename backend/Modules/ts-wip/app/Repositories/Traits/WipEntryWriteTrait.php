<?php declare(strict_types=1);
namespace Modules\TsWip\Repositories\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Helpers\Feed;
use Modules\Shared\Helpers\Rundown;

trait WipEntryWriteTrait
{
    public function postMaterialDocument(string $mode, int $idTraceHead, string $materialDoc, string $user): array
    {
        if ($mode === 'ADD') {
            $result = DB::connection('eudr_ts')->insert(
                'INSERT INTO t_material_document (id_trace_head, material_document, created_by) VALUES (?, ?, ?)',
                [$idTraceHead, $materialDoc, $user]
            );

            $id = DB::connection('eudr_ts')->select('SELECT id_matdoc FROM t_material_document ORDER BY id_matdoc DESC LIMIT 1');
            $this->logTransaction('T_MATERIAL_DOCUMENT', 'ADD',
                'ID: ' . $id[0]->id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead . ' / DOC_NO: ' . $materialDoc . ' | Status: 1', $user);

            return [['response' => $result ? '1' : '0']];
        }

        $dat = DB::connection('eudr_ts')->select(
            'SELECT id_matdoc, material_document FROM t_material_document WHERE id_trace_head = ?', [$idTraceHead]
        );
        if (empty($dat)) return [['response' => '0']];

        $oldDoc = $dat[0]->material_document;
        DB::connection('eudr_ts')->update(
            'UPDATE t_material_document SET material_document = ?, updated_by = ? WHERE id_trace_head = ?',
            [$materialDoc, $user, $idTraceHead]
        );

        $this->logTransaction('T_MATERIAL_DOCUMENT', 'UPDATE',
            'ID: ' . $dat[0]->id_matdoc . ' | IDTRACEHEAD: ' . $idTraceHead . ' / DOC_NO: ' . $oldDoc . ' >>> ' . $materialDoc . ' | Status: 1', $user);

        return [['response' => '1']];
    }

    public function postMaterialFeed(array $data, string $user): array
    {
        try {
            if (empty($data['tank'])) {
                \Log::warning('WIP Feed Save - Missing Required Field', ['field' => 'tank']);
                return [['response' => '5']];
            }
            if (!isset($data['curr_feed']) || $data['curr_feed'] === '') {
                \Log::warning('WIP Feed Save - Missing Required Field', ['field' => 'curr_feed']);
                return [['response' => '5']];
            }
            if (empty($data['curr_entryDate'])) {
                \Log::warning('WIP Feed Save - Missing Required Field', ['field' => 'curr_entryDate']);
                return [['response' => '5']];
            }

            $feedId = $this->mapFrontendSectionToDbFeedId($data['feed_id']);
            $idTank = $data['tank'];
            $idTankTail = !empty($data['tankNo']) ? json_encode($data['tankNo']) : '[]';
            if (is_array($idTankTail) || is_int($idTankTail)) {
                $idTankTail = json_encode($idTankTail);
            }
            $currQtf = $data['curr_feed'];
            $lastQtf = $data['last_feed'];
            $currEntryDate = $data['curr_entryDate'];
            $entryNo = $data['batch_no'];
            $idPlant = $this->resolvePlantId($data['id_plant'] ?? null);

            \Log::info('WIP Feed Save - Input Data', [
                'feed_id' => $data['feed_id'],
                'mapped_feed_id' => $feedId,
                'tank' => $idTank,
                'curr_feed' => $currQtf,
                'last_feed' => $lastQtf,
                'entry_date' => $currEntryDate,
                'batch_no' => $entryNo,
                'id_plant' => $idPlant,
                'user' => $user,
            ]);

            DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

            if ($this->checkPeriodLock($currEntryDate)) {
                \Log::warning('WIP Feed Save - Period Lock Failed', ['entry_date' => $currEntryDate]);
                return [['response' => '99']];
            }

            $outQty = (float)$currQtf - (float)$lastQtf;

            \Log::info('WIP Feed Save - Calculated Out Qty', ['out_qty' => $outQty, 'curr_qtf' => $currQtf, 'last_qtf' => $lastQtf]);

            $datHead = DB::connection('eudr_ts')->select('
                SELECT IFNULL(SUM(b.qty),0) AS qty
                  FROM m_material a
                  LEFT JOIN t_balance_header b ON a.id_material = b.id_material AND b.status = 1
                 WHERE a.id_feed = ? AND a.status = 1
                   AND b.qty > "0.0001"
                   AND (b.id_sloc = ? OR (JSON_VALID(b.id_sloc) AND (JSON_CONTAINS(b.id_sloc, CAST(? AS CHAR)) OR JSON_CONTAINS(b.id_sloc, JSON_QUOTE(CAST(? AS CHAR))))))
                   AND b.id_plant = ?
            ', [$feedId, $idTank, $idTank, $idTank, $idPlant]);

            $totalReserve = (float)($datHead[0]->qty ?? 0);
            \Log::info('WIP Feed Save - Reserve Balance Check', ['total_reserve' => $totalReserve, 'out_qty' => $outQty, 'feed_id' => $feedId, 'tank' => $idTank]);
            
            if (($totalReserve - $outQty) < -0.000001) {
                \Log::warning('WIP Feed Save - Insufficient Reserve Balance', ['total_reserve' => $totalReserve, 'out_qty' => $outQty]);
                return [['response' => '3']];
            }

            $idMaterial = $this->getMaterialIdBySection($feedId, 'feed');
            \Log::info('WIP Feed Save - Material ID Lookup', ['feed_id' => $feedId, 'material_id' => $idMaterial]);
            
            if (empty($idMaterial)) {
                \Log::warning('WIP Feed Save - Material Not Found', ['feed_id' => $feedId]);
                return [['response' => '4']];
            }

            $dup = DB::connection('eudr_ts')->select('
                SELECT COUNT(id_trace_head) AS flag
                  FROM t_trace_header
                 WHERE status = 1 AND entry_date = ?
                   AND (id_sloc = ? OR (JSON_VALID(id_sloc) AND (JSON_CONTAINS(id_sloc, CAST(? AS CHAR)) OR JSON_CONTAINS(id_sloc, JSON_QUOTE(CAST(? AS CHAR))))))
                   AND id_material = ?
                   AND in_qty = 0 AND SUBSTRING(to_trace_no,1,1) = 3 AND id_plant = ?
            ', [$currEntryDate, $idTank, $idTank, $idTank, $idMaterial, $idPlant]);

            if (!empty($dup) && $dup[0]->flag > 0) {
                \Log::warning('WIP Feed Save - Duplicate Entry', ['entry_date' => $currEntryDate, 'tank' => $idTank, 'material_id' => $idMaterial]);
                return [['response' => '2']];
            }

            $balanceDetails = DB::connection('eudr_ts')->select('
                SELECT COUNT(a.id_balance_tail) AS detail_count
                  FROM t_balance_header b
                  LEFT JOIN t_balance_detail a ON b.id_balance_head = a.id_balance_head AND a.status = 1 AND a.qty > "0.0001"
                 WHERE b.id_material = ? AND b.status = 1
                   AND b.qty > "0.0001"
                   AND (b.id_sloc = ? OR (JSON_VALID(b.id_sloc) AND (JSON_CONTAINS(b.id_sloc, CAST(? AS CHAR)) OR JSON_CONTAINS(b.id_sloc, JSON_QUOTE(CAST(? AS CHAR))))))
                   AND b.id_plant = ?
            ', [$idMaterial, $idTank, $idTank, $idTank, $idPlant]);

            $detailCount = (int)($balanceDetails[0]->detail_count ?? 0);
            \Log::info('WIP Feed Save - Balance Detail Count', ['detail_count' => $detailCount, 'material_id' => $idMaterial, 'tank' => $idTank]);
            
            if ($detailCount == 0) {
                \Log::error('WIP Feed Save - No Supplier Details Available', [
                    'feed_id' => $feedId,
                    'tank' => $idTank,
                    'material_id' => $idMaterial,
                    'id_plant' => $idPlant,
                    'message' => 'Balance exists but has no supplier details (t_balance_detail). Please ensure the balance for this material/tank has supplier information setup.'
                ]);
                return [['response' => '6']];
            }

            $feedData = [
                'user'         => $user,
                'entry_date'   => $currEntryDate,
                'id_material'  => $idMaterial,
                'id_sloc'      => $idTank,
                'id_sloc_tail' => $idTankTail,
                'id_plant'     => $idPlant,
                'qty'          => $outQty,
                'to_trace_no'  => $entryNo,
                'last_qtf'     => $lastQtf,
                'curr_qtf'     => $currQtf,
            ];

            \Log::info('WIP Feed Save - Calling Feed::generalFeed', ['feed_data' => $feedData]);

            $result = Feed::generalFeed(array_merge($feedData, [
                'trace_prefixes' => ['1', '2', '7', '8', '9'],
            ]));
            \Log::info('WIP Feed Save - Feed::generalFeed Result', ['result' => $result]);

            if (($result['response'] ?? 0) != 1) {
                $errorMsg = $result['response'] ?? 3;
                \Log::warning('WIP Feed Save - Feed::generalFeed Failed', ['response' => $errorMsg]);
                return [['response' => (string)$errorMsg]];
            }

            Feed::normalizeSupplierRundown($result['trace_head_ids'], $outQty);

            $feedTraceHeadId = $result['trace_head_ids'][0] ?? null;
            if ($feedTraceHeadId) {
                \Log::info('WIP Feed Save - Inserting t_prod_log', ['trace_head_id' => $feedTraceHeadId, 'batch_no' => $entryNo]);
                
                DB::connection('eudr_ts')->table('t_prod_log')->insert([
                    'id_trace_head' => $feedTraceHeadId,
                    'section' => $feedId,
                    'entry_date' => $currEntryDate,
                    'batch_no' => $entryNo,
                    'tank_id' => $idTank,
                    'tank_tail' => $idTankTail,
                    'id_material' => $idMaterial,
                    'in_qty' => 0,
                    'out_qty' => $outQty,
                    'yield' => 0,
                    'id_plant' => $idPlant,
                    'status' => 1,
                    'created_by' => $user,
                    'created_at' => now(),
                ]);
                $this->logTransaction('T_PROD_LOG', 'ADD', 'WIP FEED | IDTRACEHEAD: ' . $feedTraceHeadId . ' | BATCH: ' . $entryNo . ' | QTY: ' . $outQty, $user);
                
                \Log::info('WIP Feed Save - Success', ['batch_no' => $entryNo, 'out_qty' => $outQty]);
            }

            return [['response' => '1']];
        } catch (\Exception $e) {
            \Log::error('WIP Feed Save - Unexpected Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
                'user' => $user
            ]);
            return [['response' => '6']];
        }
    }

    public function postMaterialRundown(array $data, string $user): array
    {
        if (empty($data['tank'])) {
            \Log::warning('WIP Rundown Save - Missing Required Field', ['field' => 'tank']);
            return [['response' => '5']];
        }
        if (!isset($data['curr_rundown']) || $data['curr_rundown'] === '') {
            \Log::warning('WIP Rundown Save - Missing Required Field', ['field' => 'curr_rundown']);
            return [['response' => '5']];
        }
        if (empty($data['curr_entryDate'])) {
            \Log::warning('WIP Rundown Save - Missing Required Field', ['field' => 'curr_entryDate']);
            return [['response' => '5']];
        }

        $subgroup = $data['subgroup'] ?? null;
        $rundownId = $this->mapFrontendSectionToDbRundownId($data['rundown_id'], $subgroup);
        $lastQtf = $data['last_rundown'];
        $currQtf = $data['curr_rundown'];
        $currEntryDate = $data['curr_entryDate'];
        $entryNo = $data['batch_no'];
        $idTank = $data['tank'];
        $idTankTail = !empty($data['tankNo']) ? json_encode($data['tankNo']) : '[]';
        $idPlant = $this->resolvePlantId($data['id_plant'] ?? null);

        \Log::info('WIP Rundown Save - Input Data', [
            'rundown_id' => $data['rundown_id'],
            'mapped_rundown_id' => $rundownId,
            'subgroup' => $subgroup,
            'tank' => $idTank,
            'curr_rundown' => $currQtf,
            'last_rundown' => $lastQtf,
            'entry_date' => $currEntryDate,
            'batch_no' => $entryNo,
            'id_plant' => $idPlant,
            'user' => $user,
        ]);

        if ($this->checkPeriodLock($currEntryDate)) {
            \Log::warning('WIP Rundown Save - Period Lock Failed', ['entry_date' => $currEntryDate]);
            return [['response' => '99']];
        }

        $inQty = (float)$currQtf - (float)$lastQtf;
        \Log::info('WIP Rundown Save - Calculated In Qty', ['in_qty' => $inQty, 'curr_qtf' => $currQtf, 'last_qtf' => $lastQtf]);
        
        DB::connection('eudr_ts')->select('SET sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');

        $originalEntryNo = $entryNo;
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $check = DB::connection('eudr_ts')->select(
                'SELECT COUNT(to_trace_no) AS flag FROM t_trace_header WHERE to_trace_no = ? AND status = 1 AND id_plant = ?',
                [$entryNo, $idPlant]
            );
            if ($check[0]->flag == 0) break;
            $entryNo = $originalEntryNo + ($i + 1);
        }

        $checkFinal = DB::connection('eudr_ts')->select(
            'SELECT COUNT(to_trace_no) AS flag FROM t_trace_header WHERE to_trace_no = ? AND id_plant = ? AND status = 1',
            [$entryNo, $idPlant]
        );
        if ($checkFinal[0]->flag > 0) {
            \Log::warning('WIP Rundown Save - Duplicate Trace No', ['entry_no' => $entryNo]);
            return [['response' => '7']];
        }

        $feedSectionId = $this->mapRundownToFeedSectionId($rundownId);
        $feedId = $feedSectionId;

        \Log::info('WIP Rundown Save - Looking for Feed Trace', ['rundown_id' => $rundownId, 'feed_section_id' => $feedSectionId, 'feed_id' => $feedId, 'entry_date' => $currEntryDate]);

        $feedTrace = DB::connection('eudr_ts')->select('
            SELECT to_trace_no, id_trace_head, SUM(out_qty) AS out_qty, id_material
              FROM t_trace_header
             WHERE SUBSTRING(to_trace_no,1,1) = ?
               AND SUBSTRING(to_trace_no,8,3) = ?
               AND entry_date = ? AND id_plant = ? AND status = 1
               AND out_qty > "0.0001"
             GROUP BY id_trace_head
             ORDER BY id_trace_head DESC LIMIT 1
        ', [$this->movType2, $feedId, $currEntryDate, $idPlant]);

        if (empty($feedTrace) || $feedTrace[0]->out_qty === null) {
            \Log::warning('WIP Rundown Save - Feed Trace Not Found', ['rundown_id' => $rundownId, 'feed_id' => $feedId, 'entry_date' => $currEntryDate]);
            return [['response' => '4']];
        }

        $fromTraceNo = $feedTrace[0]->to_trace_no;
        $feedQty = (float)$feedTrace[0]->out_qty;
        \Log::info('WIP Rundown Save - Feed Trace Found', ['from_trace_no' => $fromTraceNo, 'feed_qty' => $feedQty]);

        $idMaterial = $this->getMaterialIdBySection($rundownId, 'rundown', $subgroup);
        \Log::info('WIP Rundown Save - Material ID Lookup', ['rundown_id' => $rundownId, 'material_id' => $idMaterial]);
        
        if (empty($idMaterial)) {
            \Log::warning('WIP Rundown Save - Material Not Found', ['rundown_id' => $rundownId]);
            return [['response' => '4']];
        }

        $dup = DB::connection('eudr_ts')->select('
            SELECT COUNT(id_trace_head) AS flag
              FROM t_trace_header
             WHERE status = 1 AND entry_date = ?
               AND (id_sloc = ? OR (JSON_VALID(id_sloc) AND (JSON_CONTAINS(id_sloc, CAST(? AS CHAR)) OR JSON_CONTAINS(id_sloc, JSON_QUOTE(CAST(? AS CHAR))))))
               AND id_material = ?
               AND out_qty = 0 AND id_plant = ? AND SUBSTRING(to_trace_no,1,1) = 2
        ', [$currEntryDate, $idTank, $idTank, $idTank, $idMaterial, $idPlant]);

        if (!empty($dup) && $dup[0]->flag > 0) {
            \Log::warning('WIP Rundown Save - Duplicate Entry', ['entry_date' => $currEntryDate, 'tank' => $idTank, 'material_id' => $idMaterial]);
            return [['response' => '2']];
        }

        $processYield = $feedQty > 0 ? ($inQty / $feedQty) : 0;
        \Log::info('WIP Rundown Save - Process Yield', ['feed_qty' => $feedQty, 'in_qty' => $inQty, 'yield' => $processYield]);

        $feedTraces = DB::connection('eudr_ts')->select('
            SELECT to_trace_no, id_trace_head, out_qty, id_material
              FROM t_trace_header
             WHERE SUBSTRING(to_trace_no,1,1) = ?
               AND SUBSTRING(to_trace_no,8,3) = ?
               AND entry_date = ? AND status = 1
               AND out_qty > "0.0001" AND id_plant = ?
             ORDER BY id_trace_head DESC
        ', [$this->movType2, $feedId, $currEntryDate, $idPlant]);

        \Log::info('WIP Rundown Save - Feed Traces Found', ['count' => count($feedTraces)]);

        $supplierRows = [];
        foreach ($feedTraces as $head) {
            $feedDetails = DB::connection('eudr_ts')->select('
                SELECT id_trace_tail, id_balance_tail, id_supplier, out_qty, batch_sap
                  FROM t_trace_detail
                 WHERE id_trace_head = ? AND status = 1 AND id_plant = ?
                 ORDER BY id_trace_tail ASC
            ', [$head->id_trace_head, $idPlant]);

            if (empty($feedDetails)) {
                \Log::warning('WIP Rundown Save - No Feed Details', ['trace_head_id' => $head->id_trace_head]);
                return [['response' => '6']];
            }

            foreach ($feedDetails as $detail) {
                $supplierRows[] = [
                    'id_supplier'     => $detail->id_supplier,
                    'batch_sap'       => $detail->batch_sap,
                    'rundownSupplier' => round($processYield * (float)$detail->out_qty, 4),
                ];
            }
        }

        Rundown::adjustRundownToTotal($supplierRows, $inQty);
        \Log::info('WIP Rundown Save - Supplier Rows Adjusted', ['supplier_count' => count($supplierRows), 'in_qty' => $inQty]);

        $rundownData = [
            'user'          => $user,
            'entry_date'    => $currEntryDate,
            'from_trace_no' => $fromTraceNo,
            'trace_no'      => $entryNo,
            'id_material'   => $idMaterial,
            'id_sloc'       => $idTank,
            'id_sloc_tail'  => $idTankTail,
            'in_qty'        => $inQty,
            'last_qtf'      => $lastQtf,
            'curr_qtf'      => $currQtf,
            'id_plant'      => $idPlant,
            'supplier_rows' => $supplierRows,
        ];
        
        \Log::info('WIP Rundown Save - Calling Rundown::generalRundown', ['rundown_data' => $rundownData]);

        $rundownResult = Rundown::generalRundown($rundownData);
        
        \Log::info('WIP Rundown Save - Rundown::generalRundown Result', ['result' => $rundownResult]);

        if (($rundownResult['response'] ?? 0) != 1) {
            \Log::warning('WIP Rundown Save - Rundown::generalRundown Failed', ['response' => $rundownResult['response'] ?? 0]);
            return [['response' => '3']];
        }

        \Log::info('WIP Rundown Save - Success', ['batch_no' => $entryNo, 'in_qty' => $inQty]);

        return [['response' => '1']];
    }

    public function cancelFeed(string $traceNo, string $user): array
    {
        $entryDate = DB::connection('eudr_ts')->select(
            'SELECT entry_date FROM t_trace_header WHERE to_trace_no = ? AND status = 1',
            [$traceNo]
        );
        if (empty($entryDate)) return [['response' => '0']];

        if ($this->checkPeriodLock($entryDate[0]->entry_date)) {
            return [['response' => '99']];
        }

        $traceHeads = DB::connection('eudr_ts')->select('
            SELECT id_trace_head, id_balance_head, in_qty, out_qty
              FROM t_trace_header
             WHERE to_trace_no = ? AND status = 1
             ORDER BY id_trace_head DESC
        ', [$traceNo]);

        foreach ($traceHeads as $head) {
            $idTraceHead = $head->id_trace_head;
            $idBalanceHead = $head->id_balance_head;
            $traceHeadOutQty = (float)$head->out_qty;

            DB::connection('eudr_ts')->update(
                'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ? AND status = 1',
                [$user, $idTraceHead]
            );
            $this->logTransaction('T_TRACE_HEAD', 'DELETE',
                'IDTRACEHEAD: ' . $idTraceHead . ' IDHEAD: ' . $idBalanceHead . ' | Status: 1 >>> 0', $user);

            $balHead = DB::connection('eudr_ts')->select('
                SELECT qty, in_qty, out_qty FROM t_balance_header WHERE id_balance_head = ? AND status = 1',
                [$idBalanceHead]
            );
            if (!empty($balHead)) {
                $oldQty = (float)$balHead[0]->qty;
                $oldInQty = (float)$balHead[0]->in_qty;
                $oldOutQty = (float)$balHead[0]->out_qty;

                DB::connection('eudr_ts')->update('
                    UPDATE t_balance_header SET qty = ?, in_qty = ?, out_qty = ?, updated_by = ?
                    WHERE id_balance_head = ? AND status = 1',
                    [$oldQty + $traceHeadOutQty, $oldInQty, $oldOutQty - $traceHeadOutQty, $user, $idBalanceHead]
                );

                $this->logTransaction('T_BALANCE_HEAD', 'UPDATE',
                    'IDHEAD: ' . $idBalanceHead . ' | QTY: ' . $oldQty . ' >>> ' . ($oldQty + $traceHeadOutQty) .
                    ' / OUT_QTY: ' . $oldOutQty . ' >>> ' . ($oldOutQty - $traceHeadOutQty) . ' | Status: 1', $user);
            }

            $traceTails = DB::connection('eudr_ts')->select('
                SELECT id_trace_tail, id_balance_tail, in_qty, out_qty
                  FROM t_trace_detail
                 WHERE id_trace_head = ? AND status = 1
                 ORDER BY id_trace_tail DESC
            ', [$idTraceHead]);

            foreach ($traceTails as $tail) {
                DB::connection('eudr_ts')->update(
                    'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_tail = ? AND status = 1',
                    [$user, $tail->id_trace_tail]
                );

                $balTail = DB::connection('eudr_ts')->select('
                    SELECT qty, in_qty, out_qty, init_qty FROM t_balance_detail
                     WHERE id_balance_tail = ? AND status = 1
                ', [$tail->id_balance_tail]);

                if (!empty($balTail)) {
                    $tailOutQty = (float)$tail->out_qty;
                    DB::connection('eudr_ts')->update('
                        UPDATE t_balance_detail SET qty = ?, in_qty = ?, out_qty = ?, updated_by = ?
                        WHERE id_balance_tail = ? AND status = 1',
                        [$balTail[0]->qty + $tailOutQty, $balTail[0]->in_qty, $balTail[0]->out_qty - $tailOutQty, $user, $tail->id_balance_tail]
                    );
                }
            }

            DB::connection('eudr_ts')->update(
                'UPDATE t_prod_log SET status = 0, updated_by = ? WHERE id_trace_head = ? AND status = 1',
                [$user, $idTraceHead]
            );
            $this->logTransaction('T_PROD_LOG', 'DELETE',
                'IDTRACEHEAD: ' . $idTraceHead . ' | Status: 1 >>> 0 | Cancel Feed', $user);
        }

        return [['response' => '1']];
    }

    public function cancelRundown(string $traceNo, string $user): array
    {
        $entryDate = DB::connection('eudr_ts')->select(
            'SELECT entry_date FROM t_trace_header WHERE to_trace_no = ? AND status = 1',
            [$traceNo]
        );
        if (empty($entryDate)) return [['response' => '0']];

        if ($this->checkPeriodLock($entryDate[0]->entry_date)) {
            return [['response' => '99']];
        }

        $traceHeads = DB::connection('eudr_ts')->select('
            SELECT id_trace_head, id_balance_head, in_qty, out_qty
              FROM t_trace_header
             WHERE to_trace_no = ? AND status = 1
             ORDER BY id_trace_head DESC
        ', [$traceNo]);

        foreach ($traceHeads as $head) {
            $idTraceHead = $head->id_trace_head;
            $idBalanceHead = $head->id_balance_head;

            DB::connection('eudr_ts')->update(
                'UPDATE t_trace_header SET status = 0, updated_by = ? WHERE id_trace_head = ? AND status = 1',
                [$user, $idTraceHead]
            );
            $this->logTransaction('T_TRACE_HEAD', 'DELETE',
                'IDTRACEHEAD: ' . $idTraceHead . ' IDHEAD: ' . $idBalanceHead . ' | Status: 1 >>> 0', $user);

            DB::connection('eudr_ts')->update(
                'UPDATE t_balance_header SET status = 0, updated_by = ? WHERE id_balance_head = ? AND status = 1',
                [$user, $idBalanceHead]
            );
            $this->logTransaction('T_BALANCE_HEAD', 'UPDATE',
                'IDHEAD: ' . $idBalanceHead . ' | Status: 1 >>> 0', $user);

            $traceTails = DB::connection('eudr_ts')->select('
                SELECT id_trace_tail, id_balance_tail FROM t_trace_detail
                 WHERE id_trace_head = ? AND status = 1
            ', [$idTraceHead]);

            foreach ($traceTails as $tail) {
                DB::connection('eudr_ts')->update(
                    'UPDATE t_trace_detail SET status = 0, updated_by = ? WHERE id_trace_tail = ? AND status = 1',
                    [$user, $tail->id_trace_tail]
                );
                DB::connection('eudr_ts')->update(
                    'UPDATE t_balance_detail SET status = 0, updated_by = ? WHERE id_balance_tail = ? AND status = 1',
                    [$user, $tail->id_balance_tail]
                );
            }
        }

        $adjCode = substr($traceNo, 0, 1);
        if ($adjCode === '9') {
            $adjHead = DB::connection('eudr_ts')->select(
                'SELECT id_adjust_head FROM t_adjustment_header WHERE adjust_no = ?', [$traceNo]
            );
            if (!empty($adjHead)) {
                $idAdjustHead = $adjHead[0]->id_adjust_head;
                DB::connection('eudr_ts')->update(
                    'UPDATE t_adjustment_header SET status = 0, updated_by = ? WHERE id_adjust_head = ? AND status = 1',
                    [$user, $idAdjustHead]
                );
                DB::connection('eudr_ts')->update(
                    'UPDATE t_adjustment_detail SET status = 0, updated_by = ? WHERE id_adjust_head = ? AND status = 1',
                    [$user, $idAdjustHead]
                );
            }
        }

        return [['response' => '1']];
    }

    public function updateEntrySubTank(int $idHead, array $tails, string $user): array
    {
        $jsonTails = json_encode(array_values(array_unique($tails)));

        $row = DB::connection('eudr_ts')->selectOne(
            'SELECT trace_no FROM t_balance_header WHERE id_balance_head = ? AND status = 1', [$idHead]
        );
        if (!$row) return [['response' => '0', 'message' => 'HEAD NOT FOUND']];

        DB::connection('eudr_ts')->update(
            'UPDATE t_balance_header SET id_sloc_tail = ?, updated_by = ? WHERE id_balance_head = ?',
            [$jsonTails, $user, $idHead]
        );

        DB::connection('eudr_ts')->update(
            'UPDATE t_trace_header SET id_sloc_tail = ?, updated_by = ? WHERE id_balance_head = ?',
            [$jsonTails, $user, $idHead]
        );

        DB::connection('eudr_ts')->update(
            'UPDATE t_balance_detail SET id_sloc_tail = ?, updated_by = ? WHERE id_balance_head = ?',
            [$jsonTails, $user, $idHead]
        );

        DB::connection('eudr_ts')->update('
            UPDATE t_trace_detail SET id_sloc_tail = ?, updated_by = ?
            WHERE id_trace_head IN (SELECT id_trace_head FROM t_trace_header WHERE id_balance_head = ?)
        ', [$jsonTails, $user, $idHead]);

        $this->logTransaction('T_BALANCE_HEAD', 'UPDATE_SUBTANK',
            'IDHEAD: ' . $idHead . ' | TRACE: ' . $row->trace_no . ' | SUBTANKS: ' . implode(',', $tails), $user);

        return [['response' => '1']];
    }

    public function logTransaction(string $module, string $type, string $description, string $user): void
    {
        DB::connection('eudr_ts')->insert(
            'INSERT INTO log_transactions (log_module, log_type, log_description, created_by) VALUES (?, ?, ?, ?)',
            [$module, $type, $description, $user]
        );
    }
}
