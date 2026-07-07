<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('eudr_ts')->table('t_ts_acknowledge', function (Blueprint $table) {
            $table->string('trace_no', 50)->nullable()->after('step_type');
            $table->string('material_name', 200)->nullable()->after('trace_no');
            $table->string('source_name', 200)->nullable()->after('material_name');
        });
    }

    public function down(): void
    {
        Schema::connection('eudr_ts')->table('t_ts_acknowledge', function (Blueprint $table) {
            $table->dropColumn(['trace_no', 'material_name', 'source_name']);
        });
    }
};
