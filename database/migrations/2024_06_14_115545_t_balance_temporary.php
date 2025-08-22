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
        Schema::create('t_balance_temporary', function (Blueprint $table) {
            $table->bigIncrements('id_balance_temp');
            $table->unsignedBigInteger('entry_no');
            $table->unsignedBigInteger('id_supplier');
            $table->unsignedBigInteger('id_material');
            $table->unsignedBigInteger('id_tank');
            $table->string('batch_sap', 20)->nullable();
            $table->double('qty')->default('0');
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('t_balance_temporary');
    }
};
