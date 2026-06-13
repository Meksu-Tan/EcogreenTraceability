<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        Schema::connection('eudr_ts')->create('t_warehouse_detail', function (Blueprint $table) {
            $table->bigIncrements('id_whx_tail');
            $table->unsignedBigInteger('id_whx_head')->index();
            $table->unsignedBigInteger('id_material_feed')->nullable()->index();
            $table->unsignedBigInteger('id_material_fg');
            $table->unsignedBigInteger('id_supplier')->index();
            $table->string('id_plant', 10)->nullable()->index();
            $table->string('batch_sap', 20)->nullable();
            $table->unsignedBigInteger('id_tank')->nullable()->index();
            $table->json('id_tank_tail')->nullable();
            $table->double('qty')->default('0');
            $table->double('in_qty')->default('0');
            $table->double('out_qty')->default('0');
            $table->double('init_qty')->default('0');
            $table->integer('status')->default('1')->index();
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('id_whx_head')->references('id_whx_head')->on('t_warehouse_header')->connection('eudr_ts')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::connection('eudr_ts')->dropIfExists('t_warehouse_detail');
    }
};
