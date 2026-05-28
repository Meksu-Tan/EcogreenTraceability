<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tails = \Illuminate\Support\Facades\DB::connection('eudr_ts')->select("SELECT id_tank_tail, tf_number FROM m_tank_detail WHERE id_tank = 5 LIMIT 10");
echo json_encode($tails, JSON_PRETTY_PRINT);
