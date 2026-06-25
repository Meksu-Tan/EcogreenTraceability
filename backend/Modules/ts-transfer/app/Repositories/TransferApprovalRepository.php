<?php
declare(strict_types=1);
namespace Modules\TsTransfer\Repositories;

use Modules\TsTransfer\Repositories\Contracts\TransferApprovalRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TransferApprovalRepository implements TransferApprovalRepositoryInterface
{
    protected string $connection = 'eudr_ts';

    public function findTransferForApproval(int $idBalanceHead): ?object
    {
        $result = DB::connection($this->connection)->select(
            'SELECT th.id_trace_head, bh.trace_no, bh.entry_date
               FROM t_balance_header bh
               LEFT JOIN t_trace_header th ON bh.id_balance_head = th.id_balance_head
                                            AND th.status = 1
              WHERE bh.id_balance_head = ? AND bh.status = 1
              LIMIT 1',
            [$idBalanceHead]
        );

        return $result[0] ?? null;
    }

    public function updateBalanceApprovalStatus(int $idBalanceHead, string $status, string $user): void
    {
        if ($status === 'APPROVED') {
            DB::connection($this->connection)->update(
                'UPDATE t_balance_header SET approval_status = ?, approved_by = ?, approved_at = NOW(), updated_by = ?
                 WHERE id_balance_head = ?',
                [$status, $user, $user, $idBalanceHead]
            );
        } else {
            DB::connection($this->connection)->update(
                'UPDATE t_balance_header SET approval_status = ?, updated_by = ?
                 WHERE id_balance_head = ?',
                [$status, $user, $idBalanceHead]
            );
        }
    }

    public function findApprovalRecord(int $idBalanceHead, string $activeStatus = '1'): ?object
    {
        $result = DB::connection($this->connection)->select(
            'SELECT id_approval FROM t_transfer_approval WHERE id_balance_head = ? AND status = ?',
            [$idBalanceHead, $activeStatus]
        );

        return $result[0] ?? null;
    }

    public function insertApprovalRecord(array $data): int
    {
        DB::connection($this->connection)->table('t_transfer_approval')->insert($data);
        return (int) DB::connection($this->connection)->getPdo()->lastInsertId();
    }

    public function updateApprovalStatus(int $idBalanceHead, string $approvalStatus, string $user, ?string $notes = null, ?string $reason = null): void
    {
        switch ($approvalStatus) {
            case 'PENDING':
                DB::connection($this->connection)->update(
                    'UPDATE t_transfer_approval SET status = ?, submitted_by = ?, submitted_at = NOW(), updated_by = ?
                     WHERE id_balance_head = ? AND status = 1',
                    [$approvalStatus, $user, $user, $idBalanceHead]
                );
                break;
            case 'APPROVED':
                DB::connection($this->connection)->update(
                    'UPDATE t_transfer_approval SET status = ?, approved_by = ?, approved_at = NOW(), notes = ?, updated_by = ?
                     WHERE id_balance_head = ? AND status = 1',
                    [$approvalStatus, $user, $notes, $user, $idBalanceHead]
                );
                break;
            case 'REJECTED':
                DB::connection($this->connection)->update(
                    'UPDATE t_transfer_approval SET status = ?, rejected_by = ?, rejected_at = NOW(), rejection_reason = ?, updated_by = ?
                     WHERE id_balance_head = ? AND status = 1',
                    [$approvalStatus, $user, $reason, $user, $idBalanceHead]
                );
                break;
            case 'CANCELLED':
                DB::connection($this->connection)->update(
                    'UPDATE t_transfer_approval SET status = ?, updated_by = ?
                     WHERE id_balance_head = ? AND status = 1',
                    [$approvalStatus, $user, $idBalanceHead]
                );
                break;
        }
    }

    public function findBalanceEntryDate(int $idBalanceHead): ?string
    {
        $result = DB::connection($this->connection)->select(
            'SELECT entry_date FROM t_balance_header WHERE id_balance_head = ?',
            [$idBalanceHead]
        );

        return $result[0]->entry_date ?? null;
    }

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
              WHERE ta.status = \'PENDING\' AND ta.approval_status != \'CANCELLED\'
                ' . $plantFilter . '
              ORDER BY ta.submitted_at DESC',
            $bindings
        );
    }

    public function getApprovalHistory(int $idBalanceHead): array
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

    public function getCurrentApprovalStatus(int $idBalanceHead): ?string
    {
        $result = DB::connection($this->connection)->select(
            'SELECT COALESCE(approval_status, "APPROVED") AS approval_status
               FROM t_balance_header
              WHERE id_balance_head = ?',
            [$idBalanceHead]
        );

        return $result[0]->approval_status ?? null;
    }

    public function canDelete(int $idBalanceHead): bool
    {
        $status = $this->getCurrentApprovalStatus($idBalanceHead);
        return in_array($status, ['DRAFT', 'REJECTED', 'CANCELLED', 'APPROVED']);
    }

    public function getTransferPlantBySubmit(int $idBalanceHead): int
    {
        $result = DB::connection($this->connection)->select(
            'SELECT bh.id_plant
               FROM t_balance_header bh
               LEFT JOIN t_transfer_approval ta ON bh.id_balance_head = ta.id_balance_head
              WHERE bh.id_balance_head = ? AND bh.status = 1
              LIMIT 1',
            [$idBalanceHead]
        );

        return (int) ($result[0]->id_plant ?? 0);
    }
}
