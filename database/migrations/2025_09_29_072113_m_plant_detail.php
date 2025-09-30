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
        Schema::create('m_plant_detail', function (Blueprint $table) {
            $table->bigIncrements('id_plant_tail');
            $table->unsignedBigInteger('id_plant');
            $table->string('tf_number', 100);
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('id_plant')->references('id_plant')->on('m_plant')->onDelete('restrict');

            // Adding indexes
            $table->index('status');
            $table->index('id_plant');
            $table->index('tf_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('m_plant_detail');
    }
};
