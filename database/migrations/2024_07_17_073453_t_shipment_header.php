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
        Schema::create('t_shipment_header', function (Blueprint $table) {
            $table->bigIncrements('id_ship_head');
            $table->date('entry_date')->default(null)->nullable();
            $table->unsignedBigInteger('from_trace_no');
            $table->unsignedBigInteger('trace_no');
            $table->string('so_no', 20)->nullable();
            $table->unsignedBigInteger('id_material_fg');
            $table->double('qty')->default('0');
            $table->string('doc_url', 50)->nullable();
            $table->integer('status')->default('1');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Adding a single column index
            $table->index('status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('t_shipment_header');
    }
};
