<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Services\RmEntryService;
use App\Models\Tank;
use App\Models\TankDetail;
use App\Models\Material;
use App\Models\BaseModel;
use App\Models\RawMaterial as LegacyRawMaterial;
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
            $plantId = BaseModel::resolvePlant($request);
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
            'id_balance_head' => 'nullable|integer',
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
            $user = Auth::user()?->name ?? 'System';
            $plantId = BaseModel::resolvePlant($request);
            if ((string) $plantId === '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilih plant terlebih dahulu untuk RM Entry.',
                ], 422);
            }

            $request->merge(['id_plant' => $plantId]);
            $request->merge([
                'flag' => 'post_rmEntry',
                'mode' => $request->input('mode', 'ADD'),
                'idHead' => $request->input('id_balance_head', $request->input('idHead')),
                'entry_no' => $request->input('entry_no', $request->input('rm_number')),
                'entry_date' => $request->input('entry_date'),
                'tank' => $request->input('tank', $request->input('id_tank')),
                'tankNo' => $request->input('tankNo', $request->input('id_tank_tail', [])),
                'qty' => $request->input('qty', $request->input('total_qty')),
                'po' => $request->input('po', $request->input('po_so')),
                'idMaterial' => $request->input('idMaterial', $request->input('id_material')),
                'material_doc' => $request->input('material_doc', $request->input('material_document')),
            ]);

            $result = LegacyRawMaterial::post_rmEntry($user, $request);
            $legacy = $this->legacyResponse($result, 'RM Entry created successfully', 'RM Entry');

            return response()->json($legacy, $legacy['success'] ? 200 : 422);
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
            $result = LegacyRawMaterial::deactivateRmEntry($id, $user);
            $legacy = $this->legacyResponse($result, 'RM Entry deactivated successfully', 'RM Entry');

            return response()->json($legacy, $legacy['success'] ? 200 : 422);
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
            $plantId = BaseModel::resolvePlant($request);
            if ((string) $plantId === '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilih plant terlebih dahulu untuk membuat nomor RM.',
                ], 422);
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

    /**
     * Get storage tanks
     */
    public function tanks()
    {
        try {
            $tanks = Tank::active()
                ->storage()
                ->orderBy('description')
                ->get(['id_tank', 'description as tank']);

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
                ->where('id_tank', $tankId)
                ->orderBy('tf_number')
                ->get(['id_tank_tail', 'tf_number as tankNo']);

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
            $user = Auth::user()?->name ?? 'System';
            $plantId = BaseModel::resolvePlant($request);
            if ((string) $plantId === '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilih plant terlebih dahulu untuk supplier RM.',
                ], 422);
            }

            $request->merge(['id_plant' => $plantId]);
            $request->merge([
                'flag' => 'post_rmEntrySupplier',
                'mode' => $request->input('mode', 'ADD'),
                'rmNumber' => $request->input('rmNumber', $request->input('entry_no')),
                'idSupplier' => $request->input('idSupplier', $request->input('id_supplier')),
                'idMaterial' => $request->input('idMaterial', $request->input('id_material')),
                'batchSap' => $request->input('batchSap', $request->input('batch_sap')),
                'qty' => $request->input('qty'),
                'idHead' => $request->input('idHead'),
                'idTail' => $request->input('idTail'),
            ]);

            $result = LegacyRawMaterial::post_rmEntrySupplier($user, $request);
            $legacy = $this->legacyResponse($result, 'Supplier added successfully', 'Supplier');

            return response()->json($legacy, $legacy['success'] ? 200 : 422);
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
            $mode = $request->input('mode', 'ADD');
            $entryNo = $request->input('entry_no');
            $idHead = $request->input('id_balance_head');

            $request->merge([
                'mode' => $mode,
                'number' => $mode === 'UPDATE' ? $idHead : $entryNo
            ]);

            $data = LegacyRawMaterial::get_dtSupplierList($request);

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
    public function deleteSupplier(Request $request, $id)
    {
        try {
            $user = Auth::user()?->name ?? 'System';
            $result = LegacyRawMaterial::deleteSupplier($id, $user, $request);
            $legacy = $this->legacyResponse($result, 'Supplier deleted successfully', 'Supplier');

            return response()->json($legacy, $legacy['success'] ? 200 : 422);
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
            $mode = $request->input('mode', 'ADD');
            $entryNo = $request->input('entry_no');
            $idHead = $request->input('id_balance_head');

            $request->merge([
                'mode' => $mode,
                'number' => $mode === 'UPDATE' ? $idHead : $entryNo,
            ]);
            $rows = LegacyRawMaterial::get_totalQtySupplier($request);
            $total = $rows[0]->total ?? '0.000';

            return response()->json([
                'success' => true,
                'data' => ['total' => $total]
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
            $data['id_plant'] = BaseModel::resolvePlant($request);
            if ((string) $data['id_plant'] === '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilih plant terlebih dahulu untuk transfer RM.',
                ], 422);
            }

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
            $plantId = BaseModel::resolvePlant($request);
            if ((string) $plantId === '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilih plant terlebih dahulu untuk membuat nomor transfer.',
                ], 422);
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

    protected function legacyResponse($return, string $successMessage, string $feature): array
    {
        $code = '0';

        if (is_array($return)) {
            $first = $return[0] ?? null;
            $code = (string) (is_array($first) ? ($first['response'] ?? '0') : ($first->response ?? '0'));
        }

        $message = match ($code) {
            '1' => $successMessage,
            '2' => "{$feature} already exists",
            '3' => "{$feature} has been used",
            '4' => "{$feature} cannot be activated",
            '5' => "{$feature} Stock Not Enough",
            '6' => "{$feature} No RM Data",
            '99' => "{$feature} Period Locked",
            default => "Failed {$feature}",
        };

        return [
            'success' => $code === '1',
            'status' => $code === '1' ? 1 : 0,
            'response' => $code,
            'message' => $message,
            'data' => $return,
        ];
    }
}
