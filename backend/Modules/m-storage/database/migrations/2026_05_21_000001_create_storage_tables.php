<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('m_sloc', function (Blueprint $table) {
            $table->increments('id_sloc');
            $table->string('code_2', 50)->nullable();
            $table->string('code_3', 50)->nullable();
            $table->string('code_4', 50)->nullable();
            $table->string('id_plant', 50)->nullable();
            $table->string('description', 200);
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('m_sloc_detail', function (Blueprint $table) {
            $table->increments('id_sloc_tail');
            $table->unsignedInteger('id_sloc');
            $table->string('tf_number', 100)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('id_sloc')->references('id_sloc')->on('m_sloc');
        });

        Schema::create('m_warehouse', function (Blueprint $table) {
            $table->increments('id_warehouse');
            $table->string('id_batch', 50)->nullable();
            $table->string('code', 50)->nullable();
            $table->string('description', 200);
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_warehouse');
        Schema::dropIfExists('m_sloc_detail');
        Schema::dropIfExists('m_sloc');
    }
};
