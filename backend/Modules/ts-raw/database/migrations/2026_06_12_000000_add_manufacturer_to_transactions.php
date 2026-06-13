<?php declare(strict_types=1);

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
        Schema::connection('eudr_ts')->table('t_balance_temporary', function (Blueprint $table) {
            $table->unsignedInteger('id_manufacturer')->nullable()->after('id_material');
            $table->index('id_manufacturer', 'idx_bt_manufacturer');
        });

        Schema::connection('eudr_ts')->table('t_balance_detail', function (Blueprint $table) {
            $table->unsignedInteger('id_manufacturer')->nullable()->after('id_material');
            $table->index('id_manufacturer', 'idx_bd_manufacturer');
        });

        Schema::connection('eudr_ts')->table('t_trace_detail', function (Blueprint $table) {
            $table->unsignedInteger('id_manufacturer')->nullable()->after('id_material');
            $table->index('id_manufacturer', 'idx_td_manufacturer');
        });

        Schema::connection('eudr_ts')->table('t_prod_log', function (Blueprint $table) {
            $table->unsignedInteger('id_manufacturer')->nullable()->after('id_material');
            $table->index('id_manufacturer', 'idx_pl_manufacturer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('eudr_ts')->table('t_balance_temporary', function (Blueprint $table) {
            $table->dropIndex('idx_bt_manufacturer');
            $table->dropColumn('id_manufacturer');
        });

        Schema::connection('eudr_ts')->table('t_balance_detail', function (Blueprint $table) {
            $table->dropIndex('idx_bd_manufacturer');
            $table->dropColumn('id_manufacturer');
        });

        Schema::connection('eudr_ts')->table('t_trace_detail', function (Blueprint $table) {
            $table->dropIndex('idx_td_manufacturer');
            $table->dropColumn('id_manufacturer');
        });

        Schema::connection('eudr_ts')->table('t_prod_log', function (Blueprint $table) {
            $table->dropIndex('idx_pl_manufacturer');
            $table->dropColumn('id_manufacturer');
        });
    }
};
