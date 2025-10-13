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
        Schema::table('t_balance_temporary', function (Blueprint $table) {
            //
            $table->string('id_plant', 10)->nullable()->after('id_material');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_balance_temporary', function (Blueprint $table) {
            //
            $table->dropColumn('id_plant');
        });
    }
};
