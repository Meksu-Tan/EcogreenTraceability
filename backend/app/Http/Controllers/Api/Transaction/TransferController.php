<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Services\TransferService;
use App\Models\Tank;
use App\Models\TankDetail;
use App\Models\BalanceHeader;
use App\Models\BaseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TransferController extends Controller
{
    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * Get Storage Tank Log
     */
    public function storageLog(Request $request)
    {
        try {
            $plantId = BaseModel::resolvePlant($request);
            $data = $this->transferService->getStorageLog($plantId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Feed Tank Log
     */
    public function feedLog(Request $request)
    {
        try {
            $plantId = BaseModel::resolvePlant($request);
            $data = $this->transferService->getFeedLog($plantId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Perform Transfer
     */
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entry_date'       => 'required|date',
            'id_balance_head'  => 'required|integer',
            'id_dest_tank'     => 'required|integer',
            'id_dest_tank_tail'=> 'required|array',
            'qty'              => 'required|numeric|min:0.001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['id_plant'] = BaseModel::resolvePlant($request);
            if ((string) $data['id_plant'] === '0') {
                return response()->json(['success' => false, 'message' => 'Pilih plant terlebih dahulu untuk transfer.'], 422);
            }

            $user   = Auth::user()?->name ?? 'System';
            $result = $this->transferService->transfer($data, $user);

            return response()->json(['success' => true, 'message' => 'Transfer completed successfully', 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get source entries (Storage tanks with balance)
     */
    public function sourceEntries(Request $request)
    {
        try {
            $plantId = BaseModel::resolvePlant($request);
            $entries = BalanceHeader::active()
                ->rmEntry()
                ->where('qty', '>', 0)
                ->when((string) $plantId !== '0', fn ($query) => $query->where('id_plant', $plantId))
                ->with(['material', 'tank'])
                ->get()
                ->map(function ($item) {
                    return [
                        'id_balance_head' => $item->id_balance_head,
                        'trace_no'        => $item->trace_no,
                        'material'        => $item->material->description ?? 'Unknown',
                        'tank'            => $item->tank->description ?? 'Unknown',
                        'qty'             => $item->qty
                    ];
                });

            return response()->json(['success' => true, 'data' => $entries]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get destination tanks (Feed tanks)
     */
    public function destTanks(Request $request)
    {
        try {
            $plantId = BaseModel::resolvePlant($request);
            if ((string) $plantId === '0') {
                return response()->json(['success' => true, 'data' => []]);
            }

            $tanks = Tank::active()
                ->feed()
                ->where('id_plant', $plantId)
                ->get(['id_tank', 'description as tank']);

            return response()->json(['success' => true, 'data' => $tanks]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Deactivate Feed Tank Transfer
     * $id format: "idHead|idTraceHead" — ported from monorepo transfer_destroy
     */
    public function deactivate(Request $request, $id)
    {
        try {
            $user   = Auth::user()?->name ?? 'System';
            $result = $this->transferService->deactivateTransfer($id, $user);

            $response = $result['response'] ?? 0;
            $messages = [
                1  => 'Transfer deactivated successfully',
                3  => 'Transfer has been used and cannot be deactivated',
                98 => 'Entry data not found',
                99 => 'Period is locked',
            ];

            $message = $messages[$response] ?? 'Failed to deactivate transfer';
            $success = $response == 1;

            return response()->json(['success' => $success, 'message' => $message], $success ? 200 : 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Deactivate RM Entry (Storage Tank)
     * Ported from monorepo RawMaterial::deactivateRmEntry
     */
    public function deactivateRmEntry(Request $request, $id)
    {
        try {
            $user   = Auth::user()?->name ?? 'System';
            $result = $this->transferService->deactivateRmEntry($id, $user);

            $response = $result['response'] ?? 0;
            $messages = [
                1  => 'RM Entry deactivated successfully',
                3  => 'RM Entry has been used and cannot be deactivated',
                98 => 'Entry data not found',
                99 => 'Period is locked',
            ];

            $message = $messages[$response] ?? 'Failed to deactivate RM entry';
            $success = $response == 1;

            return response()->json(['success' => $success, 'message' => $message], $success ? 200 : 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update RM Entry (Storage Tank)
     * Ported from monorepo RawMaterial::post_rmEntry mode=UPDATE
     */
    public function updateRmEntry(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'entry_date'      => 'required|date',
            'id_material'     => 'required|integer',
            'id_tank'         => 'required|integer',
            'id_tank_tail'    => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['id_balance_head'] = $id;

            $user   = Auth::user()?->name ?? 'System';
            $result = $this->transferService->updateRmEntry($data, $user);

            $response = $result['response'] ?? 0;
            $success  = $response == 1;

            return response()->json([
                'success' => $success,
                'message' => $result['message'] ?? 'Update failed'
            ], $success ? 200 : 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
