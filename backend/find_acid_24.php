<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$materials = DB::table('m_material')->where('description', 'ILIKE', '%acid 24%')->get();
foreach ($materials as $m) {
    echo $m->id_material.' | '.$m->code.' | '.$m->description."\n";
}

$plants = DB::table('m_plant')->where('description', 'ILIKE', '%eob%')->get();
foreach ($plants as $p) {
    echo $p->id_plant.' | '.$p->code.' | '.$p->description."\n";
}
