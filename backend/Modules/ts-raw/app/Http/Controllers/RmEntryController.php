<?php

namespace Modules\TsRaw\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\TsRaw\Services\RmEntryService;
use Modules\TsRaw\Http\Requests\StoreRmEntryRequest;
use Modules\TsRaw\Http\Requests\StoreSupplierTempRequest;
use Modules\Tank\Models\Tank;
use Modules\Tank\Models\TankDetail;
use Modules\Material\Models\Material;
use Modules\Supplier\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RmEntryController extends Controller
{
    protected $rmEntryService;

    public function __construct(RmEntryService $rmEntryService)
    {
        $this->rmEntryService = $rmEntryService;
    }

    public function index(Request $request)
    {
        try {
            $plantId = $request->has('id_plant')
                ? $request->input('id_plant')
                : (Auth::user()?->id_plant ?? 0);
            $data = $this->rmEntryService->getRmList($plantId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $result = $this->rmEntryService->getRmEntryById($id);
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreRmEntryRequest $request)
    {
        try {
            $data = $request->validated();
            $data['id_plant'] = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
            $user = Auth::user()?->name ?? 'System';

            $result = $this->rmEntryService->saveRmEntry($data, $user);

            return response()->json([
                'success' => true,
                'message' => 'RM Entry created successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->validated();
            $data['id_plant'] = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
            $user = Auth::user()?->name ?? 'System';

            $result = $this->rmEntryService->updateRmEntry($id, $data, $user);

            return response()->json([
                'success' => true,
                'message' => 'RM Entry updated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user()?->name ?? 'System';
            $result = $this->rmEntryService->deactivateRmEntry($id, $user);

            return response()->json([
                'success' => true,
                'message' => 'RM Entry deactivated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function newNumber(Request $request)
    {
        try {
            $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
            $tankDesc = $request->input('tank_desc');

            if ($plantId == 0 && $tankDesc) {
                $tank = Tank::where('description', $tankDesc)->first();
                if ($tank) {
                    $plantId = $tank->id_plant;
                }
            }

            $rmNumber = $this->rmEntryService->generateRmNumber($plantId);

            return response()->json([
                'success' => true,
                'data' => ['rm_number' => $rmNumber]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function tanks(Request $request)
    {
        try {
            $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
            if ($plantId) {
                if (is_numeric($plantId)) {
                    $plant = \Modules\Plant\Models\Plant::find($plantId);
                    if ($plant && $plant->code_3) {
                        $plantId = $plant->code_3;
                    }
                }
            }

            $query = Tank::active()->storage();

            if ($plantId) {
                $query->where('id_plant', $plantId);
            }

            $tanks = $query->orderBy('description')
                ->groupBy('description', 'id_plant')
                ->get(['description as tank', 'id_plant']);

            return response()->json([
                'success' => true,
                'data' => $tanks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function tankDetails(Request $request, $tankId)
    {
        try {
            $plantId = $request->input('id_plant');
            if ($plantId === '' || $plantId === null) {
                $plantId = Auth::user()?->id_plant ?? 0;
            }
            if ($plantId) {
                if (is_numeric($plantId)) {
                    $plant = \Modules\Plant\Models\Plant::find($plantId);
                    if ($plant && $plant->code_3) {
                        $plantId = $plant->code_3;
                    }
                }
            }

            $tanksQuery = Tank::active()->where('description', $tankId);
            if ($plantId) {
                $tanksQuery->where('id_plant', $plantId);
            }
            $tanks = $tanksQuery->get();
            $tankIds = $tanks->pluck('id_sloc')->toArray();

            $details = TankDetail::active()
                ->whereIn('id_sloc', $tankIds)
                ->orderBy('tf_number')
                ->get(['id_sloc_tail as id_tank_tail', 'tf_number as tankNo', 'id_sloc']);

            $detailsSlocIds = $details->pluck('id_sloc')->toArray();
            $virtualDetails = [];
            foreach ($tanks as $tank) {
                if (!in_array($tank->id_sloc, $detailsSlocIds) && !empty($tank->id_tank)) {
                    $virtualDetails[] = [
                        'id_tank_tail' => 's_' . $tank->id_sloc,
                        'tankNo' => $tank->id_tank,
                        'id_sloc' => $tank->id_sloc
                    ];
                }
            }

            $result = array_merge($details->toArray(), $virtualDetails);

            usort($result, function($a, $b) {
                return strcmp($a['tankNo'], $b['tankNo']);
            });

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function materials()
    {
        try {
            $materials = Material::where('status', 1)
                ->where('type', 'RM')
                ->orderBy('code')
                ->get()
                ->map(function ($item) {
                    return [
                        'id_material' => $item->id_material,
                        'material' => strtoupper($item->description) . ' (' . $item->code . ' / ' . $item->type . ' / Feed: ' . $item->qtf_feed . ' / Rundown: ' . $item->qtf_rundown . ')'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $materials
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function searchSuppliers(Request $request)
    {
        try {
            $search = $request->input('q', '');

            $suppliers = Supplier::where('status', 1)
                ->where('description', 'like', '%' . $search . '%')
                ->orderBy('description')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id_supplier,
                        'text' => $item->code . ' :: ' . $item->description
                    ];
                });

            return response()->json($suppliers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function batchCode(Request $request)
    {
        try {
            $supplierId = $request->input('id_supplier');
            $batchCode = $this->rmEntryService->generateBatchCode($supplierId);

            return response()->json([
                'success' => true,
                'data' => ['batch_code' => $batchCode]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addSupplier(StoreSupplierTempRequest $request)
    {
        try {
            $data = $request->validated();
            $data['id_plant'] = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
            $user = Auth::user()?->name ?? 'System';

            $result = $this->rmEntryService->addSupplierTemp($data, $user);

            return response()->json([
                'success' => true,
                'message' => 'Supplier added successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function supplierList(Request $request)
    {
        try {
            $entryNo = $request->input('entry_no');
            $data = $this->rmEntryService->getSupplierList($entryNo);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteSupplier($id)
    {
        try {
            $user = Auth::user()?->name ?? 'System';
            $this->rmEntryService->deleteSupplierTemp($id, $user);

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clearTempSuppliers($entryNo)
    {
        try {
            $user = Auth::user()?->name ?? 'System';
            // The service will call the repository to clear all temp data for the entry
            $repo = app()->make(\Modules\TsRaw\Repositories\RmEntryRepository::class);
            $repo->clearTempData($entryNo, $user);

            return response()->json([
                'success' => true,
                'message' => 'Temp suppliers cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function totalQty(Request $request)
    {
        try {
            $entryNo = $request->input('entry_no');
            $total = $this->rmEntryService->getTotalQtyTemp($entryNo);

            return response()->json([
                'success' => true,
                'data' => ['total' => number_format($total, 3)]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entry_date' => 'required|date',
            'entry_no' => 'required|string',
            'source_tank' => 'required|integer',
            'trf_tank' => 'required|integer',
            'tank_no' => 'present|array',
            'trf_tank_no' => 'present|array',
            'material_document' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['id_plant'] = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
            $user = Auth::user()?->name ?? 'System';

            $result = $this->rmEntryService->saveRmTrfEntry($data, $user);

            return response()->json([
                'success' => true,
                'message' => 'RM Transfer processed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function transferNumber(Request $request)
    {
        try {
            $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
            $tankDesc = $request->input('tank_desc');

            if ($plantId == 0 && $tankDesc) {
                $tank = Tank::where('description', $tankDesc)->first();
                if ($tank) {
                    $plantId = $tank->id_plant;
                }
            }

            $rmNumber = $this->rmEntryService->generateTransferNumber($plantId);

            return response()->json([
                'success' => true,
                'data' => ['rm_number' => $rmNumber]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function checkStockSync(Request $request)
    {
        try {
            $entryNo = $request->input('entry_no');
            $materialId = $request->input('id_material');

            if (!$entryNo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entry number is required'
                ], 422);
            }

            $syncStatus = $this->rmEntryService->checkStockSynchronization($entryNo, $materialId);

            return response()->json([
                'success' => true,
                'data' => $syncStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function debugFifoStock(Request $request)
    {
        try {
            $materialId = $request->input('id_material');
            $tankId = $request->input('id_tank');
            $tankTail = $request->input('id_tank_tail');
            $plantId = $request->input('id_plant');
            $tankMatching = $request->input('tank_matching', 'flexible');

            if (!$materialId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material ID is required'
                ], 422);
            }

            // Handle id_sloc_tail - ensure it's always a JSON array with string values
            $tankTailJson = null;
            if (!empty($tankTail)) {
                if (is_array($tankTail)) {
                    $tankTailJson = json_encode(array_map('strval', array_values($tankTail)));
                } else {
                    $decoded = json_decode($tankTail, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $tankTailJson = json_encode(array_map('strval', array_values($decoded)));
                    } else {
                        $tankTailJson = json_encode([(string)$tankTail]);
                    }
                }
            }

            $feedData = [
                'id_material' => $materialId,
                'id_sloc' => $tankId,
                'id_sloc_tail' => $tankTailJson,
                'balance_plant' => $plantId,
                'trace_prefixes' => ['1'],
                'tank_matching' => $tankMatching
            ];

            $fifoDetails = \Modules\Shared\Helpers\Feed::getDetailedFifoStock($feedData);

            return response()->json([
                'success' => true,
                'data' => $fifoDetails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verifySeparateEntries(Request $request)
    {
        try {
            $materialId = $request->input('id_material');
            $tankId = $request->input('id_tank');
            $plantId = $request->input('id_plant');
            $hoursBack = $request->input('hours_back', 24);

            if (!$materialId || !$tankId || !$plantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material ID, Tank ID, and Plant ID are required'
                ], 422);
            }

            $verification = $this->rmEntryService->verifySeparateEntries($materialId, $tankId, $plantId, $hoursBack);

            return response()->json([
                'success' => true,
                'data' => $verification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Storage and Feed Log Methods (moved from ts-transfer)
    public function storageLog(Request $request)
    {
        try {
            $plantId = $request->input('id_plant');
            if ($plantId === '' || $plantId === null) {
                $plantId = Auth::user()?->id_plant ?? 0;
            }
            $data = $this->rmEntryService->getStorageLog($plantId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function feedLog(Request $request)
    {
        try {
            $plantId = $request->input('id_plant');
            if ($plantId === '' || $plantId === null) {
                $plantId = Auth::user()?->id_plant ?? 0;
            }
            $data = $this->rmEntryService->getFeedLog($plantId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function debugFeedLog(Request $request)
    {
        try {
            $plantId = $request->input('id_plant');
            if ($plantId === '' || $plantId === null) {
                $plantId = Auth::user()?->id_plant ?? 0;
            }
            $data = $this->rmEntryService->debugFeedLog($plantId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Transfer Methods (moved from ts-transfer)
    public function sourceEntries(Request $request)
    {
        try {
            $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
            $entries = BalanceHeader::active()
                ->rmEntry()
                ->where('qty', '>', 0)
                ->where('id_plant', $plantId)
                ->with(['material', 'tank'])
                ->get()
                ->map(function ($item) {
                    return [
                        'id_balance_head' => $item->id_balance_head,
                        'trace_no' => $item->trace_no,
                        'material' => $item->material->description ?? 'Unknown',
                        'tank' => $item->tank->description ?? 'Unknown',
                        'qty' => $item->qty
                    ];
                });

            return response()->json(['success' => true, 'data' => $entries]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destTanks(Request $request)
    {
        try {
            $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
            if ($plantId) {
                $plant = \Modules\Plant\Models\Plant::find($plantId);
                if ($plant && $plant->code_3) {
                    $plantId = $plant->code_3;
                }
            }
            $query = Tank::active()
                ->feed()
                ->orderBy('description')
                ->groupBy('description', 'id_plant');
            
            if ($plantId && $plantId !== '0' && $plantId !== 0) {
                $query->where('id_plant', $plantId);
            }
            
            $tanks = $query->get(['description as tank', 'id_plant']);

            return response()->json(['success' => true, 'data' => $tanks]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function transfers(Request $request)
    {
        try {
            $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
            $data = $this->rmEntryService->getTransferList($plantId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function interPlantTransfer(Request $request)
    {
        try {
            $data = $request->all();
            $data['id_plant'] = $request->input('id_plant', Auth::user()->id_plant ?? 0);
            $user = Auth::user()->name;

            $result = $this->rmEntryService->transfer($data, $user);

            return response()->json([
                'success' => true,
                'message' => 'Transfer completed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function matlDoc(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mode' => 'required|in:ADD,UPDATE',
                'id' => 'required|integer',
                'number' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $mode = $request->input('mode');
            $id = $request->input('id');
            $number = $request->input('number');
            $user = Auth::user()->name ?? 'System';

            if ($mode === 'ADD') {
                $exists = DB::connection('eudr_ts')->table('t_material_document')
                    ->where('id_trace_head', $id)
                    ->exists();
                if ($exists) {
                    DB::connection('eudr_ts')->table('t_material_document')
                        ->where('id_trace_head', $id)
                        ->update(['material_document' => $number, 'updated_by' => $user]);
                } else {
                    DB::connection('eudr_ts')->table('t_material_document')->insert([
                        'id_trace_head' => $id,
                        'material_document' => $number,
                        'created_by' => $user
                    ]);
                }
            } else {
                DB::connection('eudr_ts')->table('t_material_document')
                    ->where('id_trace_head', $id)
                    ->update(['material_document' => $number, 'updated_by' => $user]);
            }

            return response()->json(['success' => true, 'status' => 1]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateSubTank(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_head' => 'required|integer',
                'id_tank_tail' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $idHead = $request->input('id_head');
            $idTankTail = $request->input('id_tank_tail');

            // Handle id_tank_tail - ensure it's always a JSON array with string values
            $tankTailJson = null;
            if (!empty($idTankTail)) {
                if (is_array($idTankTail)) {
                    $tankTailJson = json_encode(array_map('strval', array_values($idTankTail)));
                } else {
                    $decoded = json_decode($idTankTail, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $tankTailJson = json_encode(array_map('strval', array_values($decoded)));
                    } else {
                        // Single value as string, wrap in array
                        $tankTailJson = json_encode([(string)$idTankTail]);
                    }
                }
            }

            DB::connection('eudr_ts')->table('t_balance_header')
                ->where('id_balance_head', $idHead)
                ->update(['id_sloc_tail' => $tankTailJson]);

            return response()->json(['success' => true, 'status' => 1]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deactivateTransfer(Request $request, $id)
    {
        try {
            $user = Auth::user()->name;
            $result = $this->rmEntryService->deactivateTransfer($id, $user);

            return response()->json([
                'success' => true,
                'message' => 'Transfer deactivated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
