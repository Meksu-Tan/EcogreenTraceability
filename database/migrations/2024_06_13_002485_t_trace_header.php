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
        Schema::create('t_trace_header', function (Blueprint $table) {
            $table->bigIncrements('id_trace_head');
            $table->date('entry_date')->default(null)->nullable();
            $table->unsignedBigInteger('from_trace_no')->nullable();
            $table->unsignedBigInteger('to_trace_no')->nullable();
            $table->unsignedBigInteger('id_balance_head');
            $table->unsignedBigInteger('id_material');
            $table->double('in_qty')->default('0');
            $table->double('out_qty')->default('0');
            $table->double('last_qtf')->default('0');
            $table->double('curr_qtf')->default('0');
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Adding foreign key constraint
            // $table->foreign('id_balance_head')->references('id_balance_head')->on('t_balance_header')->onDelete('restrict');
            // $table->foreign('id_process_head')->references('id_process_head')->on('t_process_header')->onDelete('restrict');
            // $table->foreign('id_material')->references('id_material')->on('m_material')->onDelete('restrict');

            // Adding a single column index
            $table->index('status');
            $table->index('from_trace_no');
            $table->index('to_trace_no');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('t_trace_header');
    }
};
