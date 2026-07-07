<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$rows = DB::table('m_sloc')->whereIn('id_sloc', [94, 75, 156, 61, 1, 10, 44, 82, 191])->get();
print_r($rows->toArray());
