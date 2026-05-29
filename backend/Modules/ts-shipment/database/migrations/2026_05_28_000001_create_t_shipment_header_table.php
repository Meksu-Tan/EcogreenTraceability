<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_shipment_header', function (Blueprint $table) {
            $table->bigIncrements('id_ship_head');
            $table->date('entry_date')->default(null)->nullable();
            $table->unsignedBigInteger('from_trace_no')->nullable()->index('idx_sh_from_trace_no');
            $table->unsignedBigInteger('trace_no')->index('idx_sh_trace_no');
            $table->string('so_no', 20)->nullable()->index('idx_sh_so_no');
            $table->unsignedBigInteger('id_material_fg');
            $table->string('id_plant', 10)->nullable()->index();
            $table->double('qty')->default('0');
            $table->string('doc_url', 50)->nullable();
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
        Schema::dropIfExists('t_shipment_header');
    }
};
