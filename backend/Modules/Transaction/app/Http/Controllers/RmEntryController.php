<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Transaction\Services\RmEntryService;
use Modules\Transaction\Http\Requests\StoreRmEntryRequest;
use Modules\Transaction\Http\Requests\StoreSupplierTempRequest;
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
                ->groupBy('description')
                ->get(['description as tank']);

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
            $repo = app()->make(\Modules\Transaction\Repositories\RmEntryRepository::class);
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

            $feedData = [
                'id_material' => $materialId,
                'id_tank' => $tankId,
                'id_tank_tail' => $tankTail ? json_encode($tankTail) : null,
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
}
