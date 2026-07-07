<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$rows = DB::connection('eudr_ts')->table('t_balance_header')->where('id_material', 56)->where('status', 1)->get();
print_r($rows->toArray());

$qtyEob = DB::connection('eudr_ts')
    ->table('t_balance_header')
    ->where('id_material', 56)
    ->where('id_plant', 'EOB')
    ->where('status', 1)
    ->sum('qty');

echo "SUM for EOB: $qtyEob\n";
