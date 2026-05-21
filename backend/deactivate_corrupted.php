<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \Illuminate\Support\Facades\DB::connection('eudr_ts')->table('t_balance_header')
    ->where('status', 1)
    ->where('qty', '>', 0)
    ->whereNotExists(function(\Illuminate\Database\Query\Builder $query) {
        $query->select(\Illuminate\Support\Facades\DB::raw(1))
              ->from('t_balance_detail')
              ->whereRaw('t_balance_detail.id_balance_head = t_balance_header.id_balance_head')
              ->where('t_balance_detail.status', 1);
    })
    ->update(['status' => 0]);
echo "Deactivated " . $count . " records.\n";
