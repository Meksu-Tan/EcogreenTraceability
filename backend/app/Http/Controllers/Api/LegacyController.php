<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackwardTrace;
use App\Models\BaseModel;
use App\Models\Blending;
use App\Models\ForwardTrace;
use App\Models\RawMaterial;
use App\Models\Report;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\Wip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LegacyController extends Controller
{
    public function forwardList(Request $request)
    {
        return $this->run(fn () => $this->enrichRowsWithPlants(RawMaterial::get_dtRmList($request), $request));
    }

    public function forwardTrace(Request $request, $id = null)
    {
        if ($id && !$request->filled('traceNo')) {
            $request->merge(['traceNo' => $id]);
        }

        return $this->run(fn () => $this->enrichRowsWithPlants(ForwardTrace::get_dtForwardTrace($request), $request));
    }

    public function backwardList(Request $request)
    {
        return $this->run(fn () => $this->enrichRowsWithPlants(BackwardTrace::get_dtBackwardList($request), $request));
    }

    public function backwardTrace(Request $request, $id = null)
    {
        if ($id && !$request->filled('traceNo')) {
            $request->merge(['traceNo' => $id]);
        }

        return $this->run(fn () => $this->enrichRowsWithPlants(BackwardTrace::get_dtBackwardTrace($request), $request));
    }

    public function stockMaterials(Request $request)
    {
        return $this->run(fn () => Stock::get_activeMaterialStock(
            $request->input('materialStock'),
            $request->input('stockType')
        ));
    }

    public function stockSloc()
    {
        return $this->run(fn () => Stock::get_activeSloc());
    }

    public function stockDetail(Request $request)
    {
        $this->normalizeDateRange($request);

        return $this->run(fn () => Stock::get_dtStock($request));
    }

    public function stockSummary(Request $request)
    {
        $this->normalizeDateRange($request);

        return $this->run(fn () => Stock::get_dtStockSummary($request));
    }

    public function tsReport(Request $request, string $type = 'all')
    {
        $this->normalizeEntryDate($request);

        return $this->run(function () use ($request, $type) {
            $rows = match ($type) {
                'rm' => Report::get_dtTsReportRm($request),
                'packaging', 'pck' => Report::get_dtTsReportPck($request),
                'shipment', 'ship' => Report::get_dtTsReportShip($request),
                'transfer', 'trf' => Report::get_dtTsReportTrf($request),
                default => Report::get_dtTsReport($request),
            };

            return $this->enrichRowsWithPlants($rows, $request);
        });
    }

    public function rmReportSummary(Request $request)
    {
        if (!$request->filled('selectedYear')) {
            $request->merge(['selectedYear' => $request->input('year', now()->year)]);
        }

        return $this->run(fn () => Report::get_dtSummaryRmPrd($request));
    }

    public function rmReportTank(Request $request)
    {
        return $this->run(fn () => Report::get_dtDetailRmPrd_onTank($request));
    }

    public function rmReportAdjustmentOut(Request $request)
    {
        return $this->run(fn () => Report::get_dtDetailRmPrd_onAdjOut($request));
    }

    public function rmReportWarehouse(Request $request)
    {
        return $this->run(fn () => Report::get_dtDetailRmPrd_onWarehouse($request));
    }

    public function wipBalance(Request $request)
    {
        $rundownId = $request->input('rundownId', $request->input('rundown_id'));

        return $this->run(fn () => $this->runForSelectedPlants(
            $request,
            fn () => Wip::get_dtBalance($request, $rundownId)
        ));
    }

    public function wipFeed(Request $request)
    {
        $request->merge(['mode' => $request->input('mode', 'LATEST')]);
        $feedId = $request->input('feedId', $request->input('feed_id'));

        return $this->run(fn () => $this->runForSelectedPlants(
            $request,
            fn () => Wip::get_dtFeed($request, $feedId)
        ));
    }

    public function wipRundown(Request $request)
    {
        $request->merge(['mode' => $request->input('mode', 'LATEST')]);
        $rundownId = $request->input('rundownId', $request->input('rundown_id'));

        return $this->run(fn () => $this->runForSelectedPlants(
            $request,
            fn () => Wip::get_dtRundown($request, $rundownId)
        ));
    }

    public function wipOption(Request $request, string $option)
    {
        return $this->run(function () use ($request, $option) {
            return match ($option) {
                'feed-number' => Wip::get_feedNewBatchNumber($request),
                'rundown-number' => Wip::get_rundownNewBatchNumber($request),
                'feed-last-batch' => Wip::get_feedLastBatch($request),
                'rundown-last-batch' => Wip::get_rundownLastBatch($request),
                'feed-tanks' => Wip::get_cmbActiveTank_trf($request),
                'rundown-tanks' => Wip::get_cmbActiveTank_rundown($request),
                'specific-feed-tanks' => Wip::get_cmbActiveSpecificTank_trf($request),
                'quantifier' => Wip::get_quantifierData($request),
                default => [],
            };
        });
    }

    public function wipStore(Request $request, string $type)
    {
        $user = $this->userName();
        $request->merge([
            'flag' => $type === 'rundown' ? 'post_materialRundown' : 'post_materialFeed',
            'mode' => $request->input('mode', 'ADD'),
        ]);

        return $this->legacyWrite(function () use ($request, $type, $user) {
            return $type === 'rundown'
                ? Wip::post_materialRundown($user, $request)
                : Wip::post_materialFeed($user, $request);
        }, strtoupper($type), $request->input('mode', 'ADD'));
    }

    public function wipCancel(Request $request, string $type)
    {
        $user = $this->userName();
        $request->merge([
            'flag' => $type === 'rundown' ? 'post_cancelRundown' : 'post_cancelFeed',
            'mode' => $request->input('mode', 'delete'),
        ]);

        return $this->legacyWrite(function () use ($request, $type, $user) {
            return $type === 'rundown'
                ? Wip::post_cancelRundown($user, $request)
                : Wip::post_cancelFeed($user, $request);
        }, strtoupper($type), $request->input('mode', 'delete'));
    }

    public function blendingList(Request $request)
    {
        return $this->run(fn () => Blending::get_dtBlendingList($request));
    }

    public function blendingMaterials(Request $request)
    {
        return $this->run(fn () => Blending::get_dtMaterialList($request));
    }

    public function blendingOption(Request $request, string $option)
    {
        return $this->run(function () use ($request, $option) {
            return match ($option) {
                'materials' => Blending::get_cmbActiveMaterial(),
                'new-number' => Blending::get_newBlendingEntryNo($request),
                'stock' => Blending::get_totalStockMaterial($request),
                'total-qty' => Blending::get_totalQtyMaterial($request),
                'rundown-tanks' => Blending::get_cmbActiveTank_rundown($request),
                'specific-rundown-tanks' => Blending::get_cmbActiveSpecificTank_rundown($request),
                default => [],
            };
        });
    }

    public function blendingMaterialStore(Request $request)
    {
        $user = $this->userName();
        $this->normalizeBlendingMaterialPayload($request);

        return $this->legacyWrite(
            fn () => Blending::post_blendingEntryMaterial($user, $request),
            'BLEND MATERIAL',
            $request->input('mode', 'ADD')
        );
    }

    public function blendingStore(Request $request)
    {
        $user = $this->userName();
        $request->merge(['flag' => 'post_blendingEntry', 'mode' => $request->input('mode', 'ADD')]);

        return $this->legacyWrite(
            fn () => Blending::post_blendingEntry($user, $request),
            'BLENDING',
            $request->input('mode', 'ADD')
        );
    }

    public function blendingMaterialDestroy($id)
    {
        return $this->legacyWrite(
            fn () => Blending::blendingMaterial_destroy($id, $this->userName()),
            'MATERIAL',
            'delete'
        );
    }

    public function blendingDestroy($id)
    {
        return $this->legacyWrite(
            fn () => Blending::blending_destroy($id, $this->userName()),
            'BLENDING',
            'delete'
        );
    }

    public function transferList(Request $request)
    {
        return $this->run(fn () => $this->enrichRowsWithPlants(Transfer::get_dtTransferList($request), $request));
    }

    public function transferOption(Request $request, string $option)
    {
        return $this->run(function () use ($request, $option) {
            return match ($option) {
                'materials' => Transfer::get_cmbActiveMaterial(),
                'new-number' => Transfer::get_newTransferEntryNo($request),
                'rundown-tanks' => Transfer::get_cmbActiveTank_rundown($request),
                'specific-rundown-tanks' => Transfer::get_cmbActiveSpecificTank_rundown($request),
                'stock' => Transfer::get_totalStockMaterial($request),
                'supplier-material' => Transfer::get_updateSupplierMaterial($request),
                default => [],
            };
        });
    }

    public function transferStore(Request $request)
    {
        $user = $this->userName();
        $this->normalizeTransferPayload($request);

        return $this->legacyWrite(function () use ($request, $user) {
            DB::beginTransaction();
            try {
                $entryNo = $request->input('entry_no');
                $entryDate = $request->input('entry_date');
                $idMaterial = $request->input('id_material');
                $materialDoc = $request->input('material_doc');
                $trfQty = $request->input('trf_qty');
                $trfSource = $request->input('source_sloc');
                $trfDestination = $request->input('trf_sloc');
                $trfSourceTail = $request->input('source_sloc_no');
                $trfDestinationTail = $request->input('trf_sloc_no');

                $return = Transfer::post_transferEntry(
                    $user,
                    $entryNo,
                    $entryDate,
                    $idMaterial,
                    $materialDoc,
                    $trfQty,
                    $trfSource,
                    $trfDestination,
                    $trfSourceTail,
                    $trfDestinationTail
                );

                DB::commit();

                return $return;
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }, 'TRANSFER', $request->input('mode', 'ADD'));
    }

    public function transferDestroy($id)
    {
        return $this->legacyWrite(
            fn () => Transfer::transfer_destroy($id, $this->userName()),
            'TRANSFER',
            'delete'
        );
    }

    public function materialDocument(Request $request, string $module)
    {
        $user = $this->userName();
        $request->merge([
            'flag' => 'post_matlDocNumber',
            'mode' => $request->input('mode', 'ADD'),
        ]);

        $callback = match ($module) {
            'wip' => fn () => Wip::post_matlDocNumber($user, $request),
            'blending' => fn () => Blending::post_matlDocNumber($user, $request),
            'transfer' => fn () => Transfer::post_matlDocNumber($user, $request),
            default => fn () => RawMaterial::post_matlDocNumber($user, $request),
        };

        return $this->legacyWrite($callback, 'MATL DOC NO', $request->input('mode', 'ADD'));
    }

    public function updateSubTank(Request $request, string $module)
    {
        $user = $this->userName();
        $request->merge([
            'flag' => 'post_updateEntrySubTank',
            'mode' => $request->input('mode', 'UPDATE'),
        ]);

        $callback = match ($module) {
            'wip' => fn () => Wip::post_updateEntrySubTank($user, $request),
            'blending' => fn () => Blending::post_updateEntrySubTank($user, $request),
            'transfer' => fn () => Transfer::post_updateEntrySubTank($user, $request),
            default => fn () => RawMaterial::post_updateEntrySubTank($user, $request),
        };

        return $this->legacyWrite($callback, 'SUBTANK', $request->input('mode', 'UPDATE'));
    }

    protected function runForSelectedPlants(Request $request, callable $callback): array
    {
        $plantId = BaseModel::resolvePlant($request);

        if (!$this->isAllPlants($plantId)) {
            return $this->tagRowsWithPlant($this->rowsArray($callback()), (string) $plantId);
        }

        $originalPlant = $request->input('id_plant');
        $rows = [];

        foreach ($this->activePlants() as $plant) {
            $request->merge(['id_plant' => $plant->id_plant]);

            foreach ($this->rowsArray($callback()) as $row) {
                $this->applyPlantMeta($row, $plant);
                $rows[] = $row;
            }
        }

        $request->merge(['id_plant' => $originalPlant]);

        return $rows;
    }

    protected function enrichRowsWithPlants($rows, Request $request): array
    {
        $rows = $this->rowsArray($rows);
        if (empty($rows)) {
            return [];
        }

        $plantMap = $this->plantMap();
        $traceNumbers = [];

        foreach ($rows as $row) {
            foreach (['trace_no', 'trace_nos', 'to_trace_no', 'from_trace_no'] as $field) {
                foreach ($this->traceNumbersFrom($row->{$field} ?? null) as $traceNo) {
                    $traceNumbers[$traceNo] = true;
                }
            }
        }

        $traceMap = [];
        if (!empty($traceNumbers)) {
            $numbers = array_keys($traceNumbers);
            $traceRows = DB::table('t_trace_header as th')
                ->leftJoin('m_plant as p', 'p.code_3', '=', 'th.id_plant')
                ->whereIn('th.to_trace_no', $numbers)
                ->orWhereIn('th.from_trace_no', $numbers)
                ->select(
                    'th.to_trace_no',
                    'th.from_trace_no',
                    'th.id_plant',
                    DB::raw('COALESCE(p.code_2, p.code_3, th.id_plant) as plant_code'),
                    DB::raw('COALESCE(p.description, p.code_2, th.id_plant) as plant_name')
                )
                ->get();

            foreach ($traceRows as $traceRow) {
                if ($traceRow->to_trace_no) {
                    $traceMap[(string) $traceRow->to_trace_no] = $traceRow;
                }
                if ($traceRow->from_trace_no) {
                    $traceMap[(string) $traceRow->from_trace_no] = $traceRow;
                }
            }
        }

        $selectedPlant = BaseModel::resolvePlant($request);
        $filtered = [];

        foreach ($rows as $row) {
            $plantId = $row->id_plant ?? null;
            $plantMeta = $plantId ? ($plantMap[(string) $plantId] ?? null) : null;

            if (!$plantMeta) {
                foreach (['trace_no', 'trace_nos', 'to_trace_no', 'from_trace_no'] as $field) {
                    foreach ($this->traceNumbersFrom($row->{$field} ?? null) as $traceNo) {
                        if (isset($traceMap[$traceNo])) {
                            $plantMeta = $traceMap[$traceNo];
                            $plantId = $plantMeta->id_plant;
                            break 2;
                        }
                    }
                }
            }

            if (!$this->isAllPlants($selectedPlant) && (string) ($plantId ?? '') !== (string) $selectedPlant) {
                continue;
            }

            if ($plantMeta) {
                $this->applyPlantMeta($row, $plantMeta);
            }

            $filtered[] = $row;
        }

        return $filtered;
    }

    protected function activePlants(): array
    {
        return DB::table('m_plant')
            ->where('status', 1)
            ->orderBy('id_plant')
            ->get([
                DB::raw('code_3 as id_plant'),
                DB::raw('COALESCE(code_2, code_3) as plant_code'),
                DB::raw('COALESCE(description, code_2, code_3) as plant_name'),
            ])
            ->all();
    }

    protected function plantMap(): array
    {
        $map = [];
        foreach ($this->activePlants() as $plant) {
            $map[(string) $plant->id_plant] = $plant;
        }
        return $map;
    }

    protected function tagRowsWithPlant(array $rows, string $plantId): array
    {
        $plant = $this->plantMap()[$plantId] ?? null;
        if (!$plant) {
            return $rows;
        }

        foreach ($rows as $row) {
            $this->applyPlantMeta($row, $plant);
        }

        return $rows;
    }

    protected function applyPlantMeta(object $row, object $plant): void
    {
        $row->id_plant = $plant->id_plant;
        $row->plant_code = $plant->plant_code;
        $row->plant_name = $plant->plant_name;
    }

    protected function rowsArray($rows): array
    {
        if ($rows instanceof \Illuminate\Support\Collection) {
            return $rows->all();
        }

        if (is_array($rows)) {
            return $rows;
        }

        return $rows ? [$rows] : [];
    }

    protected function traceNumbersFrom($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        preg_match_all('/\b\d{12,16}\b/', (string) $value, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

    protected function isAllPlants($plantId): bool
    {
        return $plantId === null || $plantId === '' || (string) $plantId === '0';
    }

    protected function run(callable $callback)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $callback(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function legacyWrite(callable $callback, string $feature, string $mode)
    {
        try {
            $return = $callback();
            $response = $this->legacyStatus($return, $feature, $mode);

            return response()->json($response, $response['status'] ? 200 : 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => 0,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function legacyStatus($return, string $feature, string $mode): array
    {
        $code = null;

        if (is_array($return)) {
            $first = $return[0] ?? null;
            $code = is_array($first) ? ($first['response'] ?? null) : ($first->response ?? null);
        } elseif (is_object($return)) {
            $code = $return->response ?? null;
        }

        $code = (string) ($code ?? '0');

        $message = match ($code) {
            '1' => "Success {$mode} {$feature}",
            '2' => "{$feature} already exists",
            '3' => "{$feature} Entry Error",
            '4' => "{$feature} Stock Not Enough",
            '5' => "{$feature} Qty undefined",
            '6' => "{$feature} Supplier Trace Missing",
            '7' => "{$feature} Double Trace no",
            '9' => 'Source or Destination Tank is inactive',
            '98' => 'Entry data not found',
            '99' => "{$feature} Period Locked!",
            default => "Failed {$mode} {$feature}",
        };

        return [
            'success' => $code === '1',
            'status' => $code === '1' ? 1 : 0,
            'response' => $code,
            'message' => $message,
            'data' => $return,
        ];
    }

    protected function normalizeDateRange(Request $request): void
    {
        $request->merge([
            'startDate' => $request->input('startDate', $request->input('start_date', now()->startOfMonth()->toDateString())),
            'endDate' => $request->input('endDate', $request->input('end_date', now()->toDateString())),
            'stockDateStart' => $request->input('stockDateStart', $request->input('startDate', $request->input('start_date', now()->startOfMonth()->toDateString()))),
            'stockDateEnd' => $request->input('stockDateEnd', $request->input('endDate', $request->input('end_date', now()->toDateString()))),
            'mode' => $request->input('mode', 'NORMAL'),
        ]);
    }

    protected function normalizeEntryDate(Request $request): void
    {
        if (!$request->filled('entryDate')) {
            $request->merge(['entryDate' => $request->input('entry_date', now()->toDateString())]);
        }
    }

    protected function normalizeBlendingMaterialPayload(Request $request): void
    {
        $request->merge([
            'flag' => 'post_blendingEntryMaterial',
            'mode' => $request->input('mode', 'ADD'),
            'idMaterial' => $request->input('idMaterial', $request->input('id_material')),
            'entryDate' => $request->input('entryDate', $request->input('entry_date')),
            'entryNo' => $request->input('entryNo', $request->input('entry_no')),
            'idHead' => $request->input('idHead', $request->input('id_head')),
            'materialDoc' => $request->input('materialDoc', $request->input('material_doc')),
            'idTank' => $request->input('idTank', $request->input('id_tank')),
        ]);
    }

    protected function normalizeTransferPayload(Request $request): void
    {
        $request->merge([
            'flag' => 'post_transferEntry',
            'mode' => $request->input('mode', 'ADD'),
            'entry_no' => $request->input('entry_no', $request->input('entryNo')),
            'entry_date' => $request->input('entry_date', $request->input('entryDate')),
            'id_material' => $request->input('id_material', $request->input('idMaterial')),
            'material_doc' => $request->input('material_doc', $request->input('materialDoc')),
            'trf_qty' => $request->input('trf_qty', $request->input('qty')),
            'source_sloc' => $request->input('source_sloc', $request->input('sourceSloc')),
            'trf_sloc' => $request->input('trf_sloc', $request->input('transferSloc')),
            'source_sloc_no' => $request->input('source_sloc_no', $request->input('sourceSlocNo', [])),
            'trf_sloc_no' => $request->input('trf_sloc_no', $request->input('transferSlocNo', [])),
            'trf_type' => $request->input('trf_type', $request->input('transferType', 'all')),
        ]);
    }

    protected function userName(): string
    {
        return Auth::user()?->name ?? 'System';
    }
}
