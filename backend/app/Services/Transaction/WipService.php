<?php

namespace App\Services\Transaction;

use App\Models\BaseModel;
use App\Models\Wip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WipService
{
    /**
     * Run WIP queries for the resolved plant, or all active plants when id_plant is 0.
     */
    protected function runForSelectedPlants(Request $request, callable $callback)
    {
        $plantId = BaseModel::resolvePlant($request);

        if (!$this->isAllPlants($plantId)) {
            return $this->rowsArray($callback());
        }

        $originalPlant = $request->input('id_plant');
        $allResults = [];

        foreach ($this->activePlants() as $plant) {
            $request->merge(['id_plant' => $plant->id_plant]);
            $allResults = array_merge($allResults, $this->rowsArray($callback()));
        }

        $request->merge(['id_plant' => $originalPlant]);

        return $allResults;
    }

    protected function activePlants(): array
    {
        return DB::table('m_plant')
            ->where('status', 1)
            ->orderBy('id_plant')
            ->get([
                DB::raw('code_3 as id_plant'),
            ])
            ->all();
    }

    protected function isAllPlants($plantId): bool
    {
        return $plantId === null || $plantId === '' || (string) $plantId === '0';
    }

    protected function rowsArray($result): array
    {
        if ($result === null) {
            return [];
        }

        if (is_array($result)) {
            return $result;
        }

        if ($result instanceof \Illuminate\Support\Collection) {
            return $result->all();
        }

        if (is_object($result) && method_exists($result, 'toArray')) {
            return $result->toArray();
        }

        return [$result];
    }

    public function getBalance(Request $request, $rundownId)
    {
        return $this->runForSelectedPlants($request, function () use ($request, $rundownId) {
            return Wip::get_dtBalance($request, $rundownId);
        });
    }

    public function getFeed(Request $request, $feedId)
    {
        return $this->runForSelectedPlants($request, function () use ($request, $feedId) {
            return Wip::get_dtFeed($request, $feedId);
        });
    }

    public function getRundown(Request $request, $rundownId)
    {
        return $this->runForSelectedPlants($request, function () use ($request, $rundownId) {
            return Wip::get_dtRundown($request, $rundownId);
        });
    }

    public function getOptions(Request $request, string $option)
    {
        $plantId = BaseModel::resolvePlant($request);

        if (!$this->isAllPlants($plantId)) {
            return $this->resolveOption($request, $option);
        }

        // Options (batch no, tanks, etc.) must run for a single plant — use first active plant.
        $plants = $this->activePlants();
        if (empty($plants)) {
            return [];
        }

        $request->merge(['id_plant' => $plants[0]->id_plant]);

        return $this->resolveOption($request, $option);
    }

    protected function resolveOption(Request $request, string $option)
    {
        return match ($option) {
            'feed-number' => Wip::get_feedNewBatchNumber($request),
            'rundown-number' => Wip::get_rundownNewBatchNumber($request),
            'feed-last-batch' => Wip::get_feedLastBatch($request),
            'rundown-last-batch' => Wip::get_rundownLastBatch($request),
            'feed-tanks' => Wip::get_cmbActiveTank_trf($request),
            'rundown-tanks' => Wip::get_cmbActiveTank_rundown($request),
            'specific-feed-tanks' => Wip::get_cmbActiveSpecificTank_trf($request),
            default => [],
        };
    }

    public function storeFeed($user, Request $request)
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $request) {
            return Wip::post_materialFeed($user, $request);
        });
    }

    public function storeRundown($user, Request $request)
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $request) {
            return Wip::post_materialRundown($user, $request);
        });
    }

    public function cancelFeed($user, Request $request)
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $request) {
            return Wip::post_cancelFeed($user, $request);
        });
    }

    public function cancelRundown($user, Request $request)
    {
        return DB::connection('eudr_ts')->transaction(function () use ($user, $request) {
            return Wip::post_cancelRundown($user, $request);
        });
    }
}
