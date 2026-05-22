<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$sql = $argv[1];
$res = DB::connection('eudr_ts')->select($sql);
print_r($res);
