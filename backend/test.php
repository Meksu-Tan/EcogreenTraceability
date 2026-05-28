<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $repo = $app->make(\Modules\TsRaw\Repositories\RmEntryRepository::class);
    $list = $repo->getRmList(0);
    echo "SUCCESS, count: " . count($list) . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
