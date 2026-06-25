<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts_pg';

    public function up(): void
    {
        Schema::connection($this->connection)->table('t_material_document', function (Blueprint $table) {
            $table->string('mode', 10)->nullable()->after('material_document');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('t_material_document', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
