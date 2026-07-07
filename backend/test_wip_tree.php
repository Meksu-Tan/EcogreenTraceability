<?php

use Illuminate\Contracts\Console\Kernel;
use Modules\TsWip\Services\Contracts\WipTreeServiceInterface;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$service = app()->make(WipTreeServiceInterface::class);
$tree = $service->getTree(null);

file_put_contents('tree_output.json', json_encode($tree, JSON_PRETTY_PRINT));
echo 'Done';
