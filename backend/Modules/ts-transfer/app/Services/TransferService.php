<?php declare(strict_types=1);
namespace Modules\TsTransfer\Services;

use Modules\TsTransfer\Repositories\Contracts\TransferRepositoryInterface;
use Modules\Shared\Helpers\Feed;
use Modules\Shared\Helpers\Rundown;
use Modules\Plant\Models\Plant;
use Exception;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function __construct(
        protected TransferRepositoryInterface $transferRepo
    ) {}

    public function getActiveMaterials()
    {
        return $this->transferRepo->getActiveMaterials();
    }

    public function generateEntryNo(int $materialId, int $plantId): ?string
    {
        return $this->transferRepo->generateTransferEntryNo($materialId, $plantId);
    }

    public function getTotalStockMaterial(int $materialId, int $tankId, int $plantId): float
    {
        return $this->transferRepo->getTotalStockMaterial($materialId, $tankId, $plantId);
    }

    public function getTransferList(int $plantId)
    {
        $plantCode = 0;
        if ($plantId > 0) {
            $plantCode = (int) $this->resolvePlantCode($plantId);
        }
        return $this->transferRepo->getTransferList($plantCode);
    }

    public function getActiveTanksRundown(?int $materialId, int $plantId)
    {
        return $this->transferRepo->getActiveTanksRundown($materialId, $plantId);
    }

    public function getActiveSpecificTanksRundown(int $sloc)
    {
        return $this->transferRepo->getActiveSpecificTanksRundown($sloc);
    }

    public function getUpdateSupplierMaterial(int $idMaterial, int $idTank, int $plantId): ?object
    {
        return $this->transferRepo->getUpdateSupplierMaterial($idMaterial, $idTank, $plantId);
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
        return $this->transferRepo->deactivateTransfer($id, $user);
    }

    public function executeTransfer(string $user, array $data, int $plantId): array
    {
        $entryNo = $data['entry_no'];
        $entryDate = $data['entry_date'];
        $idMaterial = (int) $data['id_material'];
        $materialDoc = $data['material_doc'] ?? '';
        $trfQty = (float) str_replace(',', '', (string)$data['trf_qty']);
        $trfSource = (int) $data['source_sloc'];
        $trfDestination = (int) $data['trf_sloc'];
        $trfSourceTail = $data['source_sloc_no'] ?? [];
        $trfDestinationTail = $data['trf_sloc_no'] ?? [];
        $trfType = $data['trf_type'] ?? 'out';
        $supplierCode = $data['supplierCode'] ?? '';
        $idSupplier = (int) ($data['idSupplier'] ?? 0);

        $srcTailJson = json_encode($trfSourceTail);
        $destTailJson = json_encode($trfDestinationTail);

        // Resolve plant IDs for source and destination tanks
        $srcPlant = DB::connection('eudr_ts')->table('m_tank')
            ->where('id_tank', $trfSource)->value('id_plant');

        $destPlant = DB::connection('eudr_ts')->table('m_tank')
            ->where('id_tank', $trfDestination)->value('id_plant');

        if (!$srcPlant || !$destPlant) {
            return ['response' => 9, 'message' => 'Source or Destination Tank is inactive'];
        }

        // Check lock period
        if ($this->transferRepo->getLockStatus($entryDate)) {
            return ['response' => 99];
        }

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

        if (count($orphanHeads) > 0) {
            return ['response' => 6];
        }

        // Check total stock
        $totalReserve = $this->transferRepo->getTotalStockMaterial($idMaterial, $trfSource, $plantId);
        if (round($totalReserve - $trfQty, 4) < 0) {
            return ['response' => 4];
        }

        // Generate feed entry number (replace rundown_id digit with 0 for feed)
        $feedEntryNo = $entryNo;
        if (substr($entryNo, 7, 3) == '000') {
            $feedEntryNo = substr_replace($entryNo, '1', 9, 1);
        } else {
            $feedEntryNo = substr_replace($entryNo, '0', 8, 1);
        }

        // Enforce Plant Codes for Feed (Source) and Rundown (Destination)
        $feedPlantCode = str_pad(substr((string)$srcPlant, -2), 2, '0', STR_PAD_LEFT);
        $destPlantCode = str_pad(substr((string)$destPlant, -2), 2, '0', STR_PAD_LEFT);

        $feedEntryNo = substr_replace($feedEntryNo, $destPlantCode, 10, 2);
        $entryNo = substr_replace($entryNo, $feedPlantCode, 10, 2);

        // Handle trace number collision for rundown (dest)
        while ($this->transferRepo->checkTraceNoExists($entryNo)) {
            $seq = (int)substr($entryNo, 12, 2);
            $seq++;
            $entryNo = substr_replace($entryNo, str_pad((string)$seq, 2, '0', STR_PAD_LEFT), 12, 2);
        }

        // Handle trace number collision for feed (source)
        while ($this->transferRepo->checkTraceNoExists($feedEntryNo)) {
            $seq = (int)substr($feedEntryNo, 12, 2);
            $seq++;
            $feedEntryNo = substr_replace($feedEntryNo, str_pad((string)$seq, 2, '0', STR_PAD_LEFT), 12, 2);
        }

        try {
            return DB::connection('eudr_ts')->transaction(function () use (
                $trfQty, $idMaterial, $trfSource, $srcTailJson, $srcPlant,
                $feedEntryNo, $entryDate, $entryNo, $trfDestination, $destTailJson,
                $destPlant, $user, $materialDoc, $trfType, $supplierCode, $idSupplier,
                $plantId, $trfSourceTail, $trfDestinationTail
            ) {
                // Step 1: Feed from source tank
                $feedResult = Feed::generalFeed([
                    'qty' => $trfQty,
                    'id_material' => $idMaterial,
                    'id_tank' => $trfSource,
                    'id_tank_tail' => $srcTailJson,
                    'id_plant' => $srcPlant,
                    'to_trace_no' => $feedEntryNo,
                    'entry_date' => $entryDate,
                    'user' => $user,
                    'trace_prefixes' => [1, 2, 7, 8, 9],
                ]);

                if ($feedResult['response'] != 1) {
                    DB::connection('eudr_ts')->rollBack();
                    return ['response' => $feedResult['response']];
                }

                // Step 2: Convert feed result supplier details to rundown format
                if (empty($feedResult['feed_in_details'])) {
                    DB::connection('eudr_ts')->rollBack();
                    return ['response' => 6];
                }

                $supplierRowsFormatted = array_map(fn($d) => [
                    'id_supplier' => $d['id_supplier'],
                    'batch_sap' => $d['batch_sap'],
                    'rundownSupplier' => (float) $d['qty'],
                ], $feedResult['feed_in_details']);

                $actualQty = round($feedResult['total_out'], 4);

                // Step 5: Create destination via Rundown
                $rundownResult = Rundown::generalRundown([
                    'user' => $user,
                    'entry_date' => $entryDate,
                    'trace_no' => $entryNo,
                    'from_trace_no' => $feedEntryNo,
                    'id_material' => $idMaterial,
                    'id_tank' => $trfDestination,
                    'id_tank_tail' => $destTailJson,
                    'id_plant' => $destPlant,
                    'in_qty' => $actualQty,
                    'last_qtf' => 0,
                    'curr_qtf' => $actualQty,
                    'supplier_rows' => $supplierRowsFormatted,
                ]);

                if ($rundownResult['response'] != 1) {
                    DB::connection('eudr_ts')->rollBack();
                    return ['response' => 3];
                }

                // Step 6: Create material document if provided
                if (!empty($materialDoc) && isset($rundownResult['id_trace_head'])) {
                    $this->transferRepo->createMaterialDocument($user, $rundownResult['id_trace_head'], $materialDoc, 'ADD');
                }

                // Step 7: Auto-TRF for specific destination tanks
                $autoTrfTanks = [5, 6, 12, 13, 24, 25, 28, 29, 32, 33];
                if ($trfType !== 'all' && in_array($trfDestination, $autoTrfTanks)) {
                    $autoEntryNo = $entryNo + 1;
                    while ($this->transferRepo->checkTraceNoExists((string)$autoEntryNo)) {
                        $autoEntryNo++;
                    }

                    $autoFeedNo = substr_replace((string)$autoEntryNo, '0', 8, 1);

                    $feedResult2 = Feed::generalFeed([
                        'qty' => $actualQty,
                        'id_material' => $idMaterial,
                        'id_tank' => $trfDestination,
                        'id_tank_tail' => $destTailJson,
                        'id_plant' => $destPlant,
                        'to_trace_no' => $autoFeedNo,
                        'entry_date' => $entryDate,
                        'user' => $user,
                        'trace_prefixes' => [1, 2, 7, 8, 9],
                    ]);

                    if ($feedResult2['response'] == 1) {
                        $supplierRowsFormatted2 = array_map(fn($d) => [
                            'id_supplier' => $d['id_supplier'],
                            'batch_sap' => $d['batch_sap'],
                            'rundownSupplier' => (float) $d['qty'],
                        ], $feedResult2['feed_in_details']);

                        Rundown::generalRundown([
                            'user' => $user,
                            'entry_date' => $entryDate,
                            'trace_no' => (string)$autoEntryNo,
                            'from_trace_no' => $autoFeedNo,
                            'id_material' => $idMaterial,
                            'id_tank' => 10,
                            'id_tank_tail' => $destTailJson,
                            'id_plant' => $destPlant,
                            'in_qty' => $actualQty,
                            'last_qtf' => 0,
                            'curr_qtf' => $actualQty,
                            'supplier_rows' => $supplierRowsFormatted2,
                        ]);
                    }
                }

                return ['response' => 1];
            });
        } catch (Exception $e) {
            return ['response' => 0, 'message' => $e->getMessage()];
        }
    }

    public function executeTransferWithAdjustment(string $user, array $data, int $plantId): array
    {
        $trfQty = (float) str_replace(',', '', (string)$data['trf_qty']);
        $idMaterial = (int) $data['id_material'];
        $trfSource = (int) $data['source_sloc'];
        $supplierCode = $data['supplierCode'] ?? '';
        $idSupplier = (int) ($data['idSupplier'] ?? 0);

        // Check current stock
        $currentStock = $this->transferRepo->getTotalStockMaterial($idMaterial, $trfSource, $plantId);
        $shortQty = $trfQty - $currentStock;

        if ($shortQty > 0) {
            // Generate adjustment entry number (prefix 9)
            $adjEntryNo = '9' . date('ymd') . '001' . sprintf('%02d', $plantId) . '01';

            try {
                DB::connection('eudr_ts')->transaction(function () use (
                    $user, $adjEntryNo, $idSupplier, $idMaterial, $shortQty, $supplierCode, $plantId, $trfSource, $data
                ) {
                    // Entry to t_balance_temporary
                    $this->transferRepo->postAdjEntrySupplier(
                        $user, $adjEntryNo, $idSupplier, $idMaterial, $shortQty, $supplierCode, $plantId
                    );

                    // 1. Create t_balance_header
                    $idHead = DB::connection('eudr_ts')->table('t_balance_header')->insertGetId([
                        'trace_no' => $adjEntryNo,
                        'id_material' => $idMaterial,
                        'id_tank' => $trfSource,
                        'id_plant' => $plantId,
                        'qty' => $shortQty,
                        'in_qty' => $shortQty,
                        'out_qty' => 0,
                        'init_qty' => $shortQty,
                        'entry_date' => $data['entry_date'],
                        'created_by' => $user,
                        'status' => 1,
                    ]);

                    // 2. Create t_balance_detail
                    $idTail = DB::connection('eudr_ts')->table('t_balance_detail')->insertGetId([
                        'id_balance_head' => $idHead,
                        'id_supplier' => $idSupplier,
                        'batch_sap' => $supplierCode,
                        'id_material' => $idMaterial,
                        'id_tank' => $trfSource,
                        'id_sloc' => '0',
                        'qty' => $shortQty,
                        'in_qty' => $shortQty,
                        'out_qty' => 0,
                        'init_qty' => $shortQty,
                        'id_plant' => $plantId,
                        'created_by' => $user,
                        'status' => 1,
                    ]);

                    // 3. Create adjustment header
                    $adjHeadId = DB::connection('eudr_ts')->table('t_adjustment_header')->insertGetId([
                        'entry_date' => $data['entry_date'],
                        'adjust_no' => $adjEntryNo,
                        'id_balance_head' => $idHead,
                        'id_material' => $idMaterial,
                        'id_tank' => $trfSource,
                        'id_tank_tail' => json_encode($data['source_sloc_no'] ?? []),
                        'id_plant' => $plantId,
                        'in_qty' => $shortQty,
                        'out_qty' => 0,
                        'before_adjust' => 0,
                        'after_adjust' => $shortQty,
                        'created_by' => $user,
                        'status' => 1,
                    ]);

                    // 4. Create adjustment detail
                    DB::connection('eudr_ts')->table('t_adjustment_detail')->insert([
                        'id_adjust_head' => $adjHeadId,
                        'id_balance_tail' => $idTail,
                        'id_supplier' => $idSupplier,
                        'id_material' => $idMaterial,
                        'id_tank' => $trfSource,
                        'id_tank_tail' => json_encode($data['source_sloc_no'] ?? []),
                        'id_plant' => $plantId,
                        'batch_sap' => $supplierCode,
                        'in_qty' => $shortQty,
                        'out_qty' => 0,
                        'before_adjust' => 0,
                        'after_adjust' => $shortQty,
                        'created_by' => $user,
                        'status' => 1,
                    ]);
                });
            } catch (Exception $e) {
                return ['response' => 0, 'message' => 'Adjustment failed: ' . $e->getMessage()];
            }
        }

        // Retry the transfer after adjustment
        return $this->executeTransfer($user, $data, $plantId);
    }

    protected function resolvePlantCode($plantId): string
    {
        if ($plantId) {
            $plant = Plant::find($plantId);
            if ($plant && $plant->code_3) {
                return $plant->code_3;
            }
        }
        return (string) $plantId;
    }
}
