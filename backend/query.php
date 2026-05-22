<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$res = DB::connection('eudr_ts')->select("SELECT id_balance_head, id_plant FROM t_balance_header WHERE id_plant != 0 LIMIT 5");
print_r($res);
