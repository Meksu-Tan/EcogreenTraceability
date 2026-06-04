<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ctrl = app(Modules\TsTsreport\Http\Controllers\TsReportController::class);
$req = Illuminate\Http\Request::create('/api/v1/transactions/ts-report/all', 'GET', ['entry_date' => '2026-06-03']);
$res = $ctrl->getAllSections($req);

echo json_encode($res->getData(), JSON_PRETTY_PRINT);
