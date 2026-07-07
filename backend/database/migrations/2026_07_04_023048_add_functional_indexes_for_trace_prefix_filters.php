<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Blending/transfer/package/shipment list queries filter on
     * SUBSTRING(trace_no,1,1) and CAST(SUBSTRING(trace_no,1,1) AS INTEGER) —
     * a function wraps the column, so plain btree indexes on trace_no/to_trace_no/
     * from_trace_no are never used for these predicates. Functional indexes matching
     * the exact expression let Postgres use them without any SQL rewrite.
     */
    public function up(): void
    {
        // text-compare form: SUBSTRING(col,1,1) = '8' / SUBSTRING(col FROM 1 FOR 1) = '8'
        DB::statement('CREATE INDEX IF NOT EXISTS idx_trace_header_to_trace_prefix1_txt ON t_trace_header (substr(to_trace_no, 1, 1))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_trace_header_from_trace_prefix1_txt ON t_trace_header (substr(from_trace_no, 1, 1))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_balance_header_trace_prefix1_txt ON t_balance_header (substr(trace_no, 1, 1))');

        // integer-cast form: CAST(SUBSTRING(col,1,1) AS INTEGER) = 7
        DB::statement('CREATE INDEX IF NOT EXISTS idx_trace_header_to_trace_prefix1_int ON t_trace_header (CAST(substr(to_trace_no, 1, 1) AS INTEGER))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_trace_header_from_trace_prefix1_int ON t_trace_header (CAST(substr(from_trace_no, 1, 1) AS INTEGER))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_balance_header_trace_prefix1_int ON t_balance_header (CAST(substr(trace_no, 1, 1) AS INTEGER))');

        // warehouse-code slice: TraceHelper::warehouseCondition() → SUBSTRING(CAST(col AS TEXT),8,3)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_trace_header_to_trace_wh ON t_trace_header (substr(CAST(to_trace_no AS TEXT), 8, 3))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_trace_header_from_trace_wh ON t_trace_header (substr(CAST(from_trace_no AS TEXT), 8, 3))');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_balance_header_trace_wh ON t_balance_header (substr(CAST(trace_no AS TEXT), 8, 3))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_trace_header_to_trace_prefix1_txt');
        DB::statement('DROP INDEX IF EXISTS idx_trace_header_from_trace_prefix1_txt');
        DB::statement('DROP INDEX IF EXISTS idx_balance_header_trace_prefix1_txt');
        DB::statement('DROP INDEX IF EXISTS idx_trace_header_to_trace_prefix1_int');
        DB::statement('DROP INDEX IF EXISTS idx_trace_header_from_trace_prefix1_int');
        DB::statement('DROP INDEX IF EXISTS idx_balance_header_trace_prefix1_int');
        DB::statement('DROP INDEX IF EXISTS idx_trace_header_to_trace_wh');
        DB::statement('DROP INDEX IF EXISTS idx_trace_header_from_trace_wh');
        DB::statement('DROP INDEX IF EXISTS idx_balance_header_trace_wh');
    }
};
