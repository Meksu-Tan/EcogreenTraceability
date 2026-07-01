<?php
declare(strict_types=1);
namespace Modules\TsShipment\Support;

final class DispatchType
{
    public const CODES = ['FB', 'IS', 'VS'];

    public static function isDispatch(string $batchNo): bool
    {
        return in_array($batchNo, self::CODES, true);
    }
}
