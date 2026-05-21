<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$repo = app()->make(\Modules\Transaction\Repositories\RmEntryRepository::class);
$res = $repo->getRmList(0);
echo "Total count: " . count($res) . "\n";
for ($i=0; $i<min(5, count($res)); $i++) {
    echo "ID: " . $res[$i]->id_balance_head . " Trace: " . $res[$i]->trace_no . "\n";
}
