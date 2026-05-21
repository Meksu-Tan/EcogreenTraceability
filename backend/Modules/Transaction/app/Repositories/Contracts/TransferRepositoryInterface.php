<?php

namespace Modules\Transaction\Repositories\Contracts;

interface TransferRepositoryInterface
{
    public function getStorageLog($plantId): array;
    public function getFeedLog($plantId): array;
    public function debugFeedLog($plantId): array;
    public function generateTransferNumber($plantId, string $movSeq = '000'): ?string;
    public function findBalanceByTraceNo(string $traceNo): ?object;
    public function findTraceByBalanceHeadId(int $balanceHeadId): ?object;
    public function createTransferBalance(array $data): object;
    public function createTransferTrace(array $data): object;
    public function updateSourceBalance(int $balanceId, float $qty): bool;
    public function updateSourceTrace(int $balanceHeadId, float $qty): bool;
    public function findTransferById(int $id): ?object;
    public function deactivateBalance(int $balanceId, string $user): bool;
    public function deactivateTrace(int $traceId, string $user): bool;
    public function revertSourceBalance(string $traceNo, float $qty): bool;
    public function revertSourceTrace(int $balanceHeadId, float $qty): bool;
    public function logTransaction(string $module, string $type, string $description, string $user): void;
    public function getSourceEntries(int $plantId): array;
    public function getDestTanks(int $plantId): array;
}
