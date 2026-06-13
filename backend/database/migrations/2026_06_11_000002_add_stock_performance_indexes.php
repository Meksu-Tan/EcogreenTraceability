<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ---- t_trace_header -------------------------------------------------
        try { DB::statement('DROP INDEX `t_trace_header_entry_date_index` ON `t_trace_header`'); } catch (\Exception $e) {}
        try { DB::statement('DROP INDEX `t_trace_header_id_material_index` ON `t_trace_header`'); } catch (\Exception $e) {}
        try { DB::statement('DROP INDEX `t_trace_header_id_sloc_index` ON `t_trace_header`'); } catch (\Exception $e) {}

        DB::statement('ALTER TABLE `t_trace_header` ADD INDEX `t_trace_header_entry_date_index`(`entry_date`)');
        DB::statement('ALTER TABLE `t_trace_header` ADD INDEX `t_trace_header_id_material_index`(`id_material`)');
        // id_sloc is TEXT - must specify prefix length
        DB::statement('ALTER TABLE `t_trace_header` ADD INDEX `t_trace_header_id_sloc_index`(`id_sloc`(191))');

        // ---- t_balance_header -----------------------------------------------
        // Note: id_sloc on t_balance_header is also TEXT - use prefix length in composite index
        try { DB::statement('DROP INDEX `t_balance_header_composite_index` ON `t_balance_header`'); } catch (\Exception $e) {}
        DB::statement('ALTER TABLE `t_balance_header` ADD INDEX `t_balance_header_composite_index`(`id_material`,`id_sloc`(191),`entry_date`)');

        // ---- m_sloc ---------------------------------------------------------
        try {
            Schema::connection('eudr_ts')->table('m_sloc', function (Blueprint $table) {
                $table->index(['id_plant']);
            });
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        try { DB::statement('ALTER TABLE `t_trace_header` DROP INDEX `t_trace_header_entry_date_index`'); } catch (\Exception $e) {}
        try { DB::statement('ALTER TABLE `t_trace_header` DROP INDEX `t_trace_header_id_material_index`'); } catch (\Exception $e) {}
        try { DB::statement('ALTER TABLE `t_trace_header` DROP INDEX `t_trace_header_id_sloc_index`'); } catch (\Exception $e) {}
        try { DB::statement('ALTER TABLE `t_balance_header` DROP INDEX `t_balance_header_composite_index`'); } catch (\Exception $e) {}
        try {
            Schema::connection('eudr_ts')->table('m_sloc', function (Blueprint $table) {
                $table->dropIndex(['id_plant']);
            });
        } catch (\Exception $e) {}
    }
};
