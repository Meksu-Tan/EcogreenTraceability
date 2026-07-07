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
        Schema::create('t_adjustment_detail', function (Blueprint $table) {
            $table->bigIncrements('id_adjust_tail');
            $table->unsignedBigInteger('id_adjust_head')->index();
            $table->unsignedBigInteger('id_balance_tail')->index();
            $table->unsignedBigInteger('id_supplier')->index();
            $table->unsignedBigInteger('id_material')->index();
            $table->unsignedBigInteger('id_sloc')->index(); // for raw query compatibility
            $table->json('id_sloc_tail')->nullable();
            $table->string('id_plant', 10)->nullable()->index();
            $table->string('batch_sap', 20)->nullable();
            $table->double('in_qty')->default('0');
            $table->double('out_qty')->default('0');
            $table->double('before_adjust')->default('0');
            $table->double('after_adjust')->default('0');
            $table->integer('status')->default('1')->index();
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('id_adjust_head')->references('id_adjust_head')->on('t_adjustment_header')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_adjustment_detail');
    }
};
