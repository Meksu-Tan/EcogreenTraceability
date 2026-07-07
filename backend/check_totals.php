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
    ->whereIn('id_plant', [1, 1002])
    ->where('status', 1)
    ->get();

$totalQty = 0;
foreach ($rows as $row) {
    $totalQty += $row->qty;
}

echo "Total Qty in t_balance_header: $totalQty\n";

$rowsTraceHeader = DB::connection('eudr_ts')
    ->table('t_trace_header')
    ->where('id_material', 56)
    ->whereIn('id_balance_head', $rows->pluck('id_balance_head')->toArray())
    ->where('status', 1)
    ->get();

$totalIn = 0;
$totalOut = 0;
foreach ($rowsTraceHeader as $row) {
    $totalIn += $row->in_qty;
    $totalOut += $row->out_qty;
}

echo "Total In t_trace_header: $totalIn\n";
echo "Total Out t_trace_header: $totalOut\n";
echo 'Total Balance (In - Out): '.($totalIn - $totalOut)."\n";
