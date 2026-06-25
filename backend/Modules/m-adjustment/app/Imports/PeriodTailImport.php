<?php
declare(strict_types=1);
namespace Modules\Adjustment\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class PeriodTailImport implements ToArray
{
    public function array(array $rows): array
    {
        return $rows;
    }
}
