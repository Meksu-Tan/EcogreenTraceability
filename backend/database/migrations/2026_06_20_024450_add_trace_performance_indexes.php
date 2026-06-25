<?php declare(strict_types=1);

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

        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_trace_header_from_trace_no ON t_trace_header(from_trace_no)'
        );
        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_trace_header_to_trace_no ON t_trace_header(to_trace_no)'
        );
        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_trace_header_id_material ON t_trace_header(id_material)'
        );
        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_balance_detail_batch_sap ON t_balance_detail(batch_sap)'
        );
        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_trace_detail_batch_sap ON t_trace_detail(batch_sap)'
        );
        DB::connection($this->connection)->statement(
            'CREATE INDEX IF NOT EXISTS idx_shipment_header_trace_no ON t_shipment_header(trace_no)'
        );
    }

    public function down(): void
    {
        if (DB::connection($this->connection)->getDriverName() === 'sqlite') {
            return;
        }

        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_trace_header_from_trace_no');
        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_trace_header_to_trace_no');
        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_trace_header_id_material');
        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_balance_detail_batch_sap');
        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_trace_detail_batch_sap');
        DB::connection($this->connection)->statement('DROP INDEX IF EXISTS idx_shipment_header_trace_no');
    }
};
