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
        Schema::create('m_material', function (Blueprint $table) {
            $table->bigIncrements('id_material');
            $table->string('code', 20);
            $table->string('code_noneudr', 20)->nullable();
            $table->string('description', 500);
            $table->string('code_matl_supplier', 20)->nullable();
            $table->string('type', 20);
            $table->double('yield')->default('100');
            $table->string('qtf_feed', 20)->nullable();
            $table->string('qtf_rundown', 20)->nullable();
            $table->string('id_rundown', 2)->nullable()->default('0');
            $table->string('id_feed', 2)->nullable();
            $table->string('status_packaging', 2)->default(0);
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Adding a single column index
            $table->index('code');
            $table->index('code_noneudr');
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
        Schema::dropIfExists('m_material');
    }
};
