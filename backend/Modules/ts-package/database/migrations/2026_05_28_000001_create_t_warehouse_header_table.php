<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_warehouse_header', function (Blueprint $table) {
            $table->bigIncrements('id_whx_head');
            $table->date('entry_date')->default(null)->nullable();
            $table->unsignedBigInteger('from_trace_no')->nullable();
            $table->unsignedBigInteger('trace_no')->index();
            $table->unsignedBigInteger('id_material_feed')->nullable()->index();
            $table->unsignedBigInteger('id_material_fg');
            $table->unsignedBigInteger('id_section')->nullable();
            $table->string('id_plant', 10)->nullable()->index();
            $table->string('batch_no', 50)->nullable();
            $table->string('po_no', 50)->nullable();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_warehouse_header');
    }
};
