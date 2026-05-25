<?php

namespace Modules\TsTransfer\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\TsTransfer\Services\TransferService;
use Modules\TsTransfer\Http\Requests\StoreTransferRequest;
use Modules\Tank\Models\Tank;
use Modules\Tank\Models\TankDetail;
use Modules\TsRaw\Models\BalanceHeader;
use Modules\Plant\Models\Plant;
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

    public function storageLog(Request $request)
    {
        try {
            $plantId = $request->input('id_plant');
            if ($plantId === '' || $plantId === null) {
                $plantId = Auth::user()?->id_plant ?? 0;
            }
            $data = $this->transferService->getStorageLog($plantId);
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
            $data = $this->transferService->getFeedLog($plantId);
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
            $data = $this->transferService->debugFeedLog($plantId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function transfer(StoreTransferRequest $request)
    {
        try {
            $data = $request->validated();
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

