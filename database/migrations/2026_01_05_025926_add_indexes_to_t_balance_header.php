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
        Schema::table('t_balance_header', function (Blueprint $table) {
            // Used in WHERE + GROUP BY
            $table->index('trace_no');
            
            // Used in WHERE
            $table->index('id_plant');
            
            // Composite index (VERY IMPORTANT)
            $table->index(
                ['status', 'id_plant', 'trace_no'],
                'idx_balance_header_filter'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_balance_header', function (Blueprint $table) {
            $table->dropIndex('idx_balance_header_filter');
            $table->dropIndex(['trace_no']);
            $table->dropIndex(['id_plant']);
        });
    }
};
