<?php declare(strict_types=1);

namespace Modules\TsRaw\Repositories;

use Modules\TsRaw\Repositories\Contracts\RmEntryRepositoryInterface;
use Modules\TsRaw\Repositories\Traits\RmEntryQueryTrait;
use Modules\TsRaw\Repositories\Traits\RmEntrySupplierTrait;
use Modules\TsRaw\Repositories\Traits\RmEntryTransactionTrait;
use Modules\TsRaw\Repositories\Traits\RmEntryTransferTrait;
use Modules\TsRaw\Repositories\Traits\RmEntryModelAccessTrait;

class RmEntryRepository implements RmEntryRepositoryInterface
{
    use RmEntryQueryTrait;
    use RmEntrySupplierTrait;
    use RmEntryTransactionTrait;
    use RmEntryTransferTrait;
    use RmEntryModelAccessTrait;

    protected $movSeq = '000';
    protected $movType1 = '1';
    protected $movType2 = '9';
    protected $typeMaterial = 'RM';
    protected $idTankSrc = "T000";
    protected $movSeqTransfer = '000';
    protected $typeTransfer = '7';
}
