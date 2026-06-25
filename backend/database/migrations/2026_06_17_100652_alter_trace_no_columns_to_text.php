<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        if (DB::connection($this->connection)->getDriverName() !== 'pgsql') {
            return;
        }

        $alters = [
            't_balance_header'   => ['trace_no'],
            't_balance_temporary'=> ['entry_no'],
            't_trace_header'     => ['from_trace_no', 'to_trace_no'],
            't_shipment_header'  => ['trace_no', 'from_trace_no'],
            't_warehouse_header' => ['trace_no', 'from_trace_no'],
        ];

        foreach ($alters as $table => $columns) {
            if (!Schema::connection($this->connection)->hasTable($table)) {
                continue;
            }
            foreach ($columns as $col) {
                if (Schema::connection($this->connection)->hasColumn($table, $col)) {
                    DB::connection($this->connection)->statement(
                        "ALTER TABLE \"$table\" ALTER COLUMN \"$col\" TYPE TEXT USING \"$col\"::TEXT"
                    );
                }
            }
        }
    }

    public function down(): void
    {
        if (DB::connection($this->connection)->getDriverName() !== 'pgsql') {
            return;
        }

        $alters = [
            't_balance_header'   => ['trace_no'],
            't_balance_temporary'=> ['entry_no'],
            't_trace_header'     => ['from_trace_no', 'to_trace_no'],
            't_shipment_header'  => ['trace_no', 'from_trace_no'],
            't_warehouse_header' => ['trace_no', 'from_trace_no'],
        ];

        foreach ($alters as $table => $columns) {
            if (!Schema::connection($this->connection)->hasTable($table)) {
                continue;
            }
            foreach ($columns as $col) {
                if (Schema::connection($this->connection)->hasColumn($table, $col)) {
                    DB::connection($this->connection)->statement(
                        "ALTER TABLE \"$table\" ALTER COLUMN \"$col\" TYPE BIGINT USING \"$col\"::BIGINT"
                    );
                }
            }
        }
    }
};
