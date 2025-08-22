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
        Schema::create('m_tank_detail', function (Blueprint $table) {
            $table->bigIncrements('id_tank_tail'); // This will be the primary key with auto-increment
            $table->unsignedBigInteger('id_tank'); // Foreign key, no auto-increment here
            $table->string('tf_number', 100);
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Adding foreign key constraint
            $table->foreign('id_tank')->references('id_tank')->on('m_tank')->onDelete('restrict');

            // Adding indexes
            $table->index('status');
            $table->index('id_tank');
            $table->index('tf_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('m_tank_detail');
    }
};
