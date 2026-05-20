<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Services\RmEntryService;
use App\Models\Tank;
use App\Models\TankDetail;
use App\Models\Material;
use App\Models\Supplier;
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

    /**
     * Get RM Entry list
     */
    public function index(Request $request)
    {
        try {
            $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
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

    /**
     * Store new RM Entry
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entry_date' => 'required|date',
            'rm_number' => 'required|string',
            'id_material' => 'required|integer',
            'id_tank' => 'required|integer',
            'id_tank_tail' => 'required|array',
            'total_qty' => 'required|numeric|min:0.001',
            'material_document' => 'nullable|string',
            'po_so' => 'nullable|string',
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

    /**
     * Deactivate RM Entry
     */
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

    /**
     * Generate new RM number
     */
    public function newNumber(Request $request)
    {
        try {
            $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
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

    /**
     * Get storage tanks
     */
    public function tanks()
    {
        try {
            $tanks = Tank::active()
                ->storage()
                ->orderBy('description')
                ->get(['id_sloc as id_tank', 'description as tank']);

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

    /**
     * Get tank details (sub tanks)
     */
    public function tankDetails($tankId)
    {
        try {
            $details = TankDetail::active()
                ->where('id_sloc', $tankId)
                ->orderBy('tf_number')
                ->get(['id_sloc_tail as id_tank_tail', 'tf_number as tankNo']);

            return response()->json([
                'success' => true,
                'data' => $details
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RM materials
     */
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

    /**
     * Search suppliers
     */
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

    /**
     * Generate batch code
     */
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

    /**
     * Add supplier to temporary
     */
    public function addSupplier(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entry_no' => 'required|string',
            'id_supplier' => 'nullable|integer',
            'id_material' => 'required|integer',
            'qty' => 'required|numeric|min:0.001',
            'batch_sap' => 'nullable|string',
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

    /**
     * Get supplier list from temporary
     */
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

    /**
     * Delete supplier from temporary
     */
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

    /**
     * Get total qty from temporary
     */
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

    /**
     * Transfer RM to Feed Tank
     */
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entry_date' => 'required|date',
            'entry_no' => 'required|string',
            'source_tank' => 'required|integer',
            'trf_tank' => 'required|integer',
            'tank_no' => 'required|array',
            'trf_tank_no' => 'required|array',
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

    /**
     * Generate new transfer number
     */
    public function transferNumber(Request $request)
    {
        try {
            $plantId = $request->input('id_plant', Auth::user()?->id_plant ?? 0);
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
}
