<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trace
        Schema::table('t_trace_header', function (Blueprint $table) {
            $table->string('id_plant', 10)->nullable()->after('id_material');
        });

        Schema::table('t_trace_detail', function (Blueprint $table) {
            $table->string('id_plant', 10)->nullable()->after('id_material');
        });

        // Balance
        Schema::table('t_balance_header', function (Blueprint $table) {
            $table->string('id_plant', 10)->nullable()->after('id_tank');
        });

        Schema::table('t_balance_detail', function (Blueprint $table) {
            $table->string('id_plant', 10)->nullable()->after('id_material');
        });

        // Adjustment
        Schema::table('t_adjustment_header', function (Blueprint $table) {
            $table->string('id_plant', 10)->nullable()->after('id_tank');
        });

        Schema::table('t_adjustment_detail', function (Blueprint $table) {
            $table->string('id_plant', 10)->nullable()->after('id_material');
        });

        // Warehouse
        Schema::table('t_warehouse_header', function (Blueprint $table) {
            $table->string('id_plant', 10)->nullable()->after('id_section');
        });

        Schema::table('t_warehouse_detail', function (Blueprint $table) {
            $table->string('id_plant', 10)->nullable()->after('id_supplier');
        });

        // Shipment
        Schema::table('t_shipment_header', function (Blueprint $table) {
            $table->string('id_plant', 10)->nullable()->after('id_material_fg');
        });

        Schema::table('t_shipment_detail', function (Blueprint $table) {
            $table->string('id_plant', 10)->nullable()->after('id_supplier');
        });
    }

    public function down(): void
    {
        $tables = [
            't_shipment_detail',
            't_shipment_header',
            't_warehouse_detail',
            't_warehouse_header',
            't_adjustment_detail',
            't_adjustment_header',
            't_balance_detail',
            't_balance_header',
            't_trace_detail',
            't_trace_header',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'id_plant')) {
                    $table->dropColumn('id_plant');
                }
            });
        }
    }
};
