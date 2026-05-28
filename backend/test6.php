<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $repo = $app->make(\Modules\TsBlending\Repositories\BlendingRepository::class);
    $feed = $repo->getFeedLog(0);
    echo "FEED SUCCESS, len: " . count($feed) . "\n";
    $rundown = $repo->getRundownLog(0);
    echo "RUNDOWN SUCCESS, len: " . count($rundown) . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
