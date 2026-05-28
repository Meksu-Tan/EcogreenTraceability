<?php declare(strict_types=1);
namespace Modules\TsWip\Repositories\Contracts;

interface WipEntryRepositoryInterface
{
    // List & Fetch
    public function getBalance(string $rundownId, $plantId, ?string $subgroup = null): array;
    public function getFeed(string $feedId, string $mode, $plantId): array;
    public function getRundown(string $rundownId, string $mode, $plantId): array;

    // Batch Number Generation
    public function getFeedNewBatchNumber(string $feedId, $plantId): ?string;
    public function getRundownNewBatchNumber(string $rundownId, $plantId): ?string;
    public function generateNewFeedNumber(string $feedId, $plantId): ?string;
    public function generateNewRundownNumber(string $rundownId, $plantId, ?string $subgroup = null): ?string;

    // Last Batch Data
    public function getFeedLastBatch(string $feedId, $plantId): array;
    public function getRundownLastBatch(string $rundownId, $plantId): array;

    // Dropdowns
    public function getActiveTanksForFeed(string $feedId, $plantId): array;
    public function getActiveTanksForRundown(string $rundownId, $plantId, ?string $subgroup = null): array;
    public function getActiveSpecificTanks(int $slocId): array;

    // External Data
    public function getQuantifierData(string $date, string $tagNumber): array;

    // Write Operations
    public function postMaterialDocument(string $mode, int $idTraceHead, string $materialDoc, string $user): array;
    public function postMaterialFeed(array $data, string $user): array;
    public function postMaterialRundown(array $data, string $user): array;
    public function cancelFeed(string $traceNo, string $user): array;
    public function cancelRundown(string $traceNo, string $user): array;
    public function updateEntrySubTank(int $idHead, array $tails, string $user): array;

    // Lock check
    public function checkPeriodLock(string $date): bool;

    // Plants
    public function getUserPlants(int $userId): array;
    public function getAllPlants(): array;

    // Log
    public function logTransaction(string $module, string $type, string $description, string $user): void;
}

