<?php

declare(strict_types=1);

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
            $table->index(['id_material', 'status', 'id_sloc'], 'idx_balance_header_material_status_sloc');
            $table->index('trace_no', 'idx_balance_header_trace_no');
        });

        Schema::table('t_trace_detail', function (Blueprint $table) {
            $table->index(['id_trace_head', 'status'], 'idx_trace_detail_head_status');
        });

        Schema::table('t_trace_header', function (Blueprint $table) {
            $table->index(['id_material', 'status', 'entry_date'], 'idx_trace_header_material_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_balance_header', function (Blueprint $table) {
            $table->dropIndex('idx_balance_header_material_status_sloc');
            $table->dropIndex('idx_balance_header_trace_no');
        });

        Schema::table('t_trace_detail', function (Blueprint $table) {
            $table->dropIndex('idx_trace_detail_head_status');
        });

        Schema::table('t_trace_header', function (Blueprint $table) {
            $table->dropIndex('idx_trace_header_material_status_date');
        });
    }
};
