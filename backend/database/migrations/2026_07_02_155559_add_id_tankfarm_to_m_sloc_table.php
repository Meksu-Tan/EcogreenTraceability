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
        Schema::connection('eudr_ts')->table('m_sloc', function (Blueprint $table) {
            $table->integer('id_tankfarm')->nullable()->after('id_sloc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('eudr_ts')->table('m_sloc', function (Blueprint $table) {
            $table->dropColumn('id_tankfarm');
        });
    }
};
