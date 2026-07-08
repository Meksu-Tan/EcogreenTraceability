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
            $table->string('keterangan', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('eudr_ts')->table('t_ts_acknowledge', function (Blueprint $table) {
            $table->dropColumn(['keterangan']);
        });
    }
};
