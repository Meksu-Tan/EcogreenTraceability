<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add composite indexes optimized for the FIFO feed/rundown query patterns.
 *
 * Background
 * ----------
 * Feed::generalFeed() and related code query t_balance_header with:
 *   WHERE status = 1 AND qty > 0.0001 AND id_material = ? AND id_tank = ?
 *   ORDER BY id_balance_head ASC
 *
 * The existing idx_balance_header_filter(status, id_plant, trace_no) does NOT
 * cover this pattern because id_material and id_tank are absent from it.
 * MySQL therefore falls back to a single-column index and filters the rest
 * in memory — increasingly expensive as rows grow.
 *
 * Similarly, t_balance_detail is accessed per head:
 *   WHERE id_balance_head = ? AND status = 1 AND qty > 0.0001
 *   ORDER BY id_balance_tail ASC
 *
 * And t_balance_temporary (adjustment staging) has NO operational indexes at all.
 *
 * Safe to run on live database — adding indexes does not lock tables on
 * MariaDB 10.6+ (uses online DDL / ALGORITHM=INPLACE by default for InnoDB).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── t_balance_header ─────────────────────────────────────────────────
        Schema::table('t_balance_header', function (Blueprint $table) {

            // Core FIFO lookup: covers the WHERE + helps ORDER BY via PK.
            // Column order: status first (equality, low cardinality → best
            // prefix), then id_material (equality, high cardinality),
            // then id_tank (equality), then qty (range filter > 0.0001),
            // then id_balance_head (ORDER BY — avoids filesort).
            $table->index(
                ['status', 'id_material', 'id_tank', 'qty', 'id_balance_head'],
                'idx_bh_fifo_core'
            );

            // Secondary: per-plant + per-material lookup used in adjustment
            // and stock-on-hand queries.
            $table->index(
                ['status', 'id_plant', 'id_material', 'id_tank'],
                'idx_bh_plant_material_tank'
            );
        });

        // ── t_balance_detail ─────────────────────────────────────────────────
        Schema::table('t_balance_detail', function (Blueprint $table) {

            // FIFO tail deduction: every head fetch is followed by this query.
            // Covering id_balance_head, status, qty avoids a second lookup.
            $table->index(
                ['id_balance_head', 'status', 'qty', 'id_balance_tail'],
                'idx_bd_head_active'
            );
        });

        // ── t_balance_temporary ───────────────────────────────────────────────
        // This table has only a PRIMARY KEY. Adjustment queries filter by
        // entry_no + status on every modal open.
        Schema::table('t_balance_temporary', function (Blueprint $table) {
            $table->index(
                ['entry_no', 'status'],
                'idx_bt_entry_status'
            );
            $table->index(
                ['id_material', 'id_plant', 'status'],
                'idx_bt_material_plant'
            );
        });

        // ── t_shipment_header ─────────────────────────────────────────────────
        // Backward trace + SO lookup — currently zero indexes besides status.
        Schema::table('t_shipment_header', function (Blueprint $table) {
            $table->index('trace_no',      'idx_sh_trace_no');
            $table->index('from_trace_no', 'idx_sh_from_trace_no');
            $table->index('so_no',         'idx_sh_so_no');
        });

        // ── t_adjustment_header ────────────────────────────────────────────────
        // adjust_no is queried with SUBSTRING(adjust_no,1,9) — adding a plain
        // index does not help the SUBSTRING pattern directly, but it does
        // accelerate exact-match lookups (e.g. WHERE adjust_no = ?).
        Schema::table('t_adjustment_header', function (Blueprint $table) {
            $table->index('adjust_no', 'idx_ah_adjust_no');
        });

        // ── t_reset_quantifier ────────────────────────────────────────────────
        Schema::table('t_reset_quantifier', function (Blueprint $table) {
            $table->index(
                ['flowmeter', 'status'],
                'idx_rq_flowmeter_status'
            );
        });
    }

    public function down(): void
    {
        Schema::table('t_balance_header', function (Blueprint $table) {
            $table->dropIndex('idx_bh_fifo_core');
            $table->dropIndex('idx_bh_plant_material_tank');
        });

        Schema::table('t_balance_detail', function (Blueprint $table) {
            $table->dropIndex('idx_bd_head_active');
        });

        Schema::table('t_balance_temporary', function (Blueprint $table) {
            $table->dropIndex('idx_bt_entry_status');
            $table->dropIndex('idx_bt_material_plant');
        });

        Schema::table('t_shipment_header', function (Blueprint $table) {
            $table->dropIndex('idx_sh_trace_no');
            $table->dropIndex('idx_sh_from_trace_no');
            $table->dropIndex('idx_sh_so_no');
        });

        Schema::table('t_adjustment_header', function (Blueprint $table) {
            $table->dropIndex('idx_ah_adjust_no');
        });

        Schema::table('t_reset_quantifier', function (Blueprint $table) {
            $table->dropIndex('idx_rq_flowmeter_status');
        });
    }
};
