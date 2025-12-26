<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            't_trace_header',
            't_trace_detail',
            't_balance_header',
            't_balance_detail',
            't_adjustment_header',
            't_adjustment_detail',
            't_warehouse_header',
            't_warehouse_detail',
        ];
    
        foreach ($tables as $tableName) {
    
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
    
                $afterColumn = null;
    
                if (Schema::hasColumn($tableName, 'id_tank')) {
                    $afterColumn = 'id_tank';
                } elseif (Schema::hasColumn($tableName, 'id_sloc')) {
                    $afterColumn = 'id_sloc';
                } elseif (Schema::hasColumn($tableName, 'id_section')) {
                    $afterColumn = 'id_section';
                } elseif (Schema::hasColumn($tableName, 'id_supplier')) {
                    $afterColumn = 'id_supplier';
                }
    
                // Add JSON column only if not exists
                if (!Schema::hasColumn($tableName, 'id_tank_tail')) {
    
                    if ($afterColumn) {
                        $table->json('id_tank_tail')->nullable()->after($afterColumn);
                    } else {
                        // fallback if no matching reference column found
                        $table->json('id_tank_tail')->nullable();
                    }
                }
    
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            't_trace_header',
            't_trace_detail',
            't_balance_header',
            't_balance_detail',
            't_adjustment_header',
            't_adjustment_detail',
            't_warehouse_header',
            't_warehouse_detail',
        ];

        foreach ($tables as $tableName) {

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {

                // Drop JSON column
                if (Schema::hasColumn($tableName, 'id_tank_tail')) {
                    $table->dropColumn('id_tank_tail');
                }
            });
        }
    }
};
