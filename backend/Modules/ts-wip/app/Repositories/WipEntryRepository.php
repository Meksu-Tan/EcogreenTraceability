<?php declare(strict_types=1);
namespace Modules\TsWip\Repositories;

use Modules\TsWip\Repositories\Contracts\WipEntryRepositoryInterface;
use Modules\TsWip\Repositories\Traits\WipEntryQueryTrait;
use Modules\TsWip\Repositories\Traits\WipEntryBatchTrait;
use Modules\TsWip\Repositories\Traits\WipEntryWriteTrait;

/**
 * @todo Technical Debt: This class is 21 lines in this file but delegates to 3 traits (WipEntryQueryTrait, WipEntryBatchTrait, WipEntryWriteTrait).
 * The effective class size across all traits likely exceeds 200 lines. Requires audit of trait line counts.
 * - Split into: WipEntryQueryRepository, WipEntryBatchProcessor, WipEntryWriteRepository
 * Current trait-based decomposition is a step in the right direction — verify each trait stays under 200 lines.
 */
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
