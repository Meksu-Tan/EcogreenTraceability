<?php

namespace Modules\TsTransfer\Repositories;

use Modules\TsTransfer\Repositories\Contracts\TransferRepositoryInterface;
use Modules\TsRaw\Models\BalanceHeader;
use Modules\TsRaw\Models\TraceHeader;
use Modules\Plant\Models\Plant;
use Modules\Tank\Models\TankDetail;
use Illuminate\Support\Facades\DB;

class TransferRepository implements TransferRepositoryInterface
{
    // All transfer functionality moved to ts-raw module
    // This repository is now deprecated
}

