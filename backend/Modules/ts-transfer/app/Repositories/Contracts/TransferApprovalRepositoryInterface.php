<?php declare(strict_types=1);
namespace Modules\TsTransfer\Repositories\Contracts;

interface TransferApprovalRepositoryInterface
{
    public function findTransferForApproval(string $idBalanceHead): ?object;

    public function updateBalanceApprovalStatus(int $idBalanceHead, string $status, string $user): void;

    public function findApprovalRecord(string $idBalanceHead, string $activeStatus = '1'): ?object;

    public function insertApprovalRecord(array $data): int;

    public function updateApprovalStatus(string $idBalanceHead, string $approvalStatus, string $user, ?string $notes = null, ?string $reason = null): void;

    public function findBalanceEntryDate(int $idBalanceHead): ?string;

    public function getPendingApprovals(int $plantId = 0): array;

    public function getApprovalHistory(string $idBalanceHead): array;

    public function getCurrentApprovalStatus(string $idBalanceHead): ?string;

    public function canDelete(string $idBalanceHead): bool;

    public function getTransferPlantBySubmit(string $idBalanceHead): int;
}
