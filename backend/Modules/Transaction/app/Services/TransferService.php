<?php

namespace Modules\Transaction\Services;

use Modules\Transaction\Repositories\Contracts\TransferRepositoryInterface;
use Modules\Transaction\Models\BalanceHeader;
use Modules\Transaction\Models\TraceHeader;
use Modules\Plant\Models\Plant;
use Exception;

class TransferService
{
    protected $movSeq = '000';
    protected $typeTransfer = '7';

    public function __construct(
        protected TransferRepositoryInterface $transferRepo
    ) {}

    public function getStorageLog($plantId)
    {
        return $this->transferRepo->getStorageLog($plantId);
    }

    public function debugFeedLog($plantId)
    {
        return $this->transferRepo->debugFeedLog($plantId);
    }

    public function getFeedLog($plantId)
    {
        return $this->transferRepo->getFeedLog($plantId);
    }

    public function generateTransferNumber($plantId)
    {
        return $this->transferRepo->generateTransferNumber($plantId, $this->movSeq);
    }

    public function transfer($data, $user)
    {
        $data['id_plant'] = $this->resolvePlantCode($data['id_plant'] ?? 0);

        $connection = app('db')->connection('eudr_ts');
        $connection->beginTransaction();

        try {
            $sourceBalance = BalanceHeader::findOrFail($data['id_balance_head']);
            $sourceTrace = $this->transferRepo->findTraceByBalanceHeadId($data['id_balance_head']);

            if ($sourceBalance->qty < $data['qty']) {
                throw new Exception('Insufficient quantity in source tank');
            }

            $transferNo = $this->transferRepo->generateTransferNumber($data['id_plant']);

            $destBalance = $this->transferRepo->createTransferBalance([
                'entry_date' => $data['entry_date'],
                'trace_no' => $transferNo,
                'id_material' => $sourceBalance->id_material,
                'id_sloc' => $data['id_dest_tank'],
                'id_sloc_tail' => $data['id_dest_tank_tail'],
                'id_plant' => $data['id_plant'],
                'qty' => $data['qty'],
                'created_by' => $user,
            ]);

            $this->transferRepo->createTransferTrace([
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

            $this->transferRepo->updateSourceBalance($data['id_balance_head'], $data['qty']);

            if ($sourceTrace) {
                $this->transferRepo->updateSourceTrace($data['id_balance_head'], $data['qty']);
            }

            $this->transferRepo->logTransaction(
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

    public function deactivateTransfer($id, $user)
    {
        $connection = app('db')->connection('eudr_ts');
        $connection->beginTransaction();

        try {
            $traceHead = $this->transferRepo->findTransferById($id);

            if (!$traceHead) {
                throw new Exception('Transfer not found');
            }

            if ($traceHead->status == 0) {
                throw new Exception('Transfer already deactivated');
            }

            $sourceTraceNo = $traceHead->from_trace_no;
            $sourceBalance = $this->transferRepo->findBalanceByTraceNo($sourceTraceNo);

            if ($sourceBalance) {
                $this->transferRepo->revertSourceBalance($sourceTraceNo, $traceHead->in_qty);

                $sourceTrace = $this->transferRepo->findTraceByBalanceHeadId($sourceBalance->id_balance_head);
                if ($sourceTrace) {
                    $this->transferRepo->revertSourceTrace($sourceBalance->id_balance_head, $traceHead->in_qty);
                }
            }

            $this->transferRepo->deactivateBalance($traceHead->id_balance_head, $user);

            $this->transferRepo->deactivateTrace($id, $user);

            $this->transferRepo->logTransaction(
                'TRANSFER',
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
