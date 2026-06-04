<?php declare(strict_types=1);

namespace Modules\TsTransfer\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Services\AuditService;
use Modules\Shared\Services\PeriodLockService;

class TransferApprovalService
{
    protected string $connection = 'eudr_ts';

    /**
     * Submit transfer for approval.
     * Changes status from DRAFT to PENDING.
     */
    public function submit(string $idBalanceHead, string $user): array
    {
        try {
            $transfer = DB::connection($this->connection)->select(
                'SELECT th.id_trace_head, bh.trace_no, bh.entry_date
                   FROM t_balance_header bh
                   LEFT JOIN t_trace_header th ON bh.id_balance_head = th.id_balance_head
                                                AND th.status = 1
                   WHERE bh.id_balance_head = ? AND bh.status = 1
                   LIMIT 1',
                [$idBalanceHead]
            );

            if (empty($transfer)) {
                return ['response' => 98, 'message' => 'Transfer not found'];
            }

            $traceNo = $transfer[0]->trace_no ?? '';
            $entryDate = $transfer[0]->entry_date ?? '';

            // Check period lock
            if (PeriodLockService::isLocked($entryDate)) {
                return ['response' => 99, 'message' => 'Period is locked'];
            }

            // Check current status
            $currentStatus = $this->getCurrentStatus($idBalanceHead);
            if ($currentStatus !== 'DRAFT') {
                return ['response' => 2, 'message' => 'Only DRAFT transfers can be submitted. Current: ' . $currentStatus];
            }

            // Update status to PENDING
            DB::connection($this->connection)->update(
                'UPDATE t_balance_header SET approval_status = "PENDING", updated_by = ?
                 WHERE id_balance_head = ?',
                [$user, $idBalanceHead]
            );

            // Insert or update approval record
            $existingApproval = DB::connection($this->connection)->select(
                'SELECT id_approval FROM t_transfer_approval WHERE id_balance_head = ? AND status = 1',
                [$idBalanceHead]
            );

            if (empty($existingApproval)) {
                DB::connection($this->connection)->insert(
                    'INSERT INTO t_transfer_approval (id_balance_head, id_trace_head, entry_no, entry_date, status, submitted_by, submitted_at, created_by)
                     VALUES (?, ?, ?, ?, "PENDING", ?, NOW(), ?)',
                    [$idBalanceHead, $transfer[0]->id_trace_head ?? '', $traceNo, $entryDate, $user, $user]
                );
            } else {
                DB::connection($this->connection)->update(
                    'UPDATE t_transfer_approval SET status = "PENDING", submitted_by = ?, submitted_at = NOW(), updated_by = ?
                     WHERE id_balance_head = ? AND status = 1',
                    [$user, $user, $idBalanceHead]
                );
            }

            // Audit log
            AuditService::log('TRANSFER', 'SUBMIT',
                'Transfer submitted for approval | ID: ' . $idBalanceHead . ' | TraceNo: ' . $traceNo,
                $user, ['id_balance_head' => $idBalanceHead, 'trace_no' => $traceNo]);

            return ['response' => 1, 'message' => 'Transfer submitted for approval'];
        } catch (\Exception $e) {
            Log::error('TransferApprovalService::submit failed', ['error' => $e->getMessage()]);
            return ['response' => 0, 'message' => 'Failed to submit: ' . $e->getMessage()];
        }
    }

    /**
     * Approve a transfer.
     * Changes status from PENDING to APPROVED.
     */
    public function approve(string $idBalanceHead, string $user, ?string $notes = null): array
    {
        try {
            // Check period lock
            $entryDate = DB::connection($this->connection)->select(
                'SELECT entry_date FROM t_balance_header WHERE id_balance_head = ?',
                [$idBalanceHead]
            );

            if (!empty($entryDate) && PeriodLockService::isLocked($entryDate[0]->entry_date)) {
                return ['response' => 99, 'message' => 'Period is locked'];
            }

            // Check current status
            $currentStatus = $this->getCurrentStatus($idBalanceHead);
            if ($currentStatus !== 'PENDING') {
                return ['response' => 2, 'message' => 'Only PENDING transfers can be approved. Current: ' . $currentStatus];
            }

            // Update balance header status
            DB::connection($this->connection)->update(
                'UPDATE t_balance_header SET approval_status = "APPROVED", approved_by = ?, approved_at = NOW(), updated_by = ?
                 WHERE id_balance_head = ?',
                [$user, $user, $idBalanceHead]
            );

            // Update approval record
            DB::connection($this->connection)->update(
                'UPDATE t_transfer_approval SET status = "APPROVED", approved_by = ?, approved_at = NOW(), notes = ?, updated_by = ?
                 WHERE id_balance_head = ? AND status = 1',
                [$user, $notes, $user, $idBalanceHead]
            );

            // Audit log
            AuditService::log('TRANSFER', 'APPROVE',
                'Transfer approved | ID: ' . $idBalanceHead . ' | By: ' . $user,
                $user, ['id_balance_head' => $idBalanceHead, 'approved_by' => $user]);

            return ['response' => 1, 'message' => 'Transfer approved'];
        } catch (\Exception $e) {
            Log::error('TransferApprovalService::approve failed', ['error' => $e->getMessage()]);
            return ['response' => 0, 'message' => 'Failed to approve: ' . $e->getMessage()];
        }
    }

    /**
     * Reject a transfer.
     * Changes status from PENDING to REJECTED.
     */
    public function reject(string $idBalanceHead, string $user, string $reason): array
    {
        try {
            // Check current status
            $currentStatus = $this->getCurrentStatus($idBalanceHead);
            if ($currentStatus !== 'PENDING') {
                return ['response' => 2, 'message' => 'Only PENDING transfers can be rejected. Current: ' . $currentStatus];
            }

            // Update balance header status
            DB::connection($this->connection)->update(
                'UPDATE t_balance_header SET approval_status = "REJECTED", updated_by = ?
                 WHERE id_balance_head = ?',
                [$user, $idBalanceHead]
            );

            // Update approval record
            DB::connection($this->connection)->update(
                'UPDATE t_transfer_approval SET status = "REJECTED", rejected_by = ?, rejected_at = NOW(), rejection_reason = ?, updated_by = ?
                 WHERE id_balance_head = ? AND status = 1',
                [$user, $reason, $user, $idBalanceHead]
            );

            // Audit log
            AuditService::log('TRANSFER', 'REJECT',
                'Transfer rejected | ID: ' . $idBalanceHead . ' | Reason: ' . $reason,
                $user, ['id_balance_head' => $idBalanceHead, 'rejected_by' => $user, 'reason' => $reason]);

            return ['response' => 1, 'message' => 'Transfer rejected'];
        } catch (\Exception $e) {
            Log::error('TransferApprovalService::reject failed', ['error' => $e->getMessage()]);
            return ['response' => 0, 'message' => 'Failed to reject: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel a transfer.
     * Changes status from DRAFT to CANCELLED.
     */
    public function cancel(string $idBalanceHead, string $user): array
    {
        try {
            // Check period lock
            $entryDate = DB::connection($this->connection)->select(
                'SELECT entry_date FROM t_balance_header WHERE id_balance_head = ?',
                [$idBalanceHead]
            );

            if (!empty($entryDate) && PeriodLockService::isLocked($entryDate[0]->entry_date)) {
                return ['response' => 99, 'message' => 'Period is locked'];
            }

            // Check current status
            $currentStatus = $this->getCurrentStatus($idBalanceHead);
            if (!in_array($currentStatus, ['DRAFT', 'REJECTED'])) {
                return ['response' => 2, 'message' => 'Only DRAFT or REJECTED transfers can be cancelled. Current: ' . $currentStatus];
            }

            // Update balance header status
            DB::connection($this->connection)->update(
                'UPDATE t_balance_header SET approval_status = "CANCELLED", updated_by = ?
                 WHERE id_balance_head = ?',
                [$user, $idBalanceHead]
            );

            // Update approval record
            DB::connection($this->connection)->update(
                'UPDATE t_transfer_approval SET status = "CANCELLED", updated_by = ?
                 WHERE id_balance_head = ? AND status = 1',
                [$user, $idBalanceHead]
            );

            // Audit log
            AuditService::log('TRANSFER', 'CANCEL',
                'Transfer cancelled | ID: ' . $idBalanceHead,
                $user, ['id_balance_head' => $idBalanceHead]);

            return ['response' => 1, 'message' => 'Transfer cancelled'];
        } catch (\Exception $e) {
            Log::error('TransferApprovalService::cancel failed', ['error' => $e->getMessage()]);
            return ['response' => 0, 'message' => 'Failed to cancel: ' . $e->getMessage()];
        }
    }

    /**
     * Get list of pending approvals.
     */
    public function getPendingApprovals(int $plantId = 0): array
    {
        $plantFilter = $plantId > 0 ? 'AND bh.id_plant = ?' : '';
        $bindings = $plantId > 0 ? [$plantId] : [];

        return DB::connection($this->connection)->select(
            'SELECT ta.id_approval, ta.id_balance_head, ta.entry_no, ta.entry_date,
                    ta.id_material, ta.material_name, ta.qty, ta.source_sloc, ta.dest_sloc,
                    ta.status, ta.submitted_by, ta.submitted_at,
                    bh.trace_no, p.description AS plant_name
               FROM t_transfer_approval ta
               LEFT JOIN t_balance_header bh ON ta.id_balance_head = bh.id_balance_head
               LEFT JOIN m_plant p ON bh.id_plant = p.code_3
              WHERE ta.status = "PENDING" AND ta.approval_status != "CANCELLED"
                ' . $plantFilter . '
              ORDER BY ta.submitted_at DESC',
            $bindings
        );
    }

    /**
     * Get approval history for a transfer.
     */
    public function getApprovalHistory(string $idBalanceHead): array
    {
        return DB::connection($this->connection)->select(
            'SELECT ta.*, bh.trace_no
               FROM t_transfer_approval ta
               LEFT JOIN t_balance_header bh ON ta.id_balance_head = bh.id_balance_head
              WHERE ta.id_balance_head = ?
              ORDER BY ta.created_at DESC',
            [$idBalanceHead]
        );
    }

    /**
     * Get current approval status.
     */
    public function getCurrentStatus(string $idBalanceHead): ?string
    {
        $result = DB::connection($this->connection)->select(
            'SELECT COALESCE(approval_status, "APPROVED") AS approval_status
               FROM t_balance_header
              WHERE id_balance_head = ?',
            [$idBalanceHead]
        );

        return $result[0]->approval_status ?? null;
    }

    /**
     * Check if transfer can be edited based on approval status.
     */
    public function canEdit(string $idBalanceHead): bool
    {
        $status = $this->getCurrentStatus($idBalanceHead);
        return in_array($status, ['DRAFT', 'REJECTED']);
    }

    /**
     * Check if transfer can be deleted based on approval status.
     */
    public function canDelete(string $idBalanceHead): bool
    {
        $status = $this->getCurrentStatus($idBalanceHead);
        return in_array($status, ['DRAFT', 'REJECTED', 'CANCELLED']);
    }

    /**
     * Create approval record when transfer is created.
     */
    public function createApprovalRecord(
        string $idBalanceHead,
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
        $existing = DB::connection($this->connection)->select(
            'SELECT id_approval FROM t_transfer_approval WHERE id_balance_head = ? AND status = 1',
            [$idBalanceHead]
        );

        if (!empty($existing)) {
            return;
        }

        DB::connection($this->connection)->insert(
            'INSERT INTO t_transfer_approval
             (id_balance_head, entry_no, entry_date, id_material, material_name, qty,
              source_sloc, dest_sloc, id_plant, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "DRAFT", ?)',
            [$idBalanceHead, $traceNo, $entryDate, $idMaterial, $materialName, $qty,
             $sourceSloc, $destSloc, $plantId, $user]
        );
    }
}