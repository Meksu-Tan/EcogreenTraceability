<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        Schema::create('t_shipment_detail', function (Blueprint $table) {
            $table->bigIncrements('id_ship_tail');
            $table->unsignedBigInteger('id_ship_head')->index();
            $table->unsignedBigInteger('id_material_fg')->index();
            $table->unsignedBigInteger('id_supplier')->index();
            $table->string('id_plant', 10)->nullable()->index();
            $table->string('batch_sap', 20)->nullable();
            $table->double('qty')->default('0');
            $table->integer('status')->default('1')->index();
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('id_ship_head')->references('id_ship_head')->on('t_shipment_header')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_shipment_detail');
    }
};
