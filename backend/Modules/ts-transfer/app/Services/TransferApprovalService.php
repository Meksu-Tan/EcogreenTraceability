<?php

declare(strict_types=1);

namespace Modules\TsTransfer\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Services\AuditService;
use Modules\Shared\Services\PeriodLockService;
use Modules\Shared\Services\TransactionCancellationService;
use Modules\TsTransfer\Repositories\TransferApprovalRepositoryInterface;

class TransferApprovalService
{
    public function __construct(
        protected TransferApprovalRepositoryInterface $approvalRepo
    ) {}

    /**
     * Submit transfer for approval.
     * Changes status from DRAFT to PENDING.
     */
    public function submit(int $idBalanceHead, string $user): array
    {
        try {
            $transfer = $this->approvalRepo->findTransferForApproval($idBalanceHead);

            if (! $transfer) {
                return ['response' => 98, 'message' => 'Transfer not found'];
            }

            $traceNo = $transfer->trace_no ?? '';
            $entryDate = $transfer->entry_date ?? '';

            // Check period lock
            if (PeriodLockService::isLocked($entryDate)) {
                return ['response' => 99, 'message' => 'Period is locked'];
            }

            // Check current status
            $currentStatus = $this->getCurrentStatus($idBalanceHead);
            if ($currentStatus !== 'DRAFT') {
                return ['response' => 2, 'message' => 'Only DRAFT transfers can be submitted. Current: '.$currentStatus];
            }

            return DB::connection('eudr_ts')->transaction(function () use ($idBalanceHead, $user, $traceNo, $entryDate, $transfer) {
                // Update status to PENDING
                $this->approvalRepo->updateBalanceApprovalStatus($idBalanceHead, 'PENDING', $user);

                // Insert or update approval record
                $existingApproval = $this->approvalRepo->findApprovalRecord($idBalanceHead);

                if (! $existingApproval) {
                    $this->approvalRepo->insertApprovalRecord([
                        'id_balance_head' => $idBalanceHead,
                        'id_trace_head' => $transfer->id_trace_head ?? '',
                        'entry_no' => $traceNo,
                        'entry_date' => $entryDate,
                        'status' => 'PENDING',
                        'submitted_by' => $user,
                        'submitted_at' => now(),
                        'created_by' => $user,
                    ]);
                } else {
                    $this->approvalRepo->updateApprovalStatus($idBalanceHead, 'PENDING', $user);
                }

                // Audit log
                AuditService::log('TRANSFER', 'SUBMIT',
                    'Transfer submitted for approval | ID: '.$idBalanceHead.' | TraceNo: '.$traceNo,
                    $user, ['id_balance_head' => $idBalanceHead, 'trace_no' => $traceNo]);

                return ['response' => 1, 'message' => 'Transfer submitted for approval'];
            });
        } catch (\Exception $e) {
            Log::error('TransferApprovalService::submit failed', ['error' => $e->getMessage()]);

            return ['response' => 0, 'message' => 'Failed to submit: '.$e->getMessage()];
        }
    }

    /**
     * Approve a transfer.
     * Changes status from PENDING to APPROVED.
     */
    public function approve(int $idBalanceHead, string $user, ?string $notes = null): array
    {
        try {
            // Check period lock
            $entryDate = $this->approvalRepo->findBalanceEntryDate($idBalanceHead);

            if ($entryDate && PeriodLockService::isLocked($entryDate)) {
                return ['response' => 99, 'message' => 'Period is locked'];
            }

            // Check current status
            $currentStatus = $this->getCurrentStatus($idBalanceHead);
            if ($currentStatus !== 'PENDING') {
                return ['response' => 2, 'message' => 'Only PENDING transfers can be approved. Current: '.$currentStatus];
            }

            return DB::connection('eudr_ts')->transaction(function () use ($idBalanceHead, $user, $notes) {
                // Note: no status=1 reactivation needed here — PENDING transfers were
                // never deactivated (deactivation only happens on reject/cancel, which
                // both block re-approval via the currentStatus !== 'PENDING' check above).

                // Update balance header status
                $this->approvalRepo->updateBalanceApprovalStatus($idBalanceHead, 'APPROVED', $user);

                // Update approval record
                $this->approvalRepo->updateApprovalStatus($idBalanceHead, 'APPROVED', $user, $notes);

                // Audit log
                AuditService::log('TRANSFER', 'APPROVE',
                    'Transfer approved | ID: '.$idBalanceHead.' | By: '.$user,
                    $user, ['id_balance_head' => $idBalanceHead, 'approved_by' => $user]);

                return ['response' => 1, 'message' => 'Transfer approved'];
            });
        } catch (\Exception $e) {
            Log::error('TransferApprovalService::approve failed', ['error' => $e->getMessage()]);

            return ['response' => 0, 'message' => 'Failed to approve: '.$e->getMessage()];
        }
    }

    /**
     * Reject a transfer.
     * Changes status from PENDING to REJECTED.
     */
    public function reject(int $idBalanceHead, string $user, string $reason): array
    {
        try {
            // Check current status
            $currentStatus = $this->getCurrentStatus($idBalanceHead);
            if ($currentStatus !== 'PENDING') {
                return ['response' => 2, 'message' => 'Only PENDING transfers can be rejected. Current: '.$currentStatus];
            }

            return DB::connection('eudr_ts')->transaction(function () use ($idBalanceHead, $user, $reason) {
                // Reverse the transfer to release reserved stock
                $this->deactivateBalance($idBalanceHead, $user);

                // Update balance header status
                $this->approvalRepo->updateBalanceApprovalStatus($idBalanceHead, 'REJECTED', $user);

                // Update approval record
                $this->approvalRepo->updateApprovalStatus($idBalanceHead, 'REJECTED', $user, null, $reason);

                // Audit log
                AuditService::log('TRANSFER', 'REJECT',
                    'Transfer rejected | ID: '.$idBalanceHead.' | Reason: '.$reason,
                    $user, ['id_balance_head' => $idBalanceHead, 'rejected_by' => $user, 'reason' => $reason]);

                return ['response' => 1, 'message' => 'Transfer rejected'];
            });
        } catch (\Exception $e) {
            Log::error('TransferApprovalService::reject failed', ['error' => $e->getMessage()]);

            return ['response' => 0, 'message' => 'Failed to reject: '.$e->getMessage()];
        }
    }

    /**
     * Cancel a transfer.
     * Changes status from DRAFT to CANCELLED.
     */
    public function cancel(int $idBalanceHead, string $user): array
    {
        try {
            // Check period lock
            $entryDate = $this->approvalRepo->findBalanceEntryDate($idBalanceHead);

            if ($entryDate && PeriodLockService::isLocked($entryDate)) {
                return ['response' => 99, 'message' => 'Period is locked'];
            }

            // Check current status
            $currentStatus = $this->getCurrentStatus($idBalanceHead);
            if (! in_array($currentStatus, ['DRAFT', 'REJECTED'])) {
                return ['response' => 2, 'message' => 'Only DRAFT or REJECTED transfers can be cancelled. Current: '.$currentStatus];
            }

            return DB::connection('eudr_ts')->transaction(function () use ($idBalanceHead, $user) {
                // Reverse the transfer to release reserved stock
                $this->deactivateBalance($idBalanceHead, $user);

                // Update balance header status
                $this->approvalRepo->updateBalanceApprovalStatus($idBalanceHead, 'CANCELLED', $user);

                // Update approval record
                $this->approvalRepo->updateApprovalStatus($idBalanceHead, 'CANCELLED', $user);

                // Audit log
                AuditService::log('TRANSFER', 'CANCEL',
                    'Transfer cancelled | ID: '.$idBalanceHead,
                    $user, ['id_balance_head' => $idBalanceHead]);

                return ['response' => 1, 'message' => 'Transfer cancelled'];
            });
        } catch (\Exception $e) {
            Log::error('TransferApprovalService::cancel failed', ['error' => $e->getMessage()]);

            return ['response' => 0, 'message' => 'Failed to cancel: '.$e->getMessage()];
        }
    }

    /**
     * Reverse a transfer's balance/trace effect via TransactionCancellationService.
     * That service expects "idBalanceHead|idTraceHead" (see RmEntryService::deactivateTransfer
     * for the same bridge pattern) — without idTraceHead, explode('|', $id)[1] is undefined.
     */
    private function deactivateBalance(int $idBalanceHead, string $user): void
    {
        $transfer = $this->approvalRepo->findTransferForApproval($idBalanceHead);
        $pipeId = $idBalanceHead.'|'.($transfer->id_trace_head ?? '');

        $result = app(TransactionCancellationService::class)
            ->deactivateTransfer($pipeId, $user);

        if ((int) ($result['response'] ?? 0) !== 1) {
            throw new \RuntimeException(
                'Failed to reverse transfer balance: '.($result['message'] ?? 'unknown error')
            );
        }
    }

    /**
     * Get list of pending approvals.
     */
    public function getPendingApprovals(int $plantId = 0): array
    {
        return $this->approvalRepo->getPendingApprovals($plantId);
    }

    /**
     * Get approval history for a transfer.
     */
    public function getApprovalHistory(int $idBalanceHead): array
    {
        return $this->approvalRepo->getApprovalHistory($idBalanceHead);
    }

    /**
     * Get current approval status.
     */
    public function getCurrentStatus(int $idBalanceHead): ?string
    {
        return $this->approvalRepo->getCurrentApprovalStatus($idBalanceHead);
    }

    /**
     * Check if transfer can be edited based on approval status.
     */
    public function canEdit(int $idBalanceHead): bool
    {
        $status = $this->getCurrentStatus($idBalanceHead);

        return in_array($status, ['DRAFT', 'REJECTED']);
    }

    /**
     * Check if transfer can be deleted based on approval status.
     */
    public function canDelete(int $idBalanceHead): bool
    {
        return $this->approvalRepo->canDelete($idBalanceHead);
    }

    /**
     * Create approval record when transfer is created.
     */
    public function createApprovalRecord(
        int $idBalanceHead,
        string $traceNo,
        string $entryDate,
        string $idMaterial,
        ?string $materialName,
        float $qty,
        ?string $sourceSloc,
        ?string $destSloc,
        int $plantId,
        string $user
    ): void {
        // Check if already exists
        $existing = $this->approvalRepo->findApprovalRecord($idBalanceHead);

        if ($existing) {
            return;
        }

        $this->approvalRepo->insertApprovalRecord([
            'id_balance_head' => $idBalanceHead,
            'entry_no' => $traceNo,
            'entry_date' => $entryDate,
            'id_material' => $idMaterial,
            'material_name' => $materialName,
            'qty' => $qty,
            'source_sloc' => $sourceSloc,
            'dest_sloc' => $destSloc,
            'id_plant' => $plantId,
            'status' => 'PENDING',
            'created_by' => $user,
        ]);
    }

    /**
     * Get pending transfer history (all plants, no filter).
     */
    public function getPendingHistory(int $page = 1, int $perPage = 5): array
    {
        return $this->approvalRepo->getPendingHistory($page, $perPage);
    }
}
