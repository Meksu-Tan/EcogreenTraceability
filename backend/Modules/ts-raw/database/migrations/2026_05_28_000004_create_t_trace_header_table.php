<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        Schema::create('t_trace_header', function (Blueprint $table) {
            $table->bigIncrements('id_trace_head');
            $table->date('entry_date')->default(null)->nullable();
            $table->unsignedBigInteger('from_trace_no')->nullable()->index();
            $table->unsignedBigInteger('to_trace_no')->nullable()->index();
            $table->unsignedBigInteger('id_balance_head')->index();
            $table->unsignedBigInteger('id_material')->index();
            $table->string('id_plant', 10)->nullable()->index();
            $table->bigInteger('tf_number')->nullable()->index();
            $table->longText('id_sloc')->nullable();
            $table->longText('id_sloc_tail')->nullable();
            $table->double('in_qty')->default('0');
            $table->double('out_qty')->default('0');
            $table->double('last_qtf')->default('0');
            $table->double('curr_qtf')->default('0');
            $table->integer('status')->default('1')->index();
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_trace_header');
    }
};
