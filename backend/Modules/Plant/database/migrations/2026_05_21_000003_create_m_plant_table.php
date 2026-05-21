<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('m_plant', function (Blueprint $table) {
            $table->increments('id_plant');
            $table->string('code', 50)->nullable();
            $table->string('code_2', 50)->nullable();
            $table->string('code_3', 50)->nullable();
            $table->string('id_sloc', 50)->default('T000');
            $table->string('description', 200);
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_plant');
    }
};
