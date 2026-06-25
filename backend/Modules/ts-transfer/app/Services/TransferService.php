<?php
declare(strict_types=1);
namespace Modules\TsTransfer\Services;

use Modules\TsTransfer\Repositories\Contracts\TransferRepositoryInterface;
use Modules\TsTransfer\Services\Contracts\TransferServiceInterface;
use Modules\TsTransfer\Services\TransferApprovalService;
use Modules\Shared\Helpers\Feed;
use Modules\Shared\Helpers\Rundown;
use Modules\Shared\Services\AuditService;
use Modules\Shared\Services\PeriodLockService;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * @todo Technical Debt: This class is 421 lines (limit: 200). Requires refactoring into smaller, focused classes.
 * - Split into: TransferQueryService (getActiveMaterials, getTransferList, getActiveTanksRundown, getActiveSpecificTanksRundown, getUpdateSupplierMaterial),
 *   TransferExecutionService (executeTransfer, executeTransferWithAdjustment, deactivateTransfer, resolvePlantCode),
 *   TransferApprovalFacade (submitForApproval, approveTransfer, rejectTransfer, cancelTransfer, getPendingApprovals, getApprovalHistory)
 */
class TransferService implements TransferServiceInterface
{
    public function __construct(
        protected TransferRepositoryInterface $transferRepo,
        protected TransferApprovalService $approvalService
    ) {}

    public function getActiveMaterials(): array
    {
        return $this->transferRepo->getActiveMaterials()->toArray();
    }

    public function generateEntryNo(int $materialId, int $plantId): ?string
    {
        $plantCode = 0;
        if ($plantId > 0) {
            $plantCode = (int) $this->resolvePlantCode($plantId);
        }
        return $this->transferRepo->generateTransferEntryNo($materialId, $plantCode);
    }

    public function getTotalStockMaterial(int $materialId, int $tankId, int $plantId): float
    {
        $plantCode = 0;
        if ($plantId > 0) {
            $plantCode = (int) $this->resolvePlantCode($plantId);
        }
        return $this->transferRepo->getTotalStockMaterial($materialId, $tankId, $plantCode);
    }

    public function getTransferList(int $plantId, int $page = 1, int $perPage = 5): array
    {
        $plantCode = 0;
        if ($plantId > 0) {
            $plantCode = (int) $this->resolvePlantCode($plantId);
        }
        return $this->transferRepo->getTransferList($plantCode, $page, $perPage);
    }

    public function getActiveTanksRundown(?int $materialId, int $plantId, bool $excludePlant = true): array
    {
        $plantCode = 0;
        if ($plantId > 0) {
            $plantCode = (int) $this->resolvePlantCode($plantId);
        }
        return $this->transferRepo->getActiveTanksRundown($materialId, $plantCode, $excludePlant)->toArray();
    }

    public function getActiveSpecificTanksRundown(int $sloc): array
    {
        return $this->transferRepo->getActiveSpecificTanksRundown($sloc)->toArray();
    }

    public function getUpdateSupplierMaterial(int $idMaterial, int $idSloc, int $plantId): ?object
    {
        $plantCode = 0;
        if ($plantId > 0) {
            $plantCode = (int) $this->resolvePlantCode($plantId);
        }
        return $this->transferRepo->getUpdateSupplierMaterial($idMaterial, $idSloc, $plantCode);
    }

    public function createMaterialDocument(string $user, int $idTraceHead, string $materialDoc, string $mode): array
    {
        return $this->transferRepo->createMaterialDocument($user, $idTraceHead, $materialDoc, $mode);
    }

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array
    {
        return $this->transferRepo->updateEntrySubTank($user, $idHead, $tails);
    }

    public function deactivateTransfer(string $id, string $user): array
    {
        // Check approval status before deactivating
        $idTmp = explode("|", $id);
        $idHead = trim($idTmp[0]);

        if (!$this->approvalService->canDelete((int)$idHead)) {
            return ['response' => 5, 'message' => 'Transfer cannot be deleted in current approval status'];
        }

        return $this->transferRepo->deactivateTransfer($id, $user);
    }

    // ========== APPROVAL WORKFLOW METHODS ==========

    /**
     * Submit transfer for approval.
     */
    public function submitForApproval(int $idBalanceHead, string $user): array
    {
        return $this->approvalService->submit($idBalanceHead, $user);
    }

    /**
     * Approve a transfer.
     */
    public function approveTransfer(int $idBalanceHead, string $user, ?string $notes = null): array
    {
        return $this->approvalService->approve($idBalanceHead, $user, $notes);
    }

    /**
     * Reject a transfer.
     */
    public function rejectTransfer(int $idBalanceHead, string $user, string $reason): array
    {
        return $this->approvalService->reject($idBalanceHead, $user, $reason);
    }

    /**
     * Cancel a transfer.
     */
    public function cancelTransfer(int $idBalanceHead, string $user): array
    {
        return $this->approvalService->cancel($idBalanceHead, $user);
    }

    /**
     * Get pending approvals.
     */
    public function getPendingApprovals(int $plantId = 0): array
    {
        return $this->approvalService->getPendingApprovals($plantId);
    }

    /**
     * Get approval history.
     */
    public function getApprovalHistory(int $idBalanceHead): array
    {
        return $this->approvalService->getApprovalHistory($idBalanceHead);
    }

    public function executeTransfer(string $user, array $data, int $plantId): array
    {
        $params = $this->parseTransferParams($data);
        $plants = $this->resolveTransferPlants($params->trfSource, $params->trfDestination);
        if ($plants === null) {
            return ['response' => 9, 'message' => 'Source or Destination Tank is inactive'];
        }
        $preCheck = $this->validateTransferPreConditions(
            $params->entryDate, $params->idMaterial, $params->trfSource,
            $plants['srcPlant'], $params->trfQty, $plantId
        );
        if ($preCheck['response'] !== 1) return $preCheck;
        $traceNos = $this->generateTransferTraceNumbers(
            $params->entryNo, $plants['srcPlant'], $plants['destPlant']
        );
        return $this->runTransferTransaction($user, $data, $params, $plants, $traceNos);
    }

    private function runTransferTransaction(string $user, array $data, $params, $plants, $traceNos): array
    {
        DB::connection('eudr_ts')->beginTransaction();
        try {
            if ($this->transferRepo->getLockStatus($params->entryDate)) {
                DB::connection('eudr_ts')->rollBack();
                return ['response' => 99];
            }
            $result = $this->processTransferTransaction($params, $plants, $traceNos, $user, $data);
            if ($result['response'] == 1) {
                DB::connection('eudr_ts')->commit();
            } else {
                DB::connection('eudr_ts')->rollBack();
            }
            return $result;
        } catch (Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            AuditService::logTransfer('CREATE', $data, $user, 0);
            return ['response' => 0, 'message' => $e->getMessage()];
        }
    }

    private function processTransferTransaction($params, $plants, $traceNos, $user, $data): array
    {
        $feedParams = [
            'qty' => $params->trfQty, 'id_material' => $params->idMaterial,
            'id_sloc' => $params->trfSource, 'id_plant' => $plants['srcPlant'],
            'to_trace_no' => $traceNos['feed'], 'entry_date' => $params->entryDate,
            'user' => $user, 'trace_prefixes' => [1, 2, 7, 8, 9],
        ];

        $rundownParams = [
            'user' => $user, 'entry_date' => $params->entryDate,
            'trace_no' => $traceNos['rundown'], 'from_trace_no' => $traceNos['feed'],
            'id_material' => $params->idMaterial, 'id_sloc' => $params->trfDestination,
            'id_plant' => $plants['destPlant'], 'last_qtf' => 0,
        ];

        $orchestrator = app(\Modules\Shared\Services\FeedRundownOrchestrator::class);
        $result = $orchestrator->executeFeedRundownSequence($feedParams, $rundownParams);

        if ($result['response'] != 1) return $result;
        
        $this->createDocumentIfProvided($user, $params->materialDoc, $result);
        AuditService::logTransfer('CREATE', $data, $user, 1);
        return ['response' => 1];
    }

    private function createDocumentIfProvided(string $user, string $materialDoc, array $rundownResult): void
    {
        if (!empty($materialDoc) && isset($rundownResult['id_trace_head'])) {
            $this->transferRepo->createMaterialDocument($user, $rundownResult['id_trace_head'], $materialDoc, 'ADD');
        }
    }

    public function executeTransferWithAdjustment(string $user, array $data, int $plantId): array
    {
        $trfParams = $this->extractTransferAdjustParams($data);
        $currentStock = $this->transferRepo->getTotalStockMaterial($trfParams['idMaterial'], $trfParams['trfSource'], $plantId);
        $shortQty = $trfParams['trfQty'] - $currentStock;

        if ($shortQty > 0) {
            $adjResult = $this->createAdjustmentForTransfer(
                $user, $trfParams['idMaterial'], $trfParams['trfSource'], $shortQty,
                $trfParams['supplierCode'], $trfParams['idSupplier'], $plantId, $data['entry_date']
            );
            if ($adjResult['response'] !== 1) return $adjResult;
        } else {
            AuditService::log('TRANSFER', 'AUTO_ADJUST_CHECK',
                'No adjustment needed | Material: ' . $trfParams['idMaterial']
                . ' | CurrentStock: ' . $currentStock . ' | Requested: ' . $trfParams['trfQty'],
                $user);
        }
        return $this->executeTransfer($user, $data, $plantId);
    }

    private function extractTransferAdjustParams(array $data): array
    {
        return [
            'trfQty' => (float) str_replace(',', '', (string)$data['trf_qty']),
            'idMaterial' => (int) $data['id_material'],
            'trfSource' => (int) $data['source_sloc'],
            'supplierCode' => $data['supplierCode'] ?? '',
            'idSupplier' => (int) ($data['idSupplier'] ?? 0),
        ];
    }

    private function parseTransferParams(array $data): object
    {
        return (object) [
            'entryNo' => $data['entry_no'],
            'entryDate' => $data['entry_date'],
            'idMaterial' => (int) $data['id_material'],
            'materialDoc' => $data['material_doc'] ?? '',
            'trfQty' => (float) str_replace(',', '', (string)$data['trf_qty']),
            'trfSource' => (int) $data['source_sloc'],
            'trfDestination' => (int) $data['trf_sloc'],
            'trfType' => $data['trf_type'] ?? 'out',
        ];
    }

    private function resolveTransferPlants(int $trfSource, int $trfDestination): ?array
    {
        $srcPlant = $this->transferRepo->getSlocPlant($trfSource);
        $destPlant = $this->transferRepo->getSlocPlant($trfDestination);

        if (!$srcPlant || !$destPlant) {
            return null;
        }

        return ['srcPlant' => $srcPlant, 'destPlant' => $destPlant];
    }

    private function validateTransferPreConditions(
        string $entryDate, int $idMaterial, int $trfSource,
        int $srcPlant, float $trfQty, int $plantId
    ): array {
        if ($this->transferRepo->getLockStatus($entryDate)) {
            return ['response' => 99];
        }

        $orphanHeads = $this->transferRepo->findOrphanHeads($idMaterial, $trfSource, $srcPlant);
        if (count($orphanHeads) > 0) {
            return ['response' => 6];
        }

        $totalReserve = $this->transferRepo->getTotalStockMaterial($idMaterial, $trfSource, $plantId);
        $trfQtyFloat = (float) $trfQty;
        if (round($totalReserve - $trfQtyFloat, 4) < 0) {
            return ['response' => 4];
        }

        return ['response' => 1];
    }

    private function generateTransferTraceNumbers(string $entryNo, int $srcPlant, int $destPlant): array
    {
        $ymd = substr($entryNo, 1, 6);
        $rundownRaw = substr($entryNo, 7, 3);
        $feedPlantCode = str_pad(substr((string)$srcPlant, -2), 2, '0', STR_PAD_LEFT);
        $destPlantCode = str_pad(substr((string)$destPlant, -2), 2, '0', STR_PAD_LEFT);

        // GAP 3: For raw material (RRR=000), feedEntryNo = entryNo (no modification)
        $isRaw = ($rundownRaw == '000');
        $feedRundown = $isRaw ? $rundownRaw : substr_replace($rundownRaw, '0', 1, 1);

        // GAP 1&2: Generate sequence based on destination plant for entryNo
        // and source plant for feedEntryNo, instead of context plant
        $destSeq = $this->transferRepo->getNextSequence($ymd, $rundownRaw, $destPlantCode);
        $feedSeq = $this->transferRepo->getNextSequence($ymd, $feedRundown, $feedPlantCode);

        $entryNo = "7{$ymd}{$rundownRaw}{$destPlantCode}{$destSeq}";
        $feedEntryNo = "7{$ymd}{$feedRundown}{$feedPlantCode}{$feedSeq}";

        $resolve = fn(string $no): string => $this->resolveTraceCollision($no);

        return ['rundown' => $resolve($entryNo), 'feed' => $resolve($feedEntryNo)];
    }

    private function resolveTraceCollision(string $traceNo): string
    {
        while ($this->transferRepo->checkTraceNoExists($traceNo)) {
            $seq = (int) substr($traceNo, 12, 2) + 1;
            $traceNo = substr_replace($traceNo, str_pad((string)$seq, 2, '0', STR_PAD_LEFT), 12, 2);
        }
        return $traceNo;
    }

    private function createAdjustmentForTransfer(
        string $user, int $idMaterial, int $trfSource, float $shortQty,
        string $supplierCode, int $idSupplier, int $plantId, string $entryDate
    ): array {
        $plantRecord = $this->transferRepo->findPlantById($plantId);
        $plantCode = $plantRecord ? substr($plantRecord->code_3, -2) : '01';
        $ymd = date('ymd', strtotime($entryDate));
        $rundown = $this->transferRepo->findMaterialRundown($idMaterial);
        $prefix12 = '9' . $ymd . $rundown . $plantCode;
        $adjEntryNo = $prefix12 . $this->transferRepo->generateAdjSequence($prefix12);
        $slocJson = json_encode([$trfSource]);

        try {
            DB::connection('eudr_ts')->transaction(
                fn() => $this->writeAdjustmentRecords($user, $adjEntryNo, $idSupplier, $idMaterial, $shortQty, $supplierCode, $plantId, $trfSource, $entryDate, $slocJson)
            );
        } catch (Exception $e) {
            return $this->handleAdjustmentException($adjEntryNo, $idMaterial, $shortQty, $user, $e);
        }

        return ['response' => 1];
    }

    private function writeAdjustmentRecords(string $user, string $adjEntryNo, int $idSupplier, int $idMaterial, float $shortQty, string $supplierCode, int $plantId, int $trfSource, string $entryDate, string $slocJson): void
    {
        $this->transferRepo->postAdjEntrySupplier($user, $adjEntryNo, $idSupplier, $idMaterial, $shortQty, $supplierCode, $plantId);
        $idHead = $this->transferRepo->createBalanceHeader(
            $this->adjustmentRecord($adjEntryNo, $idMaterial, $slocJson, $plantId, $shortQty, $entryDate, $user)
        );
        $idTail = $this->transferRepo->createBalanceDetail(
            $this->adjustmentDetailRecord($idHead, $idSupplier, $supplierCode, $idMaterial, $slocJson, $plantId, $shortQty, $user)
        );
        $adjHeadId = $this->transferRepo->createAdjustmentHeader(
            $this->adjustmentHeadRecord($entryDate, $adjEntryNo, $idHead, $idMaterial, $slocJson, $plantId, $shortQty, $user)
        );
        $this->transferRepo->createAdjustmentDetail(
            $this->adjustmentTailRecord($adjHeadId, $idTail, $idSupplier, $idMaterial, $plantId, $supplierCode, $shortQty, $user)
        );
    }

    private function handleAdjustmentException(string $adjEntryNo, int $idMaterial, float $shortQty, string $user, Exception $e): array
    {
        AuditService::logAdjustment('CREATE', [
            'entry_no' => $adjEntryNo, 'id_material' => $idMaterial,
            'qty' => $shortQty, 'before_adjust' => 0, 'after_adjust' => $shortQty,
        ], $user, 0);
        return ['response' => 0, 'message' => 'Adjustment failed: ' . $e->getMessage()];
    }

    private function adjustmentRecord(string $traceNo, int $idMaterial, string $slocJson, int $plantId, float $qty, string $entryDate, string $user): array
    {
        return ['trace_no' => $traceNo, 'id_material' => $idMaterial, 'id_sloc' => $slocJson,
            'id_plant' => $plantId, 'qty' => $qty, 'in_qty' => $qty, 'out_qty' => 0,
            'init_qty' => $qty, 'entry_date' => $entryDate, 'created_by' => $user, 'status' => 1];
    }

    private function adjustmentDetailRecord(int $idHead, int $idSupplier, string $batchSap, int $idMaterial, string $slocJson, int $plantId, float $qty, string $user): array
    {
        return ['id_balance_head' => $idHead, 'id_supplier' => $idSupplier, 'batch_sap' => $batchSap,
            'id_material' => $idMaterial, 'id_sloc' => $slocJson, 'qty' => $qty, 'in_qty' => $qty,
            'out_qty' => 0, 'init_qty' => $qty, 'id_plant' => $plantId, 'created_by' => $user, 'status' => 1];
    }

    private function adjustmentHeadRecord(string $entryDate, string $adjNo, int $idHead, int $idMaterial, string $slocJson, int $plantId, float $qty, string $user): array
    {
        return ['entry_date' => $entryDate, 'adjust_no' => $adjNo, 'id_balance_head' => $idHead,
            'id_material' => $idMaterial, 'id_sloc' => $slocJson, 'id_plant' => $plantId,
            'in_qty' => $qty, 'out_qty' => 0, 'before_adjust' => 0, 'after_adjust' => $qty,
            'created_by' => $user, 'status' => 1];
    }

    private function adjustmentTailRecord(int $adjHeadId, int $idTail, int $idSupplier, int $idMaterial, int $plantId, string $batchSap, float $qty, string $user): array
    {
        return ['id_adjust_head' => $adjHeadId, 'id_balance_tail' => $idTail, 'id_supplier' => $idSupplier,
            'id_material' => $idMaterial, 'id_plant' => $plantId, 'batch_sap' => $batchSap,
            'in_qty' => $qty, 'out_qty' => 0, 'before_adjust' => 0, 'after_adjust' => $qty,
            'created_by' => $user, 'status' => 1];
    }

    protected function resolvePlantCode($plantId): string
    {
        return $this->transferRepo->findPlantCode($plantId);
    }
}
