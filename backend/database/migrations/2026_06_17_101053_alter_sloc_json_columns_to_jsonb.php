<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    private array $targets = [
        't_balance_header' => ['id_sloc', 'id_sloc_tail'],
        't_balance_detail' => ['id_sloc', 'id_sloc_tail'],
        't_trace_header' => ['id_sloc', 'id_sloc_tail'],
        't_trace_detail' => ['id_sloc', 'id_sloc_tail'],
        't_warehouse_header' => ['id_sloc'],
        't_warehouse_detail' => ['id_sloc'],
    ];

    public function up(): void
    {
        if (DB::connection($this->connection)->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->targets as $table => $columns) {
            if (! Schema::connection($this->connection)->hasTable($table)) {
                continue;
            }
            foreach ($columns as $col) {
                if (Schema::connection($this->connection)->hasColumn($table, $col)) {
                    DB::connection($this->connection)->statement(
                        "ALTER TABLE \"$table\" ALTER COLUMN \"$col\" TYPE JSONB USING CASE WHEN \"$col\" IS NULL THEN NULL ELSE \"$col\"::JSONB END"
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

        foreach ($this->targets as $table => $columns) {
            if (! Schema::connection($this->connection)->hasTable($table)) {
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
};
