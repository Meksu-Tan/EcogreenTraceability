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
    // All transfer functionality moved to ts-raw module
    // This controller is now deprecated

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }
}

