<?php
declare(strict_types=1);
namespace Modules\TsRaw\Services;

use Modules\TsRaw\Repositories\Contracts\RmEntryRepositoryInterface;
use Modules\TsRaw\Models\BalanceHeader;
use Modules\Shared\Helpers\Feed;
use Modules\Shared\Helpers\Rundown;
use Modules\Shared\Services\Contracts\PlantContextServiceInterface;
use Illuminate\Support\Facades\DB;
use Exception;

use Modules\TsRaw\Services\Contracts\RmEntryServiceInterface;

class RmEntryService implements RmEntryServiceInterface
{
    public function __construct(
        protected RmEntryRepositoryInterface $rmEntryRepo
    ) {}

    private function jsonArrayContains(string $col, string $param): string
    {
        return "{$col} = CAST({$param} AS INTEGER)";
    }

    public function getRmList($plantId, int $page = 1, int $perPage = 5): array
    {
        return $this->rmEntryRepo->getRmList($plantId, $page, $perPage);
    }

    public function getRmEntryById($id): ?array
    {
        return $this->rmEntryRepo->getRmEntryById($id);
    }

    public function generateRmNumber($plantId): ?string
    {
        return $this->rmEntryRepo->getNewNumber($plantId);
    }

    public function generateTransferNumber($plantId, $tankDescOrId = null): ?string
    {
        return $this->rmEntryRepo->getTransferNumber($plantId, $tankDescOrId);
    }

    public function getTanks($plantId): array
    {
        return $this->rmEntryRepo->getTanks($plantId);
    }

    public function getTankDetails($tankId, $plantId): array
    {
        return $this->rmEntryRepo->getTankDetails($tankId, $plantId);
    }

    public function getMaterials(): array
    {
        return $this->rmEntryRepo->getMaterials();
    }

    public function searchSuppliers($query): array
    {
        return $this->rmEntryRepo->searchSuppliers($query);
    }

    public function addSupplierTemp($data, $user): array
    {
        return $this->rmEntryRepo->addSupplierTemp($data, $user);
    }

    public function getSupplierList($entryNo): array
    {
        return $this->rmEntryRepo->getSupplierList($entryNo);
    }

    public function deleteSupplierTemp($id, $user): void
    {
        $this->rmEntryRepo->deleteSupplierTemp($id, $user);
    }

    public function getTotalQtyTemp($entryNo): float
    {
        return $this->rmEntryRepo->getTotalQtyTemp($entryNo);
    }

    public function generateBatchCode($supplierId): ?string
    {
        return $this->rmEntryRepo->generateBatchCode($supplierId);
    }

    public function saveRmEntry($data, $user): array
    {
        return $this->rmEntryRepo->saveRmEntry($data, $user);
    }

    public function saveRmTrfEntry($data, $user): array
    {
        return $this->rmEntryRepo->saveRmTrfEntry($data, $user);
    }

    public function checkStockSynchronization($entryNo, $materialId = null): array
    {
        return $this->rmEntryRepo->checkStockSynchronization($entryNo, $materialId);
    }

    public function debugFifoStock($params): array
    {
        return $this->rmEntryRepo->debugFifoStock($params);
    }

    public function verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack = 24): array
    {
        return $this->rmEntryRepo->verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack);
    }

    public function deactivateRmEntry($id, $user): array
    {
        return $this->rmEntryRepo->deactivateRmEntry($id, $user);
    }

    public function deactivateFeedLogEntry($id, $user): array
    {
        return $this->rmEntryRepo->deactivateFeedLogEntry($id, $user);
    }

    public function updateRmEntry($id, $data, $user): array
    {
        return $this->rmEntryRepo->updateRmEntry($id, $data, $user);
    }

    // Storage and Feed Log Methods (moved from ts-transfer)
    public function getStorageLog($plantId): array
    {
        return $this->rmEntryRepo->getStorageLog($plantId);
    }

    public function getFeedLog($plantId): array
    {
        return $this->rmEntryRepo->getFeedLog($plantId);
    }

    public function debugFeedLog($plantId): array
    {
        return $this->rmEntryRepo->debugFeedLog($plantId);
    }

    public function getTransferList($plantId): array
    {
        return $this->rmEntryRepo->getTransferList($plantId);
    }

    // Transfer Methods (moved from ts-transfer)
    protected $movSeqTransfer = '000';
    protected $typeTransfer = '7';

    /**
     * NOTE: Uses BalanceHeader::findOrFail() directly instead of repository.
     * Should ideally delegate to repository for consistency with architecture.
     */
    public function transfer($data, $user): array
    {
        $data['id_plant'] = $this->resolvePlantCode($data['id_plant'] ?? 0);

        $connection = app('db')->connection('eudr_ts');
        $connection->beginTransaction();

        try {
            $sourceBalance = $this->rmEntryRepo->findBalanceHeaderById($data['id_balance_head']);
            if (!$sourceBalance) {
                throw new Exception('Source balance not found');
            }

            $sourceTrace = $this->rmEntryRepo->findTraceByBalanceHeadId($data['id_balance_head']);

            if ($sourceBalance->qty < $data['qty']) {
                throw new Exception('Insufficient quantity in source tank');
            }

            $transferNo = $this->rmEntryRepo->generateTransferNumber($data['id_plant'], $sourceBalance->id_sloc);

            $destBalance = $this->rmEntryRepo->createTransferBalance([
                'entry_date' => $data['entry_date'],
                'trace_no' => $transferNo,
                'id_material' => $sourceBalance->id_material,
                'id_sloc' => $data['id_dest_tank'],
                'id_plant' => $data['id_plant'],
                'qty' => $data['qty'],
                'created_by' => $user,
            ]);

            $this->rmEntryRepo->createTransferTrace([
                'id_balance_head' => $destBalance->id_balance_head,
                'entry_date' => $data['entry_date'],
                'from_trace_no' => $sourceBalance->trace_no,
                'to_trace_no' => $transferNo,
                'id_material' => $sourceBalance->id_material,
                'id_sloc' => $data['id_dest_tank'],
                'id_plant' => $data['id_plant'],
                'qty' => $data['qty'],
                'created_by' => $user,
            ]);

            $this->rmEntryRepo->updateSourceBalance($data['id_balance_head'], $data['qty']);

            if ($sourceTrace) {
                $this->rmEntryRepo->updateSourceTrace($data['id_balance_head'], $data['qty']);
            }

            $this->rmEntryRepo->logTransaction(
                'TRANSFER',
                'ADD',
                "From: {$sourceBalance->trace_no} To: {$transferNo} | Qty: {$data['qty']}",
                $user
            );

            $connection->commit();

            return ['success' => true, 'transfer_no' => $transferNo];

        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    //  Supporting Methods for Transfer Entry (standalone)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getActiveMaterialsForTransfer(): array
    {
        return $this->rmEntryRepo->getActiveMaterialsForTransfer();
    }

    public function generateTransferEntryNo(int $materialId, $plantId): ?string
    {
        return $this->rmEntryRepo->generateTransferEntryNo($materialId, $plantId);
    }

    public function getActiveTanksForTransfer(?int $materialId, $plantId): array
    {
        return $this->rmEntryRepo->getActiveTanksForTransfer($materialId, $plantId);
    }

    public function getActiveSpecificTanksRundown(int $sloc): array
    {
        return $this->rmEntryRepo->getActiveSpecificTanksRundown($sloc);
    }

    public function getTotalStockMaterial(int $materialId, int $tankId): float
    {
        return $this->rmEntryRepo->getTotalStockMaterial($materialId, $tankId);
    }

    public function getSupplierMaterial(int $materialId, int $tankId, $plantId): ?object
    {
        return $this->rmEntryRepo->getSupplierMaterial($materialId, $tankId, $plantId);
    }

    public function getLockStatus(string $entryDate): bool
    {
        return \Modules\Shared\Services\PeriodLockService::isLocked($entryDate);
    }

    /**
     * Execute a full transfer entry using Feed::generalFeed() + Rundown::generalRundown()
     * Matching the reference Transfer::post_transferEntry() algorithm.
     *
     * @todo Technical Debt: Directly uses Feed::generalFeed() and Rundown::generalRundown() helpers.
     * Recommended: Extract to repository methods for better testability.
     */
    public function executeTransferEntry(array $data, string $user): array
    {
        $entryNo = $data['entry_no'];
        $entryDate = $data['entry_date'];
        $idMaterial = (int) $data['id_material'];
        $materialDoc = $data['material_doc'] ?? '';
        $qty = (float) str_replace(',', '', (string)$data['qty']);
        $trfSource = (int) $data['source_sloc'];
        $trfDestination = (int) $data['dest_sloc'];
        $trfSourceTail = $data['source_tails'] ?? [];
        $trfDestinationTail = $data['dest_tails'] ?? [];
        $trfType = $data['trf_type'] ?? 'in';
        $idPlant = $data['id_plant'] ?? 0;

        $srcTailJson = json_encode($trfSourceTail);
        $destTailJson = json_encode($trfDestinationTail);

        // Resolve plant codes
        $plantCode = $this->resolvePlantCode($idPlant);

        // Get tank plants
        $srcTank = DB::connection('eudr_ts')->table('m_sloc')->where('id_sloc', $trfSource)->first();
        $destTank = DB::connection('eudr_ts')->table('m_sloc')->where('id_sloc', $trfDestination)->first();
        $srcPlant = $srcTank->id_plant ?? $plantCode;
        $destPlant = $destTank->id_plant ?? $plantCode;

        // Pre-flight: validate source has supplier detail
        $jcSloc = $this->jsonArrayContains('bh.id_sloc', '?');
        $orphanHeads = DB::connection('eudr_ts')->select(
            "SELECT bh.id_balance_head, bh.trace_no, bh.qty
               FROM t_balance_header bh
               LEFT JOIN t_balance_detail bd
                 ON bh.id_balance_head = bd.id_balance_head
                AND bd.status = 1
                AND bd.qty > 0.0001
              WHERE bh.status = 1
                AND bh.qty > 0.0001
                AND bh.id_material = ?
                AND {$jcSloc}
                AND bh.id_plant = ?
                AND bd.id_balance_tail IS NULL",
            [$idMaterial, $trfSource, $srcPlant]
        );

        if (!empty($orphanHeads)) {
            return ['response' => 6, 'message' => 'Orphan balance heads found - no supplier detail'];
        }

        // Check total stock
        $jcSloc2 = $this->jsonArrayContains('c.id_sloc', '?');
        $totalStock = DB::connection('eudr_ts')->select(
            "SELECT COALESCE(SUM(c.qty),0) AS qty
               FROM m_material a
               LEFT JOIN (SELECT b.code, b.id_material
                            FROM m_material b) b
                 ON a.code = b.code
               LEFT JOIN t_balance_header c
                 ON b.id_material = c.id_material AND c.status = 1 AND {$jcSloc2}
              WHERE a.id_material = ?
                AND a.status = 1
                AND c.qty > 0.0001
                AND (CAST(SUBSTRING(c.trace_no,1,1) AS INTEGER) = 1 OR CAST(SUBSTRING(c.trace_no,1,1) AS INTEGER) = 2 OR
                     CAST(SUBSTRING(c.trace_no,1,1) AS INTEGER) = 7 OR CAST(SUBSTRING(c.trace_no,1,1) AS INTEGER) = 8 OR
                     CAST(SUBSTRING(c.trace_no,1,1) AS INTEGER) = 9)",
            [$trfSource, $idMaterial]
        );
        $totalReserve = (float) ($totalStock[0]->qty ?? 0);

        $qty = (float) $qty;
        if (round($totalReserve - $qty, 4) < 0) {
            return ['response' => 4, 'message' => 'Insufficient stock in source tank'];
        }

        // Generate feed trace no
        if (substr($entryNo, 7, 3) == '000') {
            $feedEntryNo = substr_replace($entryNo, '1', 9, 1);
        } else {
            $feedEntryNo = substr_replace($entryNo, '0', 8, 1);
        }

        try {
            $result = DB::connection('eudr_ts')->transaction(function () use (
                $qty, $idMaterial, $trfSource, $srcTailJson, $srcPlant,
                $feedEntryNo, $entryDate, $entryNo, $trfDestination, $destTailJson,
                $destPlant, $user, $materialDoc, $idPlant
            ) {
                // Step 1: Feed - take material out of source (FIFO)
                $feedParams = [
                    'qty'          => $qty,
                    'id_material'  => $idMaterial,
                    'id_sloc'      => $trfSource,
                    'id_plant'     => $srcPlant,
                    'to_trace_no'  => $feedEntryNo,
                    'entry_date'   => $entryDate,
                    'user'         => $user,
                ];

                $rundownParams = [
                    'user'          => $user,
                    'entry_date'    => $entryDate,
                    'trace_no'      => $entryNo,
                    'from_trace_no' => $feedEntryNo,
                    'id_material'   => $idMaterial,
                    'id_sloc'       => $trfDestination,
                    'id_plant'      => $destPlant,
                    'last_qtf'      => 0,
                ];

                $orchestrator = app(\Modules\Shared\Services\FeedRundownOrchestrator::class);
                $rundownResult = $orchestrator->executeFeedRundownSequence($feedParams, $rundownParams);

                if (!isset($rundownResult['response']) || $rundownResult['response'] != 1) {
                    throw new \RuntimeException('Feed/Rundown sequence failed: response ' . ($rundownResult['response'] ?? 'unknown'));
                }

                // Step 4: Create material document
                if (!empty($materialDoc)) {
                    $traceHeadId = $rundownResult['id_trace_head'];
                    app(\Modules\Shared\Services\TransactionCoreService::class)
                        ->createMaterialDocument($user, $traceHeadId, $materialDoc, 'ADD');
                }

                return $rundownResult;
            });

            if (!isset($result['response']) || $result['response'] != 1) {
                return ['response' => 3];
            }

            // Log transaction
            $this->rmEntryRepo->logTransaction(
                'TRANSFER_ENTRY',
                'ADD',
                "EntryNo: {$entryNo} | Material: {$idMaterial} | Source: {$trfSource} >> Dest: {$trfDestination} | Qty: {$qty}",
                $user
            );

            return ['response' => 1, 'message' => 'Transfer entry created successfully'];

        } catch (\Exception $e) {
            return ['response' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delegate to TransactionCancellationService for consistent chain-of-custody handling.
     * Bridge: converts id_trace_head → pipe format (idHead|idTraceHead) expected by TCS.
     */
    public function deactivateTransfer($id, $user): array
    {
        $idTraceHead = (int) $id;
        $traceHead = $this->rmEntryRepo->findTransferById($idTraceHead);
        if (!$traceHead) {
            return ['response' => 0, 'message' => 'Transfer not found'];
        }
        $pipeId = $traceHead->id_balance_head . '|' . $idTraceHead;
        return app(\Modules\Shared\Services\TransactionCancellationService::class)
            ->deactivateTransfer($pipeId, $user);
    }

    public function getStorageTanks($plantId): array
    {
        $resolvedPlant = null;
        if ($plantId) {
            if (is_numeric($plantId)) {
                $plant = $this->rmEntryRepo->findPlantById((int)$plantId);
                if ($plant && $plant->code_3) {
                    $resolvedPlant = $plant->code_3;
                }
            } elseif ($plantId !== '0') {
                $resolvedPlant = (string)$plantId;
            }
        }

        return app(\Modules\Shared\Repositories\TankQueryRepository::class)
            ->getActiveTanksByKeywords(['STORAGE'], $resolvedPlant)
            ->toArray();
    }

    public function getSpecificTankDetails(string $tankId, mixed $plantId): array
    {
        $slocId = is_numeric($tankId)
            ? (int)$tankId
            : (int)(\Illuminate\Support\Facades\DB::table('m_sloc')
                ->where('description', $tankId)
                ->where('status', 1)
                ->value('id_sloc') ?? 0);

        if (!$slocId) return [];

        return app(\Modules\Shared\Repositories\TankQueryRepository::class)
            ->getActiveSpecificTanksRundown($slocId)
            ->toArray();
    }

    public function getRmMaterials(): array
    {
        return \Modules\Material\Models\Material::where('status', 1)
            ->where('type', 'RM')
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                return [
                    'id_material' => $item->id_material,
                    'material' => strtoupper($item->description) . ' (' . $item->code . ' / ' . $item->type . ' / Feed: ' . $item->qtf_feed . ' / Rundown: ' . $item->qtf_rundown . ')'
                ];
            })
            ->toArray();
    }

    public function searchSuppliersList(string $search): array
    {
        return $this->rmEntryRepo->getSuppliersSearch($search);
    }

    public function getSourceEntriesList($plantId): array
    {
        return BalanceHeader::active()
            ->rmEntry()
            ->where('qty', '>', 0)
            ->where('id_plant', $plantId)
            ->with(['material', 'tank'])
            ->get()
            ->map(function ($item) {
                return [
                    'id_balance_head' => $item->id_balance_head,
                    'trace_no' => $item->trace_no,
                    'material' => $item->material->description ?? 'Unknown',
                    'tank' => $item->tank->description ?? 'Unknown',
                    'qty' => $item->qty
                ];
            })
            ->toArray();
    }

    public function getDestTanksList($plantId): array
    {
        $resolvedPlant = app(PlantContextServiceInterface::class)->resolvePlantId($plantId);
        $resolvedPlant = $resolvedPlant && $resolvedPlant !== '0' ? $resolvedPlant : null;

        return app(\Modules\Shared\Repositories\TankQueryRepository::class)
            ->getActiveTanksByKeywords(['FEED'], $resolvedPlant)
            ->toArray();
    }

    public function saveMatlDoc(string $mode, int $id, string $number, string $user): array
    {
        $res = app(\Modules\Shared\Services\TransactionCoreService::class)
            ->createMaterialDocument($user, $id, $number, $mode);
            
        return ['success' => $res['response'] === 1, 'status' => $res['response']];
    }

    public function updateSubTankSlocTail(int $idHead, $idSlocTail, string $user): array
    {
        $tails = is_array($idSlocTail) ? $idSlocTail : [$idSlocTail];
        return $this->rmEntryRepo->updateEntrySubTank($user, $idHead, $tails);
    }

    protected function resolvePlantCode($plantId)
    {
        return app(PlantContextServiceInterface::class)->resolvePlantId($plantId) ?: (string) $plantId;
    }

    public function clearTempData($entryNo, $user): void
    {
        $this->rmEntryRepo->clearTempData($entryNo, $user);
    }
}
