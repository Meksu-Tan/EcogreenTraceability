<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$rows = DB::connection('eudr_ts')
    ->table('t_balance_header')
    ->where('id_material', 56)
    ->where('id_plant', 1002)
    ->where('status', 1)
    ->whereIn('id_balance_head', function ($q) {
        $q->select('id_balance_head')
            ->from('t_trace_header')
            ->where('id_material', 56)
            ->where('entry_date', '>=', '2026-06-01');
    })
    ->get(['id_balance_head', 'trace_no', 'qty', 'id_sloc']);

print_r($rows->toArray());
