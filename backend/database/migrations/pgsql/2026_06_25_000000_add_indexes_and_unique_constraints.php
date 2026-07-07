<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        // Unique constraint on active trace numbers (prevents duplicate to_trace_no)
        Schema::connection($this->connection)->table('t_trace_header', function (Blueprint $table) {
            $table->unique('to_trace_no')->where('status', 1);
        });

        // Unique constraint on balance header trace_no
        Schema::connection($this->connection)->table('t_balance_header', function (Blueprint $table) {
            $table->unique('trace_no');
        });

        // Unique constraint on warehouse header trace_no
        Schema::connection($this->connection)->table('t_warehouse_header', function (Blueprint $table) {
            $table->unique('trace_no');
        });

        // Unique constraint on shipment header trace_no
        Schema::connection($this->connection)->table('t_shipment_header', function (Blueprint $table) {
            $table->unique('trace_no');
        });

        // Performance indexes on header tables
        $headerTables = ['t_balance_header', 't_trace_header', 't_warehouse_header', 't_shipment_header', 't_adjustment_header'];
        foreach ($headerTables as $table) {
            Schema::connection($this->connection)->table($table, function (Blueprint $table) {
                $table->index(['status', 'entry_date']);
                $table->index(['id_plant', 'status']);
            });
        }

        // Additional performance indexes
        Schema::connection($this->connection)->table('t_balance_header', function (Blueprint $table) {
            $table->index(['trace_no', 'status']);
            $table->index(['id_material', 'status']);
        });

        Schema::connection($this->connection)->table('t_trace_header', function (Blueprint $table) {
            $table->index(['to_trace_no', 'status']);
            $table->index(['from_trace_no', 'status']);
            $table->index(['id_balance_head', 'status']);
        });

        Schema::connection($this->connection)->table('t_balance_detail', function (Blueprint $table) {
            $table->index(['id_balance_head', 'status']);
        });

        Schema::connection($this->connection)->table('t_warehouse_detail', function (Blueprint $table) {
            $table->index(['id_whx_head', 'status']);
        });
    }

    public function down(): void
    {
        $tableSchemas = [
            't_trace_header' => ['to_trace_no', 'status_entry_date', 'id_plant_status', 'to_trace_no_status', 'from_trace_no_status', 'id_balance_head_status'],
            't_balance_header' => ['trace_no', 'status_entry_date', 'id_plant_status', 'trace_no_status', 'id_material_status'],
            't_warehouse_header' => ['trace_no', 'status_entry_date', 'id_plant_status'],
            't_shipment_header' => ['trace_no', 'status_entry_date', 'id_plant_status'],
            't_adjustment_header' => ['status_entry_date', 'id_plant_status'],
            't_balance_detail' => ['id_balance_head_status'],
            't_warehouse_detail' => ['id_whx_head_status'],
        ];

        foreach ($tableSchemas as $table => $indexes) {
            Schema::connection($this->connection)->table($table, function (Blueprint $table) use ($indexes) {
                foreach ($indexes as $index) {
                    $table->dropIndex($index);
                }
            });
        }
    }
};
