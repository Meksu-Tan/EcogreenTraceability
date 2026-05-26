<?php

namespace Modules\TsBlending\Services;

use Modules\TsBlending\Repositories\Contracts\BlendingRepositoryInterface;
use Modules\Shared\Helpers\Feed;
use Modules\Shared\Helpers\Rundown;
use Modules\Plant\Models\Plant;
use Exception;
use Illuminate\Support\Facades\DB;

class BlendingService
{
    protected $movSeq = '000';
    protected $typeBlending = '8';

    public function __construct(
        protected BlendingRepositoryInterface $blendingRepo
    ) {}

    public function getActiveMaterials()
    {
        return $this->blendingRepo->getActiveMaterials();
    }

    public function generateEntryNo(int $materialId, int $plantId): ?string
    {
        return $this->blendingRepo->generateBlendingEntryNo($materialId, $plantId);
    }

    public function getTotalStockMaterial(int $materialId, int $plantId): float
    {
        return $this->blendingRepo->getTotalStockMaterial($materialId, $plantId);
    }

    public function getTotalQtyMaterial(string $mode, string $entryNo, ?int $idHead, int $plantId): float
    {
        return $this->blendingRepo->getTotalQtyMaterial($mode, $entryNo, $idHead, $plantId);
    }

    public function getMaterialList(string $mode, string $entryNo, ?int $idHead, int $plantId)
    {
        return $this->blendingRepo->getMaterialList($mode, $entryNo, $idHead, $plantId);
    }

    public function getBlendingList(int $plantId)
    {
        return $this->blendingRepo->getBlendingList($plantId);
    }

    public function getActiveTanksRundown(int $materialId, int $plantId)
    {
        return $this->blendingRepo->getActiveTanksRundown($materialId, $plantId);
    }

    public function getActiveSpecificTanksRundown(int $sloc)
    {
        return $this->blendingRepo->getActiveSpecificTanksRundown($sloc);
    }

    public function addMaterialToBlending(string $user, array $data, int $plantId): array
    {
        $entryNo = $data['entryNo'];
        $idMaterial = $data['idMaterialSource'];
        $qty = (float) str_replace(',', '', $data['qty']);
        $idTank = $data['idTank'];
        $mode = $data['mode'];

        // Check for duplicate material in ADD mode
        if ($mode === 'ADD') {
            if ($this->blendingRepo->checkMaterialInTemporary($idMaterial, $entryNo, $plantId)) {
                return ['response' => 2]; // Already exists
            }
        }

        $result = $this->blendingRepo->addBlendingEntryMaterial(
            $user, $entryNo, $idMaterial, $qty, $idTank, $plantId
        );

        return $result;
    }

    public function deleteBlendingMaterial(int $id): bool
    {
        return $this->blendingRepo->deleteBlendingMaterial($id);
    }

    public function createMaterialDocument(string $user, int $idTraceHead, string $materialDoc, string $mode): array
    {
        return $this->blendingRepo->createMaterialDocument($user, $idTraceHead, $materialDoc, $mode);
    }

    public function updateEntrySubTank(string $user, int $idHead, array $tails): array
    {
        return $this->blendingRepo->updateEntrySubTank($user, $idHead, $tails);
    }

    public function executeBlending(string $user, array $data, int $plantId): array
    {
        $entryNo = $data['entry_no'];
        $entryDate = $data['entry_date'];
        $idMaterial = $data['id_material'];
        $materialDoc = $data['material_doc'];
        $totalQty = (float) str_replace(',', '', $data['qty']);
        $id_tank_tail = $data['tankNo'] ?? [];
        $id_tank_tail_json = json_encode($id_tank_tail);

        // Check lock period
        if ($this->blendingRepo->getLockStatus($entryDate)) {
            return ['response' => 99]; // Period locked
        }

        // Check material source count
        $itemCnt = $this->blendingRepo->getTemporaryItemCount($entryNo);
        if ($itemCnt == 0) {
            return ['response' => 4]; // No material
        }

        // Get material entries from temporary
        $datMaterial = $this->blendingRepo->getTemporaryEntries($entryNo);
        if ($datMaterial->isEmpty()) {
            return ['response' => 4];
        }

        $feed_entry_no = substr_replace($entryNo, '0', 8, 1);
        $last_qtf = 0;

        try {
            DB::connection('eudr_ts')->beginTransaction();

            // Feed each material from temporary
            foreach ($datMaterial as $row) {
                $qtySource = (float) str_replace(',', '', $row->qty);
                if ($qtySource <= 0) continue;

                $feedResult = Feed::generalFeed([
                    'user' => $user,
                    'entry_date' => $entryDate,
                    'id_material' => $row->id_material,
                    'id_tank' => $row->id_tank,
                    'id_tank_tail' => $id_tank_tail_json,
                    'id_plant' => $plantId,
                    'qty' => $qtySource,
                    'allow_partial' => true,
                    'trace_prefixes' => [1, 2, 7, 8, 9],
                    'to_trace_no' => $feed_entry_no,
                ]);

                if ($feedResult['response'] != 1) {
                    DB::connection('eudr_ts')->rollBack();
                    return ['response' => $feedResult['response']];
                }
            }

            // Get trace head for this blending
            $batch_seq = substr($feed_entry_no, 12, 2);
            $feed_id = substr($feed_entry_no, 7, 3);

            $checkTrace = DB::connection('eudr_ts')->select(
                'SELECT to_trace_no, id_trace_head, SUM(out_qty) AS out_qty, id_material
                    FROM t_trace_header
                   WHERE SUBSTRING(to_trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
                     AND SUBSTRING(to_trace_no,1,1) = 8
                     AND SUBSTRING(to_trace_no,8,3) = ?
                     AND SUBSTRING(to_trace_no,13,2) = ?
                     AND status = 1
                     AND out_qty > "0.0001"
                     AND (id_plant = ? OR ? = 0)
                   ORDER BY id_trace_head DESC
                   LIMIT 1',
                [$feed_id, $batch_seq, $plantId, $plantId]
            );

            if (!isset($checkTrace[0]->id_trace_head)) {
                $datTraceHead = [];
            } else {
                $datTraceHead = DB::connection('eudr_ts')->select(
                    'SELECT to_trace_no, id_trace_head, out_qty, id_material
                        FROM t_trace_header
                       WHERE SUBSTRING(to_trace_no,2,6) = DATE_FORMAT(CURDATE(), "%y%m%d")
                         AND SUBSTRING(to_trace_no,1,1) = 8
                         AND SUBSTRING(to_trace_no,8,3) = ?
                         AND SUBSTRING(to_trace_no,13,2) = ?
                         AND status = 1
                         AND out_qty > "0.0001"
                         AND (id_plant = ? OR ? = 0)
                       ORDER BY id_trace_head DESC',
                    [$feed_id, $batch_seq, $plantId, $plantId]
                );
            }

            $totalFeedQty = array_sum(array_column($datTraceHead, 'out_qty'));
            $in_qty = round($totalFeedQty, 4);

            // Aggregate supplier rows
            $supplierRows = [];
            foreach ($datTraceHead as $th) {
                $tails = DB::connection('eudr_ts')->select(
                    'SELECT id_supplier, batch_sap, out_qty
                        FROM t_trace_detail
                       WHERE id_trace_head = ? AND status = 1',
                    [$th->id_trace_head]
                );

                if (empty($tails)) continue;

                foreach ($tails as $t) {
                    $key = $t->id_supplier . '|' . $t->batch_sap;
                    if (!isset($supplierRows[$key])) {
                        $supplierRows[$key] = [
                            'id_supplier' => $t->id_supplier,
                            'batch_sap' => $t->batch_sap,
                            'rundownSupplier' => 0
                        ];
                    }
                    $supplierRows[$key]['rundownSupplier'] += round($t->out_qty, 4);
                }
            }

            if (empty($supplierRows)) {
                DB::connection('eudr_ts')->rollBack();
                return ['response' => 6];
            }

            $supplierRows = array_values($supplierRows);
            Rundown::adjustRundownToTotal($supplierRows, $in_qty);

            // Get target tank for this material
            $datTank = DB::connection('eudr_ts')->select(
                'SELECT b.id_tank FROM m_material a
                    LEFT JOIN m_tank b ON a.type = b.code_2 AND b.status = 1 AND b.id_plant = ?
                   WHERE a.status = 1 AND a.id_material = ?',
                [$plantId, $idMaterial]
            );

            if (!isset($datTank[0]->id_tank)) {
                DB::connection('eudr_ts')->rollBack();
                return ['response' => 6];
            }

            $id_tank = $datTank[0]->id_tank;
            $from_trace_no = $datTraceHead[0]->to_trace_no;

            // Execute rundown
            $rundownResult = Rundown::generalRundown([
                'user' => $user,
                'entry_date' => $entryDate,
                'trace_no' => $entryNo,
                'from_trace_no' => $from_trace_no,
                'id_material' => $idMaterial,
                'id_tank' => $id_tank,
                'id_tank_tail' => $id_tank_tail_json,
                'id_plant' => $plantId,
                'in_qty' => $in_qty,
                'last_qtf' => 0,
                'curr_qtf' => $totalQty,
                'supplier_rows' => $supplierRows,
            ]);

            if ($rundownResult['response'] != 1) {
                DB::connection('eudr_ts')->rollBack();
                return ['response' => 3];
            }

            // Create material document
            $this->blendingRepo->createMaterialDocument($user, $rundownResult['id_trace_head'], $materialDoc, 'ADD');

            // Clean up temporary data
            DB::connection('eudr_ts')->delete('DELETE FROM t_balance_temporary WHERE entry_no = ?', [$entryNo]);

            DB::connection('eudr_ts')->commit();
            return ['response' => 1];
        } catch (\Exception $e) {
            DB::connection('eudr_ts')->rollBack();
            return ['response' => 0, 'message' => $e->getMessage()];
        }
    }

    public function deactivateBlending(string $id, string $user): array
    {
        return $this->blendingRepo->deactivateBlending($id, $user);
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