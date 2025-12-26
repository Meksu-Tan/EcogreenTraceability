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
        Schema::table('t_warehouse_header', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tank')->nullable()->after('id_section');
        
            $table->foreign('id_tank')->references('id_tank')->on('m_tank')->onDelete('restrict');
        });
        
        Schema::table('t_warehouse_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tank')->nullable()->after('id_supplier');
        
            $table->foreign('id_tank')->references('id_tank')->on('m_tank')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            't_warehouse_detail',
            't_warehouse_header',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'id_tank')) {
                    $table->dropColumn('id_tank');
                }
            });
        }
    }
};
