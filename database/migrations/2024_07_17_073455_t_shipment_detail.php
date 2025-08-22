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
        Schema::create('t_shipment_detail', function (Blueprint $table) {
            $table->bigIncrements('id_ship_tail');
            $table->unsignedBigInteger('id_ship_head');
            $table->unsignedBigInteger('id_material_fg');
            $table->unsignedBigInteger('id_supplier');
            $table->string('batch_sap', 20)->nullable();
            $table->double('qty')->default('0');
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Adding foreign key constraint
            $table->foreign('id_ship_head')->references('id_ship_head')->on('t_shipment_header')->onDelete('restrict');

            // Adding a single column index
            $table->index('status');
            $table->index('id_ship_head');
            $table->index('id_material_fg');
            $table->index('id_supplier');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('t_shipment_detail');
    }
};
