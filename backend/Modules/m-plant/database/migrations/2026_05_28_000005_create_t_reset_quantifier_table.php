<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_reset_quantifier', function (Blueprint $table) {
            $table->bigIncrements('id_reset');
            $table->date('reset_date')->default(null)->nullable();
            $table->string('flowmeter', 10);
            $table->double('value')->default('0');
            $table->string('remark', 100);
            $table->integer('status')->default('1')->index();
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->index(['flowmeter', 'status'], 'idx_rq_flowmeter_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_reset_quantifier');
    }
};
