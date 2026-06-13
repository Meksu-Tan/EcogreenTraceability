<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('m_material_pck', function (Blueprint $table) {
            $table->string('batch_prefix', 10)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('m_material_pck', function (Blueprint $table) {
            $table->dropColumn('batch_prefix');
        });
    }
};
