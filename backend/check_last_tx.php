<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$rows = DB::connection('eudr_ts')
    ->table('t_trace_header')
    ->join('t_balance_header', 't_trace_header.id_balance_head', '=', 't_balance_header.id_balance_head')
    ->where('t_trace_header.id_material', 56)
    ->where('t_balance_header.id_plant', 1002)
    ->where('t_trace_header.status', 1)
    ->orderBy('t_trace_header.entry_date', 'DESC')
    ->limit(5)
    ->get(['t_trace_header.entry_date', 't_trace_header.in_qty', 't_trace_header.out_qty']);

print_r($rows->toArray());
