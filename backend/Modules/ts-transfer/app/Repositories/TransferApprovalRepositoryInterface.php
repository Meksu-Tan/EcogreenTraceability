<?php

declare(strict_types=1);

namespace Modules\TsTransfer\Repositories;

interface TransferApprovalRepositoryInterface
{
    public function findTransferForApproval(int $idBalanceHead): ?object;

    public function updateBalanceApprovalStatus(int $idBalanceHead, string $status, string $user): void;

    public function findApprovalRecord(int $idBalanceHead): ?object;

    public function insertApprovalRecord(array $data): int;

    public function updateApprovalStatus(int $idBalanceHead, string $approvalStatus, string $user, ?string $notes = null, ?string $reason = null): void;

    public function findBalanceEntryDate(int $idBalanceHead): ?string;

    public function getPendingApprovals(int $plantId = 0): array;

    public function getApprovalHistory(int $idBalanceHead): array;

    public function getCurrentApprovalStatus(int $idBalanceHead): ?string;

    public function canDelete(int $idBalanceHead): bool;

    public function getTransferPlantBySubmit(int $idBalanceHead): int;

    public function getPendingHistory(int $page = 1, int $perPage = 5): array;
}
