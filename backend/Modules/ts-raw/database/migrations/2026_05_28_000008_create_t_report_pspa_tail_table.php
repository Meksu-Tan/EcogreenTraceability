<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_report_pspa_tail', function (Blueprint $table) {
            $table->bigIncrements('id_report_tail');
            $table->unsignedBigInteger('id_report_head')->index();
            $table->integer('id_material')->nullable()->index();
            $table->integer('id_sloc')->nullable()->index();
            $table->integer('plant')->nullable();
            $table->string('tank', 20);
            $table->string('material_code', 50);
            $table->string('description', 100);
            $table->double('capacity')->default('0');
            $table->double('sounding')->default('0');
            $table->double('temperature')->default('0');
            $table->double('volume')->default('0');
            $table->double('density')->default('0');
            $table->double('qty')->default('0');
            $table->double('qty_data')->default('0');
            $table->string('adjust_type')->nullable();
            $table->datetime('populated_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->integer('adjust_number')->nullable();
            $table->integer('adjust_status')->nullable();
            $table->integer('status')->default('1')->index();
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->default(DB::raw('null on update CURRENT_TIMESTAMP'))->nullable();
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->foreign('id_report_head')->references('id_report_head')->on('t_report_pspa_head')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_report_pspa_tail');
    }
};
