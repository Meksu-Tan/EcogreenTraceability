<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\TsTransfer\Repositories\TransferRepository;

$repo = new TransferRepository();
$logs = $repo->getStorageLog(1002); // for plant 1002

foreach ($logs as $log) {
    if ($log->id_balance_head == 18946) {
        echo "Trace Head ID: " . $log->id_trace_head . "\n";
        echo "Trace No: " . ($log->from_trace_no ?: $log->to_trace_no) . "\n";
        echo "In Qty (formatted): " . $log->in_qty . "\n";
        echo "Out Qty (formatted): " . $log->out_qty . "\n";
        echo "Balance Qty: " . $log->balance_qty . "\n";
        echo "Balance Init Qty: " . $log->balance_init_qty . "\n";
        echo "--------------------------------------------------\n";
    }
}
