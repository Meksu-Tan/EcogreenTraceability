<?php

declare(strict_types=1);

namespace Modules\TsBlending\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Helpers\Feed;
use Modules\Shared\Helpers\Rundown;
use Modules\Shared\Helpers\TraceHelper;
use Modules\Shared\Services\Contracts\PlantContextServiceInterface;
use Modules\Shared\Traits\DbCompatTrait;
use Modules\TsBlending\Repositories\Contracts\BlendingRepositoryInterface;
use Modules\TsBlending\Services\Contracts\BlendingServiceInterface;

class BlendingService implements BlendingServiceInterface
{
    use DbCompatTrait;

    protected $movSeq = '000';

    protected $typeBlending = '8';

    public function __construct(
        protected BlendingRepositoryInterface $blendingRepo
    ) {}

    public function getAllTanks(int $plantId, ?int $materialId = null): Collection
    {
        $plantId = (int) $this->resolvePlantCode($plantId);

        return $this->blendingRepo->getAllTanks($plantId, $materialId);
    }

    public function getActiveMaterials(): Collection
    {
        return $this->blendingRepo->getActiveMaterials();
    }

    public function generateEntryNo(int $materialId, int $plantId): ?string
    {
        $plantId = (int) $this->resolvePlantCode($plantId);

        return $this->blendingRepo->generateBlendingEntryNo($materialId, $plantId);
    }

    public function getTotalStockMaterial(int $materialId, int $plantId): float
    {
        $plantId = (int) $this->resolvePlantCode($plantId);

        return $this->blendingRepo->getTotalStockMaterial($materialId, $plantId);
    }

    public function getTotalQtyMaterial(?string $mode, string $entryNo, ?int $idHead, int $plantId): float
    {
        $plantId = (int) $this->resolvePlantCode($plantId);

        return $this->blendingRepo->getTotalQtyMaterial($mode, $entryNo, $idHead, $plantId);
    }

    public function getMaterialList(?string $mode, string $entryNo, ?int $idHead, int $plantId): Collection
    {
        $plantId = (int) $this->resolvePlantCode($plantId);

        return $this->blendingRepo->getMaterialList($mode, $entryNo, $idHead, $plantId);
    }

    public function getBlendingList(int $plantId, int $page = 1, int $perPage = 5): array
    {
        $plantId = (int) $this->resolvePlantCode($plantId);

        return $this->blendingRepo->getBlendingList($plantId, $page, $perPage);
    }

    public function getActiveTanksRundown(int $materialId, int $plantId): Collection
    {
        $plantId = (int) $this->resolvePlantCode($plantId);

        return $this->blendingRepo->getActiveTanksRundown($materialId, $plantId);
    }

    public function getActiveSpecificTanksRundown(int $sloc): Collection
    {
        return $this->blendingRepo->getActiveSpecificTanksRundown($sloc);
    }

    public function getTanks(?int $plantId = null): Collection
    {
        $plantId = $plantId ? (int) $this->resolvePlantCode($plantId) : null;

        return $this->blendingRepo->getTanks($plantId);
    }

    public function getTankDetails(string $tankDescription, ?int $plantId = null): Collection
    {
        $plantId = $plantId ? (int) $this->resolvePlantCode($plantId) : null;

        return $this->blendingRepo->getTankDetails($tankDescription, $plantId);
    }

    // removed duplicate getAllTanks

    public function addMaterialToBlending(string $user, array $data, int $plantId): array
    {
        $plantId = (int) $this->resolvePlantCode($plantId);
        $entryNo = $data['entryNo'];
        $idMaterial = $data['idMaterialSource'];
        $qty = (float) str_replace(',', '', (string) $data['qty']);
        $idSloc = $data['idSloc'];
        $mode = $data['mode'];

        if ($mode === 'ADD' && $this->blendingRepo->checkMaterialInTemporary($idMaterial, $entryNo, $plantId)) {
            return ['response' => 2];
        }

        return $this->blendingRepo->addBlendingEntryMaterial(
            $user, $entryNo, $idMaterial, $qty, $idSloc, $plantId
        );
    }

    public function deleteBlendingMaterial(int $id): bool
    {
        return $this->blendingRepo->deleteBlendingMaterial($id);
    }

    public function createMaterialDocument(string $user, int $idTraceHead, ?string $materialDoc, string $mode): array
    {
        return $this->blendingRepo->createMaterialDocument($user, $idTraceHead, $materialDoc, $mode);
    }

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array
    {
        return $this->blendingRepo->updateEntrySubTank($user, $idHead, $tails);
    }

    public function executeBlending(string $user, array $data, int $plantId): array
    {
        $ctx = $this->buildBlendingContext($data, $plantId);
        $preCheck = $this->checkBlendingPreConditions($ctx['entryDate'], $ctx['entryNo']);
        if ($preCheck['response'] !== 1) {
            return $preCheck;
        }
        $feedEntryNo = substr_replace($ctx['entryNo'], '0', 8, 1);

        try {
            DB::connection('eudr_ts')->beginTransaction();

            return $this->processBlendingTransaction(
                $preCheck['materialEntries'], $user, $ctx['entryDate'], $ctx['plantId'],
                $ctx['idSlocTailJson'], $feedEntryNo, $ctx['entryNo'],
                $ctx['idMaterial'], $ctx['materialDoc'], $ctx['totalQty']
            );
        } catch (\Throwable $e) {
            return $this->handleBlendingException($e);
        }
    }

    public function deactivateBlending(string $id, string $user): array
    {
        return $this->blendingRepo->deactivateBlending($id, $user);
    }

    private function buildBlendingContext(array $data, int $plantId): array
    {
        $idSlocTail = $data['tankNo'] ?? [];

        return [
            'plantId' => (int) $this->resolvePlantCode($plantId),
            'entryNo' => $data['entry_no'],
            'entryDate' => $data['entry_date'],
            'idMaterial' => $data['id_material'],
            'materialDoc' => $data['material_doc'],
            'totalQty' => (float) str_replace(',', '', (string) $data['qty']),
            'idSlocTailJson' => json_encode($idSlocTail),
        ];
    }

    private function processBlendingTransaction(
        Collection $materialEntries,
        string $user, string $entryDate, int $plantId,
        string $idSlocTailJson, string $feedEntryNo, string $entryNo,
        $idMaterial, $materialDoc, float $totalQty
    ): array {
        $feedResult = $this->executeBlendingFeed(
            $materialEntries, $user, $entryDate, $plantId,
            $idSlocTailJson, $feedEntryNo, $entryNo
        );
        if ($feedResult['response'] !== 1) {
            DB::connection('eudr_ts')->rollBack();

            return $feedResult;
        }

        // Gap 3: Explicit check for feed trace header
        $feedTraceExists = DB::connection('eudr_ts')
            ->select('SELECT id_trace_head FROM t_trace_header WHERE to_trace_no = ? AND status = 1 LIMIT 1', [$feedEntryNo]);
        if (empty($feedTraceExists)) {
            DB::connection('eudr_ts')->rollBack();

            return ['response' => 6, 'error_detail' => 'Feed trace not created'];
        }

        return $this->continueBlendingAfterFeed(
            $feedEntryNo, $plantId, $entryNo, $idMaterial, $user,
            $entryDate, $idSlocTailJson, $totalQty, $materialDoc
        );
    }

    private function continueBlendingAfterFeed(
        string $feedEntryNo, int $plantId, string $entryNo,
        $idMaterial, string $user, string $entryDate,
        string $idSlocTailJson, float $totalQty, $materialDoc
    ): array {
        $prepared = $this->prepareBlendingSupplierData($feedEntryNo, $plantId, $entryNo, $idMaterial);
        if ($prepared['response'] !== 1) {
            return $prepared;
        }
        $rundownResult = $this->runBlendingRundown(
            $user, $entryDate, $entryNo, $prepared, $idMaterial,
            $idSlocTailJson, $plantId, $totalQty
        );
        if ($rundownResult['response'] !== 1) {
            DB::connection('eudr_ts')->rollBack();

            return $rundownResult;
        }
        $this->finalizeBlending($user, $rundownResult['id_trace_head'], $materialDoc, $entryNo);

        return ['response' => 1];
    }

    private function runBlendingRundown(
        string $user, string $entryDate, string $entryNo,
        array $prepared, int $idMaterial, string $idSlocTailJson,
        int $plantId, float $totalQty
    ): array {
        $fromTraceNo = $prepared['traceData']['headers'][0]->to_trace_no;

        return $this->executeBlendingRundown(
            $user, $entryDate, $entryNo, $fromTraceNo, $idMaterial,
            $prepared['tankId'], $idSlocTailJson, $plantId,
            $prepared['inQty'], $totalQty, $prepared['supplierRows']
        );
    }

    private function finalizeBlending(string $user, int $idTraceHead, ?string $materialDoc, string $entryNo): void
    {
        $this->blendingRepo->createMaterialDocument($user, $idTraceHead, $materialDoc, 'ADD');
        DB::connection('eudr_ts')->delete('DELETE FROM t_balance_temporary WHERE entry_no = ?', [$entryNo]);
        DB::connection('eudr_ts')->commit();
    }

    private function prepareBlendingSupplierData(string $feedEntryNo, int $plantId, string $entryNo, $idMaterial): array
    {
        $traceData = $this->getBlendingTraceData($feedEntryNo, $plantId);
        $supplierRows = $this->buildBlendingSupplierRows($traceData['headers']);
        if (empty($supplierRows)) {
            DB::connection('eudr_ts')->rollBack();

            return ['response' => 6, 'error_detail' => 'No supplier rows found in trace header details'];
        }
        $inQty = round(array_sum(array_column($traceData['headers'], 'out_qty')), 4);
        $supplierRows = array_values($supplierRows);
        Rundown::adjustRundownToTotal($supplierRows, $inQty);
        $tankResult = $this->resolveBlendingTank($idMaterial, $plantId);
        if ($tankResult['response'] !== 1) {
            return $tankResult;
        }

        return ['response' => 1, 'traceData' => $traceData, 'supplierRows' => $supplierRows, 'inQty' => $inQty, 'tankId' => $tankResult['tankId']];
    }

    private function resolveBlendingTank(int $idMaterial, int $plantId): array
    {
        $tankId = $this->findBlendingTargetTank($idMaterial, $plantId);
        if ($tankId === null) {
            DB::connection('eudr_ts')->rollBack();

            return ['response' => 6, 'error_detail' => 'Target tank not found for material '.$idMaterial];
        }

        return ['response' => 1, 'tankId' => $tankId];
    }

    private function checkBlendingPreConditions(string $entryDate, string $entryNo): array
    {
        if ($this->blendingRepo->getLockStatus($entryDate)) {
            return ['response' => 99];
        }

        $itemCnt = $this->blendingRepo->getTemporaryItemCount($entryNo);
        if ($itemCnt === 0) {
            return ['response' => 4];
        }

        $datMaterial = $this->blendingRepo->getTemporaryEntries($entryNo);
        if ($datMaterial->isEmpty()) {
            return ['response' => 4];
        }

        return ['response' => 1, 'materialEntries' => $datMaterial];
    }

    private function executeBlendingFeed(
        Collection $datMaterial,
        string $user, string $entryDate, int $plantId,
        string $idSlocTailJson, string $feedEntryNo, string $entryNo
    ): array {
        foreach ($datMaterial as $row) {
            $qtySource = (float) str_replace(',', '', (string) $row->qty);
            if ($qtySource <= 0) {
                continue;
            }
            $feedResult = Feed::generalFeed([
                'user' => $user, 'entry_date' => $entryDate,
                'id_material' => $row->id_material, 'id_sloc' => $row->tf_number,
                'id_sloc_tail' => $idSlocTailJson, 'id_plant' => $plantId,
                'qty' => $qtySource, 'allow_partial' => true,
                'trace_prefixes' => [1, 2, 7, 8, 9], 'to_trace_no' => $feedEntryNo,
            ]);
            if ($feedResult['response'] != 1) {
                return $this->feedErrorResult($entryNo, $row->id_material, $feedResult);
            }
        }

        return ['response' => 1];
    }

    private function feedErrorResult(string $entryNo, int $idMaterial, array $feedResult): array
    {
        \Log::error('Blending execute: Feed failed',
            ['entryNo' => $entryNo, 'id_material' => $idMaterial, 'feedResult' => $feedResult]);

        return ['response' => $feedResult['response'],
            'error_detail' => 'Feed failed for material '.$idMaterial.' (code '.$feedResult['response'].')'];
    }

    private function getBlendingTraceData(string $feedEntryNo, int $plantId): array
    {
        $batchSeq = substr($feedEntryNo, 12, 2);
        $feedId = substr($feedEntryNo, 7, 3);
        $curDateFmt = $this->dbDateFormat($this->dbCurDate(), '%y%m%d');
        $sql = "SELECT to_trace_no, id_trace_head, out_qty, id_material
                  FROM t_trace_header
                 WHERE SUBSTRING(to_trace_no,2,6) = {$curDateFmt}
                   AND SUBSTRING(to_trace_no,1,1) = '8'
                   AND ".TraceHelper::only14Digit('to_trace_no').'
                   AND '.TraceHelper::warehouseCondition('to_trace_no', '=', $feedId).'
                   AND SUBSTRING(to_trace_no,13,2) = ?
                   AND status = 1 AND out_qty > 0.0001
                   AND (id_plant = ? OR ? = 0)
                 ORDER BY id_trace_head DESC';
        $bind = [$batchSeq, $plantId, $plantId];

        $checkTrace = DB::connection('eudr_ts')->select($sql.' LIMIT 1', $bind);
        if (! isset($checkTrace[0]->id_trace_head)) {
            return ['headers' => []];
        }

        return ['headers' => DB::connection('eudr_ts')->select($sql, $bind)];
    }

    private function buildBlendingSupplierRows(array $datTraceHead): array
    {
        $supplierRows = [];
        foreach ($datTraceHead as $th) {
            $tails = DB::connection('eudr_ts')->select(
                'SELECT id_supplier, batch_sap, out_qty FROM t_trace_detail WHERE id_trace_head = ? AND status = 1',
                [$th->id_trace_head]
            );
            if (empty($tails)) {
                continue;
            }
            foreach ($tails as $t) {
                $key = $t->id_supplier.'|'.$t->batch_sap;
                if (! isset($supplierRows[$key])) {
                    $supplierRows[$key] = ['id_supplier' => $t->id_supplier, 'batch_sap' => $t->batch_sap, 'rundownSupplier' => 0];
                }
                $supplierRows[$key]['rundownSupplier'] += round((float) $t->out_qty, 4);
            }
        }

        return $supplierRows;
    }

    private function findBlendingTargetTank(int $idMaterial, int $plantId): ?int
    {
        $datTank = DB::connection('eudr_ts')->select(
            'SELECT b.id_sloc AS tf_number FROM m_material a
               LEFT JOIN m_sloc b ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
              WHERE a.status = 1 AND a.id_material = ?',
            [$plantId, $idMaterial]
        );

        return $datTank[0]->tf_number ?? null;
    }

    private function executeBlendingRundown(
        string $user, string $entryDate, string $entryNo,
        string $fromTraceNo, int $idMaterial, int $idSloc,
        string $idSlocTailJson, int $plantId, float $inQty,
        float $totalQty, array $supplierRows
    ): array {
        return Rundown::generalRundown([
            'user' => $user,
            'entry_date' => $entryDate,
            'trace_no' => $entryNo,
            'from_trace_no' => $fromTraceNo,
            'id_material' => $idMaterial,
            'id_sloc' => $idSloc,
            'id_sloc_tail' => $idSlocTailJson,
            'id_plant' => $plantId,
            'in_qty' => $inQty,
            'last_qtf' => 0,
            'curr_qtf' => $totalQty,
            'supplier_rows' => $supplierRows,
        ]);
    }

    protected function resolvePlantCode($plantId)
    {
        return resolve(PlantContextServiceInterface::class)->resolvePlantId($plantId) ?: (string) $plantId;
    }

    private function handleBlendingException(\Throwable $e): array
    {
        DB::connection('eudr_ts')->rollBack();
        \Log::error('Blending execute exception', [
            'message' => $e->getMessage(), 'trace' => $e->getTraceAsString(),
        ]);

        return ['response' => 0, 'message' => $e->getMessage()];
    }
}
