<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('m_manufacturer', function (Blueprint $table) {
            $table->increments('id_manufacturer');
            $table->string('code', 50)->unique();
            $table->string('description', 200);
            $table->string('type', 50)->nullable();
            $table->string('batch_code', 50)->nullable();
            $table->string('sloc', 100)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_manufacturer');
    }
};
