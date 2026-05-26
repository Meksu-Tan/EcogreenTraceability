<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$idHead = 18946;
$bh = DB::connection('eudr_ts')->table('t_balance_header')->where('id_balance_head', $idHead)->first();
echo "t_balance_header for $idHead:\n";
print_r($bh);

$bd = DB::connection('eudr_ts')->table('t_balance_detail')->where('id_balance_head', $idHead)->get();
echo "t_balance_detail for $idHead:\n";
print_r($bd->toArray());
