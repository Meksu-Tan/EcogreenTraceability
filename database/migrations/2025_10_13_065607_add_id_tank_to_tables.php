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
        Schema::table('t_trace_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sloc')->nullable()->after('id_plant');
        });

        Schema::table('t_balance_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tank')->nullable()->after('id_material');
        });

        Schema::table('t_adjustment_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tank')->nullable()->after('id_material');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_adjustment_detail', function (Blueprint $table) {
            //
            $table->dropColumn('id_tank');
        });

        Schema::table('t_balance_detail', function (Blueprint $table) {
            //
            $table->dropColumn('id_tank');
        });

        Schema::table('t_trace_detail', function (Blueprint $table) {
            //
            $table->dropColumn('id_sloc');
        });
    }
};
