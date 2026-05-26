<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Deleting data created by Administrator...\n";

try {
    // 1. Delete t_trace_detail
    $deleted1 = DB::connection('eudr_ts')->statement(
        'DELETE td FROM t_trace_detail td
         INNER JOIN t_trace_header th ON td.id_trace_head = th.id_trace_head
         WHERE th.created_by = "Administrator"'
    );
    echo "Deleted t_trace_detail rows\n";

    // 2. Delete t_trace_header
    $deleted2 = DB::connection('eudr_ts')->statement(
        'DELETE FROM t_trace_header WHERE created_by = "Administrator"'
    );
    echo "Deleted t_trace_header rows\n";

    // 3. Delete t_balance_detail
    $deleted3 = DB::connection('eudr_ts')->statement(
        'DELETE bd FROM t_balance_detail bd
         INNER JOIN t_balance_header bh ON bd.id_balance_head = bh.id_balance_head
         WHERE bh.created_by = "Administrator"'
    );
    echo "Deleted t_balance_detail rows\n";

    // 4. Delete t_balance_header
    $deleted4 = DB::connection('eudr_ts')->statement(
        'DELETE FROM t_balance_header WHERE created_by = "Administrator"'
    );
    echo "Deleted t_balance_header rows\n";

    // 5. Delete t_balance_temporary
    $deleted5 = DB::connection('eudr_ts')->statement(
        'DELETE FROM t_balance_temporary WHERE created_by = "Administrator"'
    );
    echo "Deleted t_balance_temporary rows\n";

    echo "\nAll data created by Administrator has been deleted successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
