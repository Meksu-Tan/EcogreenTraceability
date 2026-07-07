<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$m = DB::table('m_material')->where('id_material', 56)->first();
print_r($m);

$m2 = DB::table('m_material')->where('code', $m->code)->get();
echo "Materials with code {$m->code}:\n";
foreach ($m2 as $mm) {
    echo "ID: {$mm->id_material} | Code: {$mm->code} | Desc: {$mm->description}\n";
}
