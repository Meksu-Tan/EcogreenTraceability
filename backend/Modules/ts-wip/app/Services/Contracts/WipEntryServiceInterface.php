<?php

declare(strict_types=1);

namespace Modules\TsWip\Services\Contracts;

interface WipEntryServiceInterface
{
    public function index($plantId): array;

    public function getBalance(string $rundownId, $plantId, ?string $subgroup = null, int $page = 1, int $perPage = 5): array;

    public function getFeed(string $feedId, string $mode, $plantId, int $page = 1, int $perPage = 5): array;

    public function getRundown(string $rundownId, string $mode, $plantId, int $page = 1, int $perPage = 5): array;

    public function getFeedNewBatchNumber(string $feedId, $plantId): ?string;

    public function getRundownNewBatchNumber(string $rundownId, $plantId): ?string;

    public function getFeedLastBatch(string $feedId, $plantId): array;

    public function getRundownLastBatch(string $rundownId, $plantId): array;

    public function getActiveTanksForFeed(string $feedId, $plantId): array;

    public function getActiveTanksForRundown(string $rundownId, $plantId, ?string $subgroup = null): array;

    public function getActiveSpecificTanks(int $slocId): array;

    public function getQuantifierData(string $date, string $tagNumber): array;

    public function getWipTree($plantId): array;

    public function generateNewFeedNumber(string $feedId, $plantId): ?string;

    public function generateNewRundownNumber(string $rundownId, $plantId, ?string $subgroup = null): ?string;

    public function postMaterialFeed(array $data, string $user): array;

    public function postMaterialRundown(array $data, string $user): array;

    public function postMaterialDocument(string $mode, int $idTraceHead, string $materialDoc, string $user): array;

    public function cancelFeed(string $traceNo, string $user): array;

    public function cancelRundown(string $traceNo, string $user): array;

    public function cancelById(int $id, string $user): array;

    public function updateEntrySubTank(int $idHead, array $tails, string $user): array;

    public function checkPeriodLock(string $date): bool;
}
