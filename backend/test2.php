<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $list = Illuminate\Support\Facades\DB::connection('eudr_ts')->select("SELECT JSON_CONTAINS(IF(JSON_VALID(id_sloc), id_sloc, '[]'), '\"1\"') FROM t_balance_header WHERE id_sloc IS NOT NULL LIMIT 5");
    echo "SUCCESS\n";
    $list2 = Illuminate\Support\Facades\DB::connection('eudr_ts')->select("SELECT JSON_CONTAINS(id_sloc, '\"1\"') FROM t_balance_header WHERE id_sloc IS NOT NULL LIMIT 5");
    echo "SUCCESS 2\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
