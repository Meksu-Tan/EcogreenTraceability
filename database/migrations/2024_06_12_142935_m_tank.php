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
        Schema::create('m_tank', function (Blueprint $table) {
            $table->bigIncrements('id_tank');
            $table->string('code', 10)->nullable();
            $table->string('code_2', 10)->nullable();
            $table->string('code_3', 10)->nullable();
            $table->string('code_4', 10)->nullable();
            $table->string('id_plant', 10);
            $table->string('description', 50)->unique();
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Adding a single column index
            $table->index('code');
            $table->index('description');
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('m_tank');
    }
};
