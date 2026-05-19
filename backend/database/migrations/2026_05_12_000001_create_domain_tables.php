<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // m_material
        Schema::create('m_material', function (Blueprint $table) {
            $table->increments('id_material');
            $table->string('code', 50)->unique();
            $table->string('code_noneudr', 50)->nullable();
            $table->string('description', 200);
            $table->string('type', 20)->nullable();         // WIP, RM, FG
            $table->decimal('yield', 6, 2)->default(100);
            $table->string('qtf_feed', 50)->nullable();
            $table->string('qtf_rundown', 50)->nullable();
            $table->unsignedInteger('id_feed')->nullable();
            $table->unsignedInteger('id_rundown')->nullable();
            $table->string('code_matl_supplier', 50)->nullable();
            $table->tinyInteger('status_packaging')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();
        });

        // m_material_pck
        Schema::create('m_material_pck', function (Blueprint $table) {
            $table->increments('id_materialpck');
            $table->unsignedInteger('id_material');
            $table->string('code', 50)->unique();
            $table->string('code_noneudr', 50)->nullable();
            $table->string('description', 200);
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('id_material')->references('id_material')->on('m_material');
        });

        // m_storage_tank
        Schema::create('m_storage_tank', function (Blueprint $table) {
            $table->increments('id_tank');
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

        // m_storage_tank_tail (detail)
        Schema::create('m_storage_tank_tail', function (Blueprint $table) {
            $table->increments('id_tank_tail');
            $table->unsignedInteger('id_tank');
            $table->string('tf_number', 100)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->foreign('id_tank')->references('id_tank')->on('m_storage_tank');
        });

        // m_warehouse
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

        // m_supplier
        Schema::create('m_supplier', function (Blueprint $table) {
            $table->increments('id_supplier');
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

        // log_transactions
        Schema::create('log_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('log_module', 100)->nullable();
            $table->string('log_type', 50)->nullable();
            $table->text('log_description')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_transactions');
        Schema::dropIfExists('m_supplier');
        Schema::dropIfExists('m_warehouse');
        Schema::dropIfExists('m_storage_tank_tail');
        Schema::dropIfExists('m_storage_tank');
        Schema::dropIfExists('m_material_pck');
        Schema::dropIfExists('m_material');
    }
};
