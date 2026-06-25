<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        if (DB::connection('eudr_ts')->getDriverName() === 'sqlite') {
            return;
        }

        Schema::connection('eudr_ts')->table('t_trace_header', function (Blueprint $table) {
            $table->index(['entry_date']);
            $table->index(['id_material']);
            $table->index(['id_sloc']);
        });
        Schema::connection('eudr_ts')->table('t_balance_header', function (Blueprint $table) {
            $table->index(['id_material', 'id_sloc', 'entry_date']);
        });
        // Index for sloc plant relationship
        Schema::connection('eudr_ts')->table('m_sloc', function (Blueprint $table) {
            $table->index(['id_plant']);
        });
    }

    public function down(): void
    {
        if (DB::connection('eudr_ts')->getDriverName() === 'sqlite') {
            return;
        }

        Schema::connection('eudr_ts')->table('t_trace_header', function (Blueprint $table) {
            $table->dropIndex(['entry_date']);
            $table->dropIndex(['id_material']);
            $table->dropIndex(['id_sloc']);
        });
        Schema::connection('eudr_ts')->table('t_balance_header', function (Blueprint $table) {
            $table->dropIndex(['id_material', 'id_sloc', 'entry_date']);
        });
        Schema::connection('eudr_ts')->table('m_sloc', function (Blueprint $table) {
            $table->dropIndex(['id_plant']);
        });
    }
};
