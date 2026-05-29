<?php declare(strict_types=1);
namespace Modules\TsRaw\Services;

use Modules\TsRaw\Repositories\Contracts\RmEntryRepositoryInterface;
use Modules\TsRaw\Models\BalanceHeader;
use Modules\Shared\Helpers\Feed;
use Modules\Shared\Helpers\Rundown;
use Modules\Plant\Models\Plant;
use Illuminate\Support\Facades\DB;
use Exception;

use Modules\TsRaw\Services\Contracts\RmEntryServiceInterface;

class RmEntryService implements RmEntryServiceInterface
{
    public function __construct(
        protected RmEntryRepositoryInterface $rmEntryRepo
    ) {}

    public function getRmList($plantId)
    {
        return $this->rmEntryRepo->getRmList($plantId);
    }

    public function getRmEntryById($id)
    {
        return $this->rmEntryRepo->getRmEntryById($id);
    }

    public function generateRmNumber($plantId)
    {
        return $this->rmEntryRepo->getNewNumber($plantId);
    }

    public function generateTransferNumber($plantId)
    {
        return $this->rmEntryRepo->getTransferNumber($plantId);
    }

    public function getTanks($plantId)
    {
        return $this->rmEntryRepo->getTanks($plantId);
    }

    public function getTankDetails($tankId, $plantId)
    {
        return $this->rmEntryRepo->getTankDetails($tankId, $plantId);
    }

    public function getMaterials()
    {
        return $this->rmEntryRepo->getMaterials();
    }

    public function searchSuppliers($query)
    {
        return $this->rmEntryRepo->searchSuppliers($query);
    }

    public function addSupplierTemp($data, $user)
    {
        return $this->rmEntryRepo->addSupplierTemp($data, $user);
    }

    public function getSupplierList($entryNo)
    {
        return $this->rmEntryRepo->getSupplierList($entryNo);
    }

    public function deleteSupplierTemp($id, $user)
    {
        return $this->rmEntryRepo->deleteSupplierTemp($id, $user);
    }

    public function getTotalQtyTemp($entryNo)
    {
        return $this->rmEntryRepo->getTotalQtyTemp($entryNo);
    }

    public function generateBatchCode($supplierId)
    {
        return $this->rmEntryRepo->generateBatchCode($supplierId);
    }

    public function saveRmEntry($data, $user)
    {
        return $this->rmEntryRepo->saveRmEntry($data, $user);
    }

    public function saveRmTrfEntry($data, $user)
    {
        return $this->rmEntryRepo->saveRmTrfEntry($data, $user);
    }

    public function checkStockSynchronization($entryNo, $materialId = null)
    {
        return $this->rmEntryRepo->checkStockSynchronization($entryNo, $materialId);
    }

    public function debugFifoStock($params)
    {
        return $this->rmEntryRepo->debugFifoStock($params);
    }

    public function verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack = 24)
    {
        return $this->rmEntryRepo->verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack);
    }

    public function deactivateRmEntry($id, $user)
    {
        return $this->rmEntryRepo->deactivateRmEntry($id, $user);
    }

    public function updateRmEntry($id, $data, $user)
    {
        return $this->rmEntryRepo->updateRmEntry($id, $data, $user);
    }

    // Storage and Feed Log Methods (moved from ts-transfer)
    public function getStorageLog($plantId)
    {
        return $this->rmEntryRepo->getStorageLog($plantId);
    }

    public function getFeedLog($plantId)
    {
        return $this->rmEntryRepo->getFeedLog($plantId);
    }

    public function debugFeedLog($plantId)
    {
        return $this->rmEntryRepo->debugFeedLog($plantId);
    }

    public function getTransferList($plantId)
    {
        return $this->rmEntryRepo->getTransferList($plantId);
    }

    // Transfer Methods (moved from ts-transfer)
    protected $movSeqTransfer = '000';
    protected $typeTransfer = '7';

    public function transfer($data, $user)
    {
        $data['id_plant'] = $this->resolvePlantCode($data['id_plant'] ?? 0);

        $connection = app('db')->connection('eudr_ts');
        $connection->beginTransaction();

        try {
            $sourceBalance = BalanceHeader::findOrFail($data['id_balance_head']);
            $sourceTrace = $this->rmEntryRepo->findTraceByBalanceHeadId($data['id_balance_head']);

            if ($sourceBalance->qty < $data['qty']) {
                throw new Exception('Insufficient quantity in source tank');
            }

            $transferNo = $this->rmEntryRepo->generateTransferNumber($data['id_plant']);

            $destBalance = $this->rmEntryRepo->createTransferBalance([
                'entry_date' => $data['entry_date'],
                'trace_no' => $transferNo,
                'id_material' => $sourceBalance->id_material,
                'id_sloc' => $data['id_dest_tank'],
                'id_sloc_tail' => $data['id_dest_tank_tail'],
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
                'id_tank_tail' => $data['id_dest_tank_tail'],
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

    // ──────────────────────────────────────────────
    //  Supporting Methods for Transfer Entry (standalone)
    // ──────────────────────────────────────────────

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
        return $this->rmEntryRepo->getLockStatus($entryDate);
    }

    /**
     * Execute a full transfer entry using Feed::generalFeed() + Rundown::generalRundown()
     * Matching the reference Transfer::post_transferEntry() algorithm.
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
        $srcTank = DB::connection('eudr_ts')->table('m_tank')->where('id_tank', $trfSource)->first();
        $destTank = DB::connection('eudr_ts')->table('m_tank')->where('id_tank', $trfDestination)->first();
        $srcPlant = $srcTank->id_plant ?? $plantCode;
        $destPlant = $destTank->id_plant ?? $plantCode;

        // Pre-flight: validate source has supplier detail
        $orphanHeads = DB::connection('eudr_ts')->select(
            'SELECT bh.id_balance_head, bh.trace_no, bh.qty
               FROM t_balance_header bh
               LEFT JOIN t_balance_detail bd
                 ON bh.id_balance_head = bd.id_balance_head
                AND bd.status = 1
                AND bd.qty > "0.0001"
              WHERE bh.status = 1
                AND bh.qty > "0.0001"
                AND bh.id_material = ?
                AND bh.id_tank = ?
                AND bh.id_plant = ?
                AND bd.id_balance_tail IS NULL',
            [$idMaterial, $trfSource, $srcPlant]
        );

        if (!empty($orphanHeads)) {
            return ['response' => 6, 'message' => 'Orphan balance heads found - no supplier detail'];
        }

        // Check total stock
        $totalStock = DB::connection('eudr_ts')->select(
            'SELECT IFNULL(SUM(c.qty),0) AS qty
               FROM m_material a
               LEFT JOIN (SELECT b.code, b.id_material
                            FROM m_material b) b
                 ON a.code = b.code
               LEFT JOIN t_balance_header c
                 ON b.id_material = c.id_material AND c.status = 1 AND c.id_tank = ?
              WHERE a.id_material = ?
                AND a.status = 1
                AND c.qty > "0.0001"
                AND (SUBSTRING(c.trace_no,1,1) = 1 OR SUBSTRING(c.trace_no,1,1) = 2 OR
                     SUBSTRING(c.trace_no,1,1) = 7 OR SUBSTRING(c.trace_no,1,1) = 8 OR
                     SUBSTRING(c.trace_no,1,1) = 9)',
            [$trfSource, $idMaterial]
        );
        $totalReserve = (float) ($totalStock[0]->qty ?? 0);

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
                $feedResult = Feed::generalFeed([
                    'qty'          => $qty,
                    'id_material'  => $idMaterial,
                    'id_tank'      => $trfSource,
                    'id_tank_tail' => $srcTailJson,
                    'id_plant'     => $srcPlant,
                    'to_trace_no'  => $feedEntryNo,
                    'entry_date'   => $entryDate,
                    'user'         => $user,
                ]);

                if ($feedResult['response'] != 1) {
                    throw new \RuntimeException('Feed failed: response ' . $feedResult['response']);
                }

                // Step 2: Aggregate supplier proportions
                $supplierRows = DB::connection('eudr_ts')->select(
                    'SELECT id_supplier, batch_sap, SUM(out_qty) AS rundownSupplier
                       FROM t_trace_detail
                      WHERE status = 1
                        AND id_trace_head IN (
                            SELECT id_trace_head
                              FROM t_trace_header
                             WHERE status = 1
                               AND to_trace_no = ?
                        )
                      GROUP BY id_supplier, batch_sap',
                    [$feedEntryNo]
                );

                if (empty($supplierRows)) {
                    throw new \RuntimeException('No supplier rows found after feed');
                }

                $supplierRowsFormatted = array_map(fn($r) => [
                    'id_supplier'     => $r->id_supplier,
                    'batch_sap'       => $r->batch_sap,
                    'rundownSupplier' => (float) $r->rundownSupplier,
                ], $supplierRows);

                // Actual qty deducted
                $actualQty = round($feedResult['total_out'], 4);

                if ($actualQty <= 0) {
                    throw new \RuntimeException('Feed returned total_out=0');
                }

                Rundown::adjustRundownToTotal($supplierRowsFormatted, $actualQty);

                // Step 3: Rundown - put material into destination
                $rundownResult = Rundown::generalRundown([
                    'user'          => $user,
                    'entry_date'    => $entryDate,
                    'trace_no'      => $entryNo,
                    'from_trace_no' => $feedEntryNo,
                    'id_material'   => $idMaterial,
                    'id_tank'       => $trfDestination,
                    'id_tank_tail'  => $destTailJson,
                    'id_plant'      => $destPlant,
                    'in_qty'        => $actualQty,
                    'last_qtf'      => 0,
                    'curr_qtf'      => $actualQty,
                    'supplier_rows' => $supplierRowsFormatted,
                ]);

                if (!isset($rundownResult['response']) || $rundownResult['response'] != 1) {
                    throw new \RuntimeException('Rundown failed');
                }

                // Step 4: Create material document
                if (!empty($materialDoc)) {
                    $traceHeadId = $rundownResult['id_trace_head'];
                    $existingDoc = DB::connection('eudr_ts')->table('t_material_document')
                        ->where('id_trace_head', $traceHeadId)
                        ->first();

                    if ($existingDoc) {
                        DB::connection('eudr_ts')->table('t_material_document')
                            ->where('id_trace_head', $traceHeadId)
                            ->update(['material_document' => $materialDoc, 'updated_by' => $user]);
                    } else {
                        DB::connection('eudr_ts')->table('t_material_document')->insert([
                            'id_trace_head' => $traceHeadId,
                            'material_document' => $materialDoc,
                            'created_by' => $user,
                        ]);
                    }
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
     * Enhanced deactivate with lock period check (matching reference)
     */
    public function deactivateTransfer($id, $user)
    {
        $connection = app('db')->connection('eudr_ts');
        $connection->beginTransaction();

        try {
            $traceHead = $this->rmEntryRepo->findTransferById($id);

            if (!$traceHead) {
                throw new Exception('Transfer not found');
            }

            if ($traceHead->status == 0) {
                throw new Exception('Transfer already deactivated');
            }

            // Lock period check
            $entryDate = $traceHead->entry_date ?? null;
            if ($entryDate && $this->getLockStatus($entryDate)) {
                throw new Exception('Cannot deactivate: period is locked');
            }

            $toTraceNo = $traceHead->to_trace_no ?? '';
            if (substr($toTraceNo, 0, 1) !== '7') {
                throw new Exception('Only transfer entries (prefix 7) can be deactivated');
            }

            $sourceTraceNo = $traceHead->from_trace_no;
            $sourceBalance = $this->rmEntryRepo->findBalanceByTraceNo($sourceTraceNo);

            if ($sourceBalance) {
                $this->rmEntryRepo->revertSourceBalance($sourceTraceNo, $traceHead->in_qty);

                $sourceTrace = $this->rmEntryRepo->findTraceByBalanceHeadId($sourceBalance->id_balance_head);
                if ($sourceTrace) {
                    $this->rmEntryRepo->revertSourceTrace($sourceBalance->id_balance_head, $traceHead->in_qty);
                }
            }

            $this->rmEntryRepo->deactivateBalance($traceHead->id_balance_head, $user);
            $this->rmEntryRepo->deactivateTrace($id, $user);

            $this->rmEntryRepo->logTransaction(
                'TRANSFER_ENTRY',
                'DEACTIVATE',
                "ID: {$id} | Reverted From: {$traceHead->from_trace_no} | Qty: {$traceHead->in_qty}",
                $user
            );

            $connection->commit();

            return ['success' => true];

        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function getStorageTanks($plantId): array
    {
        if ($plantId) {
            if (is_numeric($plantId)) {
                $plant = Plant::find($plantId);
                if ($plant && $plant->code_3) {
                    $plantId = $plant->code_3;
                }
            }
        }

        $query = \Modules\Tank\Models\Tank::active()->storage();

        if ($plantId) {
            $query->where('id_plant', $plantId);
        }

        return $query->orderBy('description')
            ->groupBy('description', 'id_plant')
            ->get(['description as tank', 'id_plant'])
            ->toArray();
    }

    public function getSpecificTankDetails($tankId, $plantId): array
    {
        if ($plantId) {
            if (is_numeric($plantId)) {
                $plant = Plant::find($plantId);
                if ($plant && $plant->code_3) {
                    $plantId = $plant->code_3;
                }
            }
        }

        $tanksQuery = \Modules\Tank\Models\Tank::active()->where('description', $tankId);
        if ($plantId) {
            $tanksQuery->where('id_plant', $plantId);
        }
        $tanks = $tanksQuery->get();

        $result = [];
        foreach ($tanks as $tank) {
            if (!empty($tank->id_tank)) {
                $result[] = [
                    'id_tank_tail' => $tank->id_sloc,
                    'tankNo' => $tank->id_tank,
                    'id_sloc' => $tank->id_sloc
                ];
            }
        }

        usort($result, function($a, $b) {
            return strcmp($a['tankNo'], $b['tankNo']);
        });

        return $result;
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
        return \Modules\Supplier\Models\Supplier::where('status', 1)
            ->where('description', 'like', '%' . $search . '%')
            ->orderBy('description')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id_supplier,
                    'text' => $item->code . ' :: ' . $item->description
                ];
            })
            ->toArray();
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
        if ($plantId) {
            $plant = Plant::find($plantId);
            if ($plant && $plant->code_3) {
                $plantId = $plant->code_3;
            }
        }
        $query = \Modules\Tank\Models\Tank::active()
            ->feed()
            ->orderBy('description')
            ->groupBy('description', 'id_plant');
        
        if ($plantId && $plantId !== '0' && $plantId !== 0) {
            $query->where('id_plant', $plantId);
        }
        
        return $query->get(['description as tank', 'id_plant'])->toArray();
    }

    public function saveMatlDoc(string $mode, int $id, string $number, string $user): array
    {
        if ($mode === 'ADD') {
            $exists = DB::connection('eudr_ts')->table('t_material_document')
                ->where('id_trace_head', $id)
                ->exists();
            if ($exists) {
                DB::connection('eudr_ts')->table('t_material_document')
                    ->where('id_trace_head', $id)
                    ->update(['material_document' => $number, 'updated_by' => $user]);
            } else {
                DB::connection('eudr_ts')->table('t_material_document')->insert([
                    'id_trace_head' => $id,
                    'material_document' => $number,
                    'created_by' => $user
                ]);
            }
        } else {
            DB::connection('eudr_ts')->table('t_material_document')
                ->where('id_trace_head', $id)
                ->update(['material_document' => $number, 'updated_by' => $user]);
        }

        return ['success' => true, 'status' => 1];
    }

    public function updateSubTankSlocTail(int $idHead, $idTankTail): array
    {
        $tankTailJson = null;
        if (!empty($idTankTail)) {
            if (is_array($idTankTail)) {
                $tankTailJson = json_encode(array_map('strval', array_values($idTankTail)));
            } else {
                $decoded = json_decode($idTankTail, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $tankTailJson = json_encode(array_map('strval', array_values($decoded)));
                } else {
                    $tankTailJson = json_encode([(string)$idTankTail]);
                }
            }
        }

        DB::connection('eudr_ts')->table('t_balance_header')
            ->where('id_balance_head', $idHead)
            ->update(['id_sloc_tail' => $tankTailJson]);

        return ['success' => true, 'status' => 1];
    }

    protected function resolvePlantCode($plantId)
    {
        if ($plantId) {
            $plant = Plant::find($plantId);
            if ($plant && $plant->code_3) {
                return $plant->code_3;
            }
        }
        return $plantId;
    }
}
