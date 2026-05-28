<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $repo = $app->make(\Modules\TsRaw\Repositories\RmEntryRepository::class);
    $list = $repo->getRmList(0);
    $json = json_encode($list);
    if ($json === false) {
        echo 'JSON ERROR: ' . json_last_error_msg() . "\n";
    } else {
        echo 'JSON SUCCESS, len: ' . strlen($json) . "\n";
    }
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
