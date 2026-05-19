<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$results = DB::connection('eudr_ts')->select(
    "SELECT 
        JSON_CONTAINS('[56,57]', '56') AS test_str,
        JSON_CONTAINS('[56,57]', JSON_QUOTE(CAST(56 AS CHAR))) AS test_quote,
        JSON_CONTAINS('[56,57]', JSON_QUOTE('56')) AS test_explicit_quote,
        FIND_IN_SET(56, REPLACE(REPLACE(REPLACE('[56,57]','[',''),']',''),'\"','')) AS test_find_in_set"
);
print_r($results[0]);
