<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        Schema::connection('eudr_ts')->create('t_report_pspa_detail', function (Blueprint $table) {
            $table->bigIncrements('id_pspa_detail');
            $table->unsignedBigInteger('id_pspa_head')->index();
            $table->integer('id_sloc')->nullable()->index();
            $table->integer('id_material')->nullable()->index();
            $table->string('tf_number', 50)->nullable();
            $table->double('physical_stock')->default('0');
            $table->double('book_stock')->default('0');
            $table->integer('status')->default('1')->index();
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('id_pspa_head')->references('id_report_head')->on('t_report_pspa_head')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('eudr_ts')->dropIfExists('t_report_pspa_detail');
    }
};
