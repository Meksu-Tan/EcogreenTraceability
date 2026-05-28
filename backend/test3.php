<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $list2 = Illuminate\Support\Facades\DB::connection('eudr_ts')->select("SELECT id_balance_head, id_sloc FROM t_balance_header WHERE id_sloc IS NOT NULL AND JSON_VALID(id_sloc) = 0");
    echo 'INVALID JSON COUNT: ' . count($list2) . "\n";
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
