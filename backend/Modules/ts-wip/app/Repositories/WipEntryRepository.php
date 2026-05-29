<?php declare(strict_types=1);
namespace Modules\TsWip\Repositories;

use Modules\TsWip\Repositories\Contracts\WipEntryRepositoryInterface;
use Modules\TsWip\Repositories\Traits\WipEntryQueryTrait;
use Modules\TsWip\Repositories\Traits\WipEntryBatchTrait;
use Modules\TsWip\Repositories\Traits\WipEntryWriteTrait;

class WipEntryRepository implements WipEntryRepositoryInterface
{
    use WipEntryQueryTrait;
    use WipEntryBatchTrait;
    use WipEntryWriteTrait;

    protected $movType1 = '2';
    protected $movType2 = '3';
    protected $movType3 = '7';
    protected $movType4 = '8';
    protected $movType5 = '9';
}
