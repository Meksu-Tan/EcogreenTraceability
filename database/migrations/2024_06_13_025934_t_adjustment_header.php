<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        Schema::create('t_adjustment_header', function (Blueprint $table) {
            $table->bigIncrements('id_adjust_head');
            $table->date('entry_date')->default(null)->nullable();
            $table->unsignedBigInteger('adjust_no');
            $table->unsignedBigInteger('id_balance_head');
            $table->unsignedBigInteger('id_material');
            $table->unsignedBigInteger('id_tank');
            $table->double('in_qty')->default('0');
            $table->double('out_qty')->default('0');
            $table->double('before_adjust')->default('0');
            $table->double('after_adjust')->default('0');
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Adding foreign key constraint
            $table->foreign('id_material')->references('id_material')->on('m_material')->onDelete('restrict');

            // Adding a single column index
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('t_adjustment_header');
    }
};
