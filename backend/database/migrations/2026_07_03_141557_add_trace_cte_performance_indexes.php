<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        if (DB::connection($this->connection)->getDriverName() === 'sqlite') {
            return;
        }

        // CTE recursive join: ForwardBOM.child_trace_no = t.from_trace_no AND t.status = 1
        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_trace_header_from_status ON t_trace_header(from_trace_no, status)'
        );

        // CTE anchor: b.to_trace_no = ? AND b.id_material = ? AND b.status = 1
        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_trace_header_to_material_status ON t_trace_header(to_trace_no, id_material, status)'
        );

        // Forward/backward list: bh.trace_no IN (...) AND bh.status = 1
        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_balance_header_trace_status ON t_balance_header(trace_no, status)'
        );

        // Backward search: sh.id_material_fg = ? AND sh.status = 1
        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_shipment_material_status ON t_shipment_header(id_material_fg, status)'
        );

        // Forward list / plant filter: bh.id_plant = ? AND bh.status = 1
        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_balance_header_plant_status ON t_balance_header(id_plant, status)'
        );
    }

    public function down(): void
    {
        if (DB::connection($this->connection)->getDriverName() === 'sqlite') {
            return;
        }

        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_trace_header_from_status');
        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_trace_header_to_material_status');
        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_balance_header_trace_status');
        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_shipment_material_status');
        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_balance_header_plant_status');
    }
};
