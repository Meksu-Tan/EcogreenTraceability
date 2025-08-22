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
        Schema::create('t_report_pspa_head', function (Blueprint $table) {
            $table->bigIncrements('id_report_head');
            $table->date('period');
            $table->string('batch_sap', 20);
            $table->integer('adjust_status')->default('0');
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Adding a single column index
            $table->index('status');
            $table->index('period');
            $table->index('batch_sap');
            $table->index('adjust_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('t_report_pspa_head');
    }
};
