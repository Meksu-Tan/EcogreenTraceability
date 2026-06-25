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
        Schema::connection('eudr_ts')->table('t_prod_log', function (Blueprint $table) {
            $table->smallInteger('mode')->default(1)->after('section');
            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::connection('eudr_ts')->table('t_prod_log', function (Blueprint $table) {
            $table->dropIndex(['mode']);
            $table->dropColumn('mode');
        });
    }
};
