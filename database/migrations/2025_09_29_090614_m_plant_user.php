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
        Schema::create('m_plant_user', function (Blueprint $table) {
            $table->unsignedBigInteger('id_plant');
            $table->unsignedBigInteger('user_id');

            $table->primary(['id_plant', 'user_id']);

            $table->foreign('id_plant')->references('id_plant')->on('m_plant')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('m_plant_user');
    }
};
