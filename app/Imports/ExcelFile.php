<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToArray;

class ExcelFile implements ToArray
{
    /**
     * Mengubah semua baris data Excel menjadi array
     *
     * @param array $array
     * @return array
     */
    public function array(array $array)
    {
        return $array;
    }
}
