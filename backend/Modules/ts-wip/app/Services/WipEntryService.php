<?php

namespace Modules\TsWip\Services;

use Modules\TsWip\Repositories\Contracts\WipEntryRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class WipEntryService
{
    public function __construct(
        protected WipEntryRepositoryInterface $wipEntryRepo
    ) {}

    public function index($plantId): array
    {
        return [
            'plants' => $this->getPlants(),
            'selectedPlant' => $this->resolvePlant($plantId),
        ];
    }

    public function getBalance(string $rundownId, $plantId, ?string $subgroup = null): array
    {
        return $this->wipEntryRepo->getBalance($rundownId, $plantId, $subgroup);
    }

    public function getFeed(string $feedId, string $mode, $plantId): array
    {
        return $this->wipEntryRepo->getFeed($feedId, $mode, $plantId);
    }

    public function getRundown(string $rundownId, string $mode, $plantId): array
    {
        return $this->wipEntryRepo->getRundown($rundownId, $mode, $plantId);
    }

    public function getFeedNewBatchNumber(string $feedId, $plantId): ?string
    {
        return $this->wipEntryRepo->getFeedNewBatchNumber($feedId, $plantId);
    }

    public function getRundownNewBatchNumber(string $rundownId, $plantId): ?string
    {
        return $this->wipEntryRepo->getRundownNewBatchNumber($rundownId, $plantId);
    }

    public function getFeedLastBatch(string $feedId, $plantId): array
    {
        return $this->wipEntryRepo->getFeedLastBatch($feedId, $plantId);
    }

    public function getRundownLastBatch(string $rundownId, $plantId): array
    {
        return $this->wipEntryRepo->getRundownLastBatch($rundownId, $plantId);
    }

    public function getActiveTanksForFeed(string $feedId, $plantId): array
    {
        return $this->wipEntryRepo->getActiveTanksForFeed($feedId, $plantId);
    }

    public function getActiveTanksForRundown(string $rundownId, $plantId, ?string $subgroup = null): array
    {
        return $this->wipEntryRepo->getActiveTanksForRundown($rundownId, $plantId, $subgroup);
    }

    public function getActiveSpecificTanks(int $slocId): array
    {
        return $this->wipEntryRepo->getActiveSpecificTanks($slocId);
    }

    public function getQuantifierData(string $date, string $tagNumber): array
    {
        return $this->wipEntryRepo->getQuantifierData($date, $tagNumber);
    }

    // B8: WIP Tree/Dashboard
    public function getWipTree($plantId): array
    {
        return $this->wipEntryRepo->getWipTree($plantId);
    }

    // Auto Number Generation - Per Section
    public function generateNewFeedNumber(string $feedId, $plantId): ?string
    {
        return $this->wipEntryRepo->generateNewFeedNumber($feedId, $plantId);
    }

    public function generateNewRundownNumber(string $rundownId, $plantId, ?string $subgroup = null): ?string
    {
        return $this->wipEntryRepo->generateNewRundownNumber($rundownId, $plantId, $subgroup);
    }

    public function postMaterialFeed(array $data, string $user): array
    {
        $result = $this->wipEntryRepo->postMaterialFeed($data, $user);
        return $this->formatResponse($result, 'FEED', $data['feature'] ?? '');
    }

    public function postMaterialRundown(array $data, string $user): array
    {
        $result = $this->wipEntryRepo->postMaterialRundown($data, $user);
        return $this->formatResponse($result, 'RUNDOWN', $data['feature'] ?? '');
    }

    public function postMaterialDocument(string $mode, int $idTraceHead, string $materialDoc, string $user): array
    {
        $result = $this->wipEntryRepo->postMaterialDocument($mode, $idTraceHead, $materialDoc, $user);
        return $this->formatResponse($result, 'MATL DOC NO', $mode);
    }

    public function cancelFeed(string $traceNo, string $user): array
    {
        $result = $this->wipEntryRepo->cancelFeed($traceNo, $user);
        return $this->formatResponse($result, 'FEED ' . $traceNo, 'CANCEL');
    }

    public function cancelRundown(string $traceNo, string $user): array
    {
        $result = $this->wipEntryRepo->cancelRundown($traceNo, $user);
        return $this->formatResponse($result, 'RUNDOWN ' . $traceNo, 'CANCEL');
    }

    public function updateEntrySubTank(int $idHead, array $tails, string $user): array
    {
        $result = $this->wipEntryRepo->updateEntrySubTank($idHead, $tails, $user);
        return $this->formatResponse($result, 'SUBTANK', 'UPDATE');
    }

    public function checkPeriodLock(string $date): bool
    {
        return $this->wipEntryRepo->checkPeriodLock($date);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    protected function formatResponse(array $result, string $feature, string $mode): array
    {
        $response = $result[0]['response'] ?? $result[0]->response ?? '0';

        $messages = [
            '1'  => 'Success ' . $mode . ' ' . $feature,
            '0'  => 'Failed ' . $mode . ' ' . $feature,
            '2'  => $feature . ' already exists',
            '3'  => $feature . ' Not Enough Reserve!',
            '4'  => $feature . ' Feed N/A!',
            '5'  => $feature . ' Feed Qty undefined!',
            '6'  => $feature . ' No Supplier Traced!',
            '7'  => $feature . ' Double Trace no!',
            '99' => $feature . ' Period Locked!',
        ];

        return [
            'status'  => $response === '1' ? 1 : 0,
            'message' => $messages[$response] ?? $feature,
            'data'    => $result,
        ];
    }

    protected function getPlants(): array
    {
        $user = Auth::user();
        if (!$user) return [];

        if ($user->hasRole(['admin', 'super-admin'])) {
            return $this->wipEntryRepo->getAllPlants();
        }

        return $this->wipEntryRepo->getUserPlants($user->id);
    }

    protected function resolvePlant($plantId)
    {
        if ($plantId) return $plantId;
        return session('selected_plant');
    }
}

