<?php

namespace Modules\TsTransfer\Services;

use Modules\TsTransfer\Repositories\Contracts\TransferRepositoryInterface;
use Modules\TsRaw\Models\BalanceHeader;
use Modules\TsRaw\Models\TraceHeader;
use Modules\Plant\Models\Plant;
use Exception;

class TransferService
{
    // All transfer functionality moved to ts-raw module
    // This service is now deprecated

    public function __construct(
        protected TransferRepositoryInterface $transferRepo
    ) {}
}

