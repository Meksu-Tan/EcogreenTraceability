<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'id_plant')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('id_plant')->nullable()->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'id_plant')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('id_plant');
            });
        }
    }
};
