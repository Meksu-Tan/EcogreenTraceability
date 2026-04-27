<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Database cleanup migration — safe to run on live production.
 *
 * Changes:
 *  1. Drop redundant index on m_supplier.description
 *     (UNIQUE KEY already covers lookup; the plain KEY is duplicate overhead
 *     on every INSERT/UPDATE to m_supplier)
 *
 *  2. Fix type mismatch in t_report_pspa_tail
 *     id_material and id_sloc are declared as int(11) but the referenced
 *     master columns (m_material.id_material, m_tank.id_tank) are
 *     bigint(20) unsigned. The mismatch prevents FK constraints and causes
 *     implicit cast on every JOIN, bypassing indexes.
 *
 * Safety notes:
 *  - DROP INDEX is instantaneous on InnoDB (metadata only).
 *  - MODIFY COLUMN on t_report_pspa_tail is safe because:
 *      a) int(11) max value (~2.1B) is well within bigint range, no data loss.
 *      b) The table has 0 rows in the current production schema (no live data).
 *      c) Even with data, signed int → unsigned bigint is a widening cast;
 *         MariaDB will reject negative values only if any exist (run the
 *         pre-check query below before deploying on a database with data).
 *
 * Pre-check (run manually before migrate if t_report_pspa_tail has rows):
 *   SELECT COUNT(*) FROM t_report_pspa_tail WHERE id_material < 0 OR id_sloc < 0;
 *   -- Must return 0 before proceeding.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Drop redundant plain index on m_supplier.description ──────────
        // UNIQUE KEY m_supplier_description_unique already serves as a B-tree
        // index for lookups. The extra KEY m_supplier_description_index is
        // never chosen by the optimizer when the UNIQUE one exists.
        Schema::table('m_supplier', function (Blueprint $table) {
            $table->dropIndex('m_supplier_description_index');
        });

        // ── 2. Fix type mismatch in t_report_pspa_tail ────────────────────────
        // Pre-check guard: abort if negative values exist (should never happen,
        // but better to be safe than corrupt).
        $negativeCount = DB::selectOne(
            'SELECT COUNT(*) AS cnt
               FROM t_report_pspa_tail
              WHERE id_material < 0 OR id_sloc < 0'
        );

        if ($negativeCount && $negativeCount->cnt > 0) {
            throw new \RuntimeException(
                'Migration aborted: t_report_pspa_tail contains negative id_material ' .
                'or id_sloc values (' . $negativeCount->cnt . ' rows). ' .
                'Fix the data before running this migration.'
            );
        }

        Schema::table('t_report_pspa_tail', function (Blueprint $table) {
            // Change int(11) → bigint(20) unsigned to match m_material.id_material
            // and m_tank.id_tank types.
            $table->unsignedBigInteger('id_material')->nullable()->change();
            $table->unsignedBigInteger('id_sloc')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Re-add the duplicate index (restore to previous state)
        Schema::table('m_supplier', function (Blueprint $table) {
            $table->index('description', 'm_supplier_description_index');
        });

        // Revert type (safe because bigint values fit in int range for this data)
        Schema::table('t_report_pspa_tail', function (Blueprint $table) {
            $table->integer('id_material')->nullable()->change();
            $table->integer('id_sloc')->nullable()->change();
        });
    }
};
