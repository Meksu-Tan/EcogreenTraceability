<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$rows = DB::connection('eudr_ts')
    ->table('t_trace_header')
    ->where('id_material', 56)
    ->where('entry_date', '>=', '2026-06-01')
    ->get(['entry_date', 'id_sloc']);

print_r($rows->toArray());
