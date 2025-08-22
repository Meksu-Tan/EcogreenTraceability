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
        Schema::create('m_material_pck', function (Blueprint $table) {
            $table->bigIncrements('id_materialpck');
            $table->unsignedBigInteger('id_material');
            $table->string('code', 20);
            $table->string('code_noneudr', 20);
            $table->string('description', 500);
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Adding foreign key constraint
            $table->foreign('id_material')->references('id_material')->on('m_material')->onDelete('restrict');

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
        Schema::dropIfExists('m_material_pck');
    }
};
