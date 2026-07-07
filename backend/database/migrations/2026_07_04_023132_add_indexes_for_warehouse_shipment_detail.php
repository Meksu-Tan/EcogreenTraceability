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
        Schema::table('t_warehouse_header', function (Blueprint $table) {
            $table->index('trace_no', 'idx_warehouse_header_trace_no');
            $table->index('from_trace_no', 'idx_warehouse_header_from_trace_no');
            $table->index(['status', 'id_plant'], 'idx_warehouse_header_status_plant');
            $table->index('id_material_feed', 'idx_warehouse_header_material_feed');
            $table->index('id_material_fg', 'idx_warehouse_header_material_fg');
        });

        Schema::table('t_warehouse_detail', function (Blueprint $table) {
            $table->index(['id_whx_head', 'status'], 'idx_warehouse_detail_head_status');
        });

        Schema::table('t_shipment_detail', function (Blueprint $table) {
            $table->index(['id_ship_head', 'status'], 'idx_shipment_detail_head_status');
        });

        Schema::table('t_material_document', function (Blueprint $table) {
            $table->index(['id_trace_head', 'status'], 'idx_material_document_trace_head_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_warehouse_header', function (Blueprint $table) {
            $table->dropIndex('idx_warehouse_header_trace_no');
            $table->dropIndex('idx_warehouse_header_from_trace_no');
            $table->dropIndex('idx_warehouse_header_status_plant');
            $table->dropIndex('idx_warehouse_header_material_feed');
            $table->dropIndex('idx_warehouse_header_material_fg');
        });

        Schema::table('t_warehouse_detail', function (Blueprint $table) {
            $table->dropIndex('idx_warehouse_detail_head_status');
        });

        Schema::table('t_shipment_detail', function (Blueprint $table) {
            $table->dropIndex('idx_shipment_detail_head_status');
        });

        Schema::table('t_material_document', function (Blueprint $table) {
            $table->dropIndex('idx_material_document_trace_head_status');
        });
    }
};
