<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        Schema::table('m_manufacturer', function (Blueprint $table) {
            $table->dropColumn(['sloc', 'batch_code', 'code_noneudr']);
        });
    }

    public function down(): void
    {
        Schema::table('m_manufacturer', function (Blueprint $table) {
            $table->string('code_noneudr', 50)->nullable();
            $table->string('batch_code', 100)->nullable();
            $table->integer('sloc')->nullable();
        });
    }
};
