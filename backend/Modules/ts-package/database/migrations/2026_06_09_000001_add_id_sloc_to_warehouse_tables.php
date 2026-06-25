<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = 'eudr_ts';

        if (!Schema::connection($connection)->hasColumn('t_warehouse_header', 'id_sloc')) {
            Schema::connection($connection)->table('t_warehouse_header', function (Blueprint $table): void {
                $table->longText('id_sloc')->nullable();
            });
        }

        if (!Schema::connection($connection)->hasColumn('t_warehouse_detail', 'id_sloc')) {
            Schema::connection($connection)->table('t_warehouse_detail', function (Blueprint $table): void {
            });
        }
    }

    public function down(): void
    {
        $connection = 'eudr_ts';
        if (Schema::connection($connection)->hasColumn('t_warehouse_header', 'id_sloc')) {
            Schema::connection($connection)->table('t_warehouse_header', function (Blueprint $table): void {
            });
        }
        if (Schema::connection($connection)->hasColumn('t_warehouse_detail', 'id_sloc')) {
            Schema::connection($connection)->table('t_warehouse_detail', function (Blueprint $table): void {
            });
        }
    }
};
