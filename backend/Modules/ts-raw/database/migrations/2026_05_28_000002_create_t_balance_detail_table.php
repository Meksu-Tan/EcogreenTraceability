<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_balance_detail', function (Blueprint $table) {
            $table->bigIncrements('id_balance_tail');
            $table->unsignedBigInteger('id_balance_head')->index();
            $table->unsignedBigInteger('id_supplier')->index();
            $table->unsignedBigInteger('id_material')->index();
            $table->string('batch_sap', 20)->nullable();
            $table->unsignedBigInteger('id_tank')->nullable()->index();
            $table->longText('id_sloc')->nullable();
            $table->longText('id_sloc_tail')->nullable();
            $table->longText('id_tank_tail')->nullable();
            $table->string('id_plant', 10)->nullable()->index();
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

            // Composite indexes
            $table->index(['id_balance_head', 'status', 'qty', 'id_balance_tail'], 'idx_bd_head_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_balance_detail');
    }
};
