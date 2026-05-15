<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Services\TransferService;
use App\Models\Tank;
use App\Models\TankDetail;
use App\Models\BalanceHeader;
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
            $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
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
            $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
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
            'entry_date' => 'required|date',
            'id_balance_head' => 'required|integer',
            'id_dest_tank' => 'required|integer',
            'id_dest_tank_tail' => 'required|array',
            'qty' => 'required|numeric|min:0.001',
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
            $data['id_plant'] = $request->input('id_plant', Auth::user()->id_plant ?? 0);
            $user = Auth::user()->name;

            $result = $this->transferService->transfer($data, $user);

            return response()->json([
                'success' => true,
                'message' => 'Transfer completed successfully',
                'data' => $result
            ]);
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

    /**
     * Get destination tanks (Feed tanks)
     */
    public function destTanks(Request $request)
    {
        try {
            $plantId = $request->input('id_plant', Auth::user()->id_plant ?? 0);
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
     * Deactivate Transfer
     */
    public function deactivate(Request $request, $id)
    {
        try {
            $user = Auth::user()->name;
            $result = $this->transferService->deactivateTransfer($id, $user);

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
