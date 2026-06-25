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
        if (!Schema::connection('eudr_ts')->hasColumn('t_adjustment_header', 'id_sloc')) {
            Schema::connection('eudr_ts')->table('t_adjustment_header', function (Blueprint $table): void {
                $table->longText('id_sloc')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('eudr_ts')->hasColumn('t_adjustment_header', 'id_sloc')) {
            Schema::connection('eudr_ts')->table('t_adjustment_header', function (Blueprint $table): void {
            });
        }
    }
};
