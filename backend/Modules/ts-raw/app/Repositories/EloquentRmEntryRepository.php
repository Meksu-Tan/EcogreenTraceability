<?php

declare(strict_types=1);

namespace Modules\TsRaw\Repositories;

use Modules\TsRaw\Repositories\Traits\RmEntryModelAccessTrait;
use Modules\TsRaw\Repositories\Traits\RmEntryQueryTrait;
use Modules\TsRaw\Repositories\Traits\RmEntrySupplierTrait;
use Modules\TsRaw\Repositories\Traits\RmEntryTransactionTrait;
use Modules\TsRaw\Repositories\Traits\RmEntryTransferTrait;

class EloquentRmEntryRepository implements RmEntryRepositoryInterface
{
    use RmEntryModelAccessTrait;
    use RmEntryQueryTrait;
    use RmEntrySupplierTrait;
    use RmEntryTransactionTrait;
    use RmEntryTransferTrait;

    protected $movSeq = '000';

    protected $movType1 = '1';

    protected $movType2 = '9';

    protected $typeMaterial = 'RM';

    protected $idSlocSrc = 'T000';

    protected $movSeqTransfer = '000';

    protected $typeTransfer = '7';
}
